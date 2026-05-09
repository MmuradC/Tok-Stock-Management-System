# Sistem Mimarisi

Tok-Stock-Management-System, yüksek erişilebilirlik ve modülerlik için **Reverse Proxy** tabanlı bir konteyner mimarisi kullanır.

## 1. Ağ Katmanı ve Trafik Akışı
- **NGINX (Gateway):** Tüm giriş trafiğini (HTTP/HTTPS) karşılar.
- **SSL Sonlandırma:** Let's Encrypt sertifikaları NGINX üzerinde tutulur.
- **Yönlendirme:**
  - `cloud.firma.lan` -> OwnCloud Container
  - `support.firma.lan` -> osTicket Container
  - `shop.firma.lan` -> Zen Cart Container
  - `dashboard.firma.lan` -> Custom PHP/MariaDB Dashboard

## 2. Servis Segmentasyonu
| Katman | Servisler | Erişim |
| :--- | :--- | :--- |
| **Public** | NGINX, SSH, Email (SMTP/IMAP) | WAN / LAN |
| **Internal** | MariaDB, Redis, Samba, BIND9 | Sadece LAN |
| **Management** | Zabbix, syslog-ng, Portainer | Sadece Admin LAN / VPN |

## 3. Veri Akış Diyagramı (Basitleştirilmiş)
```text
[Kullanıcı] -> [Firewall (UFW/nftables)] -> [NGINX Proxy]
                                               |
        +----------------------+---------------+-----------------------+
        |                      |               |                       |
 [Custom Dashboard]      [Zen Cart]      [osTicket]               [OwnCloud]
        |                      |               |                       |
        +----------------------+---------------+-----------------------+
                               |
                        [MariaDB Cluster]
                               |
                        [Rsync Backup]
```

## 4. Konteyner Stratejisi
Her uygulama kendi `docker-compose.yml` dosyasına sahip olacak, ancak ortak bir `frontend_network` ve `backend_network` üzerinden haberleşeceklerdir. Bu, veritabanının dış dünyaya tamamen kapatılmasını sağlar.
