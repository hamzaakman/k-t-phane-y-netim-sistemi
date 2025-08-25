<?php
require_once 'db.php';

// Arama ve filtreleme
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$durum_filter = isset($_GET['durum_filter']) ? $_GET['durum_filter'] : '';

// Silme başarı mesajı
$message = '';
$messageType = '';
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $message = 'Üye başarıyla silindi!';
    $messageType = 'success';
}

// Üyeleri getir
$sql = "SELECT * FROM uyeler WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (ad_soyad LIKE ? OR eposta LIKE ? OR telefon LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($durum_filter)) {
    $sql .= " AND uyelik_durumu = ?";
    $params[] = $durum_filter;
}

$sql .= " ORDER BY ad_soyad ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$uyeler = $stmt->fetchAll(PDO::FETCH_ASSOC);

// İstatistikler için
$stats_sql = "SELECT 
    COUNT(*) as toplam,
    SUM(CASE WHEN uyelik_durumu = 'Aktif' THEN 1 ELSE 0 END) as aktif,
    SUM(CASE WHEN uyelik_durumu = 'Pasif' THEN 1 ELSE 0 END) as pasif
    FROM uyeler";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Üye Yönetimi - Kütüphane Yönetim Sistemi</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Kütüphane Yönetim Sistemi</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="add.php">Kitap Ekle</a>
                <a href="status-update.php">Durum Güncelle</a>
                <a href="members.php" class="active">Üye Yönetimi</a>
            </nav>
        </header>

        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Üye adı, e-posta veya telefon ara..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="durum_filter">
                    <option value="">Tüm Durumlar</option>
                    <option value="Aktif" <?php echo $durum_filter == 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="Pasif" <?php echo $durum_filter == 'Pasif' ? 'selected' : ''; ?>>Pasif</option>
                </select>
                <button type="submit">🔍 Ara</button>
                <?php if (!empty($search) || !empty($durum_filter)): ?>
                    <a href="members.php" class="btn-secondary">🔄 Temizle</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>👥 Toplam Üye</h3>
                <p><?php echo $stats['toplam']; ?></p>
            </div>
            <div class="stat-card" data-status="aktif">
                <h3>✅ Aktif Üye</h3>
                <p><?php echo $stats['aktif']; ?></p>
            </div>
            <div class="stat-card" data-status="pasif">
                <h3>⏸️ Pasif Üye</h3>
                <p><?php echo $stats['pasif']; ?></p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message message-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="action-bar">
            <h2>👥 Üyeler (<?php echo count($uyeler); ?> üye)</h2>
            <a href="add-member.php" class="btn-primary">👤 Yeni Üye Ekle</a>
        </div>

        <?php if (empty($uyeler)): ?>
            <div class="no-results">
                <p>🔍 Hiç üye bulunamadı.</p>
                <a href="add-member.php" class="btn-primary">👤 İlk üyeyi ekle</a>
            </div>
        <?php else: ?>
            <div class="members-grid">
                <?php foreach ($uyeler as $uye): ?>
                    <div class="member-card">
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
                            <p><strong>📋 Kayıt:</strong> <?php echo date('d.m.Y', strtotime($uye['kayit_tarihi'])); ?></p>
                        </div>
                        <div class="member-actions">
                            <a href="update-member.php?id=<?php echo $uye['id']; ?>" class="btn-edit">✏️ Düzenle</a>
                            <a href="delete-member.php?id=<?php echo $uye['id']; ?>" class="btn-delete" onclick="return confirm('Bu üyeyi silmek istediğinizden emin misiniz?')">🗑️ Sil</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
