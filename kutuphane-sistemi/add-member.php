<?php
require_once 'db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Otomatik büyük harf başlatma fonksiyonu
    function ucwords_turkish($str) {
        // Türkçe karakter desteği ile her kelimenin ilk harfini büyük yapar
        $str = mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
        return $str;
    }
    
    $ad_soyad = ucwords_turkish(trim($_POST['ad_soyad']));
    $telefon = trim($_POST['telefon']);
    $eposta = trim($_POST['eposta']);
    $uyelik_tarihi = $_POST['uyelik_tarihi'];
    $dogum_tarihi = $_POST['dogum_tarihi'];
    $adres = trim($_POST['adres']);
    $uyelik_durumu = $_POST['uyelik_durumu'];
    
    // Basit doğrulama
    if (empty($ad_soyad)) {
        $message = 'Ad Soyad alanı zorunludur!';
        $messageType = 'error';
    } elseif (empty($uyelik_tarihi)) {
        $message = 'Üyelik tarihi zorunludur!';
        $messageType = 'error';
    } else {
        try {
            $sql = "INSERT INTO uyeler (ad_soyad, telefon, eposta, uyelik_tarihi, dogum_tarihi, adres, uyelik_durumu) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$ad_soyad, $telefon, $eposta, $uyelik_tarihi, $dogum_tarihi, $adres, $uyelik_durumu]);
            
            $message = 'Üye başarıyla eklendi!';
            $messageType = 'success';
            
            // Formu temizle
            $_POST = array();
        } catch(PDOException $e) {
            $message = 'Üye eklenirken hata oluştu: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Üye Ekle - Kütüphane Yönetim Sistemi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Kütüphane Yönetim Sistemi</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="add.php">Kitap Ekle</a>
                <a href="status-update.php">Durum Güncelle</a>
                <a href="members.php">Üye Yönetimi</a>
            </nav>
        </header>

        <div class="form-container">
            <h2>👤 Yeni Üye Ekle</h2>
            
            <?php if ($message): ?>
                <div class="message message-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="book-form">
                <div class="form-group">
                    <label for="ad_soyad">Ad Soyad *</label>
                    <input type="text" id="ad_soyad" name="ad_soyad" value="<?php echo isset($_POST['ad_soyad']) ? htmlspecialchars($_POST['ad_soyad']) : ''; ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefon">Telefon</label>
                        <input type="tel" id="telefon" name="telefon" value="<?php echo isset($_POST['telefon']) ? htmlspecialchars($_POST['telefon']) : ''; ?>" placeholder="0532 123 45 67">
                    </div>

                    <div class="form-group">
                        <label for="eposta">E-posta</label>
                        <input type="email" id="eposta" name="eposta" value="<?php echo isset($_POST['eposta']) ? htmlspecialchars($_POST['eposta']) : ''; ?>" placeholder="ornek@email.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="uyelik_tarihi">Üyelik Tarihi *</label>
                        <input type="date" id="uyelik_tarihi" name="uyelik_tarihi" value="<?php echo isset($_POST['uyelik_tarihi']) ? htmlspecialchars($_POST['uyelik_tarihi']) : date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="dogum_tarihi">Doğum Tarihi</label>
                        <input type="date" id="dogum_tarihi" name="dogum_tarihi" value="<?php echo isset($_POST['dogum_tarihi']) ? htmlspecialchars($_POST['dogum_tarihi']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="adres">Adres</label>
                    <textarea id="adres" name="adres" rows="3" placeholder="Tam adres bilgisi"><?php echo isset($_POST['adres']) ? htmlspecialchars($_POST['adres']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="uyelik_durumu">Üyelik Durumu</label>
                    <select id="uyelik_durumu" name="uyelik_durumu">
                        <option value="Aktif" <?php echo (isset($_POST['uyelik_durumu']) && $_POST['uyelik_durumu'] == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Pasif" <?php echo (isset($_POST['uyelik_durumu']) && $_POST['uyelik_durumu'] == 'Pasif') ? 'selected' : ''; ?>>Pasif</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">👤 Üye Ekle</button>
                    <a href="members.php" class="btn-secondary">🔙 Geri Dön</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
