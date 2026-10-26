<?php
/**
 * Konfigurasi Koneksi Database MySQL / MariaDB
 * Portofolio Raya Mahardika Ibrahim
 */

// Konfigurasi Kredensial MySQL
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'portofolio');

/**
 * Membuat koneksi PDO ke MySQL dan menginisialisasi DB serta tabel secara otomatis jika belum ada.
 */
function getDbConnection(): array {
    static $connection = null;
    static $error = null;

    if ($connection !== null) {
        return ['pdo' => $connection, 'error' => null];
    }
    if ($error !== null) {
        return ['pdo' => null, 'error' => $error];
    }

    if (!extension_loaded('pdo_mysql')) {
        $error = "Ekstensi PHP 'pdo_mysql' belum aktif di php.ini. Jalankan server dengan 'php -d extension=pdo_mysql -S localhost:8000' atau aktifkan extension=pdo_mysql di /etc/php/php.ini.";
        return ['pdo' => null, 'error' => $error];
    }

    try {
        // Hubungkan ke server MySQL
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        // Buat database otomatis jika belum ada
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");

        // Buat tabel pesan_kontak jika belum ada
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pesan_kontak` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nama` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `telepon` VARCHAR(30) DEFAULT NULL,
            `kategori` VARCHAR(50) NOT NULL,
            `layanan` VARCHAR(50) NOT NULL,
            `pesan` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $connection = $pdo;
        return ['pdo' => $connection, 'error' => null];
    } catch (PDOException $e) {
        $error = "Gagal terhubung ke MySQL (" . $e->getMessage() . "). Pastikan server MySQL / MariaDB lokal sudah aktif dan kredensial di database.php sudah sesuai.";
        return ['pdo' => null, 'error' => $error];
    }
}

/**
 * Validasi dan simpan pesan formulir ke database.
 */
function simpanPesanKontak(array $input): array {
    $nama = trim($input['nama'] ?? '');
    $email = trim($input['email'] ?? '');
    $telepon = trim($input['telepon'] ?? '');
    $kategori = trim($input['kategori'] ?? 'diskusi');
    $layanan = trim($input['layanan'] ?? 'backend');
    $pesan = trim($input['pesan'] ?? '');

    // Validasi data masukan sebelum koneksi database
    if (empty($nama) || empty($email) || empty($pesan)) {
        return ['success' => false, 'message' => 'Mohon lengkapi semua isian yang wajib (Nama, Email, dan Pesan).'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Format email yang dimasukkan tidak valid.'];
    }

    if (mb_strlen($nama) > 100) {
        return ['success' => false, 'message' => 'Nama tidak boleh melebihi 100 karakter.'];
    }

    $db = getDbConnection();
    if ($db['error'] !== null) {
        return ['success' => false, 'message' => $db['error']];
    }

    try {
        $stmt = $db['pdo']->prepare("INSERT INTO `pesan_kontak` (`nama`, `email`, `telepon`, `kategori`, `layanan`, `pesan`) VALUES (:nama, :email, :telepon, :kategori, :layanan, :pesan)");
        $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':telepon' => $telepon !== '' ? $telepon : null,
            ':kategori' => $kategori,
            ':layanan' => $layanan,
            ':pesan' => $pesan,
        ]);
        return ['success' => true, 'message' => 'Terima kasih, ' . htmlspecialchars($nama) . '! Pesan Anda berhasil disimpan ke database.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Gagal menyimpan ke database: ' . $e->getMessage()];
    }
}
