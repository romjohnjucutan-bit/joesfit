import { Link } from 'react-router-dom'

export default function Footer() {
  return (
    <footer className="footer">
      <div className="footer-grid">
        <div>
          <div className="footer-brand">JOE'S<span>FIT</span></div>
          <p className="footer-tagline">
            Premium jackets built for the bold. Varsity, bomber, windbreaker,
            and leather styles crafted to last.
          </p>
          <div className="social-links">
            <a className="social-link" href="#" aria-label="Facebook">f</a>
            <a className="social-link" href="#" aria-label="Instagram">ig</a>
            <a className="social-link" href="#" aria-label="Twitter">x</a>
          </div>
        </div>
        <div>
          <h4 className="footer-heading">Shop</h4>
          <ul className="footer-links">
            <li><Link to="/shop">All Jackets</Link></li>
            <li><Link to="/shop?category=varsity-jackets">Varsity</Link></li>
            <li><Link to="/shop?category=bomber-jackets">Bomber</Link></li>
            <li><Link to="/shop?category=leather-jackets">Leather</Link></li>
          </ul>
        </div>
        <div>
          <h4 className="footer-heading">Help</h4>
          <ul className="footer-links">
            <li><Link to="/track">Track Order</Link></li>
            <li><Link to="/login">My Account</Link></li>
            <li><a href="#">Shipping</a></li>
            <li><a href="#">Returns</a></li>
          </ul>
        </div>
        <div>
          <h4 className="footer-heading">Company</h4>
          <ul className="footer-links">
            <li><a href="#">About</a></li>
            <li><a href="#">Contact</a></li>
            <li><a href="#">Privacy</a></li>
          </ul>
        </div>
      </div>
      <div className="footer-bottom">
        <span>© {new Date().getFullYear()} Joe's Fit. All rights reserved.</span>
        <span>
          Made in the Philippines 🇵🇭 · <Link to="/admin">Staff Login</Link>
        </span>
      </div>
    </footer>
  )
}
