<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$error = '';

$product = [
    'category_id' => '',
    'name' => '',
    'description' => '',
    'price' => '',
    'discount_price' => '',
    'size' => '',
    'material' => '',
    'flowers' => '',
    'bundle_note' => '',
    'image_url' => '',
    'is_active' => '1',
    'is_featured' => '0',
];
$bookingDatesText = '';

if ($isEdit) {
    $stmt = $conn->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();

    if (!$existing) {
        http_response_code(404);
        exit('Produk tidak ditemukan.');
    }

    $product = array_merge($product, $existing);

    $stmt = $conn->prepare('SELECT booked_date FROM product_bookings WHERE product_id = ? ORDER BY booked_date ASC');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $bookedDates = [];
    $bookings = $stmt->get_result();
    while ($booking = $bookings->fetch_assoc()) {
        $bookedDates[] = $booking['booked_date'];
    }
    $bookingDatesText = implode("\n", $bookedDates);
}

$categories = $conn->query('SELECT id, name FROM categories ORDER BY name ASC');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $discountPrice = trim($_POST['discount_price'] ?? '');
    $discountPriceValue = $discountPrice === '' ? null : (float) $discountPrice;
    $size = trim($_POST['size'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $flowers = trim($_POST['flowers'] ?? '');
    $bundleNote = trim($_POST['bundle_note'] ?? '');
    $imageUrl = sanitize_image_url($_POST['image_url'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $bookingDatesText = trim((string) ($_POST['booking_dates'] ?? ''));
    $bookingDateInputs = preg_split('/[\s,;]+/', $bookingDatesText, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $bookingDates = [];

    foreach ($bookingDateInputs as $bookingDateInput) {
        $bookingDate = DateTime::createFromFormat('Y-m-d', $bookingDateInput);
        if (!$bookingDate || $bookingDate->format('Y-m-d') !== $bookingDateInput) {
            $error = 'Tanggal booked wajib memakai format YYYY-MM-DD, contoh 2026-06-14.';
            break;
        }

        $bookingDates[$bookingDateInput] = true;
    }

    if ($error === '' && ($categoryId <= 0 || $name === '' || $description === '' || $price <= 0)) {
        $error = 'Kategori, nama, deskripsi, dan harga wajib diisi.';
    } elseif ($error === '' && $isEdit) {
        $stmt = $conn->prepare(
            'UPDATE products
             SET category_id = ?, name = ?, description = ?, price = ?, discount_price = ?,
                 size = ?, material = ?, flowers = ?, bundle_note = ?, image_url = ?, is_active = ?, is_featured = ?
             WHERE id = ?'
        );
        $stmt->bind_param(
            'issddsssssiii',
            $categoryId,
            $name,
            $description,
            $price,
            $discountPriceValue,
            $size,
            $material,
            $flowers,
            $bundleNote,
            $imageUrl,
            $isActive,
            $isFeatured,
            $id
        );
        $stmt->execute();
        $savedProductId = $id;
    } elseif ($error === '') {
        $stmt = $conn->prepare(
            'INSERT INTO products
                (category_id, name, description, price, discount_price, size, material, flowers, bundle_note, image_url, is_active, is_featured)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'issddsssssii',
            $categoryId,
            $name,
            $description,
            $price,
            $discountPriceValue,
            $size,
            $material,
            $flowers,
            $bundleNote,
            $imageUrl,
            $isActive,
            $isFeatured
        );
        $stmt->execute();
        $savedProductId = (int) $conn->insert_id;
    }

    if ($error === '') {
        $stmt = $conn->prepare('DELETE FROM product_bookings WHERE product_id = ?');
        $stmt->bind_param('i', $savedProductId);
        $stmt->execute();

        if ($bookingDates) {
            $stmt = $conn->prepare('INSERT IGNORE INTO product_bookings (product_id, booked_date) VALUES (?, ?)');
            foreach (array_keys($bookingDates) as $bookingDate) {
                $stmt->bind_param('is', $savedProductId, $bookingDate);
                $stmt->execute();
            }
        }

        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $isEdit ? 'Edit' : 'Tambah' ?> Produk - jly.projectbali</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css?v=admin-font-20260608">
  <link rel="stylesheet" href="../css/admin.css?v=admin-font-20260608">
</head>
<body class="admin-body">
  <header class="admin-topbar">
    <div>
      <span class="eyebrow">Produk</span>
      <h1><?= $isEdit ? 'Edit Produk' : 'Tambah Produk' ?></h1>
    </div>
    <nav class="admin-actions">
      <a class="button button-outline" href="index.php">Kembali</a>
    </nav>
  </header>

  <main class="admin-shell">
    <section class="admin-panel">
      <?php if ($error) : ?>
        <p class="admin-alert"><?= e($error) ?></p>
      <?php endif; ?>

      <form method="post" class="admin-form admin-form-grid">
        <?= csrf_field() ?>
        <label>
          Kategori
          <select name="category_id" required>
            <option value="">Pilih kategori</option>
            <?php if ($categories) : ?>
                <?php while ($category = $categories->fetch_assoc()) : ?>
                <option value="<?= (int) $category['id'] ?>" <?= ((int) $product['category_id'] === (int) $category['id']) ? 'selected' : '' ?>>
                    <?= e($category['name']) ?>
                </option>
                <?php endwhile; ?>
            <?php endif; ?>
          </select>
        </label>

        <label>
          Nama Produk
          <input type="text" name="name" value="<?= e($product['name']) ?>" required>
        </label>

        <label>
          Harga
          <input type="number" name="price" min="0" step="1000" value="<?= e((string) $product['price']) ?>" required>
        </label>

        <label>
          Harga Diskon
          <input type="number" name="discount_price" min="0" step="1000" value="<?= e((string) $product['discount_price']) ?>">
        </label>

        <label class="admin-full">
          Deskripsi
          <textarea name="description" rows="4" required><?= e($product['description']) ?></textarea>
        </label>

        <label>
          Ukuran
          <input type="text" name="size" value="<?= e($product['size']) ?>">
        </label>

        <label>
          Material
          <input type="text" name="material" value="<?= e($product['material']) ?>">
        </label>

        <label>
          Bunga/Dekorasi
          <input type="text" name="flowers" value="<?= e($product['flowers']) ?>">
        </label>

        <label>
          Info Bundling
          <input type="text" name="bundle_note" value="<?= e($product['bundle_note']) ?>" placeholder="Kosongkan jika produk tidak bisa bundling">
        </label>

        <label>
          URL Gambar
          <input type="url" name="image_url" value="<?= e($product['image_url']) ?>">
        </label>

        <label class="admin-full">
          Tanggal Sudah Dipesan
          <textarea name="booking_dates" rows="4" placeholder="2026-06-14&#10;2026-06-21"><?= e($bookingDatesText) ?></textarea>
        </label>

        <label class="admin-check">
          <input type="checkbox" name="is_active" <?= ((int) $product['is_active'] === 1) ? 'checked' : '' ?>>
          Tampilkan di katalog
        </label>

        <label class="admin-check">
          <input type="checkbox" name="is_featured" <?= ((int) $product['is_featured'] === 1) ? 'checked' : '' ?>>
          Tampilkan di Koleksi Terpilih beranda
        </label>

        <div class="admin-full admin-submit">
          <button class="button button-primary" type="submit">Simpan Produk</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
