<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
$pageTitle = "Premium Jackets";

// Fetch featured products
$stmt = $pdo->prepare("
  SELECT p.*, c.name as category_name 
  FROM products p JOIN categories c ON p.category_id = c.id 
  WHERE p.is_featured = 1 AND p.is_active = 1 
  ORDER BY p.created_at DESC LIMIT 8
");
$stmt->execute();
$featured = $stmt->fetchAll();

// Fetch categories
$cats = $pdo->query("SELECT * FROM categories WHERE is_active = 1")->fetchAll();

// Approved reviews
$reviews = $pdo->query("
  SELECT r.*, p.name as product_name 
  FROM reviews r JOIN products p ON r.product_id = p.id 
  WHERE r.is_approved = 1 ORDER BY r.created_at DESC LIMIT 6
")->fetchAll();

include 'includes/header.php';
?>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-eyebrow">// New Collection 2025</div>
    <h1 class="hero-title">
      <span class="line-accent">WEAR</span><br>
      THE<br>
      <span class="line-stroke">STREET</span>
    </h1>
    <p class="hero-desc">Premium jackets crafted for the bold. From classic varsity to sleek bombers — find your fit and own the block.</p>
    <div class="hero-cta">
      <a href="/joesfit/shop.php" class="btn btn-primary btn-lg">Shop Now →</a>
      <a href="/joesfit/shop.php?category=varsity-jackets" class="btn btn-outline btn-lg">Varsity Collection</a>
    </div>
  </div>
  <div class="hero-visual">
    <div style="width:100%;height:100%;background:linear-gradient(135deg,#111 0%,#2a2a2a 50%,#111 100%);display:flex;align-items:center;justify-content:center;min-height:600px">
      <div style="text-align:center;color:rgba(255,255,255,0.15)">
        <div style="font-family:'Bebas Neue',sans-serif;font-size:8rem;line-height:1;letter-spacing:5px">JOE'S<br>FIT</div>
        <div style="font-size:0.8rem;letter-spacing:6px;margin-top:1rem;opacity:0.5">PREMIUM JACKETS</div>
      </div>
    </div>
    <div class="hero-badge">
      UP TO 30% OFF
      <small>Selected Styles</small>
    </div>
  </div>
</section>

<!-- CATEGORIES STRIP -->
<div class="categories-strip">
  <div class="cat-chip active" data-category="all">All Styles</div>
  <?php foreach ($cats as $cat): ?>
    <a href="/joesfit/shop.php?category=<?= $cat['slug'] ?>" class="cat-chip"><?= htmlspecialchars($cat['name']) ?></a>
  <?php endforeach; ?>
</div>

<!-- FEATURED PRODUCTS -->
<section class="section">
  <div class="section-header">
    <div>
      <h2 class="section-title">FEATURED <span>DROPS</span></h2>
      <p class="section-sub">Handpicked styles our community is loving right now</p>
    </div>
    <a href="/joesfit/shop.php" class="btn btn-outline">View All →</a>
  </div>

  <div class="products-grid">
    <?php foreach ($featured as $p): ?>
      <?php $displayPrice = $p['sale_price'] ?: $p['price']; ?>
      <div class="product-card" data-category="<?= htmlspecialchars($p['category_name']) ?>">
        <a href="/joesfit/product.php?slug=<?= $p['slug'] ?>">
          <div class="product-img-wrap">
            <?php if ($p['image']): ?>
              <img src="/joesfit/uploads/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
            <?php else: ?>
              <div class="img-placeholder">
                <span style="font-size:3rem">🧥</span>
                <span><?= htmlspecialchars($p['name']) ?></span>
              </div>
            <?php endif; ?>
            <?php if ($p['sale_price']): ?>
              <span class="product-badge badge-sale">SALE</span>
            <?php elseif ((time() - strtotime($p['created_at'])) < 7 * 86400): ?>
              <span class="product-badge badge-new">NEW</span>
            <?php endif; ?>
            <div class="product-actions-overlay">
              <button class="btn btn-primary btn-sm add-to-cart"
                      data-id="<?= $p['id'] ?>"
                      data-name="<?= htmlspecialchars($p['name']) ?>"
                      data-price="<?= $displayPrice ?>"
                      data-image="<?= $p['image'] ? '/joesfit/uploads/products/' . $p['image'] : '' ?>"
                      onclick="event.preventDefault();event.stopPropagation();window.location='/joesfit/product.php?slug=<?= $p['slug'] ?>'">
                Quick Add
              </button>
            </div>
          </div>
        </a>
        <div class="product-info">
          <div class="product-category"><?= htmlspecialchars($p['category_name']) ?></div>
          <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
          <div class="product-price">
            <span class="price-current"><?= formatPrice($displayPrice) ?></span>
            <?php if ($p['sale_price']): ?>
              <span class="price-original"><?= formatPrice($p['price']) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($p['colors']): ?>
            <div class="product-colors">
              <?php foreach (array_slice(explode(',', $p['colors']), 0, 4) as $color): ?>
                <div class="color-dot" title="<?= trim($color) ?>" 
                     style="background:<?= strtolower(explode('/', trim($color))[0]) === 'black' ? '#111' : (strtolower(explode('/', trim($color))[0]) === 'white' ? '#f0f0f0' : (strtolower(explode('/', trim($color))[0]) === 'navy' ? '#001f5b' : (strtolower(explode('/', trim($color))[0]) === 'red' ? '#c42a14' : (strtolower(explode('/', trim($color))[0]) === 'gold' ? '#c9a84c' : (strtolower(explode('/', trim($color))[0]) === 'olive' ? '#6b7c43' : '#888'))))) ?>">
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- WHY CHOOSE US -->
<section class="section-sm" style="background:var(--bg-card);border-top:1px solid var(--border);border-bottom:1px solid var(--border)">
  <div class="features-grid" style="max-width:1200px;margin:0 auto;padding:0 5%">
    <div class="feature-card">
      <div class="feature-icon">🧵</div>
      <div class="feature-title">Premium Quality</div>
      <div class="feature-desc">Crafted from carefully selected materials. Every stitch tells a story of quality.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🚚</div>
      <div class="feature-title">Nationwide Delivery</div>
      <div class="feature-desc">Fast and reliable shipping to all Philippine provinces. Track in real time.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🔄</div>
      <div class="feature-title">Easy Returns</div>
      <div class="feature-desc">Not satisfied? Return within 7 days for a full exchange or refund.</div>
    </div>
    <div class="feature-card">
      <div class="feature-icon">🔒</div>
      <div class="feature-title">Secure Payment</div>
      <div class="feature-desc">GCash, Maya, Card, or COD — all transactions are safe and encrypted.</div>
    </div>
  </div>
</section>

<!-- REVIEWS -->
<?php if (!empty($reviews)): ?>
<section class="section">
  <div class="section-header">
    <div>
      <h2 class="section-title">WHAT THEY <span>SAY</span></h2>
      <p class="section-sub">Real reviews from verified customers</p>
    </div>
  </div>
  <div class="reviews-grid">
    <?php foreach ($reviews as $r): ?>
      <div class="review-card">
        <div class="review-stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></div>
        <?php if ($r['title']): ?>
          <div style="font-weight:700;margin-bottom:0.5rem;font-size:0.95rem"><?= htmlspecialchars($r['title']) ?></div>
        <?php endif; ?>
        <p class="review-text">"<?= htmlspecialchars($r['body'] ?? '') ?>"</p>
        <div class="review-author">
          <div class="review-avatar"><?= strtoupper(substr($r['customer_name'], 0, 1)) ?></div>
          <div>
            <div class="review-name"><?= htmlspecialchars($r['customer_name']) ?></div>
            <div class="review-product"><?= htmlspecialchars($r['product_name']) ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- BANNER CTA -->
<section style="padding:5rem 5%;background:var(--bg-dark);text-align:center;color:white">
  <div style="max-width:600px;margin:0 auto">
    <div style="font-family:var(--font-display);font-size:clamp(2.5rem,5vw,5rem);line-height:1;letter-spacing:2px;margin-bottom:1.5rem">
      FIND YOUR<br><span style="color:var(--accent)">SIGNATURE</span><br>LOOK
    </div>
    <p style="opacity:0.7;margin-bottom:2rem;font-size:1rem;line-height:1.6">
      From the courts to the streets — Joe's Fit jackets are built for those who stand out.
    </p>
    <a href="/joesfit/shop.php" class="btn btn-primary btn-lg">Explore All Styles →</a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
