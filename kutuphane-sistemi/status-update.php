<?php
require_once 'db.php';

$message = '';
$messageType = '';

// Arama parametresi
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Sadece ödünç kitapları üye bilgileriyle birlikte getir - arama filtresi ile
$sql = "SELECT k.*, u.ad_soyad as uye_adi FROM kitaplar k 
        LEFT JOIN uyeler u ON k.odunc_verilen_uye_id = u.id 
        WHERE k.durum = 'Ödünç'";
$params = [];

if (!empty($search)) {
    $sql .= " AND (k.kitap_adi LIKE ? OR k.yazar LIKE ? OR u.ad_soyad LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql .= " ORDER BY k.kitap_adi ASC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
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
            
            // Listeyi yenile - arama parametresi ile
            $refreshSql = "SELECT k.*, u.ad_soyad as uye_adi FROM kitaplar k 
                          LEFT JOIN uyeler u ON k.odunc_verilen_uye_id = u.id 
                          WHERE k.durum = 'Ödünç'";
            $refreshParams = [];
            
            if (!empty($search)) {
                $refreshSql .= " AND (k.kitap_adi LIKE ? OR k.yazar LIKE ? OR u.ad_soyad LIKE ?)";
                $searchParam = "%$search%";
                $refreshParams[] = $searchParam;
                $refreshParams[] = $searchParam;
                $refreshParams[] = $searchParam;
            }
            
            $refreshSql .= " ORDER BY k.kitap_adi ASC";
            $stmt = $conn->prepare($refreshSql);
            $stmt->execute($refreshParams);
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
                <a href="members.php">Üye Yönetimi</a>
            </nav>
        </header>

        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Kitap adı, yazar veya üye adı ara..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">🔍 Ara</button>
                <?php if (!empty($search)): ?>
                    <a href="status-update.php" class="btn-secondary">🔄 Temizle</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="form-container">
            <h2>📤 Ödünç Kitaplar - Durum Güncelleme
                <?php if (!empty($search)): ?>
                    <span style="font-size: 0.8em; color: #666;">(<?php echo count($odunc_kitaplar); ?> sonuç: "<?php echo htmlspecialchars($search); ?>")</span>
                <?php else: ?>
                    <span style="font-size: 0.8em; color: #666;">(<?php echo count($odunc_kitaplar); ?> kitap)</span>
                <?php endif; ?>
            </h2>
            
            <?php if ($message): ?>
                <div class="message message-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($odunc_kitaplar)): ?>
                <div class="no-books">
                    <?php if (!empty($search)): ?>
                        <p>🔍 "<?php echo htmlspecialchars($search); ?>" araması için hiç sonuç bulunamadı.</p>
                        <a href="status-update.php" class="btn-secondary">🔄 Tüm Kitapları Göster</a>
                    <?php else: ?>
                        <p>📚 Şu anda ödünç verilen kitap bulunmuyor.</p>
                        <a href="index.php" class="btn-secondary">🔙 Ana Sayfaya Dön</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="status-update-info">
                    <div class="info-box">
                        <h3>ℹ️ Bilgi</h3>
                        <p>Bu sayfada sadece <strong>ödünç verilen kitaplar</strong> listelenir.</p>
                        <p>Kitap geri alındığında <strong>"Mevcut"</strong>, kaybolduğunda <strong>"Kayıp"</strong> olarak işaretleyin.</p>
                        <p>🔍 <strong>Arama yapabilirsiniz:</strong> Kitap adı, yazar adı veya üye adı ile arama yapın.</p>
                    </div>
                </div>

                <div class="books-grid">
                    <?php foreach ($odunc_kitaplar as $kitap): ?>
                        <div class="book-card">
                            <div class="book-header">
                                <h3><?php echo htmlspecialchars($kitap['kitap_adi']); ?></h3>
                                <?php
                                // YENİ RENK SİSTEMİ
                                $statusClass = '';
                                
                                if (!empty($kitap['son_teslim_tarihi'])) {
                                    $bugun = new DateTime();
                                    $sonTeslim = new DateTime($kitap['son_teslim_tarihi']);
                                    
                                    if ($bugun > $sonTeslim) {
                                        // Gecikmiş - Kırmızı
                                        $statusClass = 'status-odunc-gecmis';
                                    } else {
                                        $fark = $bugun->diff($sonTeslim);
                                        $gunFarki = $fark->days;
                                        
                                        if ($gunFarki <= 3) {
                                            // Yakın tarih - Turuncu
                                            $statusClass = 'status-odunc-yakin';
                                        } else {
                                            // Güvenli - Mavi
                                            $statusClass = 'status-odunc-guvenli';
                                        }
                                    }
                                } else {
                                    // Fallback
                                    $statusClass = 'status-ödünç';
                                }
                                ?>
                                <span class="status <?php echo $statusClass; ?>">
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
                                
                                <?php if (!empty($kitap['uye_adi'])): ?>
                                    <p><strong>👤 Ödünç Alan:</strong> <?php echo htmlspecialchars($kitap['uye_adi']); ?></p>
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
