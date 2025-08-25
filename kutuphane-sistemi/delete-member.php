<?php
require_once 'db.php';

$message = '';
$messageType = '';
$uye = null;

// Üye ID'sini al
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: members.php');
    exit;
}

// Üye bilgilerini al
try {
    $stmt = $conn->prepare("SELECT * FROM uyeler WHERE id = ?");
    $stmt->execute([$id]);
    $uye = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$uye) {
        header('Location: members.php');
        exit;
    }
} catch(PDOException $e) {
    $message = 'Üye bilgileri alınırken hata oluştu: ' . $e->getMessage();
    $messageType = 'error';
}

// Silme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM uyeler WHERE id = ?");
        $stmt->execute([$id]);
        
        // Başarılı silme sonrası üye listesine yönlendir
        header('Location: members.php?deleted=1');
        exit;
    } catch(PDOException $e) {
        $message = 'Üye silinirken hata oluştu: ' . $e->getMessage();
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üye Sil - Kütüphane Yönetim Sistemi</title>
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
            <?php if ($uye): ?>
                <h2>🗑️ Üye Sil</h2>
                
                <?php if ($message): ?>
                    <div class="message message-<?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="delete-warning">
                    <p><strong>⚠️ DİKKAT:</strong> Bu işlem geri alınamaz!</p>
                    <p>Aşağıdaki üyeyi silmek istediğinizden emin misiniz?</p>
                </div>

                <div class="member-details">
                    <div class="member-header">
                        <h3><?php echo htmlspecialchars($uye['ad_soyad']); ?></h3>
                        <span class="status status-<?php echo strtolower($uye['uyelik_durumu']); ?>">
                            <?php echo $uye['uyelik_durumu'] == 'Aktif' ? '✅ Aktif' : '⏸️ Pasif'; ?>
                        </span>
                    </div>
                    <div class="member-info">
                        <p><strong>📞 Telefon:</strong> <?php echo htmlspecialchars($uye['telefon'] ?? 'Belirtilmemiş'); ?></p>
                        <p><strong>📧 E-posta:</strong> <?php echo htmlspecialchars($uye['eposta'] ?? 'Belirtilmemiş'); ?></p>
                        <p><strong>📅 Üyelik Tarihi:</strong> <?php echo date('d.m.Y', strtotime($uye['uyelik_tarihi'])); ?></p>
                        <?php if (!empty($uye['dogum_tarihi'])): ?>
                            <p><strong>🎂 Doğum Tarihi:</strong> <?php echo date('d.m.Y', strtotime($uye['dogum_tarihi'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($uye['adres'])): ?>
                            <p><strong>🏠 Adres:</strong> <?php echo htmlspecialchars($uye['adres']); ?></p>
                        <?php endif; ?>
                        <p><strong>🆔 Üye No:</strong> <?php echo str_pad($uye['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        <p><strong>📋 Kayıt:</strong> <?php echo date('d.m.Y H:i', strtotime($uye['kayit_tarihi'])); ?></p>
                    </div>
                </div>

                <form method="POST" action="" class="delete-form">
                    <div class="form-actions">
                        <button type="submit" name="confirm_delete" class="btn-delete" onclick="return confirm('Bu üyeyi kalıcı olarak silmek istediğinizden emin misiniz?')">
                            🗑️ Evet, Sil
                        </button>
                        <a href="members.php" class="btn-secondary">❌ İptal</a>
                        <a href="update-member.php?id=<?php echo $uye['id']; ?>" class="btn-primary">✏️ Düzenle</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="error-message">
                    <p>Üye bulunamadı!</p>
                    <a href="members.php" class="btn-secondary">🔙 Üye Listesine Dön</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
