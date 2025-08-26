<?php
require_once 'db.php';

$message = '';
$messageType = '';

// Arama parametresi ve aktif sekme
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'borrowed';

// Ödünç kitapları getir - arama filtresi ile
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

// Mevcut kitapları getir - arama filtresi ile
$mevcut_sql = "SELECT * FROM kitaplar WHERE durum = 'Mevcut'";
$mevcut_params = [];

if (!empty($search)) {
    $mevcut_sql .= " AND (kitap_adi LIKE ? OR yazar LIKE ?)";
    $searchParam = "%$search%";
    $mevcut_params[] = $searchParam;
    $mevcut_params[] = $searchParam;
}

$mevcut_sql .= " ORDER BY kitap_adi ASC";
$mevcut_stmt = $conn->prepare($mevcut_sql);
$mevcut_stmt->execute($mevcut_params);
$mevcut_kitaplar = $mevcut_stmt->fetchAll(PDO::FETCH_ASSOC);

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
            
            // Listeleri yenile - arama parametresi ile
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
            
            // Mevcut kitapları da yenile
            $mevcut_refresh_sql = "SELECT * FROM kitaplar WHERE durum = 'Mevcut'";
            $mevcut_refresh_params = [];
            
            if (!empty($search)) {
                $mevcut_refresh_sql .= " AND (kitap_adi LIKE ? OR yazar LIKE ?)";
                $searchParam = "%$search%";
                $mevcut_refresh_params[] = $searchParam;
                $mevcut_refresh_params[] = $searchParam;
            }
            
            $mevcut_refresh_sql .= " ORDER BY kitap_adi ASC";
            $mevcut_stmt = $conn->prepare($mevcut_refresh_sql);
            $mevcut_stmt->execute($mevcut_refresh_params);
            $mevcut_kitaplar = $mevcut_stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                <input type="text" name="search" placeholder="Kitap adı, yazar veya üye adı ara..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">🔍 Ara</button>
                <?php if (!empty($search)): ?>
                    <a href="status-update.php?tab=<?php echo $active_tab; ?>" class="btn-secondary">🔄 Temizle</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="form-container">
            <h2>📚 Kitap Yönetimi - Durum Güncelleme</h2>
            
            <!-- Sekmeler -->
            <div class="tabs-container">
                <div class="tabs-nav">
                    <a href="?tab=borrowed<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="tab-button <?php echo $active_tab == 'borrowed' ? 'active' : ''; ?>">
                        📤 Ödünç Kitaplar (<?php echo count($odunc_kitaplar); ?>)
                    </a>
                    <a href="?tab=available<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="tab-button <?php echo $active_tab == 'available' ? 'active' : ''; ?>">
                        📚 Mevcut Kitaplar (<?php echo count($mevcut_kitaplar); ?>)
                    </a>
                </div>

                <div class="tab-content">
            
            <?php if ($message): ?>
                <div class="message message-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Ödünç Kitaplar Sekmesi -->
            <div class="tab-pane <?php echo $active_tab == 'borrowed' ? 'active' : ''; ?>">
                <div class="tab-stats four-cards">
                    <div class="tab-stat-card stat-total">
                        <span class="stat-icon">📚</span>
                        <h4>Toplam Ödünç</h4>
                        <p class="stat-number"><?php echo count($odunc_kitaplar); ?></p>
                        <div class="stat-progress">
                            <div class="stat-progress-bar progress-blue" style="width: <?php echo count($odunc_kitaplar) > 0 ? '100' : '0'; ?>%;"></div>
                        </div>
                    </div>
                    <div class="tab-stat-card stat-danger">
                        <span class="stat-icon">⚠️</span>
                        <h4>Geciken Kitaplar</h4>
                        <p class="stat-number"><?php 
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
                        <div class="stat-progress">
                            <div class="stat-progress-bar progress-red" style="width: <?php echo count($odunc_kitaplar) > 0 ? ($geciken / count($odunc_kitaplar) * 100) : 0; ?>%;"></div>
                        </div>
                        <?php if ($geciken > 0): ?>
                            <small style="color: #c53030; font-weight: 600;">Acil İşlem Gerekli!</small>
                        <?php else: ?>
                            <small style="color: #38a169; font-weight: 600;">Gecikme Yok ✓</small>
                        <?php endif; ?>
                    </div>
                    <div class="tab-stat-card stat-warning">
                        <span class="stat-icon">⏰</span>
                        <h4>Yakında Teslim</h4>
                        <p class="stat-number"><?php 
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
                        <div class="stat-progress">
                            <div class="stat-progress-bar progress-orange" style="width: <?php echo count($odunc_kitaplar) > 0 ? ($yakin / count($odunc_kitaplar) * 100) : 0; ?>%;"></div>
                        </div>
                        <?php if ($yakin > 0): ?>
                            <small style="color: #c05621; font-weight: 600;">3 Gün İçinde Teslim</small>
                        <?php else: ?>
                            <small style="color: #38a169; font-weight: 600;">Teslim Zamanı İyi ✓</small>
                        <?php endif; ?>
                    </div>
                    <div class="tab-stat-card stat-success">
                        <span class="stat-icon">✅</span>
                        <h4>Zamanında</h4>
                        <p class="stat-number"><?php 
                        $zamaninda = 0;
                        foreach($odunc_kitaplar as $kitap) {
                            if (!empty($kitap['son_teslim_tarihi'])) {
                                $bugun = new DateTime();
                                $sonTeslim = new DateTime($kitap['son_teslim_tarihi']);
                                if ($bugun <= $sonTeslim) {
                                    $fark = $bugun->diff($sonTeslim);
                                    if ($fark->days > 3) $zamaninda++;
                                }
                            }
                        }
                        echo $zamaninda;
                        ?></p>
                        <div class="stat-progress">
                            <div class="stat-progress-bar progress-green" style="width: <?php echo count($odunc_kitaplar) > 0 ? ($zamaninda / count($odunc_kitaplar) * 100) : 0; ?>%;"></div>
                        </div>
                        <small style="color: #2f855a; font-weight: 600;">Sorun Yok 👍</small>
                    </div>
                </div>

                <div class="status-update-info">
                    <div class="info-box">
                        <h3>ℹ️ Bilgi</h3>
                        <p>Bu sekmede sadece <strong>ödünç verilen kitaplar</strong> listelenir.</p>
                        <p>Kitap geri alındığında <strong>"Mevcut"</strong>, kaybolduğunda <strong>"Kayıp"</strong> olarak işaretleyin.</p>
                        <p>🔍 <strong>Arama yapabilirsiniz:</strong> Kitap adı, yazar adı veya üye adı ile arama yapın.</p>
                    </div>
                </div>

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

            <!-- Mevcut Kitaplar Sekmesi -->
            <div class="tab-pane <?php echo $active_tab == 'available' ? 'active' : ''; ?>">
                <div class="tab-stats four-cards">
                    <div class="tab-stat-card stat-success">
                        <span class="stat-icon">📚</span>
                        <h4>Toplam Mevcut</h4>
                        <p class="stat-number"><?php echo count($mevcut_kitaplar); ?></p>
                        <div class="stat-progress">
                            <div class="stat-progress-bar progress-green" style="width: <?php echo count($mevcut_kitaplar) > 0 ? '100' : '0'; ?>%;"></div>
                        </div>
                        <small style="color: #2f855a; font-weight: 600;">Ödünç Verilebilir</small>
                    </div>
                    <div class="tab-stat-card stat-total">
                        <span class="stat-icon">📂</span>
                        <h4>Farklı Kategori</h4>
                        <p class="stat-number"><?php 
                        $kategoriler = array_unique(array_filter(array_column($mevcut_kitaplar, 'kategori')));
                        echo count($kategoriler);
                        ?></p>
                        <div class="stat-progress">
                            <div class="stat-progress-bar progress-blue" style="width: <?php echo count($kategoriler) > 0 ? min(100, count($kategoriler) * 20) : 0; ?>%;"></div>
                        </div>
                        <small style="color: #2b6cb0; font-weight: 600;">Çeşitlilik</small>
                    </div>
                    <div class="tab-stat-card stat-warning">
                        <span class="stat-icon">✨</span>
                        <h4>Yeni Eklenen</h4>
                        <p class="stat-number"><?php 
                        $yeni = 0;
                        $bugun = new DateTime();
                        foreach($mevcut_kitaplar as $kitap) {
                            $ekleme = new DateTime($kitap['eklenme_tarihi']);
                            $fark = $bugun->diff($ekleme);
                            if ($fark->days <= 7) $yeni++;
                        }
                        echo $yeni;
                        ?></p>
                        <div class="stat-progress">
                            <div class="stat-progress-bar progress-orange" style="width: <?php echo count($mevcut_kitaplar) > 0 ? ($yeni / count($mevcut_kitaplar) * 100) : 0; ?>%;"></div>
                        </div>
                        <small style="color: #c05621; font-weight: 600;">Son 7 Gün</small>
                    </div>
                    <div class="tab-stat-card stat-total">
                        <span class="stat-icon">📊</span>
                        <h4>Ortalama Yaş</h4>
                        <p class="stat-number"><?php 
                        $toplam_yas = 0;
                        $sayac = 0;
                        $bugun = new DateTime();
                        foreach($mevcut_kitaplar as $kitap) {
                            if (!empty($kitap['yayin_yili']) && $kitap['yayin_yili'] > 1900) {
                                $kitap_yasi = date('Y') - $kitap['yayin_yili'];
                                $toplam_yas += $kitap_yasi;
                                $sayac++;
                            }
                        }
                        echo $sayac > 0 ? round($toplam_yas / $sayac) : 0;
                        ?></p>
                        <div class="stat-progress">
                            <div class="stat-progress-bar progress-blue" style="width: 75%;"></div>
                        </div>
                        <small style="color: #2b6cb0; font-weight: 600;">Yıl</small>
                    </div>
                </div>

                <div class="status-update-info">
                    <div class="info-box">
                        <h3>ℹ️ Bilgi</h3>
                        <p>Bu sekmede <strong>ödünç verilebilir mevcut kitaplar</strong> listelenir.</p>
                        <p>Kitapların detay bilgilerini görüntüleyebilir ve düzenleyebilirsiniz.</p>
                        <p>🔍 <strong>Arama yapabilirsiniz:</strong> Kitap adı veya yazar adı ile arama yapın.</p>
                    </div>
                </div>

                <?php if (empty($mevcut_kitaplar)): ?>
                    <div class="no-books">
                        <?php if (!empty($search)): ?>
                            <p>🔍 "<?php echo htmlspecialchars($search); ?>" araması için hiç sonuç bulunamadı.</p>
                            <a href="status-update.php?tab=available" class="btn-secondary">🔄 Tüm Kitapları Göster</a>
                        <?php else: ?>
                            <p>📚 Şu anda mevcut kitap bulunmuyor.</p>
                            <a href="add.php" class="btn-primary">➕ Yeni Kitap Ekle</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="books-grid">
                        <?php foreach ($mevcut_kitaplar as $kitap): ?>
                            <div class="book-card">
                                <div class="book-header">
                                    <h3><?php echo htmlspecialchars($kitap['kitap_adi']); ?></h3>
                                    <span class="status status-mevcut">✅ Mevcut</span>
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
                                    <p><strong>Eklenme:</strong> <?php echo date('d.m.Y', strtotime($kitap['eklenme_tarihi'])); ?></p>
                                </div>
                                <div class="book-actions">
                                    <a href="update.php?id=<?php echo $kitap['id']; ?>" class="btn-edit">✏️ Düzenle</a>
                                    <a href="#" onclick="alert('Bu kitabı ödünç vermek için üye seçimi gerekli!')" class="btn-primary">📤 Ödünç Ver</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        // Sayfa yüklendiğinde animasyonları başlat
        document.addEventListener('DOMContentLoaded', function() {
            // Stat kartları animasyonu
            const statCards = document.querySelectorAll('.tab-stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });

            // Progress bar animasyonu
            setTimeout(() => {
                const progressBars = document.querySelectorAll('.stat-progress-bar');
                progressBars.forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 300);
                });
            }, 800);

            // Sayıların animasyonlu sayımı
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(numberEl => {
                const finalNumber = parseInt(numberEl.textContent);
                let currentNumber = 0;
                const increment = Math.ceil(finalNumber / 20);
                
                const counter = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        currentNumber = finalNumber;
                        clearInterval(counter);
                        numberEl.classList.remove('counting');
                    }
                    numberEl.textContent = currentNumber;
                    numberEl.classList.add('counting');
                }, 50);
            });

            // Hover efektleri
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Sekme değiştirme smooth animasyonu
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Mevcut aktif sekmeyi fade out yap
                    const activePane = document.querySelector('.tab-pane.active');
                    if (activePane) {
                        activePane.style.opacity = '0.5';
                        activePane.style.transform = 'translateX(-10px)';
                    }
                });
            });

            // Kitap kartları için hover efektleri
            const bookCards = document.querySelectorAll('.book-card');
            bookCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                    this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
                });
            });

            // Loading animasyonu için
            const loadingCards = document.querySelectorAll('.tab-pane.active .book-card');
            loadingCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.4s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 1200 + (index * 100));
            });
        });

        // Dinamik sayaç güncellemesi
        function updateStats() {
            const geciken = document.querySelector('.stat-danger .stat-number');
            if (geciken && parseInt(geciken.textContent) > 0) {
                // Geciken kitaplar varsa kartı hafifçe titret
                geciken.closest('.tab-stat-card').style.animation = 'pulse 2s infinite';
            }
        }

        // CSS animasyonunu ekle
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            .book-card {
                transition: all 0.3s ease;
            }
            
            .tab-stat-card {
                cursor: pointer;
            }
        `;
        document.head.appendChild(style);

        // Sayfa yüklendiğinde stats'ı güncelle
        setTimeout(updateStats, 2000);
    </script>
</body>
</html>
