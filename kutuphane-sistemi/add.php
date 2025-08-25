<?php
require_once 'db.php';

$message = '';
$messageType = '';

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
    
    // Basit doğrulama
    if (empty($kitap_adi) || empty($yazar)) {
        $message = 'Kitap adı ve yazar alanları zorunludur!';
        $messageType = 'error';
    } elseif ($durum == 'Ödünç' && (empty($odunc_tarihi) || empty($son_teslim_tarihi))) {
        $message = 'Ödünç kitaplar için ödünç tarihi ve son teslim tarihi zorunludur!';
        $messageType = 'error';
    } else {
        try {
            // Ödünç durumu değilse tarihleri null yap
            if ($durum != 'Ödünç') {
                $odunc_tarihi = null;
                $son_teslim_tarihi = null;
            }
            
            $sql = "INSERT INTO kitaplar (kitap_adi, yazar, yayin_evi, yayin_yili, isbn, kategori, durum, odunc_tarihi, son_teslim_tarihi) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$kitap_adi, $yazar, $yayin_evi, $yayin_yili, $isbn, $kategori, $durum, $odunc_tarihi, $son_teslim_tarihi]);
            
            $message = 'Kitap başarıyla eklendi!';
            $messageType = 'success';
            
            // Formu temizle
            $_POST = array();
        } catch(PDOException $e) {
            $message = 'Kitap eklenirken hata oluştu: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Mevcut kategorileri al
$kategoriStmt = $conn->query("SELECT DISTINCT kategori FROM kitaplar WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
$kategoriler = $kategoriStmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitap Ekle - Kütüphane Yönetim Sistemi</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Kütüphane Yönetim Sistemi</h1>
            <nav>
                <a href="index.php">Ana Sayfa</a>
                <a href="add.php" class="active">Kitap Ekle</a>
                <a href="status-update.php">Durum Güncelle</a>
            </nav>
        </header>

        <div class="form-container">
            <h2>📖 Yeni Kitap Ekle</h2>
            
            <?php if ($message): ?>
                <div class="message message-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="book-form">
                <div class="form-group">
                    <label for="kitap_adi">Kitap Adı *</label>
                    <input type="text" id="kitap_adi" name="kitap_adi" value="<?php echo isset($_POST['kitap_adi']) ? htmlspecialchars($_POST['kitap_adi']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="yazar">Yazar *</label>
                    <input type="text" id="yazar" name="yazar" value="<?php echo isset($_POST['yazar']) ? htmlspecialchars($_POST['yazar']) : ''; ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="yayin_evi">Yayınevi</label>
                        <input type="text" id="yayin_evi" name="yayin_evi" value="<?php echo isset($_POST['yayin_evi']) ? htmlspecialchars($_POST['yayin_evi']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="yayin_yili">Yayın Yılı</label>
                        <input type="number" id="yayin_yili" name="yayin_yili" min="1800" max="<?php echo date('Y') + 1; ?>" value="<?php echo isset($_POST['yayin_yili']) ? htmlspecialchars($_POST['yayin_yili']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn" value="<?php echo isset($_POST['isbn']) ? htmlspecialchars($_POST['isbn']) : ''; ?>" placeholder="13 haneli ISBN">
                    </div>

                    <div class="form-group">
                        <label for="kategori">Kategori</label>
                        <select id="kategori" name="kategori">
                            <option value="">Kategori Seçin</option>
                            <option value="Roman" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'Roman') ? 'selected' : ''; ?>>Roman</option>
                            <option value="Bilim Kurgu" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'Bilim Kurgu') ? 'selected' : ''; ?>>Bilim Kurgu</option>
                            <option value="Tarih" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'Tarih') ? 'selected' : ''; ?>>Tarih</option>
                            <option value="Bilim" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'Bilim') ? 'selected' : ''; ?>>Bilim</option>
                            <option value="Felsefe" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'Felsefe') ? 'selected' : ''; ?>>Felsefe</option>
                            <option value="Psikoloji" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'Psikoloji') ? 'selected' : ''; ?>>Psikoloji</option>
                            <option value="Edebiyat" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'Edebiyat') ? 'selected' : ''; ?>>Edebiyat</option>
                            <option value="Çocuk" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'Çocuk') ? 'selected' : ''; ?>>Çocuk</option>
                            <option value="Diğer" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'Diğer') ? 'selected' : ''; ?>>Diğer</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="durum">Durum</label>
                    <select id="durum" name="durum" onchange="toggleDateFields()">
                        <option value="Mevcut" <?php echo (isset($_POST['durum']) && $_POST['durum'] == 'Mevcut') ? 'selected' : ''; ?>>Mevcut</option>
                        <option value="Ödünç" <?php echo (isset($_POST['durum']) && $_POST['durum'] == 'Ödünç') ? 'selected' : ''; ?>>Ödünç</option>
                        <option value="Kayıp" <?php echo (isset($_POST['durum']) && $_POST['durum'] == 'Kayıp') ? 'selected' : ''; ?>>Kayıp</option>
                    </select>
                </div>

                <!-- Ödünç tarihleri (sadece ödünç durumunda görünür) -->
                <div id="date-fields" class="date-fields" style="<?php echo (isset($_POST['durum']) && $_POST['durum'] == 'Ödünç') ? 'display: block;' : 'display: none;'; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="odunc_tarihi">Ödünç Tarihi *</label>
                            <input type="date" id="odunc_tarihi" name="odunc_tarihi" value="<?php echo isset($_POST['odunc_tarihi']) ? htmlspecialchars($_POST['odunc_tarihi']) : date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="son_teslim_tarihi">Son Teslim Tarihi *</label>
                            <input type="date" id="son_teslim_tarihi" name="son_teslim_tarihi" value="<?php echo isset($_POST['son_teslim_tarihi']) ? htmlspecialchars($_POST['son_teslim_tarihi']) : date('Y-m-d', strtotime('+14 days')); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">📚 Kitap Ekle</button>
                    <a href="index.php" class="btn-secondary">🔙 Geri Dön</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDateFields() {
            const durumSelect = document.getElementById('durum');
            const dateFields = document.getElementById('date-fields');
            const oduncTarihi = document.getElementById('odunc_tarihi');
            const sonTeslimTarihi = document.getElementById('son_teslim_tarihi');
            
            if (durumSelect.value === 'Ödünç') {
                dateFields.style.display = 'block';
                oduncTarihi.required = true;
                sonTeslimTarihi.required = true;
                
                // Varsayılan tarih değerlerini ayarla
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
            }
        }

        // Sayfa yüklendiğinde durumu kontrol et
        document.addEventListener('DOMContentLoaded', function() {
            toggleDateFields();
        });
    </script>
</body>
</html>
