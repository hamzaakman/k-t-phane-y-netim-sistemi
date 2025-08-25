<?php
require_once 'db.php';

// Arama ve filtreleme
$search = isset($_GET['search']) ? $_GET['search'] : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$durum = isset($_GET['durum']) ? $_GET['durum'] : '';

// SQL sorgusu oluştur
$sql = "SELECT * FROM kitaplar WHERE 1=1";
$params = array();

if (!empty($search)) {
    $sql .= " AND (kitap_adi LIKE ? OR yazar LIKE ? OR isbn LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($kategori)) {
    $sql .= " AND kategori = ?";
    $params[] = $kategori;
}

if (!empty($durum)) {
    $sql .= " AND durum = ?";
    $params[] = $durum;
}

$sql .= " ORDER BY kitap_adi ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$kitaplar = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategorileri al
$kategoriStmt = $conn->query("SELECT DISTINCT kategori FROM kitaplar WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
$kategoriler = $kategoriStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kütüphane Yönetim Sistemi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Kütüphane Yönetim Sistemi</h1>
            <nav>
                <a href="index.php" class="active">Ana Sayfa</a>
                <a href="add.php">Kitap Ekle</a>
                <a href="status-update.php">Durum Güncelle</a>
                <a href="members.php">Üye Yönetimi</a>
            </nav>
        </header>

        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Kitap adı, yazar veya ISBN ara..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select name="kategori">
                    <option value="">Tüm Kategoriler</option>
                    <?php foreach ($kategoriler as $kat): ?>
                        <option value="<?php echo htmlspecialchars($kat); ?>" <?php echo $kategori == $kat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($kat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="durum">
                    <option value="">Tüm Durumlar</option>
                    <option value="Mevcut" <?php echo $durum == 'Mevcut' ? 'selected' : ''; ?>>Mevcut</option>
                    <option value="Ödünç" <?php echo $durum == 'Ödünç' ? 'selected' : ''; ?>>Ödünç</option>
                    <option value="Kayıp" <?php echo $durum == 'Kayıp' ? 'selected' : ''; ?>>Kayıp</option>
                </select>
                
                <button type="submit">🔍 Ara</button>
                <a href="index.php" class="clear-btn">Temizle</a>
            </form>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>📚 Toplam Kitap</h3>
                <p><?php echo count($kitaplar); ?></p>
            </div>
            <div class="stat-card" data-status="mevcut">
                <h3>✅ Mevcut</h3>
                <p><?php echo count(array_filter($kitaplar, function($k) { return $k['durum'] == 'Mevcut'; })); ?></p>
            </div>
            <div class="stat-card" data-status="odunc">
                <h3>📤 Ödünç</h3>
                <p><?php echo count(array_filter($kitaplar, function($k) { return $k['durum'] == 'Ödünç'; })); ?></p>
            </div>
            <div class="stat-card" data-status="kayip">
                <h3>❌ Kayıp</h3>
                <p><?php echo count(array_filter($kitaplar, function($k) { return $k['durum'] == 'Kayıp'; })); ?></p>
            </div>
        </div>

        <div class="books-section">
            <?php if (empty($kitaplar)): ?>
                <div class="no-books">
                    <p>Kitap bulunamadı.</p>
                    <a href="add.php" class="btn-primary">İlk Kitabı Ekle</a>
                </div>
            <?php else: ?>
                <div class="books-grid">
                    <?php foreach ($kitaplar as $kitap): ?>
                        <div class="book-card">
                            <div class="book-header">
                                <h3><?php echo htmlspecialchars($kitap['kitap_adi']); ?></h3>
                                                        <?php
                        // YENİ RENK SİSTEMİ
                        $statusClass = '';
                        
                        if ($kitap['durum'] == 'Mevcut') {
                            $statusClass = 'status-mevcut';
                        } elseif ($kitap['durum'] == 'Kayıp') {
                            $statusClass = 'status-kayıp';
                        } elseif ($kitap['durum'] == 'Ödünç' && !empty($kitap['son_teslim_tarihi'])) {
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
                            // Fallback için normal ödünç
                            $statusClass = 'status-' . strtolower($kitap['durum']);
                        }
                        ?>
                        <span class="status <?php echo $statusClass; ?>">
                            <?php
                            $durum_icons = [
                                'Mevcut' => '✅ Mevcut',
                                'Ödünç' => '📤 Ödünç',
                                'Kayıp' => '❌ Kayıp'
                            ];
                            echo $durum_icons[$kitap['durum']] ?? $kitap['durum'];
                            
                            // Ödünç kitaplar için tarih bilgisi (test sayfasından working code)
                            if ($kitap['durum'] == 'Ödünç' && !empty($kitap['son_teslim_tarihi'])) {
                                $bugun = new DateTime();
                                $sonTeslim = new DateTime($kitap['son_teslim_tarihi']);
                                
                                if ($bugun > $sonTeslim) {
                                    $fark = $bugun->diff($sonTeslim);
                                    echo ' (' . $fark->days . ' gün gecikme)';
                                } else {
                                    $fark = $bugun->diff($sonTeslim);
                                    echo ' (' . $fark->days . ' gün kaldı)';
                                }
                            }
                            ?>
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
                                
                                <?php if ($kitap['durum'] == 'Ödünç' && !empty($kitap['odunc_tarihi'])): ?>
                                    <p><strong>Ödünç Tarihi:</strong> <?php echo date('d.m.Y', strtotime($kitap['odunc_tarihi'])); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($kitap['durum'] == 'Ödünç' && !empty($kitap['son_teslim_tarihi'])): ?>
                                    <p><strong>Son Teslim:</strong> <?php echo date('d.m.Y', strtotime($kitap['son_teslim_tarihi'])); ?></p>
                                <?php endif; ?>
                                
                                <p><strong>Eklenme:</strong> <?php echo date('d.m.Y', strtotime($kitap['eklenme_tarihi'])); ?></p>
                            </div>
                            <div class="book-actions">
                                <a href="update.php?id=<?php echo $kitap['id']; ?>" class="btn-edit">✏️ Düzenle</a>
                                <a href="delete.php?id=<?php echo $kitap['id']; ?>" class="btn-delete" onclick="return confirm('Bu kitabı silmek istediğinizden emin misiniz?')">🗑️ Sil</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
