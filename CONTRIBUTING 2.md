# Contributing to AdminKit

AdminKit açık kaynak bir projedir ve katkılarınızı bekliyoruz! Bu rehber, projeye nasıl katkıda bulunabileceğinizi açıklar.

## 🚀 Hızlı Başlangıç

1. **Repository'yi fork edin**
2. **Development branch oluşturun**: `git checkout -b feature/amazing-feature`
3. **Değişikliklerinizi commit edin**: `git commit -m 'feat: add amazing feature'`
4. **Branch'inizi push edin**: `git push origin feature/amazing-feature`
5. **Pull Request oluşturun**

## 📋 Geliştirme Ortamı

### Gereksinimler
- PHP 8.1 veya üzeri
- Composer
- MySQL/PostgreSQL
- Git

### Kurulum
```bash
git clone https://github.com/oktayaydogan/admin-kit.git
cd admin-kit
composer install
cp .env.example .env
# .env dosyasını yapılandırın
php bin/adminkit migrate
```

### Testing
```bash
# Unit testleri çalıştır
composer test

# Code coverage
composer test-coverage

# Static analysis
composer analyse

# Code formatting
composer format
```

## 🎯 Katkı Türleri

### 🐛 Bug Reports
Bug bulduğunuzda:
1. [Issues](https://github.com/oktayaydogan/admin-kit/issues) sayfasını kontrol edin
2. Aynı bug daha önce raporlanmış mı kontrol edin
3. Yeni issue oluşturun ve template'i doldurun

#### Bug Report Template
```markdown
**Bug Açıklaması**
Kısa ve net bug açıklaması.

**Yeniden Üretme Adımları**
1. '...' sayfasına git
2. '....' butonuna tıkla
3. Hatayı gör

**Beklenen Davranış**
Ne olmasını bekliyordunuz?

**Gerçek Davranış**
Ne oldu?

**Ekran Görüntüleri**
Varsa ekran görüntüleri ekleyin.

**Ortam:**
- OS: [örn. Windows 10]
- PHP Sürümü: [örn. 8.1.0]
- AdminKit Sürümü: [örn. 1.0.0]
- Tarayıcı: [örn. Chrome 90]
```

### ✨ Feature Requests
Yeni özellik önerisi için:
1. [Discussions](https://github.com/oktayaydogan/admin-kit/discussions) kısmında tartışın
2. Topluluk geri bildirimini bekleyin
3. Onaylandıktan sonra issue oluşturun

#### Feature Request Template
```markdown
**Özellik Açıklaması**
Yeni özelliğin açıklaması.

**Problem**
Bu özellik hangi problemi çözüyor?

**Çözüm**
Önerilen çözüm nedir?

**Alternatifler**
Değerlendirdiğiniz alternatif çözümler.

**Ek Bilgi**
Başka eklemek istediğiniz bilgiler.
```

### 💻 Code Contributions

#### Coding Standards
- **PSR-12** coding standard
- **PHP 8.1+** syntax kullanın
- **Type declarations** kullanın
- **PHPDoc** comments yazın

#### Commit Message Format
[Conventional Commits](https://www.conventionalcommits.org/) formatını kullanıyoruz:

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

**Types:**
- `feat`: Yeni özellik
- `fix`: Bug fix
- `docs`: Dokümantasyon
- `style`: Code formatting
- `refactor`: Code refactoring
- `test`: Test ekleme/güncelleme
- `chore`: Build process, tooling

**Örnekler:**
```bash
feat: add 2FA authentication
fix: resolve queue worker memory leak
docs: update installation guide
refactor: improve cache service performance
```

#### Pull Request Süreci
1. **Branch naming**: `feature/`, `fix/`, `docs/` prefix kullanın
2. **Tests**: Yeni kod için test yazın
3. **Documentation**: Gerekirse dokümantasyon güncelleyin
4. **Changelog**: Değişikliği CHANGELOG.md'ye ekleyin

#### PR Template
```markdown
## Değişiklik Türü
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Açıklama
Bu PR'ın ne yaptığını açıklayın.

## Test Edildi
- [ ] Unit tests
- [ ] Integration tests
- [ ] Manual testing

## Checklist
- [ ] Code PSR-12 standartlarına uygun
- [ ] Tests yazıldı/güncellendi
- [ ] Dokümantasyon güncellendi
- [ ] CHANGELOG.md güncellendi
```

## 📚 Dokümantasyon Katkıları

### Dokümantasyon Türleri
- **API Referansı**: Service ve method dokümantasyonu
- **Tutorials**: Adım adım rehberler
- **Examples**: Gerçek dünya örnekleri
- **Translations**: Türkçe çeviriler

### Dokümantasyon Yazma Kuralları
- **Türkçe öncelik**: Ana dokümantasyon Türkçe
- **Pratik örnekler**: Her özellik için çalışan örnek
- **Kod örnekleri**: Syntax highlighting kullanın
- **Başlık yapısı**: H2, H3 kullanarak organize edin

## 🔧 Service Geliştirme

AdminKit'e yeni service eklemek için:

### 1. Service Class Oluştur
```php
namespace Turkpin\AdminKit\Services;

class YourService
{
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    // Service methods...
}
```

### 2. Service Provider'a Ekle
```php
// src/Providers/AdminKitServiceProvider.php
$this->container->set(YourService::class, function () {
    $config = $this->container->get(ConfigService::class);
    return new YourService($config->get('yourservice', []));
});
```

### 3. AdminKit Class'ına Ekle
```php
// src/AdminKit.php
public function getYourService(): YourService
{
    return $this->container->get(YourService::class);
}
```

### 4. Dokümantasyon Ekle
- `docs/services/your-service.md` oluşturun
- `docs/services/README.md`'ye ekleyin
- Kullanım örnekleri yazın

## 🌐 Çeviri Katkıları

### Yeni Dil Ekleme
1. `src/Translations/` altında yeni dosya oluşturun
2. Tüm anahtarları çevirin (600+ anahtar)
3. `LocalizationService`'e dil ekleyin

### Mevcut Çevirileri Güncelleme
1. `src/Translations/tr.php` veya `src/Translations/en.php` düzenleyin
2. Yeni anahtarlar için çeviri ekleyin
3. Test edin

## 🧪 Test Yazma

### Unit Tests
```php
namespace Turkpin\AdminKit\Tests\Services;

use PHPUnit\Framework\TestCase;
use Turkpin\AdminKit\Services\YourService;

class YourServiceTest extends TestCase
{
    public function testServiceMethod(): void
    {
        $service = new YourService();
        $result = $service->yourMethod();
        
        $this->assertNotNull($result);
    }
}
```

### Integration Tests
```php
namespace Turkpin\AdminKit\Tests\Integration;

use PHPUnit\Framework\TestCase;

class AdminKitIntegrationTest extends TestCase
{
    public function testFullWorkflow(): void
    {
        // Integration test...
    }
}
```

## 🎨 UI/Frontend Katkıları

### CSS/SCSS
- **Tailwind CSS** kullanıyoruz
- Responsive design zorunlu
- Dark mode desteği ekleyin

### JavaScript
- **Vanilla JS** veya **Alpine.js**
- ES6+ syntax kullanın
- Browser compatibility IE11+

### Assets
```bash
# Asset'leri build edin
npm run build

# Development mode
npm run dev

# Watch mode
npm run watch
```

## 🚀 Release Süreci

### Version Naming
[Semantic Versioning](https://semver.org/) kullanıyoruz:
- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes

### Release Checklist
- [ ] CHANGELOG.md güncellendi
- [ ] Version number güncellendi
- [ ] Tests geçiyor
- [ ] Documentation güncel
- [ ] Git tag oluşturuldu

## 📞 İletişim

### GitHub
- **Issues**: Bug reports ve feature requests
- **Discussions**: Genel tartışmalar
- **Pull Requests**: Code contributions

### Email
- **Maintainer**: oktayaydogan@gmail.com
- **Community**: info@turkpin.com

### Turkish Community
- AdminKit özellikle Türk geliştiriciler için optimize edilmiştir
- Türkçe issue ve PR'lar memnuniyetle karşılanır
- Türkçe dokümantasyon katkıları özel olarak değerlidir

## 🏆 Contributors

AdminKit'e katkıda bulunan herkese teşekkürler:
- [Okta Yaydoğan](https://github.com/oktayaydogan) - Creator & Maintainer
- [Contributors](https://github.com/oktayaydogan/admin-kit/contributors)

## 📜 License

Bu projeye katkıda bulunarak, katkılarınızın [MIT License](LICENSE) altında lisanslanacağını kabul ediyorsunuz.

---

**Teşekkürler!** AdminKit'i daha iyi hale getirmeye yardım ettiğiniz için! 🚀
