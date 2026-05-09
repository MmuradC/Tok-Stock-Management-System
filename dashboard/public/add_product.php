<?php
require_once __DIR__ . '/../src/Database.php';

use TokStock\Database;

$categories = [];
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
} catch (\Exception $e) {
    $error = "Kategoriler yüklenemedi: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Ürün Ekle - Tok-Stock</title>
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
            <a href="index.php" class="text-white text-xl font-bold">Tok-Stock Yönetimi</a>
            <div class="text-white">
                <a href="index.php" class="px-3 hover:text-gray-300">Geri Dön</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-8 px-4 max-w-2xl">
        <div class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Yeni Ürün Ekle</h2>
            
            <form action="process_product.php" method="POST">
                <input type="hidden" name="action" value="create">

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="sku">
                        Stok Kodu (SKU) <span class="text-red-500">*</span>
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="sku" name="sku" type="text" placeholder="Örn: ELK-001" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        Ürün Adı <span class="text-red-500">*</span>
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" name="name" type="text" placeholder="Örn: Kablosuz Mouse" required>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="category_id">
                        Kategori
                    </label>
                    <div class="relative">
                        <select class="block appearance-none w-full bg-white border border-gray-400 hover:border-gray-500 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline" id="category_id" name="category_id">
                            <option value="">-- Kategori Seçin --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap -mx-3 mb-4">
                    <div class="w-full md:w-1/2 px-3 mb-4 md:mb-0">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="stock_quantity">
                            Başlangıç Stoğu
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="stock_quantity" name="stock_quantity" type="number" min="0" value="0">
                    </div>
                    <div class="w-full md:w-1/2 px-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="min_stock_level">
                            Kritik Stok Seviyesi
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="min_stock_level" name="min_stock_level" type="number" min="0" value="5">
                    </div>
                </div>

                <div class="flex flex-wrap -mx-3 mb-6">
                    <div class="w-full md:w-1/2 px-3 mb-4 md:mb-0">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="price_purchase">
                            Alış Fiyatı (₺)
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="price_purchase" name="price_purchase" type="number" step="0.01" min="0" value="0.00">
                    </div>
                    <div class="w-full md:w-1/2 px-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="price_sale">
                            Satış Fiyatı (₺)
                        </label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="price_sale" name="price_sale" type="number" step="0.01" min="0" value="0.00">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <button class="bg-brand hover:bg-brand-mid text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">
                        Ürünü Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
