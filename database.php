<?php
/**
 * Konfigurasi Koneksi Backend Supabase (REST API)
 * Portofolio Raya Mahardika Ibrahim
 */

/**
 * Parser sederhana untuk membaca berkas .env tanpa dependensi pihak ketiga.
 */
function loadEnv(string $filePath): void {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Bersihkan kutip pembungkus
            $value = trim($value, "\"'");
            if (!getenv($key)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Muat otomatis berkas .env jika tersedia
loadEnv(__DIR__ . '/.env');

define('SUPABASE_URL', getenv('SUPABASE_URL') ?: ($_ENV['SUPABASE_URL'] ?? ''));
define('SUPABASE_KEY', getenv('SUPABASE_KEY') ?: ($_ENV['SUPABASE_KEY'] ?? ''));
define('SUPABASE_TABLE', 'pesan_kontak');

/**
 * Validasi dan kirim data formulir kontak ke Supabase melalui REST API PostgREST.
 */
function simpanPesanKontak(array $input): array {
    $nama = trim($input['nama'] ?? '');
    $email = trim($input['email'] ?? '');
    $telepon = trim($input['telepon'] ?? '');
    $kategori = trim($input['kategori'] ?? 'diskusi');
    $layanan = trim($input['layanan'] ?? 'backend');
    $pesan = trim($input['pesan'] ?? '');

    // 1. Validasi Input
    if (empty($nama) || empty($email) || empty($pesan)) {
        return ['success' => false, 'message' => 'Mohon lengkapi semua isian yang wajib (Nama, Email, dan Pesan).'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Format email yang dimasukkan tidak valid.'];
    }

    if (mb_strlen($nama) > 100) {
        return ['success' => false, 'message' => 'Nama tidak boleh melebihi 100 karakter.'];
    }

    // 2. Cek apakah berkas .env sudah dikonfigurasi
    if (
        empty(SUPABASE_URL) || 
        empty(SUPABASE_KEY) || 
        strpos(SUPABASE_URL, 'YOUR_PROJECT_REF') !== false ||
        strpos(SUPABASE_KEY, 'YOUR_SUPABASE_ANON_KEY') !== false
    ) {
        return [
            'success' => false,
            'message' => 'File .env belum ditemukan atau belum diisi. Buat file .env dari .env.example lalu masukkan SUPABASE_URL dan SUPABASE_KEY Anda.'
        ];
    }

    // 3. Siapkan cURL untuk POST ke Supabase REST API
    $endpoint = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . SUPABASE_TABLE;
    $payload = json_encode([
        'nama' => $nama,
        'email' => $email,
        'telepon' => $telepon !== '' ? $telepon : null,
        'kategori' => $kategori,
        'layanan' => $layanan,
        'pesan' => $pesan,
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Prefer: return=minimal',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // 4. Periksa Hasil cURL & Kode Status HTTP
    if ($curlError) {
        return ['success' => false, 'message' => 'Gagal terhubung ke Supabase: ' . $curlError];
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'success' => true,
            'message' => 'Terima kasih, ' . htmlspecialchars($nama) . '! Pesan Anda telah berhasil tersimpan di Supabase.'
        ];
    }

    // Tangani respons error spesifik dari Supabase
    $errorData = json_decode($response, true);
    $detailMsg = $errorData['message'] ?? $response ?? 'Kode status HTTP ' . $httpCode;

    if ($httpCode === 401 || $httpCode === 403) {
        return [
            'success' => false,
            'message' => 'Akses ditolak oleh Supabase (' . $detailMsg . '). Pastikan Anon Key benar dan RLS Policy untuk INSERT anon sudah diaktifkan.'
        ];
    }

    if ($httpCode === 404) {
        return [
            'success' => false,
            'message' => 'Tabel "' . SUPABASE_TABLE . '" tidak ditemukan di Supabase. Jalankan script SQL di database.sql pada Supabase SQL Editor.'
        ];
    }

    return ['success' => false, 'message' => 'Supabase error (' . $httpCode . '): ' . $detailMsg];
}
