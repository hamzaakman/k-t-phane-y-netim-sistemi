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
    odunc_tarihi DATE NULL,
    son_teslim_tarihi DATE NULL,
    eklenme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $conn->exec($sql);
    
    // Mevcut tabloya yeni sütunları ekle (eğer yoksa)
    $alterSql1 = "ALTER TABLE kitaplar ADD COLUMN IF NOT EXISTS odunc_tarihi DATE NULL";
    $alterSql2 = "ALTER TABLE kitaplar ADD COLUMN IF NOT EXISTS son_teslim_tarihi DATE NULL";
    
    try {
        $conn->exec($alterSql1);
        $conn->exec($alterSql2);
    } catch(PDOException $e) {
        // Sütun zaten varsa hata vermez
    }
    
} catch(PDOException $e) {
    echo "Tablo oluşturma hatası: " . $e->getMessage();
}

// Üyeler tablosunu oluştur (eğer yoksa)
$uyelerSql = "CREATE TABLE IF NOT EXISTS uyeler (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(255) NOT NULL,
    telefon VARCHAR(20),
    eposta VARCHAR(255),
    uyelik_tarihi DATE NOT NULL,
    dogum_tarihi DATE,
    adres TEXT,
    uyelik_durumu ENUM('Aktif', 'Pasif') DEFAULT 'Aktif',
    kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

try {
    $conn->exec($uyelerSql);
} catch(PDOException $e) {
    echo "Üyeler tablosu oluşturma hatası: " . $e->getMessage();
}
?>
