# Alan Türleri (Field Types)

AdminKit 14 farklı alan türünü destekler. Her alan türü kendi özelleştirme seçenekleriyle gelir.

## Genel Alan Seçenekleri

Tüm alan türleri için kullanılabilir ortak seçenekler:

```php
'field_name' => [
    'type' => 'text',               // Alan türü (zorunlu)
    'label' => 'Alan Etiketi',      // Görüntülenecek etiket
    'required' => true,             // Zorunlu alan mı?
    'help' => 'Yardım metni',       // Alan altında gösterilecek açıklama
    'placeholder' => 'Örnek metin', // Placeholder metni
    'default' => 'varsayılan',      // Varsayılan değer
    'readonly' => false,            // Salt okunur mu?
    'disabled' => false,            // Devre dışı mı?
    'class' => 'custom-class',      // Özel CSS class
    'attr' => ['data-test' => 'value'] // Özel HTML attribute'ları
]
```

## 📝 Metin Alan Türleri

### 1. Text Field

Tek satır metin girişi için kullanılır.

```php
'name' => [
    'type' => 'text',
    'label' => 'Ad Soyad',
    'required' => true,
    'min_length' => 2,
    'max_length' => 100,
    'pattern' => '^[A-Za-zÇĞıİÖŞÜçğıöşü\s]+$', // Regex pattern
    'placeholder' => 'Adınızı ve soyadınızı girin'
]
```

**Özel Seçenekler:**
- `min_length`: Minimum karakter sayısı
- `max_length`: Maksimum karakter sayısı
- `pattern`: Regex doğrulama pattern'i

### 2. Textarea Field

Çok satır metin girişi için kullanılır.

```php
'description' => [
    'type' => 'textarea',
    'label' => 'Açıklama',
    'rows' => 5,
    'cols' => 50,
    'min_length' => 10,
    'max_length' => 1000,
    'resize' => true, // false: resize devre dışı
    'placeholder' => 'Detaylı açıklama yazın...'
]
```

**Özel Seçenekler:**
- `rows`: Satır sayısı
- `cols`: Sütun sayısı
- `resize`: Resize özelliği açık/kapalı

### 3. Email Field

E-posta adresi girişi için özel doğrulamalı alan.

```php
'email' => [
    'type' => 'email',
    'label' => 'E-posta Adresi',
    'required' => true,
    'unique' => true, // Veritabanında benzersiz olmalı
    'domains' => ['gmail.com', 'outlook.com'], // İzin verilen domain'ler
    'placeholder' => 'ornek@email.com'
]
```

**Özel Seçenekler:**
- `unique`: Benzersizlik kontrolü
- `domains`: İzin verilen e-posta domain'leri

### 4. Password Field

Şifre girişi için özel alan.

```php
'password' => [
    'type' => 'password',
    'label' => 'Şifre',
    'required' => true,
    'min_length' => 8,
    'max_length' => 50,
    'strength_meter' => true, // Şifre güçlülük göstergesi
    'requirements' => [
        'uppercase' => true,  // Büyük harf zorunlu
        'lowercase' => true,  // Küçük harf zorunlu
        'numbers' => true,    // Rakam zorunlu
        'symbols' => true     // Sembol zorunlu
    ]
]
```

**Özel Seçenekler:**
- `strength_meter`: Şifre güçlülük göstergesi
- `requirements`: Şifre gereksinimleri

## 🔢 Sayısal Alan Türleri

### 5. Number Field

Sayısal değerler için kullanılır.

```php
'age' => [
    'type' => 'number',
    'label' => 'Yaş',
    'min' => 0,
    'max' => 120,
    'step' => 1,
    'decimals' => 0, // Ondalık basamak sayısı
    'thousand_separator' => ',',
    'decimal_separator' => '.'
]
```

**Özel Seçenekler:**
- `min`: Minimum değer
- `max`: Maksimum değer
- `step`: Artış miktarı
- `decimals`: Ondalık basamak sayısı

### 6. Money Field

Para birimi değerleri için özel alan.

```php
'price' => [
    'type' => 'money',
    'label' => 'Fiyat',
    'currency' => 'TL',
    'currency_position' => 'after', // before, after
    'min' => 0,
    'max' => 999999,
    'decimals' => 2,
    'required' => true
]
```

**Özel Seçenekler:**
- `currency`: Para birimi sembolü
- `currency_position`: Para birimi pozisyonu (before/after)

## 📅 Tarih Alan Türleri

### 7. Date Field

Tarih seçimi için kullanılır.

```php
'birth_date' => [
    'type' => 'date',
    'label' => 'Doğum Tarihi',
    'format' => 'Y-m-d',
    'min_date' => '1900-01-01',
    'max_date' => date('Y-m-d'),
    'default' => 'today', // today, yesterday, +1 week
    'locale' => 'tr'
]
```

**Özel Seçenekler:**
- `format`: Tarih formatı
- `min_date`: Minimum tarih
- `max_date`: Maksimum tarih
- `locale`: Dil ayarı

### 8. DateTime Field

Tarih ve saat seçimi için kullanılır.

```php
'published_at' => [
    'type' => 'datetime',
    'label' => 'Yayın Tarihi',
    'format' => 'Y-m-d H:i:s',
    'timezone' => 'Europe/Istanbul',
    'min_date' => 'now',
    'step' => 15, // Dakika artış miktarı
    'default' => 'now'
]
```

**Özel Seçenekler:**
- `timezone`: Saat dilimi
- `step`: Dakika artış miktarı

## ☑️ Seçim Alan Türleri

### 9. Boolean Field

Açık/kapalı, evet/hayır seçimleri için kullanılır.

```php
'is_active' => [
    'type' => 'boolean',
    'label' => 'Aktif',
    'default' => true,
    'true_label' => 'Aktif',
    'false_label' => 'Pasif',
    'style' => 'switch' // checkbox, switch, radio
]
```

**Özel Seçenekler:**
- `true_label`: True için etiket
- `false_label`: False için etiket
- `style`: Görünüm stili (checkbox/switch/radio)

### 10. Choice Field

Seçenek listesi için kullanılır.

```php
'status' => [
    'type' => 'choice',
    'label' => 'Durum',
    'choices' => [
        'draft' => 'Taslak',
        'published' => 'Yayında',
        'archived' => 'Arşivlendi'
    ],
    'multiple' => false, // Çoklu seçim
    'expanded' => false, // Radio button olarak göster
    'placeholder' => 'Durum seçin'
]
```

**Özel Seçenekler:**
- `choices`: Seçenek listesi
- `multiple`: Çoklu seçim izni
- `expanded`: Radio button görünümü

## 📎 Dosya Alan Türleri

### 11. File Field

Genel dosya yükleme için kullanılır.

```php
'document' => [
    'type' => 'file',
    'label' => 'Döküman',
    'upload_path' => 'uploads/documents',
    'max_size' => '5MB',
    'allowed_types' => ['pdf', 'doc', 'docx', 'txt'],
    'multiple' => false,
    'required' => false
]
```

**Özel Seçenekler:**
- `upload_path`: Yükleme klasörü
- `max_size`: Maksimum dosya boyutu
- `allowed_types`: İzin verilen dosya türleri
- `multiple`: Çoklu dosya yükleme

### 12. Image Field

Resim yükleme için özel alan.

```php
'profile_photo' => [
    'type' => 'image',
    'label' => 'Profil Fotoğrafı',
    'upload_path' => 'uploads/profiles',
    'max_size' => '2MB',
    'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],
    'resize' => [
        'width' => 300,
        'height' => 300,
        'crop' => true
    ],
    'thumbnails' => [
        'small' => [100, 100],
        'medium' => [200, 200]
    ],
    'watermark' => [
        'enabled' => true,
        'image' => 'assets/watermark.png',
        'position' => 'bottom-right',
        'opacity' => 0.7
    ]
]
```

**Özel Seçenekler:**
- `resize`: Otomatik boyutlandırma ayarları
- `thumbnails`: Küçük resim oluşturma
- `watermark`: Filigran ayarları

## 🔗 İlişki Alan Türleri

### 13. Association Field

Diğer entity'lerle ilişki kurmak için kullanılır.

```php
'category_id' => [
    'type' => 'association',
    'label' => 'Kategori',
    'target_entity' => 'Category',
    'display_field' => 'name',
    'value_field' => 'id',
    'multiple' => false,
    'autocomplete' => true,
    'min_search_length' => 2,
    'ajax_url' => '/admin/api/categories/search',
    'where' => ['is_active' => true] // Filtreleme koşulu
]
```

**Özel Seçenekler:**
- `target_entity`: Hedef entity adı
- `display_field`: Gösterilecek alan
- `value_field`: Değer alanı
- `autocomplete`: Otomatik tamamlama
- `ajax_url`: AJAX arama URL'i

### 14. Collection Field

Çoklu form koleksiyonu için kullanılır.

```php
'addresses' => [
    'type' => 'collection',
    'label' => 'Adresler',
    'entry_type' => [
        'street' => ['type' => 'text', 'label' => 'Sokak'],
        'city' => ['type' => 'text', 'label' => 'Şehir'],
        'postal_code' => ['type' => 'text', 'label' => 'Posta Kodu']
    ],
    'allow_add' => true,
    'allow_delete' => true,
    'min_entries' => 1,
    'max_entries' => 5,
    'add_button_text' => 'Yeni Adres Ekle',
    'delete_button_text' => 'Sil'
]
```

**Özel Seçenekler:**
- `entry_type`: Alt form alanları
- `allow_add`: Yeni ekleme izni
- `allow_delete`: Silme izni
- `min_entries`: Minimum entry sayısı
- `max_entries`: Maksimum entry sayısı

## 🎨 Alan Görünümü Özelleştirme

### CSS Class'ları

```php
'custom_field' => [
    'type' => 'text',
    'label' => 'Özel Alan',
    'class' => 'custom-input',
    'wrapper_class' => 'custom-wrapper',
    'label_class' => 'custom-label'
]
```

### Inline Stil

```php
'styled_field' => [
    'type' => 'text',
    'label' => 'Stillenmiş Alan',
    'style' => 'background-color: #f0f0f0; border: 2px solid #ccc;'
]
```

### Özel Template

```php
'template_field' => [
    'type' => 'text',
    'label' => 'Template Alan',
    'template' => 'custom/field-template.tpl'
]
```

## 🔍 Doğrulama (Validation)

### Yerleşik Doğrulamalar

```php
'validated_field' => [
    'type' => 'text',
    'label' => 'Doğrulamalı Alan',
    'required' => true,
    'min_length' => 5,
    'max_length' => 50,
    'pattern' => '^[A-Za-z0-9]+$',
    'unique' => true
]
```

### Özel Doğrulama

```php
$adminKit->addValidationRule('User', 'username', function($value, $data) {
    if (strlen($value) < 3) {
        return 'Kullanıcı adı en az 3 karakter olmalıdır';
    }
    
    if (preg_match('/[^a-zA-Z0-9_]/', $value)) {
        return 'Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir';
    }
    
    return true; // Doğrulama başarılı
});
```

### AJAX Doğrulama

```php
'ajax_validated' => [
    'type' => 'text',
    'label' => 'AJAX Doğrulamalı',
    'ajax_validation' => true,
    'validation_url' => '/admin/api/validate/username',
    'validation_delay' => 500 // ms
]
```

## 🎯 Koşullu Alanlar

```php
// Dinamik görünüm
'company_type' => [
    'type' => 'choice',
    'label' => 'Şirket Türü',
    'choices' => [
        'individual' => 'Bireysel',
        'corporate' => 'Kurumsal'
    ]
],

'tax_number' => [
    'type' => 'text',
    'label' => 'Vergi Numarası',
    'conditions' => [
        'show_when' => ['company_type' => 'corporate']
    ]
]
```

## 💡 İpuçları ve Best Practices

### 1. Performans İçin

```php
// Autocomplete için cache kullanın
'category_id' => [
    'type' => 'association',
    'target_entity' => 'Category',
    'cache_results' => true,
    'cache_ttl' => 3600
]
```

### 2. Kullanıcı Deneyimi

```php
// Yardımcı metinler ekleyin
'password' => [
    'type' => 'password',
    'help' => 'En az 8 karakter, büyük harf, küçük harf ve rakam içermelidir'
]
```

### 3. Güvenlik

```php
// Dosya yükleme güvenliği
'upload' => [
    'type' => 'file',
    'scan_viruses' => true,
    'allowed_mimes' => ['image/jpeg', 'image/png'],
    'max_size' => '2MB'
]
```

## 🔗 İlgili Dokümantasyon

- **[Dynamic Forms](services/dynamic-forms.md)** - Koşullu alanlar ve çok adımlı formlar
- **[Validation Service](services/validation-service.md)** - Gelişmiş doğrulama kuralları
- **[File Management](services/file-service.md)** - Dosya yükleme ve işleme

---

AdminKit'in güçlü alan türleri ile her türlü veri girişi ihtiyacınızı karşılayabilirsiniz.
