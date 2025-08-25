<?php
require_once 'db.php';

// Üye ID'sini al
$uye_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($uye_id <= 0) {
    header('Location: members.php');
    exit;
}

// Üye bilgilerini al
try {
    $stmt = $conn->prepare("SELECT * FROM uyeler WHERE id = ?");
    $stmt->execute([$uye_id]);
    $uye = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$uye) {
        header('Location: members.php');
        exit;
    }
} catch(PDOException $e) {
    echo "Üye bilgileri alınırken hata oluştu: " . $e->getMessage();
    exit;
}

// Üyenin ödünç aldığı kitapları al
try {
    $sql = "SELECT * FROM kitaplar WHERE odunc_verilen_uye_id = ? AND durum = 'Ödünç' ORDER BY odunc_tarihi DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$uye_id]);
    $odunc_kitaplar = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $odunc_kitaplar = [];
    $error_message = "Kitap bilgileri alınırken hata oluştu: " . $e->getMessage();
}

// Üyenin geçmiş kitap geçmişi (teslim ettiği kitaplar)
try {
    $sql = "SELECT k.*, 'Teslim Edildi' as eski_durum 
            FROM kitaplar k 
            WHERE k.durum IN ('Mevcut', 'Kayıp') 
            AND k.odunc_verilen_uye_id IS NULL 
            AND EXISTS (
                SELECT 1 FROM kitaplar k2 
                WHERE k2.id = k.id 
                AND k2.eklenme_tarihi < NOW()
            )
            LIMIT 10";
    // Bu sorgu basitleştirilmiş - gerçek uygulamada geçmiş tablosu olmalı
    $gecmis_kitaplar = [];
} catch(PDOException $e) {
    $gecmis_kitaplar = [];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($uye['ad_soyad']); ?> - Kitapları - Kütüphane Yönetim Sistemi</title>
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
            <div class="member-detail-header">
                <h2>👤 <?php echo htmlspecialchars($uye['ad_soyad']); ?></h2>
                <span class="status status-<?php echo strtolower($uye['uyelik_durumu']); ?>">
                    <?php echo $uye['uyelik_durumu'] == 'Aktif' ? '✅ Aktif' : '⏸️ Pasif'; ?>
                </span>
            </div>

            <div class="member-quick-info">
                <p><strong>🆔 Üye No:</strong> <?php echo str_pad($uye['id'], 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>📞 Telefon:</strong> <?php echo htmlspecialchars($uye['telefon'] ?? 'Belirtilmemiş'); ?></p>
                <p><strong>📧 E-posta:</strong> <?php echo htmlspecialchars($uye['eposta'] ?? 'Belirtilmemiş'); ?></p>
                <p><strong>📅 Üyelik:</strong> <?php echo date('d.m.Y', strtotime($uye['uyelik_tarihi'])); ?></p>
            </div>

            <div class="books-section">
                <h3>📚 Şu Anda Ödünç Aldığı Kitaplar (<?php echo count($odunc_kitaplar); ?> adet)</h3>
                
                <?php if (empty($odunc_kitaplar)): ?>
                    <div class="no-books">
                        <p>📖 Bu üyenin şu anda ödünç aldığı kitap bulunmuyor.</p>
                    </div>
                <?php else: ?>
                    <div class="books-grid">
                        <?php foreach ($odunc_kitaplar as $kitap): ?>
                            <div class="book-card">
                                <div class="book-header">
                                    <h4><?php echo htmlspecialchars($kitap['kitap_adi']); ?></h4>
                                    <?php
                                    $statusClass = '';
                                    if (!empty($kitap['son_teslim_tarihi'])) {
                                        $bugun = new DateTime();
                                        $sonTeslim = new DateTime($kitap['son_teslim_tarihi']);
                                        
                                        if ($bugun > $sonTeslim) {
                                            $statusClass = 'status-odunc-gecmis';
                                        } else {
                                            $fark = $bugun->diff($sonTeslim);
                                            $gunFarki = $fark->days;
                                            
                                            if ($gunFarki <= 3) {
                                                $statusClass = 'status-odunc-yakin';
                                            } else {
                                                $statusClass = 'status-odunc-guvenli';
                                            }
                                        }
                                    }
                                    ?>
                                    <span class="status <?php echo $statusClass; ?>">📤 Ödünç</span>
                                </div>
                                <div class="book-info">
                                    <p><strong>Yazar:</strong> <?php echo htmlspecialchars($kitap['yazar']); ?></p>
                                    <?php if (!empty($kitap['odunc_tarihi'])): ?>
                                        <p><strong>Ödünç Tarihi:</strong> <?php echo date('d.m.Y', strtotime($kitap['odunc_tarihi'])); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($kitap['son_teslim_tarihi'])): ?>
                                        <p><strong>Son Teslim:</strong> <?php echo date('d.m.Y', strtotime($kitap['son_teslim_tarihi'])); ?></p>
                                        <?php
                                        $bugun = new DateTime();
                                        $sonTeslim = new DateTime($kitap['son_teslim_tarihi']);
                                        
                                        if ($bugun > $sonTeslim) {
                                            $fark = $bugun->diff($sonTeslim);
                                            echo '<p style="color: #e53e3e; font-weight: bold;">⚠️ ' . $fark->days . ' gün gecikme!</p>';
                                        } else {
                                            $fark = $bugun->diff($sonTeslim);
                                            $gunFarki = $fark->days;
                                            
                                            if ($gunFarki <= 3) {
                                                echo '<p style="color: #fbb040; font-weight: bold;">⏰ ' . $gunFarki . ' gün kaldı</p>';
                                            } else {
                                                echo '<p style="color: #38a169; font-weight: bold;">✅ ' . $gunFarki . ' gün kaldı</p>';
                                            }
                                        }
                                        ?>
                                    <?php endif; ?>
                                </div>
                                <div class="book-actions">
                                    <a href="update.php?id=<?php echo $kitap['id']; ?>" class="btn-edit">✏️ Düzenle</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="action-bar">
                <a href="members.php" class="btn-secondary">🔙 Üye Listesine Dön</a>
                <a href="update-member.php?id=<?php echo $uye['id']; ?>" class="btn-primary">✏️ Üye Bilgilerini Düzenle</a>
            </div>
        </div>
    </div>
</body>
</html>
