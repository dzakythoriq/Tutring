<?php
$conn = new mysqli("localhost", "root", "", "tutring");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
echo "Koneksi berhasil!";
?>
