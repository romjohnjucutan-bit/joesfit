// Joe's Fit - Main JS

document.addEventListener('DOMContentLoaded', () => {

  // ===== DARK MODE =====
  const darkToggle = document.getElementById('darkToggle');
  const savedTheme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);

  if (darkToggle) {
    darkToggle.addEventListener('click', () => {
      const cur = document.documentElement.getAttribute('data-theme');
      const next = cur === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('theme', next);
    });
  }

  // ===== NAVBAR SCROLL =====
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 20);
    });
  }

  // ===== CART SIDEBAR =====
  const cartOverlay  = document.getElementById('cartOverlay');
  const cartSidebar  = document.getElementById('cartSidebar');
  const cartOpenBtns = document.querySelectorAll('[data-cart-open]');
  const cartCloseBtn = document.getElementById('cartClose');

  function openCart()  { cartOverlay?.classList.add('open'); cartSidebar?.classList.add('open'); }
  function closeCart() { cartOverlay?.classList.remove('open'); cartSidebar?.classList.remove('open'); }

  cartOpenBtns.forEach(b => b.addEventListener('click', openCart));
  cartCloseBtn?.addEventListener('click', closeCart);
  cartOverlay?.addEventListener('click', e => { if (e.target === cartOverlay) closeCart(); });

  // ===== CHECKOUT MODAL =====
  const checkoutModal   = document.getElementById('checkoutModal');
  const checkoutOpenBtn = document.getElementById('checkoutBtn');
  const checkoutCloseBtn = document.getElementById('checkoutClose');

  checkoutOpenBtn?.addEventListener('click', () => {
    closeCart();
    setTimeout(() => checkoutModal?.classList.add('open'), 200);
  });
  checkoutCloseBtn?.addEventListener('click', () => checkoutModal?.classList.remove('open'));
  checkoutModal?.addEventListener('click', e => { if (e.target === checkoutModal) checkoutModal.classList.remove('open'); });

  // Payment option selection
  document.querySelectorAll('.payment-option').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      document.getElementById('paymentMethod').value = opt.dataset.value;
    });
  });

  // Delivery option selection
  document.querySelectorAll('.delivery-option').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.delivery-option').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      document.getElementById('deliveryMethod').value = opt.dataset.value;
      document.getElementById('shippingFee').value = opt.dataset.fee;
      updateOrderSummary();
    });
  });

  // ===== CART ACTIONS =====
  document.addEventListener('click', e => {
    if (e.target.closest('.qty-btn')) {
      const btn  = e.target.closest('.qty-btn');
      const key  = btn.dataset.key;
      const dir  = btn.dataset.dir;
      const num  = btn.closest('.cart-qty').querySelector('.qty-num');
      let qty    = parseInt(num.textContent);
      qty        = dir === 'up' ? qty + 1 : qty - 1;
      if (qty < 1) return;
      updateCart(key, qty);
    }
    if (e.target.closest('.cart-remove')) {
      const key = e.target.closest('.cart-remove').dataset.key;
      removeFromCart(key);
    }
    if (e.target.closest('.add-to-cart')) {
      const btn = e.target.closest('.add-to-cart');
      addToCartAction(btn.dataset.id, btn.dataset.name, btn.dataset.price, btn.dataset.image);
    }
  });

  function updateCart(key, qty) {
    fetch('/joesfit/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=update&key=${encodeURIComponent(key)}&qty=${qty}`
    }).then(r => r.json()).then(d => {
      if (d.success) refreshCart();
    });
  }

  function removeFromCart(key) {
    fetch('/joesfit/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=remove&key=${encodeURIComponent(key)}`
    }).then(r => r.json()).then(d => {
      if (d.success) refreshCart();
    });
  }

  function addToCartAction(id, name, price, image, size = '', color = '') {
    // Check if product detail page has selections
    const selSize = document.querySelector('.size-btn.selected')?.dataset.size || size;
    const selColor = document.querySelector('.color-option.selected')?.dataset.color || color;

    if (!selSize || !selColor) {
      showToast('Please select size and color', 'error');
      return;
    }

    fetch('/joesfit/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=add&product_id=${id}&name=${encodeURIComponent(name)}&price=${price}&image=${encodeURIComponent(image)}&size=${encodeURIComponent(selSize)}&color=${encodeURIComponent(selColor)}&quantity=1`
    }).then(r => r.json()).then(d => {
      if (d.success) {
        refreshCart();
        showToast('Added to cart!', 'success');
        openCart();
      }
    });
  }

  window.addToCartAction = addToCartAction;

  function refreshCart() {
    fetch('/joesfit/api/cart.php?action=get')
      .then(r => r.json())
      .then(d => {
        const items = d.cart || {};
        const count = Object.values(items).reduce((a, i) => a + i.quantity, 0);
        const total = Object.values(items).reduce((a, i) => a + i.price * i.quantity, 0);

        document.querySelectorAll('.cart-badge').forEach(b => b.textContent = count);
        document.querySelectorAll('.cart-count-text').forEach(b => b.textContent = count);

        const container = document.getElementById('cartItems');
        if (container) {
          if (!count) {
            container.innerHTML = '<div class="cart-empty"><div class="cart-empty-icon">🛒</div><p>Your cart is empty</p></div>';
          } else {
            container.innerHTML = Object.entries(items).map(([key, item]) => `
              <div class="cart-item">
                <div class="cart-item-img">
                  ${item.image ? `<img src="${item.image}" alt="${item.name}">` : '<div class="img-placeholder"><span>👗</span></div>'}
                </div>
                <div class="cart-item-info">
                  <div class="cart-item-name">${item.name}</div>
                  <div class="cart-item-meta">${item.size} · ${item.color}</div>
                  <div class="cart-qty">
                    <button class="qty-btn" data-key="${key}" data-dir="down">−</button>
                    <span class="qty-num">${item.quantity}</span>
                    <button class="qty-btn" data-key="${key}" data-dir="up">+</button>
                  </div>
                </div>
                <div>
                  <div class="cart-item-price">₱${(item.price * item.quantity).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                  <button class="cart-remove" data-key="${key}">✕</button>
                </div>
              </div>
            `).join('');
          }
        }

        const totalEl = document.getElementById('cartTotal');
        if (totalEl) totalEl.textContent = '₱' + total.toLocaleString('en-PH', {minimumFractionDigits:2});
      });
  }

  // ===== CHECKOUT FORM =====
  const checkoutForm = document.getElementById('checkoutForm');
  checkoutForm?.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(checkoutForm);
    fetch('/joesfit/api/checkout.php', {
      method: 'POST',
      body: formData
    }).then(r => r.json()).then(d => {
      if (d.success) {
        checkoutModal.classList.remove('open');
        document.getElementById('trackingCodeDisplay').textContent = d.tracking_code;
        document.getElementById('successModal').classList.add('open');
        refreshCart();
      } else {
        showToast(d.error || 'Checkout failed', 'error');
      }
    });
  });

  // Coupon
  document.getElementById('applyCoupon')?.addEventListener('click', () => {
    const code = document.getElementById('couponCode').value;
    fetch('/joesfit/api/coupon.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `code=${encodeURIComponent(code)}`
    }).then(r => r.json()).then(d => {
      if (d.success) {
        document.getElementById('couponDiscount').value = d.discount;
        showToast('Coupon applied! -₱' + d.discount, 'success');
        updateOrderSummary();
      } else {
        showToast(d.error || 'Invalid coupon', 'error');
      }
    });
  });

  function updateOrderSummary() {
    fetch('/joesfit/api/cart.php?action=get')
      .then(r => r.json())
      .then(d => {
        const items   = d.cart || {};
        const sub     = Object.values(items).reduce((a, i) => a + i.price * i.quantity, 0);
        const fee     = parseFloat(document.getElementById('shippingFee')?.value || 150);
        const disc    = parseFloat(document.getElementById('couponDiscount')?.value || 0);
        const total   = sub + fee - disc;
        const fmt     = v => '₱' + v.toLocaleString('en-PH', {minimumFractionDigits:2});
        document.getElementById('summarySubtotal').textContent = fmt(sub);
        document.getElementById('summaryFee').textContent      = fmt(fee);
        document.getElementById('summaryDiscount').textContent = disc > 0 ? '-' + fmt(disc) : fmt(0);
        document.getElementById('summaryTotal').textContent    = fmt(total);
      });
  }

  // ===== SUCCESS MODAL =====
  document.getElementById('successClose')?.addEventListener('click', () => {
    document.getElementById('successModal').classList.remove('open');
  });
  document.getElementById('trackOrderBtn')?.addEventListener('click', () => {
    const code = document.getElementById('trackingCodeDisplay')?.textContent;
    window.location.href = '/joesfit/track.php?code=' + code;
  });

  // ===== PRODUCT DETAIL SIZE/COLOR =====
  document.querySelectorAll('.size-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
    });
  });

  document.querySelectorAll('.color-option').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
    });
  });

  // ===== TABS =====
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('tab-' + target)?.classList.add('active');
    });
  });

  // ===== STAR RATING =====
  const stars = document.querySelectorAll('.star-rating .star');
  stars.forEach((star, i) => {
    star.addEventListener('click', () => {
      stars.forEach((s, j) => s.classList.toggle('active', j <= i));
      document.getElementById('ratingValue').value = i + 1;
    });
    star.addEventListener('mouseenter', () => {
      stars.forEach((s, j) => s.classList.toggle('active', j <= i));
    });
  });
  document.querySelector('.star-rating')?.addEventListener('mouseleave', () => {
    const val = parseInt(document.getElementById('ratingValue')?.value || 0);
    stars.forEach((s, j) => s.classList.toggle('active', j < val));
  });

  // ===== GALLERY THUMBS =====
  document.querySelectorAll('.gallery-thumb').forEach(thumb => {
    thumb.addEventListener('click', () => {
      const src = thumb.querySelector('img')?.src;
      const main = document.getElementById('galleryMain');
      if (main && src) main.src = src;
      document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
    });
  });

  // ===== CATEGORY FILTER =====
  document.querySelectorAll('.cat-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      const cat = chip.dataset.category;
      filterProducts(cat);
    });
  });

  function filterProducts(category) {
    document.querySelectorAll('.product-card[data-category]').forEach(card => {
      const show = category === 'all' || card.dataset.category === category;
      card.style.display = show ? '' : 'none';
    });
  }

  // ===== SEARCH =====
  const searchInput = document.getElementById('searchInput');
  const searchForm = document.getElementById('searchForm');
  searchForm?.addEventListener('submit', e => {
    e.preventDefault();
    const q = searchInput.value.trim();
    if (q) window.location.href = '/joesfit/shop.php?search=' + encodeURIComponent(q);
  });

  // ===== TOAST =====
  function showToast(msg, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = msg;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 3500);
  }

  window.showToast = showToast;

  // ===== INIT =====
  refreshCart();
  updateOrderSummary();
});
