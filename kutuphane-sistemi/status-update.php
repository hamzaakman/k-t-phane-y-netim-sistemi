<?php
require_once 'db.php';

$message = '';
$messageType = '';

// Sadece ödünç kitapları getir
$sql = "SELECT * FROM kitaplar WHERE durum = 'Ödünç' ORDER BY kitap_adi ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$odunc_kitaplar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Durum güncelleme
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kitap_id = $_POST['kitap_id'];
    $yeni_durum = $_POST['yeni_durum'];
    
    if (empty($yeni_durum)) {
        $message = 'Lütfen yeni durum seçin!';
        $messageType = 'error';
    } else {
        try {
            $sql = "UPDATE kitaplar SET durum = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$yeni_durum, $kitap_id]);
            
            $message = 'Kitap durumu başarıyla güncellendi!';
            $messageType = 'success';
            
            // Listeyi yenile
            $stmt = $conn->prepare("SELECT * FROM kitaplar WHERE durum = 'Ödünç' ORDER BY kitap_adi ASC");
            $stmt->execute();
            $odunc_kitaplar = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $message = 'Güncelleme hatası: ' . $e->getMessage();
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
    <title>Durum Güncelle - Kütüphane Yönetim Sistemi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Kütüphane Yönetim Sistemi</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="add.php">Kitap Ekle</a>
                <a href="status-update.php" class="active">Durum Güncelle</a>
            </nav>
        </header>

        <div class="form-container">
            <h2>📤 Ödünç Kitaplar - Durum Güncelleme</h2>
            
            <?php if ($message): ?>
                <div class="message message-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($odunc_kitaplar)): ?>
                <div class="no-books">
                    <p>📚 Şu anda ödünç verilen kitap bulunmuyor.</p>
                    <a href="index.php" class="btn-secondary">🔙 Ana Sayfaya Dön</a>
                </div>
            <?php else: ?>
                <div class="status-update-info">
                    <div class="info-box">
                        <h3>ℹ️ Bilgi</h3>
                        <p>Bu sayfada sadece <strong>ödünç verilen kitaplar</strong> listelenir.</p>
                        <p>Kitap geri alındığında <strong>"Mevcut"</strong>, kaybolduğunda <strong>"Kayıp"</strong> olarak işaretleyin.</p>
                    </div>
                </div>

                <div class="books-grid">
                    <?php foreach ($odunc_kitaplar as $kitap): ?>
                        <div class="book-card">
                            <div class="book-header">
                                <h3><?php echo htmlspecialchars($kitap['kitap_adi']); ?></h3>
                                <span class="status status-ödünç">
                                    📤 Ödünç
                                </span>
                            </div>
                            <div class="book-info">
                                <p><strong>Yazar:</strong> <?php echo htmlspecialchars($kitap['yazar']); ?></p>
                                <?php if (!empty($kitap['yayin_evi'])): ?>
                                    <p><strong>Yayınevi:</strong> <?php echo htmlspecialchars($kitap['yayin_evi']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($kitap['yayin_yili'])): ?>
                                    <p><strong>Yayın Yılı:</strong> <?php echo $kitap['yayin_yili']; ?></p>
                                <?php endif; ?>
                                <?php if (!empty($kitap['isbn'])): ?>
                                    <p><strong>ISBN:</strong> <?php echo htmlspecialchars($kitap['isbn']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($kitap['kategori'])): ?>
                                    <p><strong>Kategori:</strong> <?php echo htmlspecialchars($kitap['kategori']); ?></p>
                                <?php endif; ?>
                                <p><strong>Ödünç Verilme:</strong> <?php echo date('d.m.Y H:i', strtotime($kitap['eklenme_tarihi'])); ?></p>
                            </div>
                            <form method="POST" action="" class="status-form">
                                <input type="hidden" name="kitap_id" value="<?php echo $kitap['id']; ?>">
                                <div class="status-update-section">
                                    <label for="yeni_durum_<?php echo $kitap['id']; ?>">Yeni Durum:</label>
                                    <select name="yeni_durum" id="yeni_durum_<?php echo $kitap['id']; ?>" required>
                                        <option value="">Durum Seçin</option>
                                        <option value="Mevcut">✅ Mevcut (Geri Alındı)</option>
                                        <option value="Kayıp">❌ Kayıp</option>
                                    </select>
                                    <button type="submit" class="btn-primary">💾 Güncelle</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
