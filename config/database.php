<?php
require_once __DIR__ . '/functions.php';


// Konfigurasi koneksi database dari .env
$dbHost = $_ENV['DB_HOST'];
$dbName = $_ENV['DB_NAME'];
$dbUser = $_ENV['DB_USER'];
$dbPass = $_ENV['DB_PASS'];

try {
    // Buat koneksi PDO
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Tangani error koneksi
    die('Koneksi database gagal: ' . $e->getMessage());
}
