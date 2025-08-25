<?php
// Veritabanı bağlantı bilgileri
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kutuphane";

// Veritabanı bağlantısı oluştur
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES 'utf8'");
} catch(PDOException $e) {
    echo "Bağlantı hatası: " . $e->getMessage();
}

// Veritabanı tablosunu oluştur (eğer yoksa)
$sql = "CREATE TABLE IF NOT EXISTS kitaplar (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    kitap_adi VARCHAR(255) NOT NULL,
    yazar VARCHAR(255) NOT NULL,
    yayin_evi VARCHAR(255),
    yayin_yili INT(4),
    isbn VARCHAR(13),
    kategori VARCHAR(100),
    durum ENUM('Mevcut', 'Ödünç', 'Kayıp') DEFAULT 'Mevcut',
    eklenme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $conn->exec($sql);
} catch(PDOException $e) {
    echo "Tablo oluşturma hatası: " . $e->getMessage();
}
?>
