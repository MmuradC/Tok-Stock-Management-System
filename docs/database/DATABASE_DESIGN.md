# Veritabanı Tasarımı

Proje kapsamında MariaDB üzerinde tek bir instance altında birden fazla database şeması yönetilecektir.

## 1. Paylaşımlı Veritabanı Yapısı
- `tok_stock_db`: Özel dashboard ve ana stok verileri.
- `zencart_db`: E-ticaret verileri.
- `osticket_db`: Destek sistemi verileri.
- `owncloud_db`: Bulut depolama metadata.

## 2. Ana Stok Tablosu (tok_stock_db.products)
| Kolon | Tip | Açıklama |
| :--- | :--- | :--- |
| `id` | INT (PK, AI) | Benzersiz Ürün ID |
| `sku` | VARCHAR(50) | Stok Kodu (Benzersiz) |
| `name` | VARCHAR(255) | Ürün Adı |
| `category_id` | INT (FK) | Kategori Bağlantısı |
| `stock_quantity` | INT | Mevcut Stok Adedi |
| `min_stock_level` | INT | Kritik Stok Eşiği (Alarm için) |
| `price_purchase` | DECIMAL(10,2) | Alış Fiyatı |
| `price_sale` | DECIMAL(10,2) | Satış Fiyatı |
| `excel_sync_id` | VARCHAR(100) | Excel'den gelen dış ID (Opsiyonel) |
| `updated_at` | TIMESTAMP | Son Güncelleme |

## 3. Hareket Geçmişi (tok_stock_db.stock_logs)
| Kolon | Tip | Açıklama |
| :--- | :--- | :--- |
| `id` | INT (PK) | İşlem ID |
| `product_id` | INT (FK) | Hangi Ürün? |
| `change_amount` | INT | +/- Değişim miktarı |
| `action_type` | ENUM | 'IN', 'OUT', 'ADJUSTMENT' |
| `user_id` | INT | İşlemi yapan kullanıcı |
| `notes` | TEXT | İşlem detayı (Örn: "Zencart Satışı") |

## 4. Senkronizasyon Mantığı
Custom Dashboard, ZenCart veritabanını belirli aralıklarla (Cron job) veya Trigger'lar vasıtasıyla okuyarak stok adetlerini merkezi `tok_stock_db` ile senkronize eder.
