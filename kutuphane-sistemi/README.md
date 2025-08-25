# 📚 Kütüphane Yönetim Sistemi

Modern ve kullanıcı dostu bir kütüphane yönetim sistemi. PHP ve MySQL kullanılarak geliştirilmiştir.

## ✨ Özellikler

- 📖 Kitap ekleme, düzenleme ve silme
- 🔍 Gelişmiş arama ve filtreleme
- 📊 Kitap durumu takibi (Mevcut, Ödünç, Kayıp)
- 📱 Responsive tasarım
- 🎨 Modern ve güzel arayüz
- 🔒 Güvenli veritabanı işlemleri

## 🚀 Kurulum

### Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Web sunucusu (Apache/Nginx)

### Adımlar

1. **Dosyaları web sunucunuza yükleyin**
   ```
   /var/www/html/kutuphane-sistemi/
   ```

2. **Veritabanı oluşturun**
   ```sql
   CREATE DATABASE kutuphane CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Veritabanı bağlantı ayarlarını düzenleyin**
   `db.php` dosyasında veritabanı bilgilerinizi güncelleyin:
   ```php
   $servername = "localhost";
   $username = "your_username";
   $password = "your_password";
   $dbname = "kutuphane";
   ```

4. **Web tarayıcınızda açın**
   ```
   http://localhost/kutuphane-sistemi/
   ```

## 📁 Dosya Yapısı

```
kutuphane-sistemi/
├── index.php          # Ana sayfa - kitap listesi
├── add.php            # Kitap ekleme formu
├── update.php         # Kitap düzenleme formu
├── delete.php         # Kitap silme onay sayfası
├── db.php             # Veritabanı bağlantısı
├── style.css          # CSS stilleri
└── README.md          # Bu dosya
```

## 🎯 Kullanım

### Ana Sayfa (index.php)
- Tüm kitapları görüntüleme
- Arama ve filtreleme
- Kitap durumu istatistikleri
- Kitap düzenleme ve silme işlemleri

### Kitap Ekleme (add.php)
- Yeni kitap bilgilerini girme
- Zorunlu alanlar: Kitap adı, Yazar
- Opsiyonel alanlar: Yayınevi, Yayın yılı, ISBN, Kategori, Durum

### Kitap Düzenleme (update.php)
- Mevcut kitap bilgilerini güncelleme
- Kitap durumunu değiştirme
- Tüm alanları düzenleme

### Kitap Silme (delete.php)
- Kitap silme onayı
- Silinecek kitap bilgilerini görüntüleme
- Güvenli silme işlemi

## 🎨 Tasarım Özellikleri

- **Modern Gradient Arka Plan**: Mavi-mor geçişli arka plan
- **Glassmorphism**: Şeffaf kartlar ve blur efektleri
- **Responsive Grid**: Mobil uyumlu grid sistemi
- **Hover Animasyonları**: Etkileşimli buton ve kart animasyonları
- **Status Badges**: Renkli durum etiketleri
- **Modern Typography**: Okunabilir ve şık yazı tipleri

## 🔧 Özelleştirme

### Renk Teması
`style.css` dosyasında CSS değişkenlerini kullanarak renkleri özelleştirebilirsiniz:

```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --success-color: #c6f6d5;
    --error-color: #fed7d7;
}
```

### Veritabanı Şeması
Veritabanı tablosu otomatik olarak oluşturulur. Ek alanlar eklemek için `db.php` dosyasındaki SQL sorgusunu düzenleyin.

## 📱 Mobil Uyumluluk

Sistem tamamen responsive tasarıma sahiptir:
- Mobil cihazlarda tek sütun düzeni
- Dokunmatik ekranlar için optimize edilmiş butonlar
- Esnek grid sistemi

## 🔒 Güvenlik

- **SQL Injection Koruması**: Prepared statements kullanımı
- **XSS Koruması**: HTML escape işlemleri
- **Input Validation**: Form verilerinin doğrulanması
- **Error Handling**: Güvenli hata mesajları

## 🐛 Sorun Giderme

### Veritabanı Bağlantı Hatası
- Veritabanı bilgilerini kontrol edin
- MySQL servisinin çalıştığından emin olun
- Veritabanı kullanıcısının yetkilerini kontrol edin

### Sayfa Yüklenmiyor
- PHP hata loglarını kontrol edin
- Dosya izinlerini kontrol edin
- Web sunucusu ayarlarını kontrol edin

## 📞 Destek

Herhangi bir sorun yaşarsanız:
1. Hata mesajlarını kontrol edin
2. PHP ve MySQL loglarını inceleyin
3. Tarayıcı konsol hatalarını kontrol edin

## 📄 Lisans

Bu proje açık kaynak kodludur ve eğitim amaçlı kullanılabilir.

---

**Geliştirici**: Kütüphane Yönetim Sistemi  
**Versiyon**: 1.0  
**Son Güncelleme**: 2025
