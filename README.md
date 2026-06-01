# Joe's Fit — E-Commerce Web System
### Complete Setup Guide for XAMPP / phpMyAdmin

---

## 📁 Project Structure

```
joesfit/
├── index.php               ← Homepage
├── shop.php                ← Product catalog with filters
├── product.php             ← Product detail + reviews
├── track.php               ← Order tracking
├── login.php               ← Customer login/register
├── account.php             ← Customer account & orders
├── database.sql            ← Full database schema + sample data
│
├── includes/
│   ├── config.php          ← DB connection + helpers
│   ├── auth.php            ← Customer session/cart functions
│   ├── header.php          ← Site header + cart sidebar + modals
│   └── footer.php          ← Footer + scripts
│
├── assets/
│   ├── css/style.css       ← Customer-facing styles
│   └── js/main.js          ← Customer-facing JS
│
├── api/
│   ├── cart.php            ← Cart CRUD API
│   ├── checkout.php        ← Order placement API
│   └── coupon.php          ← Coupon validation API
│
├── uploads/products/       ← Product image uploads (auto-created)
│
└── admin/
    ├── login.php           ← Admin login
    ├── logout.php          ← Admin logout
    ├── index.php           ← Dashboard
    ├── assets/css/admin.css
    ├── includes/
    │   ├── admin_auth.php
    │   ├── admin_header.php
    │   └── admin_footer.php
    ├── pages/
    │   ├── orders.php       ← Order management + status updates
    │   ├── products.php     ← Product CRUD + image upload
    │   ├── categories.php   ← Category management
    │   ├── inventory.php    ← Stock management
    │   ├── staff.php        ← Staff/user management (admin only)
    │   ├── reviews.php      ← Review moderation
    │   ├── reports.php      ← Revenue & analytics
    │   ├── notifications.php ← System notifications
    │   └── coupons.php      ← Coupon management (admin only)
    └── api/
        └── mark_read.php    ← Mark notifications read
```

---

## 🚀 Installation Steps

### Step 1: Copy Files to XAMPP
1. Copy the entire `joesfit/` folder to: `C:\xampp\htdocs\joesfit\`

### Step 2: Create the Database
1. Start **Apache** and **MySQL** in XAMPP Control Panel
2. Open browser → `http://localhost/phpmyadmin`
3. Click **"New"** to create a database named `joesfit`
   - OR simply import — the SQL file creates it automatically
4. Click **"Import"** tab
5. Choose file: `joesfit/database.sql`
6. Click **Go**

### Step 3: Verify Config
Open `joesfit/includes/config.php` and confirm:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Default XAMPP has no password
define('DB_NAME', 'joesfit');
```
> If you've set a MySQL root password in XAMPP, update `DB_PASS`.

### Step 4: Create Upload Directory
The system will auto-create `joesfit/uploads/products/` when needed.
If it doesn't auto-create, manually create it and ensure it's writable.

---

## 🌐 Access URLs

| Page | URL |
|------|-----|
| **Store Homepage** | http://localhost/joesfit/ |
| **Shop All Products** | http://localhost/joesfit/shop.php |
| **Order Tracking** | http://localhost/joesfit/track.php |
| **Customer Login** | http://localhost/joesfit/login.php |
| **Admin Login** | http://localhost/joesfit/admin/login.php |
| **Admin Dashboard** | http://localhost/joesfit/admin/ |

---

## 🔑 Default Login Credentials

### Admin Account (Full Access)
- **Email:** admin@joesfit.com
- **Password:** password

### Staff Account (Limited Access)
- **Email:** staff@joesfit.com
- **Password:** password

### Sample Order Tracking Codes
- `JF-20240001` — Delivered
- `JF-20240002` — Shipped
- `JF-20240003` — Processing

---

## ✨ Features Summary

### Customer-Facing
- ✅ Product catalog with search, filters, pagination
- ✅ Product detail pages with size/color selection
- ✅ Image gallery with thumbnails
- ✅ Cart sidebar (add/remove/update quantity)
- ✅ Checkout modal (COD, GCash, Maya, Card)
- ✅ Delivery options (Standard ₱150 / Express ₱250 / Free Pickup)
- ✅ Coupon/discount codes
- ✅ Order success modal with tracking code
- ✅ Order tracking with status timeline
- ✅ Product reviews (verified purchases only)
- ✅ Dark mode toggle (saved to localStorage)
- ✅ Customer login & registration
- ✅ Account page with order history

### Admin Panel
- ✅ Dashboard with revenue charts & stats
- ✅ Orders management with status updates & history
- ✅ Products CRUD with image upload
- ✅ Categories management
- ✅ Inventory management (bulk stock updates)
- ✅ Staff management with role-based access (Admin only)
- ✅ Revenue & analytics reports (Today/Week/Month/Year)
- ✅ Reviews moderation (approve/reject/delete)
- ✅ Notifications system (new orders, low stock)
- ✅ Coupon/discount management (Admin only)

### Security
- ✅ PDO prepared statements (SQL injection protection)
- ✅ Password hashing with bcrypt
- ✅ Session-based authentication
- ✅ Role-based access control (Admin vs Staff)
- ✅ Input sanitization with htmlspecialchars
- ✅ CSRF-resistant form handling

---

## 🛠️ Customization

### Change Store Name
Edit `joesfit/includes/config.php`:
```php
define('SITE_NAME', "Joe's Fit");
```

### Change Currency
```php
define('CURRENCY', '₱');
```

### Change Timezone
```php
date_default_timezone_set('Asia/Manila');
```

### Add Product Images
- Upload images to `joesfit/uploads/products/`
- Supported formats: JPG, PNG, WebP
- When adding products in admin, use the image upload field

---

## 📋 Sample Coupon Codes

| Code | Type | Value | Min. Order |
|------|------|-------|-----------|
| JOESFIT10 | 10% off | 10% | ₱500 |
| WELCOME20 | 20% off | 20% | ₱1,000 |
| FLAT200 | Fixed discount | ₱200 | ₱1,500 |

---

## 🔧 Troubleshooting

**"Database connection failed"**
→ Make sure MySQL is running in XAMPP and the database was imported

**"Permission denied" on image upload**
→ Right-click `uploads/products/` folder → Properties → Security → Give write permissions

**Pages show blank / errors**
→ Enable error reporting: add `ini_set('display_errors',1);` to config.php temporarily

**Session issues**
→ Make sure cookies are enabled in your browser

---

*Joe's Fit E-Commerce System — Built with PHP, MySQL, HTML, CSS, JavaScript*
*Timezone: Asia/Manila (UTC+8)*
