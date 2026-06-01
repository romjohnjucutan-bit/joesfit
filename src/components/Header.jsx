import { useEffect, useState } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import { useCart } from '../context/CartContext.jsx'
import { useAuth } from '../context/AuthContext.jsx'

export default function Header() {
  const { count, setOpen } = useCart()
  const { user } = useAuth()
  const navigate = useNavigate()
  const [scrolled, setScrolled] = useState(false)
  const [theme, setTheme] = useState(() => localStorage.getItem('theme') || 'light')

  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme)
    localStorage.setItem('theme', theme)
  }, [theme])

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 10)
    window.addEventListener('scroll', onScroll)
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  return (
    <nav className={`navbar ${scrolled ? 'scrolled' : ''}`}>
      <Link to="/" className="nav-logo">JOE'S<span>FIT</span></Link>
      <ul className="nav-links">
        <li><NavLink to="/" end>Home</NavLink></li>
        <li><NavLink to="/shop">Shop</NavLink></li>
        <li><NavLink to="/track">Track Order</NavLink></li>
      </ul>
      <div className="nav-actions">
        <button
          className="dark-toggle"
          title="Toggle dark mode"
          onClick={() => setTheme((t) => (t === 'dark' ? 'light' : 'dark'))}
        />
        <button
          className="nav-btn"
          title={user ? 'Account' : 'Login'}
          onClick={() => navigate(user ? '/account' : '/login')}
        >
          {user ? '👤' : '🔑'}
        </button>
        <button className="nav-btn" title="Cart" onClick={() => setOpen(true)}>
          🛒
          {count > 0 && <span className="cart-badge">{count}</span>}
        </button>
      </div>
    </nav>
  )
}
