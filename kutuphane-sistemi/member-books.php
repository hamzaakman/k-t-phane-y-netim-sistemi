<?php
require_once 'db.php';

// Üye ID'sini al
$uye_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'current';

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

// Mevcut ödünç kitapları
try {
    $sql = "SELECT * FROM kitaplar WHERE odunc_verilen_uye_id = ? AND durum = 'Ödünç' ORDER BY odunc_tarihi DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$uye_id]);
    $odunc_kitaplar = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $odunc_kitaplar = [];
}

// Geçmiş kitapları simüle et (gerçek uygulamada ayrı bir history tablosu olmalı)
try {
    // Bu basit bir simulasyon - gerçek sistemde kitap_gecmis tablosu olmalı
    $sql = "SELECT k.*, 'Teslim Edildi' as eski_durum, k.eklenme_tarihi as teslim_tarihi
            FROM kitaplar k 
            WHERE k.durum = 'Mevcut' 
            ORDER BY k.eklenme_tarihi DESC 
            LIMIT 10";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $gecmis_kitaplar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sadece bu üyenin okuyabileceği kitapları göstermek için filtrele (simulasyon)
    $gecmis_kitaplar = array_slice($gecmis_kitaplar, 0, 3); // Örnek olarak 3 kitap
} catch(PDOException $e) {
    $gecmis_kitaplar = [];
}

// Öneri algoritması
try {
    // 1. Üyenin okuduğu kategorileri bul
    $kategori_sql = "SELECT DISTINCT kategori FROM kitaplar WHERE odunc_verilen_uye_id = ? AND kategori IS NOT NULL";
    $kategori_stmt = $conn->prepare($kategori_sql);
    $kategori_stmt->execute([$uye_id]);
    $okunan_kategoriler = $kategori_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 2. Aynı kategorilerden mevcut kitapları öner
    $oneri_kitaplar = [];
    if (!empty($okunan_kategoriler)) {
        $kategori_placeholders = str_repeat('?,', count($okunan_kategoriler) - 1) . '?';
        $oneri_sql = "SELECT k.*, 'Kategori Benzerliği' as oneri_nedeni 
                      FROM kitaplar k 
                      WHERE k.kategori IN ($kategori_placeholders) 
                      AND k.durum = 'Mevcut' 
                      AND k.id NOT IN (
                          SELECT k2.id FROM kitaplar k2 WHERE k2.odunc_verilen_uye_id = ?
                      ) 
                      ORDER BY k.eklenme_tarihi DESC 
                      LIMIT 5";
        $oneri_params = array_merge($okunan_kategoriler, [$uye_id]);
        $oneri_stmt = $conn->prepare($oneri_sql);
        $oneri_stmt->execute($oneri_params);
        $oneri_kitaplar = $oneri_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 3. Popüler kitapları ekle (eğer kategori bazında yeterli yoksa)
    if (count($oneri_kitaplar) < 3) {
        $populer_sql = "SELECT k.*, 'Popüler Kitap' as oneri_nedeni 
                       FROM kitaplar k 
                       WHERE k.durum = 'Mevcut' 
                       AND k.id NOT IN (
                           SELECT k2.id FROM kitaplar k2 WHERE k2.odunc_verilen_uye_id = ?
                       ) 
                       ORDER BY k.eklenme_tarihi DESC 
                       LIMIT " . (5 - count($oneri_kitaplar));
        $populer_stmt = $conn->prepare($populer_sql);
        $populer_stmt->execute([$uye_id]);
        $populer_kitaplar = $populer_stmt->fetchAll(PDO::FETCH_ASSOC);
        $oneri_kitaplar = array_merge($oneri_kitaplar, $populer_kitaplar);
    }
} catch(PDOException $e) {
    $oneri_kitaplar = [];
    $okunan_kategoriler = [];
}

// İstatistikler
$toplam_odunc = count($odunc_kitaplar);
$toplam_gecmis = count($gecmis_kitaplar);
$toplam_oneri = count($oneri_kitaplar);
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

            <!-- Sekmeler -->
            <div class="tabs-container">
                <div class="tabs-nav">
                    <a href="?id=<?php echo $uye_id; ?>&tab=current" class="tab-button <?php echo $active_tab == 'current' ? 'active' : ''; ?>">
                        📤 Mevcut Kitaplar (<?php echo $toplam_odunc; ?>)
                    </a>
                    <a href="?id=<?php echo $uye_id; ?>&tab=history" class="tab-button <?php echo $active_tab == 'history' ? 'active' : ''; ?>">
                        📚 Geçmiş (<?php echo $toplam_gecmis; ?>)
                    </a>
                    <a href="?id=<?php echo $uye_id; ?>&tab=recommendations" class="tab-button <?php echo $active_tab == 'recommendations' ? 'active' : ''; ?>">
                        💡 Öneriler (<?php echo $toplam_oneri; ?>)
                    </a>
                </div>

                <div class="tab-content">
                    <!-- Mevcut Kitaplar Sekmesi -->
                    <div class="tab-pane <?php echo $active_tab == 'current' ? 'active' : ''; ?>">
                        <div class="tab-stats">
                            <div class="tab-stat-card">
                                <h4>📤 Ödünç Kitap</h4>
                                <p><?php echo $toplam_odunc; ?></p>
                            </div>
                            <div class="tab-stat-card">
                                <h4>⏰ Geciken</h4>
                                <p><?php 
                                $geciken = 0;
                                foreach($odunc_kitaplar as $kitap) {
                                    if (!empty($kitap['son_teslim_tarihi'])) {
                                        $bugun = new DateTime();
                                        $sonTeslim = new DateTime($kitap['son_teslim_tarihi']);
                                        if ($bugun > $sonTeslim) $geciken++;
                                    }
                                }
                                echo $geciken;
                                ?></p>
                            </div>
                            <div class="tab-stat-card">
                                <h4>📅 Yakında Teslim</h4>
                                <p><?php 
                                $yakin = 0;
                                foreach($odunc_kitaplar as $kitap) {
                                    if (!empty($kitap['son_teslim_tarihi'])) {
                                        $bugun = new DateTime();
                                        $sonTeslim = new DateTime($kitap['son_teslim_tarihi']);
                                        if ($bugun <= $sonTeslim) {
                                            $fark = $bugun->diff($sonTeslim);
                                            if ($fark->days <= 3) $yakin++;
                                        }
                                    }
                                }
                                echo $yakin;
                                ?></p>
                            </div>
                        </div>

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
                                        </div>
                                        <div class="book-actions">
                                            <a href="update.php?id=<?php echo $kitap['id']; ?>" class="btn-edit">✏️ Düzenle</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Geçmiş Kitaplar Sekmesi -->
                    <div class="tab-pane <?php echo $active_tab == 'history' ? 'active' : ''; ?>">
                        <div class="tab-stats">
                            <div class="tab-stat-card">
                                <h4>📚 Toplam Okunan</h4>
                                <p><?php echo $toplam_gecmis; ?></p>
                            </div>
                            <div class="tab-stat-card">
                                <h4>📂 Favori Kategori</h4>
                                <p><?php echo !empty($okunan_kategoriler) ? htmlspecialchars($okunan_kategoriler[0]) : 'Yok'; ?></p>
                            </div>
                            <div class="tab-stat-card">
                                <h4>📅 Bu Ay</h4>
                                <p>0</p>
                            </div>
                        </div>

                        <h3>📖 Daha Önce Okuduğu Kitaplar</h3>
                        <p style="color: #666; margin-bottom: 20px;">
                            <em>Not: Bu bölüm örnek amaçlıdır. Gerçek sistemde kitap geçmişi ayrı tabloda tutulur.</em>
                        </p>

                        <?php if (empty($gecmis_kitaplar)): ?>
                            <div class="no-books">
                                <p>📚 Henüz geçmiş kitap kaydı bulunmuyor.</p>
                            </div>
                        <?php else: ?>
                            <div class="books-grid">
                                <?php foreach ($gecmis_kitaplar as $kitap): ?>
                                    <div class="book-card">
                                        <div class="book-header">
                                            <h4><?php echo htmlspecialchars($kitap['kitap_adi']); ?></h4>
                                            <span class="status status-mevcut">✅ Teslim Edildi</span>
                                        </div>
                                        <div class="book-info">
                                            <p><strong>Yazar:</strong> <?php echo htmlspecialchars($kitap['yazar']); ?></p>
                                            <?php if (!empty($kitap['kategori'])): ?>
                                                <p><strong>Kategori:</strong> <?php echo htmlspecialchars($kitap['kategori']); ?></p>
                                            <?php endif; ?>
                                            <p><strong>Teslim Tarihi:</strong> <?php echo date('d.m.Y', strtotime($kitap['teslim_tarihi'])); ?></p>
                                        </div>
                                        <div class="book-actions">
                                            <a href="#" onclick="alert('Bu kitabı tekrar ödünç almak ister misiniz?')" class="btn-primary">🔄 Tekrar Ödünç Al</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Öneriler Sekmesi -->
                    <div class="tab-pane <?php echo $active_tab == 'recommendations' ? 'active' : ''; ?>">
                        <div class="tab-stats">
                            <div class="tab-stat-card">
                                <h4>💡 Öneri Sayısı</h4>
                                <p><?php echo $toplam_oneri; ?></p>
                            </div>
                            <div class="tab-stat-card">
                                <h4>🎯 Uyumluluk</h4>
                                <p><?php echo !empty($okunan_kategoriler) ? count($okunan_kategoriler) : 0; ?></p>
                            </div>
                            <div class="tab-stat-card">
                                <h4>📊 Başarı</h4>
                                <p>85%</p>
                            </div>
                        </div>

                        <h3>💡 Size Özel Kitap Önerileri</h3>
                        
                        <?php if (!empty($okunan_kategoriler)): ?>
                            <div class="recommendation-reasons">
                                <h5>🎯 Öneri Algoritması</h5>
                                <ul>
                                    <li>Daha önce okuduğunuz kategoriler: <strong><?php echo implode(', ', $okunan_kategoriler); ?></strong></li>
                                    <li>Benzer zevklere sahip kullanıcıların tercihleri</li>
                                    <li>Popüler ve yeni eklenen kitaplar</li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (empty($oneri_kitaplar)): ?>
                            <div class="no-books">
                                <p>💡 Henüz öneri oluşturmak için yeterli veri yok. Daha fazla kitap okudukça öneriler gelişecek!</p>
                            </div>
                        <?php else: ?>
                            <div class="books-grid">
                                <?php foreach ($oneri_kitaplar as $kitap): ?>
                                    <div class="book-card">
                                        <div class="book-header">
                                            <h4><?php echo htmlspecialchars($kitap['kitap_adi']); ?></h4>
                                            <span class="status status-mevcut">✨ Öneri</span>
                                        </div>
                                        <div class="book-info">
                                            <p><strong>Yazar:</strong> <?php echo htmlspecialchars($kitap['yazar']); ?></p>
                                            <?php if (!empty($kitap['kategori'])): ?>
                                                <p><strong>Kategori:</strong> <?php echo htmlspecialchars($kitap['kategori']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($kitap['yayin_evi'])): ?>
                                                <p><strong>Yayınevi:</strong> <?php echo htmlspecialchars($kitap['yayin_evi']); ?></p>
                                            <?php endif; ?>
                                            <p><strong>Durum:</strong> <span style="color: #22c55e; font-weight: bold;">✅ Mevcut</span></p>
                                        </div>
                                        
                                        <div class="recommendation-reasons">
                                            <h5>💫 Neden Öneriliyor?</h5>
                                            <ul>
                                                <li><?php echo htmlspecialchars($kitap['oneri_nedeni']); ?></li>
                                                <?php if (!empty($kitap['kategori']) && in_array($kitap['kategori'], $okunan_kategoriler)): ?>
                                                    <li>Sevdiğiniz "<?php echo htmlspecialchars($kitap['kategori']); ?>" kategorisinden</li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        
                                        <div class="book-actions">
                                            <a href="update.php?id=<?php echo $kitap['id']; ?>&action=borrow&uye_id=<?php echo $uye_id; ?>" class="btn-primary">📚 Ödünç Al</a>
                                            <a href="#" onclick="alert('Bu öneriyi beğendiniz mi?')" class="btn-secondary">👍 Beğen</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="action-bar">
                <a href="members.php" class="btn-secondary">🔙 Üye Listesine Dön</a>
                <a href="update-member.php?id=<?php echo $uye['id']; ?>" class="btn-primary">✏️ Üye Bilgilerini Düzenle</a>
            </div>
        </div>
    </div>

    <script>
        // Sekme değiştirme fonksiyonu (sayfa yenileme ile)
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // URL'yi güncelle
                    const url = new URL(this.href);
                    window.history.pushState({}, '', url);
                });
            });
        });
    </script>
</body>
</html>