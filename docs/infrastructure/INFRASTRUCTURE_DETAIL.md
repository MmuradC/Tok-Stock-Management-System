# Altyapı ve Güvenlik Detayları

## 1. DNS Yapılandırması (BIND9)
Yerel ağda cihazların birbirine isimle erişmesi için BIND9 kullanılacaktır.
- **Master Zone:** `firma.lan`
- **A Records:**
  - `srv01.firma.lan` -> 192.168.1.10 (Ana Sunucu)
  - `nas.firma.lan` -> 192.168.1.11 (Samba/Backup)

## 2. İzleme ve Loglama (Zabbix & syslog-ng)
- **Zabbix:** CPU, RAM, Disk kullanımının yanı sıra MariaDB query performansını ve NGINX aktif bağlantı sayısını izler.
- **syslog-ng:** Tüm konteyner loglarını ve sistem loglarını (auth.log, kern.log) merkezi bir dosyada toplar ve kritik hatalarda Email Server üzerinden uyarı gönderir.

## 3. Güvenlik Katmanları
- **SSH:**
  - Port: 2222 (Default 22 kapalı).
  - Auth: Sadece SSH Key.
  - Fail2Ban: Yanlış denemelerde IP bloklama.
- **Firewall (UFW / Firewalld):**
  - Gelen: 80, 443, 2222, 25, 587, 993 (Açık).
  - Diğer her şey (KAPALI).

## 4. Yedekleme (Rsync + SSH)
- **Daily:** `/var/lib/docker/volumes` dizini NAS ünitesine rsync ile çekilir.
- **DB Backup:** `mysqldump` ile her gece alınan yedekler şifrelenerek OwnCloud üzerinde de bir kopyası saklanır.
