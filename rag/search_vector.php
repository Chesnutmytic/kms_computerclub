<?php
/**
 * search_vector.php
 * =================
 * Dipanggil secara internal oleh prompt_llm.php (bukan langsung oleh user).
 *
 * Fungsi getRelevantContext($pertanyaan):
 *   1. Embed pertanyaan via Gemini Embedding API
 *   2. Query ke Pinecone (top-k = 5)
 *   3. Kembalikan array berisi teks_asli dari metadata tiap match
 */

// ─── KONFIGURASI API ────────────────────────────────────────────────────────
define('SV_GEMINI_API_KEY',      'AQ.Ab8RN6LxQ8QiMSAsqS01eXbPrFJpQ6uEBQdqLyiXGNub-LWf8A');
define('SV_PINECONE_API_KEY',    'pcsk_3JLjmb_734u7AkHqc96tD3do1yReFQFwBowm5E9cm1uF3K8fWgGxi5SDQ6LCqE91j21nFJ');
define('SV_PINECONE_INDEX_HOST', 'https://kms-index-6hofqz1.svc.aped-4627-b74a.pinecone.io');
define('SV_GEMINI_EMBED_MODEL',  'gemini-embedding-001');
define('SV_TOP_K',               5);

/**
 * Mengubah satu teks menjadi vektor embedding via Gemini.
 *
 * @return float[]|null
 */
function sv_getEmbedding(string $text): ?array
{
    $url     = 'https://generativelanguage.googleapis.com/v1beta/models/'
               . SV_GEMINI_EMBED_MODEL . ':embedContent?key=' . SV_GEMINI_API_KEY;
    $payload = json_encode([
        'model'   => 'models/' . SV_GEMINI_EMBED_MODEL,
        'content' => ['parts' => [['text' => $text]]],
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $httpCode !== 200) {
        error_log("[search_vector] Embedding error HTTP $httpCode: $curlErr — " . substr($response, 0, 300));
        return null;
    }

    $data = json_decode($response, true);
    return $data['embedding']['values'] ?? null;
}

/**
 * Query Pinecone dengan vektor dan kembalikan top-k matches.
 *
 * @param float[] $vector
 * @return array  Array of match objects dari Pinecone
 */
function sv_queryPinecone(array $vector): array
{
    $url     = SV_PINECONE_INDEX_HOST . '/query';
    $payload = json_encode([
        'vector'          => $vector,
        'topK'            => SV_TOP_K,
        'includeMetadata' => true,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Api-Key: ' . SV_PINECONE_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 20,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $httpCode !== 200) {
        error_log("[search_vector] Pinecone query error HTTP $httpCode: $curlErr — " . substr($response, 0, 300));
        return [];
    }

    $data = json_decode($response, true);
    return $data['matches'] ?? [];
}

/**
 * Fungsi utama yang dipanggil dari prompt_llm.php.
 * Mengembalikan array berisi teks_asli yang relevan dan sumber referensi.
 *
 * @return array{konteks_teks: string[], sumber_referensi: array[]}
 */
function getRelevantContext(string $pertanyaan): array
{
    // 1. Embed pertanyaan
    $vector = sv_getEmbedding($pertanyaan);
    if ($vector === null) {
        return ['konteks_teks' => [], 'sumber_referensi' => []];
    }

    // 2. Query Pinecone
    $matches = sv_queryPinecone($vector);
    if (empty($matches)) {
        return ['konteks_teks' => [], 'sumber_referensi' => []];
    }

    // 3. Kumpulkan teks_asli dari metadata
    $konteks = [];
    $referensi = [];
    $seenUrls = [];

    foreach ($matches as $match) {
        $teks = $match['metadata']['teks_asli'] ?? '';
        $score = round($match['score'] ?? 0, 4);
        
        if ($teks !== '') {
            $tipe = $match['metadata']['tipe_sumber'] ?? 'Sumber';
            $konteks[] = "[{$tipe} | score={$score}] {$teks}";
        }

        // Kumpulkan referensi jika score > 0.6
        if ($score > 0.6) {
            $judul = $match['metadata']['judul_dokumen'] ?? null;
            $url = $match['metadata']['url_download'] ?? null;
            
            if ($judul && $url && !isset($seenUrls[$url])) {
                $referensi[] = [
                    'judul_dokumen' => $judul,
                    'url_download' => $url
                ];
                $seenUrls[$url] = true;
            }
        }
    }

    return [
        'konteks_teks' => $konteks,
        'sumber_referensi' => $referensi
    ];
}
