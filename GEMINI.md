# Proje Kuralları (GEMINI.md)

## Mimari Prensipler
- **Konteyner Öncelikli:** Uygulama katmanındaki servisler (ZenCart, osTicket, OwnCloud) mümkünse Docker üzerinde çalıştırılmalıdır.
- **Hibrit Destek:** Sistem kurulumları hem Ubuntu hem de Arch Linux uyumlu olmalıdır.
- **Güvenlik:** Tüm şifreler `.env` dosyalarında tutulmalı ve asla commit edilmemelidir.

## Teknoloji Yığını
- **Web:** NGINX, PHP 8.x
- **Veritabanı:** MariaDB
- **Altyapı:** Docker, Rsync, Zabbix
- **Güvenlik:** UFW/Firewalld, SSH (Key-Auth)

## Geliştirme Standartları
- Tüm veritabanı şemaları `sql/` klasöründe saklanmalıdır.
- Custom CRUD mantığı için temiz PHP veya seçilen bir framework (örn: Laravel/Slim) kullanılacaktır.
- API uç noktaları dökümante edilmelidir.
