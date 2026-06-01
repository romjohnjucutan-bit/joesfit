import { Routes, Route, Outlet } from 'react-router-dom'
import StorefrontLayout from './components/StorefrontLayout.jsx'
import Home from './pages/Home.jsx'
import Shop from './pages/Shop.jsx'
import Product from './pages/Product.jsx'
import Checkout from './pages/Checkout.jsx'
import Track from './pages/Track.jsx'
import NotFound from './pages/NotFound.jsx'

import { AdminAuthProvider } from './admin/AdminAuthContext.jsx'
import RequireStaff from './admin/RequireStaff.jsx'
import AdminLogin from './admin/AdminLogin.jsx'
import AdminLayout from './admin/AdminLayout.jsx'
import Dashboard from './admin/pages/Dashboard.jsx'
import Orders from './admin/pages/Orders.jsx'
import Products from './admin/pages/Products.jsx'
import Inventory from './admin/pages/Inventory.jsx'
import Categories from './admin/pages/Categories.jsx'
import Coupons from './admin/pages/Coupons.jsx'
import Reviews from './admin/pages/Reviews.jsx'
import Staff from './admin/pages/Staff.jsx'

export default function App() {
  return (
    <Routes>
      {/* Storefront (guest-only: browse, cart, guest checkout, track) */}
      <Route element={<StorefrontLayout />}>
        <Route path="/" element={<Home />} />
        <Route path="/shop" element={<Shop />} />
        <Route path="/product/:slug" element={<Product />} />
        <Route path="/checkout" element={<Checkout />} />
        <Route path="/track" element={<Track />} />
        <Route path="*" element={<NotFound />} />
      </Route>

      {/* Admin (staff/admin only) */}
      <Route path="/admin" element={<AdminAuthProvider><Outlet /></AdminAuthProvider>}>
        <Route path="login" element={<AdminLogin />} />
        <Route element={<RequireStaff><AdminLayout /></RequireStaff>}>
          <Route index element={<Dashboard />} />
          <Route path="orders" element={<Orders />} />
          <Route path="products" element={<Products />} />
          <Route path="inventory" element={<Inventory />} />
          <Route path="categories" element={<Categories />} />
          <Route path="coupons" element={<Coupons />} />
          <Route path="reviews" element={<Reviews />} />
          <Route path="staff" element={<Staff />} />
        </Route>
      </Route>
    </Routes>
  )
}
