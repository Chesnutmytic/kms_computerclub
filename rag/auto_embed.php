<?php
/**
 * auto_embed.php
 * ==============
 * Skrip ini dipanggil secara otomatis (background) oleh proses_catatan.php
 * setiap kali anggota menambahkan catatan baru agar AI langsung mempelajarinya.
 */

// Nilai-nilai ini sudah saya sinkronkan otomatis dengan generate_embed.php Anda
define('AUTO_GEMINI_API',      'AQ.Ab8RN6LxQ8QiMSAsqS01eXbPrFJpQ6uEBQdqLyiXGNub-LWf8A');
define('AUTO_PINECONE_API',    'pcsk_3JLjmb_734u7AkHqc96tD3do1yReFQFwBowm5E9cm1uF3K8fWgGxi5SDQ6LCqE91j21nFJ');
define('AUTO_PINECONE_HOST',   'https://kms-index-6hofqz1.svc.aped-4627-b74a.pinecone.io');
define('AUTO_EMBED_MODEL',     'gemini-embedding-001');


// Panggil library PDFParser agar sistem bisa membaca file saat auto-embed
require_once __DIR__ . '/../vendor/autoload.php';

// Fungsi ekstrak teks PPTX (Versi Auto)
function extractTextFromPPTX_Auto($filepath) {
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) return "";
    $text = "";
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (preg_match('/ppt\/slides\/slide\d+\.xml/', $name)) {
            $xml = $zip->getFromName($name);
            preg_match_all('/<a:t>([^<]+)<\/a:t>/', $xml, $matches);
            if (!empty($matches[1])) $text .= implode(" ", $matches[1]) . " ";
        }
    }
    $zip->close();
    return trim($text);
}

// Fungsi ekstrak teks PDF (Versi Auto)
function extractTextFromPDF_Auto($filepath) {
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filepath);
        return $pdf->getText();
    } catch (Exception $e) {
        return ""; 
    }
}

// Fungsi Utama: Mengambil data Materi terbaru dari DB (Menggunakan PDO)
function triggerEmbedMateri($conn, $id_arsip) {
    $id_aman = intval($id_arsip);
    $query = "SELECT judul_dokumen, kategori, deskripsi, file_path, status FROM arsip_materi WHERE id_arsip = $id_aman";
    
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && $row['status'] === 'Published') {
        // Rangkai teks dasar dari database
        $teks = implode(' | ', array_filter([
            $row['judul_dokumen'],
            $row['kategori'],
            strip_tags($row['deskripsi'] ?? '')
        ]));

        // Cek dan sedot isi file jika ada
        $teksFile = "";
        if (!empty($row['file_path'])) {
            $fullPath = __DIR__ . '/../' . $row['file_path']; 
            if (file_exists($fullPath)) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if ($ext === 'pptx') {
                    $teksFile = extractTextFromPPTX_Auto($fullPath);
                } elseif ($ext === 'pdf') {
                    $teksFile = extractTextFromPDF_Auto($fullPath);
                }
            }
        }

        // Gabungkan semuanya
        $teksGabungan = $teks . " | Isi Materi: " . $teksFile;
        
        // Ubah jadi vektor dan simpan ke Pinecone
        $vektor = getSingleEmbedding($teksGabungan);
        if ($vektor) {
            $metadata = [
                'teks_asli' => mb_substr(trim($teksGabungan), 0, 10000),
                'tipe_sumber' => 'Materi',
                'judul_dokumen' => $row['judul_dokumen']
            ];
            if (!empty($row['file_path'])) {
                $metadata['url_download'] = $row['file_path'];
            }
            upsertSinglePinecone('materi_' . $id_aman, $vektor, $metadata);
        }
    }
}

// Fungsi untuk mengekstrak 1 teks menjadi vektor
function getSingleEmbedding($text) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . AUTO_EMBED_MODEL . ':embedContent?key=' . AUTO_GEMINI_API;
    $payload = json_encode([
        'model' => 'models/' . AUTO_EMBED_MODEL,
        'content' => ['parts' => [['text' => $text]]]
    ]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return $data['embedding']['values'] ?? null;
}

// Fungsi untuk menyimpan 1 vektor ke Pinecone
function upsertSinglePinecone($id, $vector, $metadata) {
    $url = AUTO_PINECONE_HOST . '/vectors/upsert';
    $payload = json_encode(['vectors' => [['id' => $id, 'values' => $vector, 'metadata' => $metadata]]]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Api-Key: ' . AUTO_PINECONE_API
        ],
        CURLOPT_TIMEOUT => 30
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// Fungsi Utama: Mengambil data catatan terbaru dari DB (Menggunakan PDO)
function triggerEmbedCatatan($conn, $id_catatan) {
    $id_aman = intval($id_catatan);
    $query = "SELECT judul_kegiatan, jenis_kegiatan, kategori, pengalaman, kendala, solusi, status FROM catatan_pengalaman WHERE id_catatan = $id_aman";
    
    // Menggunakan PDO query sesuai dengan format koneksi Anda
    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row && $row['status'] === 'Published') {
        // Merangkai teks
        $teks = implode(' | ', array_filter([
            $row['judul_kegiatan'],
            $row['jenis_kegiatan'],
            $row['kategori'],
            strip_tags($row['pengalaman'] ?? ''),
            $row['kendala'] ?? '',
            $row['solusi'] ?? ''
        ]));
        
        // Ubah jadi vektor dan simpan
        $vektor = getSingleEmbedding($teks);
        if ($vektor) {
            $metadata = [
                'teks_asli'     => mb_substr(trim($teks), 0, 10000),
                'tipe_sumber'   => 'Catatan',
                'judul_dokumen' => $row['judul_kegiatan'],
                'url_download'  => 'portal/detail_catatan.php?id=' . $id_aman,
            ];
            upsertSinglePinecone('catatan_' . $id_aman, $vektor, $metadata);
        }
    }
}

// Fungsi Utama: Mengambil data arsip_organisasi dari DB dan embed ke Pinecone
function triggerEmbedOrganisasi($conn, $id_organisasi) {
    $id_aman = intval($id_organisasi);
    $query = "SELECT judul_dokumen, kategori_organisasi, deskripsi, file_path, status FROM arsip_organisasi WHERE id_organisasi = $id_aman";

    $stmt = $conn->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['status'] === 'Published') {
        // Rangkai teks dasar dari database
        $teks = implode(' | ', array_filter([
            $row['judul_dokumen'],
            $row['kategori_organisasi'],
            strip_tags($row['deskripsi'] ?? '')
        ]));

        // Cek dan sedot isi file jika ada (PDF atau PPTX)
        $teksFile = "";
        if (!empty($row['file_path'])) {
            $fullPath = __DIR__ . '/../' . $row['file_path'];
            if (file_exists($fullPath)) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if ($ext === 'pptx') {
                    $teksFile = extractTextFromPPTX_Auto($fullPath);
                } elseif ($ext === 'pdf') {
                    $teksFile = extractTextFromPDF_Auto($fullPath);
                }
            }
        }

        // Gabungkan semuanya
        $teksGabungan = $teks . ($teksFile ? " | Isi Dokumen: " . $teksFile : "");

        // Ubah jadi vektor dan simpan ke Pinecone
        $vektor = getSingleEmbedding($teksGabungan);
        if ($vektor) {
            $metadata = [
                'teks_asli'     => mb_substr(trim($teksGabungan), 0, 10000),
                'tipe_sumber'   => 'Organisasi',
                'judul_dokumen' => $row['judul_dokumen'],
            ];
            if (!empty($row['file_path'])) {
                $metadata['url_download'] = $row['file_path'];
            }
            upsertSinglePinecone('organisasi_' . $id_aman, $vektor, $metadata);
        }
    }
}
?>