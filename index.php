<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';

$featuredProducts = $conn->query(
    "SELECT p.*, c.name AS category_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.is_active = 1
       AND (p.is_featured = 1 OR (p.discount_price IS NOT NULL AND p.discount_price > 0))
     ORDER BY
       CASE WHEN p.discount_price IS NOT NULL AND p.discount_price > 0 THEN 0 ELSE 1 END,
       p.is_featured DESC,
       p.updated_at DESC
     LIMIT 6"
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Penyewaan mirror selfie, papan nama akrilik, dan stand foto elegan untuk pernikahan di Bali.">
  <title>jly.projectbali - Abadi dalam Keanggunan</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css?v=promo-2">
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
          <a class="active" href="index.php">Beranda</a>
          <a href="katalog.php">Katalog</a>
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

        <a class="button button-primary header-cta" href="https://wa.me/?text=Halo%20jly.projectbali%2C%20saya%20ingin%20konsultasi%20dekorasi%20pernikahan." target="_blank" rel="noopener">
          Hubungi Kami
        </a>
      </div>
    </div>
  </header>

  <main>
    <section class="hero" id="beranda">
      <img
        class="hero-image"
        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDl03RVS77Dgo-cMKH6PMzWDe9AmffwfnFCK8CykodQgjixdTzobU6s6h-EYkev5gIawgwsoJ7bSpJ4Dm7MhoZ_OZzkjKRgJrr8kjh2NittbCYU4RRolcmBPAB-Dd62NOXLUutpnDxN66e40Bwz8fEngs6jLgMFd4Kz4g5j22vQcexUWkxf0Oe49LjSVBtlgcuIf7EwnHkJo_2anGG_esgB-F2A7qFwUDPqx9eHyLoMCHC4Kk3aK996H99CoqcV7I6ZMqTcUXDj1u-p"
        alt="Ruang resepsi pernikahan mewah dengan chandelier dan dekorasi putih keemasan"
      >
      <div class="container hero-content">
        <div class="hero-copy">
          <h1>Abadikan Momen Indah di Hari Bahagia Anda</h1>
          <p>Penyewaan Mirror Selfie, Papan Nama Akrilik, dan Stand Foto Elegan untuk Pernikahan Impian di Bali.</p>
        </div>
        <div class="hero-actions">
          <a class="button button-primary" href="katalog.php">Lihat Katalog</a>
          <a class="button button-outline" href="cara-sewa.html">Cara Sewa</a>
        </div>
      </div>
    </section>

    <section class="section section-light" id="cara-sewa">
      <div class="container">
        <div class="section-heading reveal">
          <h2 class="section-title">Mengapa Memilih jly.projectbali?</h2>
        </div>

        <div class="feature-grid">
          <article class="feature reveal" data-search-title="Kualitas Premium" data-search-category="Keunggulan" data-search-text="kualitas premium koleksi dekorasi rental wedding pernikahan">
            <span class="feature-icon material-symbols-outlined" aria-hidden="true">workspace_premium</span>
            <h3>Kualitas Premium</h3>
            <p>Setiap koleksi kami dirawat dengan standar hotel bintang lima untuk memastikan kesempurnaan visual.</p>
          </article>
          <article class="feature reveal" data-search-title="Pengiriman Tepat Waktu" data-search-category="Keunggulan" data-search-text="pengiriman tepat waktu logistik acara pernikahan">
            <span class="feature-icon material-symbols-outlined" aria-hidden="true">schedule</span>
            <h3>Pengiriman Tepat Waktu</h3>
            <p>Logistik terukur menjamin semua kebutuhan Anda tiba sebelum acara dimulai tanpa kendala.</p>
          </article>
          <article class="feature reveal" data-search-title="Instalasi Profesional" data-search-category="Keunggulan" data-search-text="instalasi profesional pemasangan dekorasi wedding">
            <span class="feature-icon material-symbols-outlined" aria-hidden="true">engineering</span>
            <h3>Instalasi Profesional</h3>
            <p>Tim ahli kami menangani pemasangan dengan presisi tinggi agar estetika dekorasi tetap terjaga.</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section" id="katalog">
      <div class="container">
        <div class="section-heading split reveal">
          <div>
            <span class="eyebrow">Koleksi Terpilih</span>
            <h2 class="section-title">Elemen Estetik untuk Hari Spesial Anda</h2>
          </div>
          <a class="text-link" href="katalog.php">Lihat Semua Produk</a>
        </div>

        <div class="product-grid">
          <?php if ($featuredProducts && $featuredProducts->num_rows > 0): ?>
            <?php while ($row = $featuredProducts->fetch_assoc()): ?>
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
              ?>
              <article
                class="product-card reveal <?= $hasDiscount ? 'has-promo' : '' ?>"
                data-search-title="<?= e($row['name']) ?>"
                data-search-category="<?= e($row['category_name'] ?? 'Produk') ?>"
                data-search-text="<?= e($row['description'] . ' ' . $row['size'] . ' ' . $row['material']) ?>"
                data-product-price="<?= e($finalPrice) ?> / Hari"
                data-product-description="<?= e($row['description']) ?>"
                data-product-size="<?= e($row['size']) ?>"
                data-product-material="<?= e($row['material']) ?>"
                data-product-flowers="<?= e($row['flowers']) ?>"
                data-product-bundle="<?= e($row['bundle_note'] ?? '') ?>"
              >
                <?php if ($imageUrl !== ''): ?>
                  <div class="product-media">
                    <?php if ($hasDiscount): ?>
                      <span class="discount-badge">
                        <span>Hemat</span>
                        <?= $discountPercent ?>%
                      </span>
                    <?php endif; ?>
                    <img src="<?= e($imageUrl) ?>" alt="<?= e($row['name']) ?>">
                  </div>
                <?php else: ?>
                  <div class="product-media product-placeholder">
                    <?php if ($hasDiscount): ?>
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
                  <h3 class="card-title"><?= e($row['name']) ?></h3>
                  <p class="price-tag <?= $hasDiscount ? 'has-discount' : '' ?>">
                    <?php if ($hasDiscount): ?>
                      <span class="price-original"><?= e($displayPrice) ?></span>
                    <?php endif; ?>
                    <span class="price-current"><strong><?= e($finalPrice) ?></strong> / Hari</span>
                    <?php if ($hasDiscount): ?>
                      <span class="promo-caption">Harga promo terbatas</span>
                    <?php endif; ?>
                  </p>
                  <?php if (!empty($row['bundle_note'])): ?>
                    <p class="bundle-note"><span>Bundling</span><?= e($row['bundle_note']) ?></p>
                  <?php endif; ?>
                  <p><?= e($row['description']) ?></p>
                  <button class="button button-outline" type="button" data-product-detail>Detail Produk</button>
                </div>
              </article>
            <?php endwhile; ?>
          <?php else: ?>
            <p>Belum ada produk terpilih. Centang produk dari dashboard admin.</p>
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
      <div class="product-modal-media"><img data-modal-image src="" alt=""></div>
      <div class="product-modal-content">
        <span class="eyebrow" data-modal-category>Produk</span>
        <h2 id="product-modal-title" data-modal-title>Nama Produk</h2>
        <p class="product-modal-price" data-modal-price></p>
        <div class="product-modal-description">
          <h3>Deskripsi</h3>
          <p data-modal-description></p>
        </div>
        <dl class="product-specs modal-specs">
          <div><dt>Ukuran</dt><dd data-modal-size></dd></div>
          <div><dt>Material</dt><dd data-modal-material></dd></div>
          <div><dt>Jumlah bunga/dekorasi</dt><dd data-modal-flowers></dd></div>
          <div data-modal-bundle-row><dt>Bundling</dt><dd data-modal-bundle></dd></div>
        </dl>
        <div class="product-actions">
          <a class="button button-primary" data-modal-availability href="#" target="_blank" rel="noopener">Cek Ketersediaan</a>
          <a class="button button-outline" data-modal-order href="#" target="_blank" rel="noopener">Pesan Sekarang</a>
        </div>
      </div>
    </section>
  </div>

  <footer class="site-footer" id="kontak">
    <div class="container footer-main">
      <div>
        <a class="footer-brand" href="index.php">
          <img src="img/jlypoject logo.png" alt="Logo jly.projectbali">
          <span>jly.projectbali</span>
        </a>
        <p>Elevasi estetika pernikahan Anda dengan koleksi rental premium kami yang dikurasi dengan penuh cinta untuk momen berharga di Bali.</p>
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
        <section>
          <h3>Social</h3>
          <ul>
            <li><a href="https://www.instagram.com/jly.projectbali">Instagram</a></li>
            <li><a href="https://wa.me/?text=Halo%20jly.projectbali%2C%20saya%20ingin%20bertanya%20tentang%20rental%20dekorasi%20pernikahan." target="_blank" rel="noopener">WhatsApp</a></li>
          </ul>
        </section>
      </div>
    </div>
    <div class="copyright">&copy; 2026 jly.projectbali. Abadi dalam Keanggunan.</div>
  </footer>

  <script src="js/script.js"></script>
</body>
</html>
