<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

$categoryId = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT) ?: 0;
$search = trim((string) ($_GET['q'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'newest');
$sortOptions = [
    'newest' => 'p.id DESC',
    'price_asc' => 'COALESCE(p.discount_price, p.price) ASC, p.id DESC',
    'price_desc' => 'COALESCE(p.discount_price, p.price) DESC, p.id DESC',
    'name_asc' => 'p.name ASC, p.id DESC',
];
$orderBy = $sortOptions[$sort] ?? $sortOptions['newest'];

$query = 'SELECT p.*, c.name AS category_name, COALESCE(pb.booked_dates, "") AS booked_dates
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN (
            SELECT product_id, GROUP_CONCAT(booked_date ORDER BY booked_date ASC SEPARATOR ",") AS booked_dates
            FROM product_bookings
            WHERE booked_date >= CURDATE()
            GROUP BY product_id
          ) pb ON pb.product_id = p.id
          WHERE p.is_active = 1';
$types = '';
$params = [];

if ($categoryId > 0) {
    $query .= ' AND p.category_id = ?';
    $types .= 'i';
    $params[] = $categoryId;
}

if ($search !== '') {
    $query .= ' AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)';
    $likeSearch = '%' . $search . '%';
    $types .= 'sss';
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

$query .= " ORDER BY $orderBy";
$stmt = $conn->prepare($query);

if ($params) {
    $bindParams = [$types];
    foreach ($params as $index => $value) {
        $bindParams[] = &$params[$index];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
}

$stmt->execute();
$result = $stmt->get_result();
$productCount = $result ? $result->num_rows : 0;
$bookingStatus = (string) ($_GET['booking'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Katalog produk rental dekorasi pernikahan jly.projectbali.">
  <title>Katalog Produk - jly.projectbali</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css?v=booking-form-20260608">
</head>
<body>
  <header class="site-header">
    <div class="container">
      <a class="brand" href="index.php" aria-label="jly.projectbali beranda">
        <img src="img/jlypoject logo.png" alt="Logo jly.projectbali">
        <span>jly.projectbali</span>
      </a>

      <div class="header-actions">
        <nav class="site-nav" aria-label="Navigasi utama">
          <a href="index.php">Beranda</a>
          <a class="active" href="katalog.php">Katalog</a>
          <a href="cara-sewa.html">Cara Sewa</a>
          <a href="#kontak">Kontak</a>
        </nav>

        <form class="search-form" role="search">
          <label class="sr-only" for="site-search">Cari produk atau layanan</label>
          <span class="material-symbols-outlined search-icon" aria-hidden="true">search</span>
          <input id="site-search" type="search" autocomplete="off" placeholder="Cari..." aria-controls="search-results" aria-expanded="false">
          <div class="search-results" id="search-results" role="listbox"></div>
        </form>

        <button class="mobile-menu-toggle" type="button" aria-label="Buka menu navigasi" aria-expanded="false">
          <span class="material-symbols-outlined" aria-hidden="true">menu</span>
        </button>
      </div>
    </div>
  </header>

  <main>
    <?php if ($bookingStatus !== '') : ?>
      <div class="booking-alert <?= $bookingStatus === 'success' ? 'is-success' : 'is-error' ?>">
        <div class="container">
          <?php if ($bookingStatus === 'success') : ?>
            Request booking berhasil dikirim. Admin akan menghubungi Anda untuk konfirmasi.
          <?php elseif ($bookingStatus === 'unavailable') : ?>
            Salah satu produk sudah booked di tanggal tersebut. Silakan pilih tanggal atau produk lain.
          <?php else : ?>
            Request booking belum bisa diproses. Pastikan data dan produk sudah lengkap.
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <section class="catalog-hero">
      <div class="container catalog-hero-content">
        <div>
          <span class="eyebrow">Katalog Produk</span>
          <p>Pilih koleksi dekorasi untuk area penyambutan, galeri foto, dan sudut interaktif di hari bahagia Anda.</p>
        </div>
        <div class="catalog-summary" aria-label="Ringkasan katalog">
          <strong><?= str_pad((string) $productCount, 2, '0', STR_PAD_LEFT) ?></strong>
          <span>Koleksi rental siap dikurasi untuk acara Anda</span>
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container">
        <div class="catalog-grid">
          <?php if ($result && $productCount > 0) : ?>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <?php
                    $displayPrice = 'Rp ' . number_format((float) $row['price'], 0, ',', '.');
                    $hasDiscount = $row['discount_price'] !== null && (float) $row['discount_price'] > 0;
                    $finalPrice = $hasDiscount
                    ? 'Rp ' . number_format((float) $row['discount_price'], 0, ',', '.')
                    : $displayPrice;
                    $discountPercent = $hasDiscount
                    ? max(1, (int) round((((float) $row['price'] - (float) $row['discount_price']) / (float) $row['price']) * 100))
                    : 0;
                    $imageUrl = sanitize_image_url($row['image_url'] ?? '');
                    $bookedDates = array_values(array_filter(explode(',', (string) $row['booked_dates'])));
                    $bookedDatesJson = json_encode($bookedDates, JSON_UNESCAPED_SLASHES);
                    ?>
              <article
                class="product-card reveal <?= $hasDiscount ? 'has-promo' : '' ?>"
                data-product-id="<?= (int) $row['id'] ?>"
                data-search-title="<?= e($row['name']) ?>"
                data-search-category="<?= e($row['category_name'] ?? 'Produk') ?>"
                data-search-text="<?= e($row['description'] . ' ' . $row['size'] . ' ' . $row['material']) ?>"
                data-product-price="<?= e($finalPrice) ?> / Hari"
                data-product-booked-dates="<?= e($bookedDatesJson ?: '[]') ?>"
                data-product-description="<?= e($row['description']) ?>"
                data-product-size="<?= e($row['size']) ?>"
                data-product-material="<?= e($row['material']) ?>"
                data-product-flowers="<?= e($row['flowers']) ?>"
                data-product-bundle="<?= e($row['bundle_note'] ?? '') ?>"
              >
                    <?php if ($imageUrl !== '') : ?>
                  <div class="product-media">
                        <?php if ($hasDiscount) : ?>
                      <span class="discount-badge">
                        <span>Hemat</span>
                            <?= $discountPercent ?>%
                      </span>
                        <?php endif; ?>
                    <img src="<?= e($imageUrl) ?>" alt="<?= e($row['name']) ?>">
                  </div>
                    <?php else : ?>
                  <div class="product-media product-placeholder">
                        <?php if ($hasDiscount) : ?>
                      <span class="discount-badge">
                        <span>Hemat</span>
                            <?= $discountPercent ?>%
                      </span>
                        <?php endif; ?>
                    <span class="material-symbols-outlined" aria-hidden="true">photo_frame</span>
                  </div>
                    <?php endif; ?>

                <div class="product-content">
                  <span class="eyebrow"><?= e($row['category_name'] ?? 'Produk') ?></span>
                  <h2 class="card-title"><?= e($row['name']) ?></h2>
                  <p class="price-tag <?= $hasDiscount ? 'has-discount' : '' ?>">
                    <?php if ($hasDiscount) : ?>
                      <span class="price-original"><?= e($displayPrice) ?></span>
                    <?php endif; ?>
                    <span class="price-current"><strong><?= e($finalPrice) ?></strong> / Hari</span>
                    <?php if ($hasDiscount) : ?>
                      <span class="promo-caption">Harga promo terbatas</span>
                    <?php endif; ?>
                  </p>
                    <?php if (!empty($row['bundle_note'])) : ?>
                    <p class="bundle-note"><span>Bundling</span><?= e($row['bundle_note']) ?></p>
                    <?php endif; ?>
                  <p><?= e($row['description']) ?></p>
                  <div class="product-card-actions">
                    <button class="button button-outline" type="button" data-product-detail>Detail Produk</button>
                    <button class="button button-primary" type="button" data-cart-add>
                      <span class="material-symbols-outlined" aria-hidden="true">add_shopping_cart</span>
                      Tambah
                    </button>
                  </div>
                </div>
              </article>
                <?php endwhile; ?>
          <?php else : ?>
            <p>Belum ada produk di katalog.</p>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>

  <div class="product-modal" data-product-modal aria-hidden="true">
    <div class="product-modal-backdrop" data-product-close></div>
    <section class="product-modal-panel" role="dialog" aria-modal="true" aria-labelledby="product-modal-title">
      <button class="product-modal-close" type="button" data-product-close aria-label="Tutup detail produk">
        <span class="material-symbols-outlined" aria-hidden="true">close</span>
      </button>
      <div class="product-modal-media">
        <img data-modal-image src="" alt="">
      </div>
      <div class="product-modal-content">
        <span class="eyebrow" data-modal-category>Produk</span>
        <h2 id="product-modal-title" data-modal-title>Nama Produk</h2>
        <p class="product-modal-price" data-modal-price></p>
        <div class="product-modal-description">
          <h3>Deskripsi</h3>
          <p data-modal-description></p>
        </div>
        <dl class="product-specs modal-specs">
          <div>
            <dt>Ukuran</dt>
            <dd data-modal-size></dd>
          </div>
          <div>
            <dt>Material</dt>
            <dd data-modal-material></dd>
          </div>
          <div>
            <dt>Jumlah bunga/dekorasi</dt>
            <dd data-modal-flowers></dd>
          </div>
          <div data-modal-bundle-row>
            <dt>Bundling</dt>
            <dd data-modal-bundle></dd>
          </div>
        </dl>
        <section class="availability-calendar" data-availability-calendar>
          <div class="calendar-heading">
            <div>
              <h3>Ketersediaan Tanggal</h3>
              <p data-calendar-status>Pilih tanggal acara untuk cek produk ini.</p>
            </div>
            <div class="calendar-nav">
              <button type="button" data-calendar-prev aria-label="Bulan sebelumnya">
                <span class="material-symbols-outlined" aria-hidden="true">chevron_left</span>
              </button>
              <strong data-calendar-month></strong>
              <button type="button" data-calendar-next aria-label="Bulan berikutnya">
                <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
              </button>
            </div>
          </div>
          <div class="calendar-legend" aria-label="Legenda ketersediaan">
            <span><i class="is-available"></i>Tersedia</span>
            <span><i class="is-booked"></i>Sudah dipesan</span>
          </div>
          <div class="calendar-weekdays" aria-hidden="true">
            <span>Min</span>
            <span>Sen</span>
            <span>Sel</span>
            <span>Rab</span>
            <span>Kam</span>
            <span>Jum</span>
            <span>Sab</span>
          </div>
          <div class="calendar-grid" data-calendar-grid></div>
        </section>
        <div class="product-actions">
          <button class="button button-primary" type="button" data-modal-cart-add>Tambah ke Form Booking</button>
        </div>
      </div>
    </section>
  </div>

  <aside class="cart-panel" data-cart-panel aria-live="polite">
    <div class="cart-panel-header">
      <div>
        <span class="eyebrow">Keranjang Rental</span>
        <strong><span data-cart-count>0</span> produk dipilih</strong>
      </div>
      <button type="button" data-cart-clear>Bersihkan</button>
    </div>
    <div class="cart-items" data-cart-items></div>
    <form class="booking-form" method="post" action="booking-submit.php" data-booking-form>
      <?= csrf_field() ?>
      <input type="hidden" name="cart_json" data-cart-json value="[]">
      <label>
        Tanggal acara
        <input type="date" name="event_date" data-event-date required>
      </label>
      <label>
        Nama
        <input type="text" name="customer_name" autocomplete="name" required>
      </label>
      <label>
        WhatsApp
        <input type="tel" name="phone" autocomplete="tel" required>
      </label>
      <label>
        Email
        <input type="email" name="email" autocomplete="email">
      </label>
      <label>
        Lokasi acara
        <input type="text" name="event_location" autocomplete="street-address">
      </label>
      <label>
        Catatan
        <textarea name="note" rows="3" placeholder="Tema acara, jam setup, atau detail tambahan"></textarea>
      </label>
      <button class="button button-primary" type="submit" data-booking-submit>Kirim Request Booking</button>
    </form>
  </aside>

  <footer class="site-footer" id="kontak">
    <div class="container footer-main">
      <div>
        <a class="footer-brand" href="index.php">
          <img src="img/jlypoject logo.png" alt="Logo jly.projectbali">
          <span>jly.projectbali</span>
        </a>
        <p>Elevasi estetika pernikahan Anda dengan koleksi rental premium kami yang dikurasi untuk momen berharga di Bali.</p>
      </div>
      <div class="footer-links">
        <section>
          <h3>Tautan</h3>
          <ul>
            <li><a href="index.php">Beranda</a></li>
            <li><a href="katalog.php">Katalog</a></li>
            <li><a href="cara-sewa.html">Cara Sewa</a></li>
          </ul>
        </section>
      </div>
    </div>
    <div class="copyright">&copy; 2026 jly.projectbali. Abadi dalam Keanggunan.</div>
  </footer>

  <script src="js/script.js?v=booking-form-20260608"></script>
</body>
</html>
