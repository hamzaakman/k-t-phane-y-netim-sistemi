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

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kitap_adi = trim($_POST['kitap_adi']);
    $yazar = trim($_POST['yazar']);
    $yayin_evi = trim($_POST['yayin_evi']);
    $yayin_yili = trim($_POST['yayin_yili']);
    $isbn = trim($_POST['isbn']);
    $kategori = trim($_POST['kategori']);
    $durum = $_POST['durum'];
    $odunc_tarihi = $_POST['odunc_tarihi'] ?? null;
    $son_teslim_tarihi = $_POST['son_teslim_tarihi'] ?? null;
    $odunc_verilen_uye_id = $_POST['odunc_verilen_uye_id'] ?? null;
    
    // Basit doğrulama
    if (empty($kitap_adi) || empty($yazar)) {
        $message = 'Kitap adı ve yazar alanları zorunludur!';
        $messageType = 'error';
    } elseif ($durum == 'Ödünç' && (empty($odunc_tarihi) || empty($son_teslim_tarihi) || empty($odunc_verilen_uye_id))) {
        $message = 'Ödünç kitaplar için ödünç tarihi, son teslim tarihi ve üye seçimi zorunludur!';
        $messageType = 'error';
    } else {
        try {
            // Ödünç durumu değilse tarihleri ve üye ID'sini null yap
            if ($durum != 'Ödünç') {
                $odunc_tarihi = null;
                $son_teslim_tarihi = null;
                $odunc_verilen_uye_id = null;
            }
            
            $sql = "UPDATE kitaplar SET kitap_adi = ?, yazar = ?, yayin_evi = ?, yayin_yili = ?, isbn = ?, kategori = ?, durum = ?, odunc_tarihi = ?, son_teslim_tarihi = ?, odunc_verilen_uye_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$kitap_adi, $yazar, $yayin_evi, $yayin_yili, $isbn, $kategori, $durum, $odunc_tarihi, $son_teslim_tarihi, $odunc_verilen_uye_id, $id]);
            
            $message = 'Kitap başarıyla güncellendi!';
            $messageType = 'success';
            
            // Güncel bilgileri al
            $stmt = $conn->prepare("SELECT * FROM kitaplar WHERE id = ?");
            $stmt->execute([$id]);
            $kitap = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            $message = 'Kitap güncellenirken hata oluştu: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Aktif üyeleri al
$uyelerStmt = $conn->query("SELECT id, ad_soyad FROM uyeler WHERE uyelik_durumu = 'Aktif' ORDER BY ad_soyad");
$uyeler = $uyelerStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Düzenle - Kütüphane Yönetim Sistemi</title>
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
            <h2>✏️ Kitap Düzenle</h2>
            
            <?php if ($message): ?>
                <div class="message message-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($kitap): ?>
                <form method="POST" action="" class="book-form">
                    <div class="form-group">
                        <label for="kitap_adi">Kitap Adı *</label>
                        <input type="text" id="kitap_adi" name="kitap_adi" value="<?php echo htmlspecialchars($kitap['kitap_adi']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="yazar">Yazar *</label>
                        <input type="text" id="yazar" name="yazar" value="<?php echo htmlspecialchars($kitap['yazar']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="yayin_evi">Yayınevi</label>
                            <input type="text" id="yayin_evi" name="yayin_evi" value="<?php echo htmlspecialchars($kitap['yayin_evi']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="yayin_yili">Yayın Yılı</label>
                            <input type="number" id="yayin_yili" name="yayin_yili" min="1800" max="<?php echo date('Y') + 1; ?>" value="<?php echo htmlspecialchars($kitap['yayin_yili']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="isbn">ISBN</label>
                            <input type="text" id="isbn" name="isbn" value="<?php echo htmlspecialchars($kitap['isbn']); ?>" placeholder="13 haneli ISBN">
                        </div>

                        <div class="form-group">
                            <label for="kategori">Kategori</label>
                            <select id="kategori" name="kategori">
                                <option value="">Kategori Seçin</option>
                                <option value="Roman" <?php echo $kitap['kategori'] == 'Roman' ? 'selected' : ''; ?>>Roman</option>
                                <option value="Bilim Kurgu" <?php echo $kitap['kategori'] == 'Bilim Kurgu' ? 'selected' : ''; ?>>Bilim Kurgu</option>
                                <option value="Tarih" <?php echo $kitap['kategori'] == 'Tarih' ? 'selected' : ''; ?>>Tarih</option>
                                <option value="Bilim" <?php echo $kitap['kategori'] == 'Bilim' ? 'selected' : ''; ?>>Bilim</option>
                                <option value="Felsefe" <?php echo $kitap['kategori'] == 'Felsefe' ? 'selected' : ''; ?>>Felsefe</option>
                                <option value="Psikoloji" <?php echo $kitap['kategori'] == 'Psikoloji' ? 'selected' : ''; ?>>Psikoloji</option>
                                <option value="Edebiyat" <?php echo $kitap['kategori'] == 'Edebiyat' ? 'selected' : ''; ?>>Edebiyat</option>
                                <option value="Çocuk" <?php echo $kitap['kategori'] == 'Çocuk' ? 'selected' : ''; ?>>Çocuk</option>
                                <option value="Diğer" <?php echo $kitap['kategori'] == 'Diğer' ? 'selected' : ''; ?>>Diğer</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="durum">Durum</label>
                        <select id="durum" name="durum" onchange="toggleDateFields()">
                            <option value="Mevcut" <?php echo $kitap['durum'] == 'Mevcut' ? 'selected' : ''; ?>>Mevcut</option>
                            <option value="Ödünç" <?php echo $kitap['durum'] == 'Ödünç' ? 'selected' : ''; ?>>Ödünç</option>
                            <option value="Kayıp" <?php echo $kitap['durum'] == 'Kayıp' ? 'selected' : ''; ?>>Kayıp</option>
                        </select>
                    </div>

                    <!-- Ödünç tarihleri (sadece ödünç durumunda görünür) -->
                    <div id="date-fields" class="date-fields" style="<?php echo ($kitap['durum'] == 'Ödünç') ? 'display: block;' : 'display: none;'; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="odunc_tarihi">Ödünç Tarihi *</label>
                                <input type="date" id="odunc_tarihi" name="odunc_tarihi" value="<?php echo !empty($kitap['odunc_tarihi']) ? $kitap['odunc_tarihi'] : date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="son_teslim_tarihi">Son Teslim Tarihi *</label>
                                <input type="date" id="son_teslim_tarihi" name="son_teslim_tarihi" value="<?php echo !empty($kitap['son_teslim_tarihi']) ? $kitap['son_teslim_tarihi'] : date('Y-m-d', strtotime('+14 days')); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="odunc_verilen_uye_id">Ödünç Verilen Üye *</label>
                            <select name="odunc_verilen_uye_id" id="odunc_verilen_uye_id" required>
                                <option value="">Üye Seçin</option>
                                <?php foreach ($uyeler as $uye): ?>
                                    <option value="<?php echo $uye['id']; ?>" 
                                        <?php echo (!empty($kitap['odunc_verilen_uye_id']) && $kitap['odunc_verilen_uye_id'] == $uye['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($uye['ad_soyad']); ?> (<?php echo str_pad($uye['id'], 6, '0', STR_PAD_LEFT); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="date-info">
                            <p>💡 <strong>İpucu:</strong> Kitabı ödünç verirken bu tarihleri doldurun ve hangi üyeye verdiğinizi seçin.</p>
                        </div>
                    </div>

                    <div class="form-info">
                        <p><strong>Kitap ID:</strong> <?php echo $kitap['id']; ?></p>
                        <p><strong>Eklenme Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($kitap['eklenme_tarihi'])); ?></p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">💾 Değişiklikleri Kaydet</button>
                        <a href="index.php" class="btn-secondary">🔙 Geri Dön</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="error-message">
                    <p>Kitap bulunamadı!</p>
                    <a href="index.php" class="btn-secondary">🔙 Ana Sayfaya Dön</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleDateFields() {
            const durumSelect = document.getElementById('durum');
            const dateFields = document.getElementById('date-fields');
            const oduncTarihi = document.getElementById('odunc_tarihi');
            const sonTeslimTarihi = document.getElementById('son_teslim_tarihi');
            const uyeSelect = document.getElementById('odunc_verilen_uye_id');
            
            if (durumSelect.value === 'Ödünç') {
                dateFields.style.display = 'block';
                oduncTarihi.required = true;
                sonTeslimTarihi.required = true;
                uyeSelect.required = true;
                
                // Eğer tarihler boşsa varsayılan değerleri ayarla
                if (!oduncTarihi.value) {
                    oduncTarihi.value = new Date().toISOString().split('T')[0];
                }
                if (!sonTeslimTarihi.value) {
                    const futureDate = new Date();
                    futureDate.setDate(futureDate.getDate() + 14);
                    sonTeslimTarihi.value = futureDate.toISOString().split('T')[0];
                }
            } else {
                dateFields.style.display = 'none';
                oduncTarihi.required = false;
                sonTeslimTarihi.required = false;
                uyeSelect.required = false;
                uyeSelect.value = '';
            }
        }

        // Sayfa yüklendiğinde durumu kontrol et
        document.addEventListener('DOMContentLoaded', function() {
            toggleDateFields();
        });
    </script>
</body>
</html>
