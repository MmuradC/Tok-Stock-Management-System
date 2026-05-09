<?php
// Geçici Autoloader (Composer kullanana kadar)
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/ProductService.php';

use TokStock\ProductService;

$error = null;
$products = [];

try {
    $productService = new ProductService();
    $products = $productService->getAllProducts();
} catch (\Exception $e) {
    $error = "Veritabanı bağlantısı kurulamadı. (Hata: " . $e->getMessage() . ") Lütfen Docker servislerinin çalıştığından emin olun.";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tok-Stock Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              'brand':        '#8A5F41',
              'brand-mid':    '#A77F60',
              'brand-light':  '#F3E4C9',
              'brand-accent': '#CCD67F',
            }
          }
        }
      }
    </script>
</head>
<body class="bg-brand-light font-sans leading-normal tracking-normal">

    <nav class="bg-brand p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <a href="#" class="text-white text-xl font-bold">Tok-Stock Yönetimi</a>
            <div class="text-white">
                <a href="#" class="px-3 hover:text-gray-300">Dashboard</a>
                <a href="#" class="px-3 hover:text-gray-300">Ürünler</a>
                <a href="#" class="px-3 hover:text-gray-300">Ayarlar</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-8 px-4">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl font-bold text-gray-800">Ürün Envanteri</h1>
            <div class="flex flex-wrap items-center gap-2">
                <form action="import_csv.php" method="POST" enctype="multipart/form-data" class="flex items-center gap-2 bg-white p-2 border rounded shadow-sm">
                    <input type="file" name="csv_file" accept=".csv" required class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-brand-light file:text-brand hover:file:bg-brand-mid hover:file:text-white">
                    <button type="submit" class="bg-brand hover:bg-brand-mid text-white font-bold py-2 px-4 rounded text-sm shadow">
                        İçe Aktar (CSV)
                    </button>
                </form>
                <a href="export_csv.php" class="bg-brand-accent hover:bg-brand-mid text-brand hover:text-white font-bold py-2 px-4 rounded shadow text-sm">
                    Dışa Aktar (CSV)
                </a>
                <a href="add_product.php" class="bg-brand hover:bg-brand-mid text-white font-bold py-2 px-4 rounded shadow text-sm">
                    + Yeni Ürün
                </a>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Ürün başarıyla eklendi!</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Ürün başarıyla güncellendi!</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Ürün başarıyla silindi!</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'import_success'): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">İçe aktarım tamamlandı. Başarılı: <?= htmlspecialchars($_GET['success'] ?? 0) ?>, Atlanan/Hatalı: <?= htmlspecialchars($_GET['skipped'] ?? 0) ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'import_error'): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">İçe Aktarım Hatası!</strong>
                <span class="block sm:inline"><?= htmlspecialchars($_GET['detail'] ?? '') ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Sistem Uyarısı!</strong>
                <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
                <table class="text-left w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="py-4 px-6 bg-brand-light font-bold uppercase text-sm text-gray-600 border-b border-gray-200">SKU</th>
                            <th class="py-4 px-6 bg-brand-light font-bold uppercase text-sm text-gray-600 border-b border-gray-200">Ürün Adı</th>
                            <th class="py-4 px-6 bg-brand-light font-bold uppercase text-sm text-gray-600 border-b border-gray-200">Kategori</th>
                            <th class="py-4 px-6 bg-brand-light font-bold uppercase text-sm text-gray-600 border-b border-gray-200">Stok</th>
                            <th class="py-4 px-6 bg-brand-light font-bold uppercase text-sm text-gray-600 border-b border-gray-200">Fiyat (Satış)</th>
                            <th class="py-4 px-6 bg-brand-light font-bold uppercase text-sm text-gray-600 border-b border-gray-200">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="py-4 px-6 border-b border-gray-200 text-center text-gray-500">
                                    Henüz ürün bulunmuyor.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr class="hover:bg-brand-light">
                                    <td class="py-4 px-6 border-b border-gray-200"><?= htmlspecialchars($product['sku']) ?></td>
                                    <td class="py-4 px-6 border-b border-gray-200 font-semibold text-gray-800"><?= htmlspecialchars($product['name']) ?></td>
                                    <td class="py-4 px-6 border-b border-gray-200 text-gray-600"><?= htmlspecialchars($product['category_name'] ?? 'Kategorisiz') ?></td>
                                    <td class="py-4 px-6 border-b border-gray-200">
                                        <span class="<?= $product['stock_quantity'] <= $product['min_stock_level'] ? 'text-red-600 font-bold' : 'text-brand-accent font-semibold' ?>">
                                            <?= htmlspecialchars($product['stock_quantity']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 border-b border-gray-200"><?= htmlspecialchars(number_format($product['price_sale'], 2)) ?> ₺</td>
                                    <td class="py-4 px-6 border-b border-gray-200">
                                        <a href="edit_product.php?id=<?= $product['id'] ?>" class="text-brand hover:text-brand-mid mr-2">Düzenle</a>
                                        <a href="process_product.php?action=delete&id=<?= $product['id'] ?>" onclick="return confirm('Bu ürünü silmek istediğinize emin misiniz?');" class="text-red-500 hover:text-red-700">Sil</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                let cells = row.querySelectorAll('td');
                if(cells.length > 1) { // Sadece veri satırlarını filtrele
                    let sku = cells[0].innerText.toLowerCase();
                    let name = cells[1].innerText.toLowerCase();
                    let category = cells[2].innerText.toLowerCase();
                    
                    if (sku.includes(filter) || name.includes(filter) || category.includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>
