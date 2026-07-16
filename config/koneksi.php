<?php
$host     = 'localhost';
$dbname   = 'km_computerclub';
$username = 'root';   
$password = '';        

try {
    $conn = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // jika koneksi gagal
    die("Koneksi ke database gagal: " . $e->getMessage());
}