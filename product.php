<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$slug = sanitize($_GET['slug'] ?? '');
if (!$slug) { header('Location: /joesfit/shop.php'); exit; }

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND p.is_active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();
if (!$product) { header('Location: /joesfit/shop.php'); exit; }

$pageTitle = $product['name'];
$displayPrice = $product['sale_price'] ?: $product['price'];

// Reviews
$reviews = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? AND is_approved = 1 ORDER BY created_at DESC");
$reviews->execute([$product['id']]);
$reviews = $reviews->fetchAll();

$avgRating = !empty($reviews) ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

// Can review (logged in + completed order)
$canReview = false;
$reviewedOrderId = null;
if (isLoggedIn()) {
    $rStmt = $pdo->prepare("
        SELECT o.id FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.customer_id = ? AND oi.product_id = ? AND o.status = 'delivered'
        AND o.id NOT IN (SELECT order_id FROM reviews WHERE product_id = ? AND customer_id = ?)
        LIMIT 1
    ");
    $rStmt->execute([$_SESSION['customer_id'], $product['id'], $product['id'], $_SESSION['customer_id']]);
    $reviewOrder = $rStmt->fetch();
    if ($reviewOrder) { $canReview = true; $reviewedOrderId = $reviewOrder['id']; }
}

// Related products
$related = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 LIMIT 4");
$related->execute([$product['category_id'], $product['id']]);
$related = $related->fetchAll();

$sizes = $product['sizes'] ? explode(',', $product['sizes']) : [];
$colors = $product['colors'] ? explode(',', $product['colors']) : [];
$images = $product['images'] ? json_decode($product['images'], true) : [];

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && $canReview) {
    $rating = min(5, max(1, (int)$_POST['rating']));
    $title  = sanitize($_POST['title'] ?? '');
    $body   = sanitize($_POST['body'] ?? '');
    $stmt2  = $pdo->prepare("INSERT INTO reviews (product_id, order_id, customer_id, customer_name, rating, title, body) VALUES (?,?,?,?,?,?,?)");
    $stmt2->execute([$product['id'], $reviewedOrderId, $_SESSION['customer_id'], getCustomer()['name'], $rating, $title, $body]);
    header("Location: /joesfit/product.php?slug=$slug&reviewed=1");
    exit;
}

include 'includes/header.php';
?>

<div class="section">
  <!-- Breadcrumb -->
  <div style="font-size:0.8rem;color:var(--text-muted);margin-bottom:2rem;display:flex;gap:0.5rem;align-items:center">
    <a href="/joesfit/">Home</a> › <a href="/joesfit/shop.php">Shop</a> › 
    <a href="/joesfit/shop.php?category=<?= $product['category_slug'] ?>"><?= htmlspecialchars($product['category_name']) ?></a> › 
    <span style="color:var(--text)"><?= htmlspecialchars($product['name']) ?></span>
  </div>

  <div class="product-detail-grid">
    <!-- GALLERY -->
    <div class="product-gallery">
      <div class="gallery-main">
        <?php if ($product['image']): ?>
          <img id="galleryMain" src="/joesfit/uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
        <?php else: ?>
          <div class="img-placeholder" style="height:100%;min-height:400px">
            <span style="font-size:5rem">🧥</span>
            <span><?= htmlspecialchars($product['name']) ?></span>
          </div>
        <?php endif; ?>
      </div>
      <?php if (!empty($images)): ?>
        <div class="gallery-thumbs">
          <?php if ($product['image']): ?>
            <div class="gallery-thumb active">
              <img src="/joesfit/uploads/products/<?= htmlspecialchars($product['image']) ?>" alt="">
            </div>
          <?php endif; ?>
          <?php foreach ($images as $img): ?>
            <div class="gallery-thumb">
              <img src="/joesfit/uploads/products/<?= htmlspecialchars($img) ?>" alt="">
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- PRODUCT INFO -->
    <div>
      <div style="font-size:0.75rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--accent);margin-bottom:0.5rem">
        <?= htmlspecialchars($product['category_name']) ?>
      </div>
      <h1 style="font-family:var(--font-display);font-size:clamp(2rem,4vw,3.5rem);letter-spacing:1px;line-height:1;margin-bottom:1rem">
        <?= strtoupper(htmlspecialchars($product['name'])) ?>
      </h1>

      <!-- Rating summary -->
      <?php if (!empty($reviews)): ?>
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem">
          <span style="color:var(--gold);font-size:1rem"><?= str_repeat('★', round($avgRating)) . str_repeat('☆', 5-round($avgRating)) ?></span>
          <span style="font-size:0.85rem;color:var(--text-muted)">(<?= count($reviews) ?> reviews)</span>
        </div>
      <?php endif; ?>

      <!-- Price -->
      <div style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem">
        <span style="font-family:var(--font-display);font-size:2.5rem;color:var(--accent);letter-spacing:1px">
          <?= formatPrice($displayPrice) ?>
        </span>
        <?php if ($product['sale_price']): ?>
          <span style="font-size:1.2rem;color:var(--text-light);text-decoration:line-through"><?= formatPrice($product['price']) ?></span>
          <span style="background:var(--accent);color:white;padding:0.2rem 0.6rem;border-radius:4px;font-size:0.75rem;font-weight:700">
            -<?= round((($product['price'] - $product['sale_price']) / $product['price']) * 100) ?>%
          </span>
        <?php endif; ?>
      </div>

      <!-- Description -->
      <p style="color:var(--text-muted);line-height:1.7;margin-bottom:2rem;font-size:0.95rem">
        <?= htmlspecialchars($product['description'] ?? '') ?>
      </p>

      <!-- Sizes -->
      <?php if (!empty($sizes)): ?>
        <div style="margin-bottom:1.5rem">
          <div style="font-size:0.8rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.7rem">
            Select Size
          </div>
          <div class="product-sizes">
            <?php foreach ($sizes as $s): ?>
              <button class="size-btn" data-size="<?= trim($s) ?>"><?= trim($s) ?></button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Colors -->
      <?php if (!empty($colors)): ?>
        <div style="margin-bottom:2rem">
          <div style="font-size:0.8rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:0.7rem">
            Select Color
          </div>
          <div class="color-select">
            <?php foreach ($colors as $c): ?>
              <div class="color-option" data-color="<?= trim($c) ?>"><?= trim($c) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Stock info -->
      <div style="font-size:0.85rem;margin-bottom:1.5rem">
        <?php if ($product['stock'] > 10): ?>
          <span style="color:#34D399">✓ In Stock (<?= $product['stock'] ?> available)</span>
        <?php elseif ($product['stock'] > 0): ?>
          <span style="color:var(--accent)">⚡ Low Stock — only <?= $product['stock'] ?> left!</span>
        <?php else: ?>
          <span style="color:#999">✕ Out of Stock</span>
        <?php endif; ?>
      </div>

      <!-- Quantity + Add to Cart -->
      <div style="display:flex;gap:1rem;margin-bottom:2rem;align-items:center">
        <div style="display:flex;align-items:center;gap:0.5rem;border:1.5px solid var(--border);border-radius:8px;padding:0.3rem">
          <button class="qty-btn" onclick="changeQty(-1)" style="width:36px;height:36px">−</button>
          <span id="detailQty" style="min-width:30px;text-align:center;font-weight:700">1</span>
          <button class="qty-btn" onclick="changeQty(1)" style="width:36px;height:36px">+</button>
        </div>
        <button class="btn btn-primary btn-lg" style="flex:1" 
                onclick="addFromDetail(<?= $product['id'] ?>, '<?= addslashes($product['name']) ?>', <?= $displayPrice ?>, '<?= $product['image'] ? '/joesfit/uploads/products/' . $product['image'] : '' ?>')"
                <?= $product['stock'] == 0 ? 'disabled' : '' ?>>
          <?= $product['stock'] == 0 ? '✕ Out of Stock' : '🛒 Add to Cart' ?>
        </button>
      </div>

      <!-- Meta -->
      <div style="font-size:0.82rem;color:var(--text-light);border-top:1px solid var(--border);padding-top:1rem;display:flex;flex-direction:column;gap:0.3rem">
        <div>SKU: <span style="font-family:var(--font-mono)"><?= htmlspecialchars($product['sku'] ?? 'N/A') ?></span></div>
        <div>Category: <a href="/joesfit/shop.php?category=<?= $product['category_slug'] ?>" style="color:var(--accent)"><?= htmlspecialchars($product['category_name']) ?></a></div>
      </div>
    </div>
  </div>

  <!-- TABS -->
  <div class="tabs">
    <div class="tab-nav">
      <button class="tab-btn active" data-tab="reviews">Reviews (<?= count($reviews) ?>)</button>
      <button class="tab-btn" data-tab="details">Details & Care</button>
      <button class="tab-btn" data-tab="sizing">Size Guide</button>
    </div>

    <div id="tab-reviews" class="tab-content active">
      <?php if (isset($_GET['reviewed'])): ?>
        <div style="background:rgba(52,211,153,0.1);border:1px solid #34D399;border-radius:8px;padding:1rem;margin-bottom:1.5rem;color:#059669">
          ✅ Thank you! Your review has been submitted for approval.
        </div>
      <?php endif; ?>

      <?php if ($canReview): ?>
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;margin-bottom:2rem">
          <h3 style="font-weight:700;margin-bottom:1.5rem">Write a Review</h3>
          <form method="POST">
            <input type="hidden" name="submit_review" value="1">
            <div class="form-group">
              <label class="form-label">Your Rating</label>
              <div class="star-rating">
                <?php for ($i=1; $i<=5; $i++): ?>
                  <span class="star" data-val="<?= $i ?>">★</span>
                <?php endfor; ?>
              </div>
              <input type="hidden" name="rating" id="ratingValue" value="5">
            </div>
            <div class="form-group">
              <label class="form-label">Review Title</label>
              <input type="text" name="title" class="form-control" placeholder="Summarize your experience">
            </div>
            <div class="form-group">
              <label class="form-label">Your Review</label>
              <textarea name="body" class="form-control" rows="4" placeholder="Tell others what you think..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Review</button>
          </form>
        </div>
      <?php endif; ?>

      <?php if (empty($reviews)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:3rem">No reviews yet. Be the first to review this product!</p>
      <?php else: ?>
        <div style="display:flex;gap:2rem;align-items:center;margin-bottom:2rem;padding-bottom:2rem;border-bottom:1px solid var(--border)">
          <div style="text-align:center">
            <div style="font-family:var(--font-display);font-size:4rem;line-height:1;color:var(--accent)"><?= number_format($avgRating, 1) ?></div>
            <div style="color:var(--gold);font-size:1.3rem"><?= str_repeat('★', round($avgRating)) ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted)"><?= count($reviews) ?> reviews</div>
          </div>
        </div>
        <div class="reviews-grid">
          <?php foreach ($reviews as $r): ?>
            <div class="review-card">
              <div class="review-stars"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5-$r['rating']) ?></div>
              <?php if ($r['title']): ?><div style="font-weight:700;margin-bottom:0.5rem"><?= htmlspecialchars($r['title']) ?></div><?php endif; ?>
              <?php if ($r['body']): ?><p class="review-text">"<?= htmlspecialchars($r['body']) ?>"</p><?php endif; ?>
              <div class="review-author">
                <div class="review-avatar"><?= strtoupper(substr($r['customer_name'], 0, 1)) ?></div>
                <div>
                  <div class="review-name"><?= htmlspecialchars($r['customer_name']) ?></div>
                  <div class="review-product"><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div id="tab-details" class="tab-content">
      <div style="max-width:600px;line-height:1.8;color:var(--text-muted)">
        <h4 style="color:var(--text);margin-bottom:1rem">Product Details</h4>
        <p><?= htmlspecialchars($product['description'] ?? 'Premium quality jacket crafted with care.') ?></p>
        <h4 style="color:var(--text);margin:1.5rem 0 0.5rem">Care Instructions</h4>
        <ul style="padding-left:1.2rem">
          <li>Machine wash cold with similar colors</li>
          <li>Do not bleach</li>
          <li>Tumble dry low</li>
          <li>Iron on low heat if needed</li>
          <li>Do not dry clean</li>
        </ul>
      </div>
    </div>

    <div id="tab-sizing" class="tab-content">
      <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:0.88rem">
          <tr style="background:var(--bg-dark);color:white">
            <th style="padding:0.8rem;text-align:left">Size</th>
            <th style="padding:0.8rem">Chest (cm)</th>
            <th style="padding:0.8rem">Shoulder (cm)</th>
            <th style="padding:0.8rem">Length (cm)</th>
          </tr>
          <?php foreach (['XS'=>[84,40,63],'S'=>[88,42,65],'M'=>[96,44,67],'L'=>[104,46,69],'XL'=>[112,48,71],'XXL'=>[120,50,73],'3XL'=>[128,52,75]] as $s => $m): ?>
          <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:0.8rem;font-weight:700"><?= $s ?></td>
            <td style="padding:0.8rem;text-align:center"><?= $m[0] ?>–<?= $m[0]+4 ?></td>
            <td style="padding:0.8rem;text-align:center"><?= $m[1] ?></td>
            <td style="padding:0.8rem;text-align:center"><?= $m[2] ?></td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </div>

  <!-- RELATED PRODUCTS -->
  <?php if (!empty($related)): ?>
    <div style="margin-top:5rem">
      <h2 style="font-family:var(--font-display);font-size:2.5rem;letter-spacing:1px;margin-bottom:2rem">
        YOU MAY ALSO <span style="color:var(--accent)">LIKE</span>
      </h2>
      <div class="products-grid">
        <?php foreach ($related as $rp): ?>
          <?php $rpPrice = $rp['sale_price'] ?: $rp['price']; ?>
          <div class="product-card">
            <a href="/joesfit/product.php?slug=<?= $rp['slug'] ?>">
              <div class="product-img-wrap">
                <?php if ($rp['image']): ?>
                  <img src="/joesfit/uploads/products/<?= htmlspecialchars($rp['image']) ?>" alt="<?= htmlspecialchars($rp['name']) ?>" loading="lazy">
                <?php else: ?>
                  <div class="img-placeholder"><span style="font-size:3rem">🧥</span></div>
                <?php endif; ?>
              </div>
            </a>
            <div class="product-info">
              <div class="product-name"><a href="/joesfit/product.php?slug=<?= $rp['slug'] ?>"><?= htmlspecialchars($rp['name']) ?></a></div>
              <div class="product-price">
                <span class="price-current"><?= formatPrice($rpPrice) ?></span>
                <?php if ($rp['sale_price']): ?><span class="price-original"><?= formatPrice($rp['price']) ?></span><?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
let detailQty = 1;
function changeQty(d) {
  detailQty = Math.max(1, detailQty + d);
  document.getElementById('detailQty').textContent = detailQty;
}
function addFromDetail(id, name, price, image) {
  const size  = document.querySelector('.size-btn.selected')?.dataset.size;
  const color = document.querySelector('.color-option.selected')?.dataset.color;
  if (!size || !color) {
    window.showToast('Please select size and color', 'error');
    return;
  }
  fetch('/joesfit/api/cart.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `action=add&product_id=${id}&name=${encodeURIComponent(name)}&price=${price}&image=${encodeURIComponent(image)}&size=${encodeURIComponent(size)}&color=${encodeURIComponent(color)}&quantity=${detailQty}`
  }).then(r=>r.json()).then(d=>{
    if (d.success) {
      window.showToast('Added to cart!', 'success');
      document.querySelectorAll('.cart-badge').forEach(b => b.textContent = d.count);
    }
  });
}
</script>

<?php include 'includes/footer.php'; ?>
