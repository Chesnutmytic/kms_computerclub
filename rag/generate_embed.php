<?php
/**
 * generate_embed.php
 * ==================
 * Skrip admin (one-time / berkala) untuk:
 *   1. Mengambil semua data Published dari DB (ARSIP_MATERI, CATATAN_PENGALAMAN, ALUR_PEMBELAJARAN)
 *   2. Mengubah teks gabungan menjadi vektor via Gemini Embedding API
 *   3. Melakukan upsert ke Pinecone dengan metadata teks_asli & tipe_sumber
 *
 * JALANKAN dari browser: http://localhost/kms_computerclub/rag/generate_embed.php
 */

require_once __DIR__ . '/../config/koneksi.php';

// ─── KONFIGURASI API ────────────────────────────────────────────────────────
define('GEMINI_API_KEY',      'AQ.Ab8RN6LxQ8QiMSAsqS01eXbPrFJpQ6uEBQdqLyiXGNub-LWf8A');
define('PINECONE_API_KEY',    'pcsk_3JLjmb_734u7AkHqc96tD3do1yReFQFwBowm5E9cm1uF3K8fWgGxi5SDQ6LCqE91j21nFJ');
define('PINECONE_INDEX_HOST', 'https://kms-index-6hofqz1.svc.aped-4627-b74a.pinecone.io');
define('GEMINI_EMBED_MODEL',  'gemini-embedding-001');
define('UPSERT_BATCH_SIZE',   50); // Pinecone max 100 per request

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300); // 5 menit untuk data besar

require_once __DIR__ . '/../vendor/autoload.php';

// --- FUNGSI EKSTRAK TEKS DARI PPTX ---
function extractTextFromPPTX($filepath) {
    $zip = new ZipArchive();
    if ($zip->open($filepath) !== true) return "";
    
    $text = "";
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        // Membaca hanya file slide XML di dalam PPTX
        if (preg_match('/ppt\/slides\/slide\d+\.xml/', $name)) {
            $xml = $zip->getFromName($name);
            // Menyedot teks di antara tag <a:t>
            preg_match_all('/<a:t>([^<]+)<\/a:t>/', $xml, $matches);
            if (!empty($matches[1])) {
                $text .= implode(" ", $matches[1]) . " ";
            }
        }
    }
    $zip->close();
    return trim($text);
}

// --- FUNGSI EKSTRAK TEKS DARI PDF ---
function extractTextFromPDF($filepath) {
    try {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filepath);
        return $pdf->getText();
    } catch (Exception $e) {
        return ""; // Jika gagal baca, abaikan file ini
    }
}

// ─── HELPER: Embed teks via Gemini ──────────────────────────────────────────
function getEmbedding(string $text): ?array
{
    $url     = 'https://generativelanguage.googleapis.com/v1beta/models/'
               . GEMINI_EMBED_MODEL . ':embedContent?key=' . GEMINI_API_KEY;
    $payload = json_encode([
        'model'   => 'models/' . GEMINI_EMBED_MODEL,
        'content' => ['parts' => [['text' => $text]]]
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo "  [cURL ERROR] Embedding: $curlErr\n";
        return null;
    }
    $data = json_decode($response, true);
    if ($httpCode !== 200 || !isset($data['embedding']['values'])) {
        echo "  [API ERROR] HTTP $httpCode — " . substr($response, 0, 200) . "\n";
        return null;
    }
    return $data['embedding']['values'];
}

// ─── HELPER: Upsert batch ke Pinecone ───────────────────────────────────────
function pineconeUpsert(array $vectors): bool
{
    $url     = PINECONE_INDEX_HOST . '/vectors/upsert';
    $payload = json_encode(['vectors' => $vectors]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Api-Key: ' . PINECONE_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo "  [cURL ERROR] Pinecone upsert: $curlErr\n";
        return false;
    }
    if ($httpCode !== 200) {
        echo "  [PINECONE ERROR] HTTP $httpCode — " . substr($response, 0, 200) . "\n";
        return false;
    }
    return true;
}

// ─── HELPER: Proses satu sumber data ────────────────────────────────────────
function processBatch(array $records, string $tipeSumber): void
{
    $batch       = [];
    $totalOk     = 0;
    $totalFail   = 0;

    foreach ($records as $row) {
        $id       = $row['_pinecone_id'];
        $teks     = $row['_teks'];

        echo "  → Embedding [{$tipeSumber}] ID=$id ... ";
        $vector = getEmbedding($teks);
        if ($vector === null) {
            echo "GAGAL\n";
            $totalFail++;
            continue;
        }
        echo "OK (" . count($vector) . " dim)\n";

        $batch[] = [
            'id'       => $id,
            'values'   => $vector,
            'metadata' => [
                'teks_asli'   => $teks,
                'tipe_sumber' => $tipeSumber,
            ],
        ];

        // Upsert per batch
        if (count($batch) >= UPSERT_BATCH_SIZE) {
            echo "  [BATCH] Upsert " . count($batch) . " vektor ke Pinecone ... ";
            echo pineconeUpsert($batch) ? "SUKSES\n" : "GAGAL\n";
            $totalOk += count($batch);
            $batch    = [];
        }
    }

    // Sisa batch
    if (!empty($batch)) {
        echo "  [BATCH] Upsert " . count($batch) . " vektor ke Pinecone ... ";
        echo pineconeUpsert($batch) ? "SUKSES\n" : "GAGAL\n";
        $totalOk += count($batch);
    }

    echo "  Selesai — Berhasil: $totalOk, Gagal: $totalFail\n\n";
}

// ════════════════════════════════════════════════════════════════════════════
echo "=== GENERATE EMBEDDING — KMS Computer Club ===\n";
echo "Waktu: " . date('Y-m-d H:i:s') . "\n\n";

// ─── 1. ARSIP_MATERI ────────────────────────────────────────────────────────
echo "--- [1/3] ARSIP_MATERI ---\n";
$stmt = $conn->query(
    "SELECT id_arsip, judul_dokumen, kategori, deskripsi, file_path
     FROM ARSIP_MATERI
     WHERE status = 'Published'"
);
$rows = [];
foreach ($stmt->fetchAll() as $r) {
    $teks = implode(' | ', array_filter([
        $r['judul_dokumen'],
        $r['kategori'],
        strip_tags($r['deskripsi'] ?? ''),
    ]));

    // --- PROSES BACA ISI FILE ---
    $teksFile = "";
    if (!empty($r['file_path'])) {
        $fullPath = __DIR__ . '/../' . $r['file_path']; 
        
        if (file_exists($fullPath)) {
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            
            if ($ext === 'pptx') {
                echo "  [INFO] Membaca isi presentasi PPTX ID=" . $r['id_arsip'] . "...\n";
                $teksFile = extractTextFromPPTX($fullPath);
            } elseif ($ext === 'pdf') {
                echo "  [INFO] Membaca isi PDF ID=" . $r['id_arsip'] . "...\n";
                $teksFile = extractTextFromPDF($fullPath);
            }
        }
    }

    // Gabungkan teks database dengan teks dari dalam file
    $teksGabungan = $teks . " | Isi Materi: " . $teksFile;

    $rows[] = [
        '_pinecone_id' => 'materi_' . $r['id_arsip'],
        '_teks'        => mb_substr(trim($teksGabungan), 0, 10000), 
    ];
}
echo "  Ditemukan " . count($rows) . " record.\n";
processBatch($rows, 'Materi');

// ─── 2. CATATAN_PENGALAMAN ──────────────────────────────────────────────────
echo "--- [2/3] CATATAN_PENGALAMAN ---\n";
$stmt = $conn->query(
    "SELECT id_catatan, judul_kegiatan, jenis_kegiatan, kategori, pengalaman, kendala, solusi
     FROM CATATAN_PENGALAMAN
     WHERE status = 'Published'"
);
$rows = [];
foreach ($stmt->fetchAll() as $r) {
    $teks = implode(' | ', array_filter([
        $r['judul_kegiatan'],
        $r['jenis_kegiatan'],
        $r['kategori'],
        strip_tags($r['pengalaman'] ?? ''),
        $r['kendala'] ?? '',
        $r['solusi']  ?? '',
    ]));
    $rows[] = [
        '_pinecone_id' => 'catatan_' . $r['id_catatan'],
        '_teks'        => mb_substr(trim($teks), 0, 10000),
    ];
}
echo "  Ditemukan " . count($rows) . " record.\n";
processBatch($rows, 'Catatan');

// ─── 3. ALUR_PEMBELAJARAN ───────────────────────────────────────────────────
echo "--- [3/3] ALUR_PEMBELAJARAN ---\n";
$stmt = $conn->query(
    "SELECT id_alur, nama_alur, tingkat_level
     FROM ALUR_PEMBELAJARAN
     WHERE status = 'Published'"
);
$rows = [];
foreach ($stmt->fetchAll() as $r) {
    $teks = implode(' | ', array_filter([
        $r['nama_alur'],
        $r['tingkat_level'],
    ]));
    $rows[] = [
        '_pinecone_id' => 'alur_' . $r['id_alur'],
        '_teks'        => mb_substr(trim($teks), 0, 10000),
    ];
}
echo "  Ditemukan " . count($rows) . " record.\n";
processBatch($rows, 'Alur');

echo "=== SELESAI ===\n";
