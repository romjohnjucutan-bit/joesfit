import { useState } from 'react'
import { NavLink, Outlet, useNavigate } from 'react-router-dom'
import { useAdminAuth } from './AdminAuthContext.jsx'
import './admin.css'

const NAV = [
  { to: '/admin', label: 'Dashboard', icon: '📊', end: true },
  { to: '/admin/orders', label: 'Orders', icon: '📦' },
  { to: '/admin/products', label: 'Products', icon: '👕' },
  { to: '/admin/inventory', label: 'Inventory', icon: '📋' },
  { to: '/admin/categories', label: 'Categories', icon: '🏷️' },
  { to: '/admin/coupons', label: 'Coupons', icon: '🎟️', adminOnly: true },
  { to: '/admin/reviews', label: 'Reviews', icon: '⭐' },
  { to: '/admin/staff', label: 'Staff', icon: '👥', adminOnly: true },
]

export default function AdminLayout() {
  const { staffUser, isAdmin, signOut } = useAdminAuth()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)

  async function logout() {
    await signOut()
    navigate('/admin/login', { replace: true })
  }

  return (
    <div className="admin-shell">
      <aside className={`admin-sidebar ${open ? 'open' : ''}`}>
        <div className="admin-brand">JOE'S<span>FIT</span><small>Admin</small></div>
        <nav className="admin-nav">
          {NAV.filter((n) => !n.adminOnly || isAdmin).map((n) => (
            <NavLink key={n.to} to={n.to} end={n.end} className="admin-nav-link"
              onClick={() => setOpen(false)}>
              <span className="admin-nav-icon">{n.icon}</span> {n.label}
            </NavLink>
          ))}
        </nav>
        <a className="admin-nav-link" href="/" target="_blank" rel="noreferrer">
          <span className="admin-nav-icon">🌐</span> View Store
        </a>
      </aside>

      <div className="admin-main">
        <header className="admin-topbar">
          <button className="admin-burger" onClick={() => setOpen((o) => !o)}>☰</button>
          <div className="admin-user">
            <div>
              <div className="admin-user-name">{staffUser?.name}</div>
              <div className={`admin-role-badge ${isAdmin ? 'admin' : 'staff'}`}>
                {staffUser?.role}
              </div>
            </div>
            <button className="btn btn-outline btn-sm" onClick={logout}>Log Out</button>
          </div>
        </header>
        <div className="admin-content">
          <Outlet />
        </div>
      </div>
    </div>
  )
}
