<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$summary = $conn->query(
    'SELECT
        COUNT(*) AS total_products,
        SUM(is_active = 1) AS active_products,
        SUM(is_featured = 1) AS featured_products,
        SUM(discount_price IS NOT NULL AND discount_price > 0) AS promo_products
     FROM products'
)->fetch_assoc();

$bookingSummary = $conn->query(
    'SELECT
        COUNT(*) AS upcoming_bookings,
        COUNT(DISTINCT product_id) AS booked_products
     FROM product_bookings
     WHERE booked_date >= CURDATE()'
)->fetch_assoc();

$requestSummary = $conn->query(
    "SELECT
        COUNT(*) AS total_requests,
        SUM(status = 'new') AS new_requests
     FROM booking_requests"
)->fetch_assoc();

$nextBooking = $conn->query(
    'SELECT MIN(booked_date) AS next_booked_date
     FROM product_bookings
     WHERE booked_date >= CURDATE()'
)->fetch_assoc();

$upcomingBookings = $conn->query(
    'SELECT pb.booked_date, pb.customer_name, pb.note, p.id AS product_id, p.name AS product_name, c.name AS category_name
     FROM product_bookings pb
     INNER JOIN products p ON p.id = pb.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE pb.booked_date >= CURDATE()
     ORDER BY pb.booked_date ASC, p.name ASC
     LIMIT 8'
);

$bookingRequests = $conn->query(
    'SELECT br.*, COALESCE(bri.product_names, "") AS product_names
     FROM booking_requests br
     LEFT JOIN (
       SELECT booking_request_id, GROUP_CONCAT(product_name ORDER BY id ASC SEPARATOR ", ") AS product_names
       FROM booking_request_items
       GROUP BY booking_request_id
     ) bri ON bri.booking_request_id = br.id
     ORDER BY br.created_at DESC
     LIMIT 8'
);

$categoryStats = $conn->query(
    'SELECT c.name, COUNT(p.id) AS total_products
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY total_products DESC, c.name ASC'
);

$products = $conn->query(
    'SELECT p.*, c.name AS category_name,
            COALESCE(pb.booked_dates_total, 0) AS booked_dates_total,
            pb.next_booked_date
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN (
       SELECT product_id, COUNT(*) AS booked_dates_total, MIN(booked_date) AS next_booked_date
       FROM product_bookings
       WHERE booked_date >= CURDATE()
       GROUP BY product_id
     ) pb ON pb.product_id = p.id
     ORDER BY p.id DESC'
);

function format_admin_date(?string $date): string
{
    if (!$date) {
        return '-';
    }

    return date('d M Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - jly.projectbali</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css?v=admin-booking-20260608">
  <link rel="stylesheet" href="../css/admin.css?v=admin-booking-20260608">
</head>
<body class="admin-body">
  <header class="admin-topbar">
    <div>
      <span class="eyebrow">jly.projectbali</span>
      <h1>Dashboard Admin</h1>
    </div>
    <nav class="admin-actions">
      <a class="button button-outline" href="../katalog.php" target="_blank">Lihat Katalog</a>
      <a class="button button-primary" href="product-form.php">Tambah Produk</a>
      <a class="button button-outline" href="logout.php">Logout</a>
    </nav>
  </header>

  <main class="admin-shell">
    <section class="admin-dashboard-hero">
      <div>
        <span class="eyebrow">Ringkasan Operasional</span>
        <h2>Kontrol rental harian.</h2>
        <p class="admin-hero-copy">Pantau produk, jadwal booked, dan prioritas katalog dengan cepat.</p>
      </div>
      <div class="admin-next-booking">
        <span>Booking terdekat</span>
        <strong><?= e(format_admin_date($nextBooking['next_booked_date'] ?? null)) ?></strong>
      </div>
    </section>

    <section class="admin-stat-grid" aria-label="Ringkasan dashboard">
      <article class="admin-stat">
        <span>Total Produk</span>
        <strong><?= (int) ($summary['total_products'] ?? 0) ?></strong>
        <small>Semua item rental</small>
      </article>
      <article class="admin-stat">
        <span>Aktif di Katalog</span>
        <strong><?= (int) ($summary['active_products'] ?? 0) ?></strong>
        <small>Produk yang tampil ke user</small>
      </article>
      <article class="admin-stat">
        <span>Koleksi Beranda</span>
        <strong><?= (int) ($summary['featured_products'] ?? 0) ?></strong>
        <small>Item yang diprioritaskan</small>
      </article>
      <article class="admin-stat">
        <span>Request Baru</span>
        <strong><?= (int) ($requestSummary['new_requests'] ?? 0) ?></strong>
        <small><?= (int) ($requestSummary['total_requests'] ?? 0) ?> request booking tersimpan</small>
      </article>
    </section>

    <div class="admin-dashboard-grid">
      <section class="admin-panel admin-panel-wide">
        <div class="admin-panel-heading">
          <div>
            <span class="eyebrow">Request</span>
            <h2>Booking Masuk</h2>
          </div>
        </div>

        <div class="admin-booking-list">
          <?php if ($bookingRequests && $bookingRequests->num_rows > 0) : ?>
                <?php while ($request = $bookingRequests->fetch_assoc()) : ?>
              <article class="admin-booking-item admin-request-item">
                <time datetime="<?= e($request['event_date']) ?>"><?= e(format_admin_date($request['event_date'])) ?></time>
                <div>
                  <strong><?= e($request['customer_name']) ?></strong>
                  <span><?= e($request['product_names'] ?? 'Produk belum tercatat') ?></span>
                  <small><?= e($request['phone']) ?><?= !empty($request['event_location']) ? ' - ' . e($request['event_location']) : '' ?></small>
                </div>
                <span class="admin-status <?= $request['status'] === 'new' ? 'is-active' : 'is-muted' ?>">
                    <?= e($request['status']) ?>
                </span>
              </article>
                <?php endwhile; ?>
          <?php else : ?>
            <p class="admin-empty">Belum ada request booking dari katalog.</p>
          <?php endif; ?>
        </div>
      </section>

      <section class="admin-panel admin-panel-wide">
        <div class="admin-panel-heading">
          <div>
            <span class="eyebrow">Jadwal</span>
            <h2>Booked Terdekat</h2>
          </div>
        </div>

        <div class="admin-booking-list">
          <?php if ($upcomingBookings && $upcomingBookings->num_rows > 0) : ?>
                <?php while ($booking = $upcomingBookings->fetch_assoc()) : ?>
              <article class="admin-booking-item">
                <time datetime="<?= e($booking['booked_date']) ?>"><?= e(format_admin_date($booking['booked_date'])) ?></time>
                <div>
                  <strong><?= e($booking['product_name']) ?></strong>
                  <span><?= e($booking['category_name'] ?? 'Produk') ?></span>
                    <?php if (!empty($booking['customer_name']) || !empty($booking['note'])) : ?>
                    <small><?= e(trim(($booking['customer_name'] ?? '') . ' ' . ($booking['note'] ?? ''))) ?></small>
                    <?php endif; ?>
                </div>
                <a href="product-form.php?id=<?= (int) $booking['product_id'] ?>">Edit</a>
              </article>
                <?php endwhile; ?>
          <?php else : ?>
            <p class="admin-empty">Belum ada tanggal booked yang akan datang.</p>
          <?php endif; ?>
        </div>
      </section>

      <aside class="admin-panel admin-panel-compact">
        <div class="admin-panel-heading">
          <div>
            <span class="eyebrow">Kategori</span>
            <h2>Komposisi Produk</h2>
          </div>
        </div>

        <div class="admin-category-list">
          <?php if ($categoryStats && $categoryStats->num_rows > 0) : ?>
                <?php while ($category = $categoryStats->fetch_assoc()) : ?>
              <div>
                <span><?= e($category['name']) ?></span>
                <strong><?= (int) $category['total_products'] ?></strong>
              </div>
                <?php endwhile; ?>
          <?php else : ?>
            <p class="admin-empty">Kategori belum tersedia.</p>
          <?php endif; ?>
        </div>
      </aside>
    </div>

    <section class="admin-panel admin-panel-wide">
      <div class="admin-panel-heading">
        <div>
          <span class="eyebrow">Produk</span>
          <h2>Daftar Produk Rental</h2>
        </div>
        <a class="button button-outline" href="product-form.php">Tambah Produk</a>
      </div>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Produk</th>
              <th>Kategori</th>
              <th>Harga</th>
              <th>Status</th>
              <th>Booked</th>
              <th>Beranda</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($products && $products->num_rows > 0) : ?>
                <?php while ($product = $products->fetch_assoc()) : ?>
                <tr>
                  <td>
                    <strong><?= e($product['name']) ?></strong>
                    <span><?= e($product['size']) ?></span>
                  </td>
                  <td><?= e($product['category_name'] ?? '-') ?></td>
                  <td>
                    Rp <?= number_format((float) $product['price'], 0, ',', '.') ?>
                    <?php if (!empty($product['discount_price'])) : ?>
                      <span>Diskon: Rp <?= number_format((float) $product['discount_price'], 0, ',', '.') ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="admin-status <?= ((int) $product['is_active'] === 1) ? 'is-active' : 'is-muted' ?>">
                      <?= ((int) $product['is_active'] === 1) ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                  </td>
                  <td>
                    <?= (int) $product['booked_dates_total'] ?> tanggal
                    <span>Next: <?= e(format_admin_date($product['next_booked_date'] ?? null)) ?></span>
                  </td>
                  <td><?= ((int) $product['is_featured'] === 1) ? 'Koleksi Terpilih' : '-' ?></td>
                  <td class="admin-row-actions">
                    <a href="product-form.php?id=<?= (int) $product['id'] ?>">Edit</a>
                    <form method="post" action="delete-product.php" onsubmit="return confirm('Hapus produk ini?')">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                      <button type="submit">Hapus</button>
                    </form>
                  </td>
                </tr>
                <?php endwhile; ?>
            <?php else : ?>
              <tr>
                <td colspan="7">Belum ada produk. Tambahkan produk pertama dari tombol di atas.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
