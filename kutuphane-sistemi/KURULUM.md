# 🚀 Kütüphane Yönetim Sistemi - Kurulum Rehberi

Bu rehber, kütüphane yönetim sistemini kendi bilgisayarınıza nasıl kuracağınızı adım adım açıklar.

## 📋 Gereksinimler

### Minimum Sistem Gereksinimleri
- **İşletim Sistemi**: Windows 7/8/10/11, macOS, Linux
- **RAM**: En az 2GB
- **Disk Alanı**: En az 500MB boş alan
- **İnternet**: İlk kurulum için gerekli

## 🛠️ Kurulum Adımları

### 1. Web Sunucusu Paketi Kurulumu

#### Windows için (Önerilen):
1. **XAMPP İndirme**: https://www.apachefriends.org/download.html
2. **Kurulum**: İndirilen dosyayı çalıştırın
3. **Bileşen Seçimi**: Apache, MySQL, PHP seçili olsun
4. **Kurulum Tamamlanması**: Kurulum bitene kadar bekleyin

#### macOS için:
1. **MAMP İndirme**: https://www.mamp.info/en/downloads/
2. **Kurulum**: DMG dosyasını açıp uygulamayı Applications klasörüne sürükleyin

#### Linux için:
```bash
sudo apt update
sudo apt install apache2 mysql-server php php-mysql
```

### 2. Veritabanı Yönetim Aracı (Önerilen: HeidiSQL)

#### HeidiSQL Kurulumu:
1. **İndirme**: https://www.heidisql.com/download.php
2. **Kurulum**: İndirilen dosyayı çalıştırın
3. **Avantajları**: 
   - Görsel arayüz
   - Türkçe karakter desteği
   - Kolay veritabanı yönetimi
   - MySQL uyumlu

#### Alternatif: phpMyAdmin
- XAMPP ile birlikte gelir
- Web tabanlı arayüz
- Tarayıcıda çalışır

### 3. Proje Dosyalarını Kopyalama

1. **Klasör Açma**: Web sunucusu kurulum klasörünü açın
   - XAMPP: `C:\xampp\htdocs\`
   - WAMP: `C:\wamp\www\`
   - MAMP: `/Applications/MAMP/htdocs/`

2. **Proje Kopyalama**: `kutuphane-sistemi` klasörünü buraya kopyalayın

3. **Sonuç**: `htdocs/kutuphane-sistemi/` şeklinde olmalı

### 4. Veritabanı Oluşturma

#### HeidiSQL ile (Önerilen):
1. **HeidiSQL Açma**: HeidiSQL uygulamasını açın
2. **Yeni Bağlantı**:
   - **Hostname**: `localhost`
   - **User**: `root`
   - **Password**: (XAMPP'te boş, WAMP'te "root")
   - **Port**: `3306`
   - **Open** tıklayın

3. **Veritabanı Oluşturma**:
   - Sol panelde **sağ tık** → "Create new" → "Database"
   - **Database name**: `kutuphane`
   - **Collation**: `utf8mb4_unicode_ci`
   - **OK** tıklayın

#### phpMyAdmin ile:
1. **phpMyAdmin Açma**: Tarayıcıda `http://localhost/phpmyadmin` yazın
2. **Yeni Veritabanı**: Sol menüde "New" veya "Yeni" tıklayın
3. **Veritabanı Adı**: `kutuphane` yazın
4. **Karakter Seti**: `utf8mb4_unicode_ci` seçin
5. **Oluştur**: "Create" veya "Oluştur" tıklayın

#### Komut Satırı ile:
```sql
CREATE DATABASE kutuphane CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Veritabanı Bağlantı Ayarları

1. **db.php Dosyası Açma**: `kutuphane-sistemi/db.php` dosyasını not defteri ile açın

2. **Bilgileri Güncelleme**:
```php
$servername = "localhost";        // Genellikle değişmez
$username = "root";              // Varsayılan kullanıcı adı
$password = "";                  // XAMPP'te boş, WAMP'te "root"
$dbname = "kutuphane";           // Oluşturduğunuz veritabanı adı
```

3. **Kaydetme**: Dosyayı kaydedin

### 6. Web Sunucusunu Başlatma

1. **XAMPP Control Panel**: XAMPP'ı açın
2. **Apache Start**: Apache yanında "Start" tıklayın
3. **MySQL Start**: MySQL yanında "Start" tıklayın
4. **Durum Kontrolü**: Her ikisi de yeşil olmalı

### 7. Sistemi Test Etme

1. **Tarayıcı Açma**: Web tarayıcınızı açın
2. **Adres Yazma**: `http://localhost/kutuphane-sistemi/` yazın
3. **Ana Sayfa**: Kütüphane yönetim sistemi ana sayfası açılmalı

## ✅ Kurulum Tamamlandı!

Artık sistemi kullanabilirsiniz:
- **İlk Kitap Ekleme**: "Kitap Ekle" butonuna tıklayın
- **Kitap Yönetimi**: Kitapları ekleyin, düzenleyin, silin
- **Arama**: Kitapları arayın ve filtreleyin

## 🔧 Sorun Giderme

### "Veritabanı bağlantı hatası" alıyorsanız:
1. MySQL servisinin çalıştığından emin olun
2. Veritabanı adının doğru olduğunu kontrol edin
3. Kullanıcı adı ve şifreyi kontrol edin
4. HeidiSQL'de bağlantıyı test edin

### "Sayfa bulunamadı" hatası alıyorsanız:
1. Apache servisinin çalıştığından emin olun
2. Dosyaların doğru klasörde olduğunu kontrol edin
3. Klasör adının doğru olduğunu kontrol edin

### Türkçe karakterler düzgün görünmüyorsa:
1. Veritabanı karakter setinin `utf8mb4` olduğundan emin olun
2. `db.php` dosyasında `SET NAMES 'utf8'` satırının olduğunu kontrol edin
3. HeidiSQL'de veritabanı özelliklerini kontrol edin

### HeidiSQL Bağlantı Sorunları:
1. **Port kontrolü**: 3306 portunun açık olduğundan emin olun
2. **Firewall**: Windows Firewall'da MySQL'e izin verin
3. **Servis durumu**: XAMPP Control Panel'de MySQL'in çalıştığını kontrol edin

## 📊 HeidiSQL ile Veritabanı Yönetimi

### Tablo Görüntüleme:
- Sol panelde `kutuphane` veritabanını genişletin
- `kitaplar` tablosunu göreceksiniz
- Tabloya çift tıklayarak verileri görüntüleyin

### Veri Ekleme:
- Tabloya sağ tık → "Insert new row"
- Verileri girin ve kaydedin

### SQL Sorguları:
- **Query** sekmesinde SQL yazabilirsiniz
- Örnek: `SELECT * FROM kitaplar WHERE durum = 'Mevcut';`

## 📞 Yardım

Sorun yaşarsanız:
1. Hata mesajlarını not alın
2. Hangi adımda takıldığınızı belirtin
3. Ekran görüntüsü ekleyin
4. HeidiSQL hata mesajlarını paylaşın

## 🎯 Sonraki Adımlar

Sistem çalıştıktan sonra:
- **Güvenlik**: Varsayılan şifreleri değiştirin
- **Yedekleme**: Veritabanını düzenli yedekleyin
- **Güncelleme**: Sistemi güncel tutun
- **Veritabanı Yönetimi**: HeidiSQL ile verileri düzenli kontrol edin

## 💡 İpuçları

### HeidiSQL Kullanımı:
- **F5**: Verileri yenile
- **Ctrl+Shift+E**: SQL sorgusu çalıştır
- **Ctrl+S**: Veritabanı yedekle
- **F9**: Seçili satırı düzenle

### Performans:
- Büyük veri setlerinde sayfalama kullanın
- Düzenli veritabanı yedeklemesi yapın
- Gereksiz verileri temizleyin

---

**Not**: Bu sistem eğitim amaçlıdır. Üretim ortamında kullanmadan önce güvenlik testleri yapın.
