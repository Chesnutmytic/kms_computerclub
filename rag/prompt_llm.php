<?php
/**
 * prompt_llm.php
 * ==============
 * Endpoint AJAX untuk chatbot RAG.
 *
 * Method  : POST
 * Input   : JSON body  { "pertanyaan": "..." }
 *           ATAU form-data  pertanyaan=...
 * Output  : JSON { "jawaban": "..." }
 *              | { "error": "..." }
 */

// ─── Hanya izinkan POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// ─── Sesi (opsional, bisa dihapus jika dipanggil dari skrip lain) ───────────
session_start();

// ─── Baca input ─────────────────────────────────────────────────────────────
$rawBody    = file_get_contents('php://input');
$jsonInput  = json_decode($rawBody, true);
$pertanyaan = trim(
    $jsonInput['pertanyaan']
    ?? $_POST['pertanyaan']
    ?? ''
);

header('Content-Type: application/json; charset=utf-8');

if ($pertanyaan === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parameter "pertanyaan" tidak boleh kosong.']);
    exit;
}

// ─── Konfigurasi Gemini LLM ─────────────────────────────────────────────────
define('LLM_GEMINI_API_KEY', 'AIzaSyBNpYkP5vPTOTCjQvnFtd4jiQdufg5wP9w');
define('LLM_GEMINI_MODEL',   'gemini-2.5-flash');

// ─── Load search_vector.php ─────────────────────────────────────────────────
require_once __DIR__ . '/search_vector.php';

// ─── 1. Retrieval: ambil konteks relevan dari Pinecone ───────────────────────
$konteksArr = getRelevantContext($pertanyaan);

if (empty($konteksArr)) {
    // Jika retrieval gagal total (error API), beri tahu user
    $konteksTeks = '(Tidak ada konteks yang ditemukan di arsip pengetahuan.)';
} else {
    $konteksTeks = implode("\n\n", $konteksArr);
}

// ─── 2. Susun prompt ketat ───────────────────────────────────────────────────
$systemPrompt = <<<PROMPT
Anda adalah asisten AI cerdas untuk Knowledge Management System Computer Club SMAN 1 Rancaekek.
Tugas Anda HANYA menjawab berdasarkan Konteks yang diberikan.
Jika jawaban tidak ada di dalam konteks, katakan 'Maaf, saya tidak menemukan informasi tersebut di dalam arsip pengetahuan klub'.
Jawab dengan bahasa Indonesia yang ramah dan terstruktur.

Konteks:
{$konteksTeks}

Pertanyaan: {$pertanyaan}
PROMPT;

// ─── 3. Panggil Gemini LLM ──────────────────────────────────────────────────
$url     = 'https://generativelanguage.googleapis.com/v1beta/models/'
           . LLM_GEMINI_MODEL . ':generateContent?key=' . LLM_GEMINI_API_KEY;
$payload = json_encode([
    'contents' => [
        [
            'role'  => 'user',
            'parts' => [['text' => $systemPrompt]],
        ],
    ],
    'generationConfig' => [
        'temperature'     => 0.2,
        'maxOutputTokens' => 1024,
    ],
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

// ─── 4. Parse respons Gemini ─────────────────────────────────────────────────
if ($curlErr) {
    http_response_code(502);
    echo json_encode(['error' => 'Koneksi ke Gemini gagal: ' . $curlErr]);
    exit;
}

if ($httpCode !== 200) {
    $errDetail = json_decode($response, true)['error']['message'] ?? substr($response, 0, 200);
    http_response_code(502);
    echo json_encode(['error' => "Gemini API error (HTTP $httpCode): $errDetail"]);
    exit;
}

$data    = json_decode($response, true);
$jawaban = $data['candidates'][0]['content']['parts'][0]['text']
           ?? 'Maaf, saya tidak dapat memproses pertanyaan ini saat ini.';

echo json_encode(['jawaban' => $jawaban], JSON_UNESCAPED_UNICODE);
