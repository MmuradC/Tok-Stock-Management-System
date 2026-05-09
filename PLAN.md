# Proje Planı: Tok-Stock-Management-System

Bu plan, Ubuntu Server ve Arch Linux sistemlerinde çalışabilecek, modüler ve adaptif bir yapıda tasarlanmıştır.

## 1. Aşama: Temel Sistem ve Ağ Yapılandırması (Murad)
- [ ] OS Kurulumu (Ubuntu 22.04 LTS veya Arch Linux).
- [ ] Paket Yöneticisi Adaptasyonu (apt/pacman wrapper scriptleri veya Ansible).
- [ ] SSH Güvenliği (Key-based auth, port değiştirme).
- [ ] NTP (Zaman Senkronizasyonu) Yapılandırması.
- [ ] DNS/BIND9 Kurulumu ve Yerel Alan Adı Tanımlamaları.

## 2. Aşama: Güvenlik ve İzleme (Murad)
- [ ] Firewall Yapılandırması (Ubuntu: UFW, Arch: nftables/firewalld).
- [ ] Merkezi Log Yönetimi (syslog-ng).
- [ ] Zabbix Monitoring Sunucusu ve Agent Kurulumu.
- [ ] Yedekleme Stratejisi (Rsync + SSH) (Albawendi ile koordineli).

## 3. Aşama: Veri Katmanı ve Altyapı (Albawendi & Juan)
- [ ] MariaDB Kurulumu ve Cluster/Replication (Opsiyonel).
- [ ] Docker ve Docker Compose Kurulumu (Juan).
- [ ] Samba Dosya Paylaşım Sunucusu.
- [ ] Email Server (Postfix/Dovecot) Kurulumu (osTicket için).

## 4. Aşama: Uygulama Dağıtımı (Juan & Ekip)
- [ ] NGINX ve PHP-FPM Yapılandırması.
- [ ] **OwnCloud:** Özel bulut depolama ve veri göçü.
- [ ] **osTicket:** Müşteri destek sistemi.
- [ ] **Zen Cart:** E-ticaret ve temel stok altyapısı.

## 5. Aşama: Özel Stok Yönetim Modülü (Dashboard & CRUD)
- [ ] **Backend Geliştirme:** PHP (Slim veya Laravel Zero) kullanarak API uç noktalarının oluşturulması.
- [ ] **Frontend Geliştirme:** Bootstrap veya Tailwind CSS ile responsive dashboard.
- [ ] **Excel Motoru:** PhpSpreadsheet kütüphanesi ile `.xlsx` okuma/yazma entegrasyonu.
- [ ] **Search & Filter:** SQL tabanlı hızlı arama ve kategori bazlı filtreleme.
- [ ] **Data Migration:** Eski Excel verilerinin `tok_stock_db`'ye aktarımı için script yazımı.

## Teknik Dökümantasyon Linkleri
- [Sistem Mimarisi](docs/architecture/SYSTEM_ARCHITECTURE.md)
- [Veritabanı Tasarımı](docs/database/DATABASE_DESIGN.md)
- [Altyapı ve Güvenlik](docs/infrastructure/INFRASTRUCTURE_DETAIL.md)
- [Prodüksiyon ve SSL Güvenliği](docs/infrastructure/SECURITY_GUIDE.md)

### Paket Kurulum Komutları

| Servis | Ubuntu (apt) | Arch Linux (pacman) |
| :--- | :--- | :--- |
| **Web Sunucu** | `sudo apt install nginx` | `sudo pacman -S nginx` |
| **Veritabanı** | `sudo apt install mariadb-server` | `sudo pacman -S mariadb` |
| **PHP** | `sudo apt install php-fpm php-mysql` | `sudo pacman -S php-fpm php-gd` |
| **Docker** | `sudo apt install docker.io docker-compose` | `sudo pacman -S docker docker-compose` |
| **Firewall** | `sudo apt install ufw` | `sudo pacman -S firewalld` |

### Servis Yönetimi (Ortak)
```bash
# Servis başlatma ve etkinleştirme
sudo systemctl enable --now nginx
sudo systemctl enable --now mariadb
sudo systemctl enable --now docker
```

- **Konfigürasyon:** `/etc/nginx/sites-available` (Ubuntu) ve `/etc/nginx/conf.d` (Arch) gibi yol farklılıkları Docker kullanılarak minimize edilecektir.
- **Veritabanı Başlatma:** Arch Linux'ta MariaDB ilk kurulumdan sonra `mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql` komutuyla initialize edilmelidir.
