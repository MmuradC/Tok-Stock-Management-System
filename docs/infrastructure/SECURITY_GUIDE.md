# Güvenlik ve Prodüksiyon Kılavuzu (UFW & SSL)

Sistemi canlıya (Production) almadan önce sunucunun dış tehditlere karşı korunması ve veri trafiğinin şifrelenmesi kritik önem taşır. Bu kılavuz Murad'ın "Sysadmin" rolü kapsamında uygulanmalıdır.

## 1. Güvenlik Duvarı (Firewall) Yapılandırması

Sunucumuzda varsayılan SSH portunu `2222` olarak değiştirdiğimizi (önerilen) varsayarak sadece gerekli HTTP(80) ve HTTPS(443) portlarını dışarıya açacağız. MariaDB ve diğer servisler sadece içeriden (Docker ağı veya Localhost) erişilebilir olacak.

### Ubuntu Server (UFW)
Ubuntu'da varsayılan olarak gelen `ufw` aracını kullanacağız.

```bash
# UFW'yi etkinleştirmeden önce varsayılan kuralları belirle
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Açılması gereken portlar
sudo ufw allow 2222/tcp  # Özel SSH Portu (22 yerine bunu kullanın)
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS

# (Opsiyonel) Eğer şirket içi VPN veya Zabbix izlemesi varsa o IP'ye tam izin
# sudo ufw allow from 192.168.1.50 to any

# UFW'yi başlat
sudo ufw enable

# Durumu kontrol et
sudo ufw status verbose
```

### Arch Linux (firewalld)
Arch Linux'ta genellikle `firewalld` tercih edilir.

```bash
# Servisi başlat ve aktifleştir
sudo systemctl enable --now firewalld

# Portları kalıcı olarak public zone'a ekle
sudo firewall-cmd --zone=public --add-port=2222/tcp --permanent
sudo firewall-cmd --zone=public --add-service=http --permanent
sudo firewall-cmd --zone=public --add-service=https --permanent

# Kuralları uygula
sudo firewall-cmd --reload

# Durumu kontrol et
sudo firewall-cmd --list-all
```

---

## 2. SSL Sertifikası (Let's Encrypt & Certbot)

Sistemde şifrelemeyi sağlamak için ücretsiz ve otomatik Let's Encrypt kullanacağız. Sistem NGINX Reverse Proxy arkasında olduğu için SSL yapılandırması tek merkezden (NGINX üzerinden) yapılacaktır.

### Certbot Kurulumu

**Ubuntu:**
```bash
sudo apt update
sudo apt install certbot python3-certbot-nginx
```

**Arch Linux:**
```bash
sudo pacman -S certbot certbot-nginx
```

### Sertifikaların Alınması
Alt alan adlarımız (subdomainler) için SSL sertifikalarını tek bir komutla talep edeceğiz:

```bash
# NGINX konfigürasyonlarını otomatik okuyup sertifikaları tanımlar
sudo certbot --nginx -d dashboard.firma.lan -d shop.firma.lan -d support.firma.lan -d cloud.firma.lan
```
*Not: `firma.lan` yerel bir alan adıysa Certbot çalışmaz. Bunun yerine gerçek bir alan adınız (`firma.com`) olmalı ve DNS A kayıtları sunucuya yönlendirilmiş olmalıdır.*

### Otomatik Yenileme (Auto-Renewal)
Let's Encrypt sertifikaları 90 günde bir sona erer. Her iki işletim sisteminde de Systemd timer'ları bu işi otomatik yapar.

Sistemin aktif olup olmadığını kontrol etmek için:
```bash
sudo systemctl status certbot.timer
```

Test etmek için (Sertifikaları yenileme simülasyonu):
```bash
sudo certbot renew --dry-run
```

---

## 3. Ek Güvenlik Önlemleri
1. **Fail2Ban:** SSH brute-force (kaba kuvvet) saldırılarını engellemek için Fail2Ban kurun.
   * `sudo apt install fail2ban`
2. **.env Dosyası:** `/home/.../Tok-Stock-Management-System/.env` dosyanızın izinlerinin sadece `root` veya çalıştıran kullanıcı tarafından okunabilir (`chmod 600 .env`) olduğundan emin olun.
3. **Docker Ağı:** MariaDB'nin host makinenin 3306 portuna bind (publish) EDİLMEDİĞİNDEN emin olun. `docker-compose.yml` dosyasında yazdığımız gibi sadece konteyner içi ağda kalmalıdır.
