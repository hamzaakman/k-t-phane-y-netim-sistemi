<?php
require_once 'db.php';

$message = '';
$messageType = '';
$kitap = null;

// Kitap ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Kitap bilgilerini al
try {
    $stmt = $conn->prepare("SELECT * FROM kitaplar WHERE id = ?");
    $stmt->execute([$id]);
    $kitap = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kitap) {
        header('Location: index.php');
        exit;
    }
} catch(PDOException $e) {
    $message = 'Kitap bilgileri alınırken hata oluştu: ' . $e->getMessage();
    $messageType = 'error';
}

// Silme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM kitaplar WHERE id = ?");
        $stmt->execute([$id]);
        
        $message = 'Kitap başarıyla silindi!';
        $messageType = 'success';
        
        // 2 saniye sonra ana sayfaya yönlendir
        header("refresh:2;url=index.php");
    } catch(PDOException $e) {
        $message = 'Kitap silinirken hata oluştu: ' . $e->getMessage();
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Sil - Kütüphane Yönetim Sistemi</title>
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
            </nav>
        </header>

        <div class="form-container">
            <h2>🗑️ Kitap Sil</h2>
            
            <?php if ($message): ?>
                <div class="message message-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                    <?php if ($messageType == 'success'): ?>
                        <p>Ana sayfaya yönlendiriliyorsunuz...</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($kitap && !$message): ?>
                <div class="delete-confirmation">
                    <div class="warning-box">
                        <h3>⚠️ Dikkat!</h3>
                        <p>Bu kitabı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!</p>
                    </div>

                    <div class="book-info">
                        <h3>Silinecek Kitap Bilgileri:</h3>
                        <div class="book-details">
                            <p><strong>Kitap Adı:</strong> <?php echo htmlspecialchars($kitap['kitap_adi']); ?></p>
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
                            <p><strong>Durum:</strong> 
                                <span class="status status-<?php echo strtolower($kitap['durum']); ?>">
                                    <?php 
                                    $durum_icons = [
                                        'Mevcut' => '✅ Mevcut',
                                        'Ödünç' => '📤 Ödünç', 
                                        'Kayıp' => '❌ Kayıp'
                                    ];
                                    echo $durum_icons[$kitap['durum']] ?? $kitap['durum']; 
                                    ?>
                                </span>
                            </p>
                            <p><strong>Eklenme Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($kitap['eklenme_tarihi'])); ?></p>
                        </div>
                    </div>

                    <form method="POST" action="" class="delete-form">
                        <div class="form-actions">
                            <button type="submit" name="confirm_delete" class="btn-delete" onclick="return confirm('Bu kitabı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!')">
                                🗑️ Evet, Bu Kitabı Sil
                            </button>
                            <a href="index.php" class="btn-secondary">❌ İptal Et</a>
                        </div>
                    </form>
                </div>
            <?php elseif (!$kitap && !$message): ?>
                <div class="error-message">
                    <p>Kitap bulunamadı!</p>
                    <a href="index.php" class="btn-secondary">🔙 Ana Sayfaya Dön</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
