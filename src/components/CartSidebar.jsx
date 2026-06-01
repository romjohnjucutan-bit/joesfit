import { useNavigate } from 'react-router-dom'
import { useCart } from '../context/CartContext.jsx'
import { formatPrice, productImage } from '../lib/format.js'

export default function CartSidebar() {
  const { items, subtotal, updateQty, removeItem, open, setOpen } = useCart()
  const navigate = useNavigate()

  function goCheckout() {
    setOpen(false)
    navigate('/checkout')
  }

  return (
    <>
      <div className={`cart-overlay ${open ? 'open' : ''}`} onClick={() => setOpen(false)} />
      <aside className={`cart-sidebar ${open ? 'open' : ''}`}>
        <div className="cart-header">
          <h2>Your Cart</h2>
          <button className="cart-close" onClick={() => setOpen(false)}>✕</button>
        </div>

        <div className="cart-items">
          {items.length === 0 ? (
            <div className="cart-empty">
              <div className="cart-empty-icon">🛒</div>
              <p>Your cart is empty.</p>
            </div>
          ) : (
            items.map((item) => {
              const img = productImage(item.image)
              return (
                <div key={item.key} className="cart-item">
                  <div className="cart-item-img">
                    {img ? <img src={img} alt={item.name} /> : <div className="img-placeholder">🧥</div>}
                  </div>
                  <div className="cart-item-info">
                    <div className="cart-item-name">{item.name}</div>
                    <div className="cart-item-meta">
                      {[item.size, item.color].filter(Boolean).join(' · ')}
                    </div>
                    <div className="cart-qty">
                      <button className="qty-btn" onClick={() => updateQty(item.key, item.quantity - 1)}>−</button>
                      <span className="qty-num">{item.quantity}</span>
                      <button className="qty-btn" onClick={() => updateQty(item.key, item.quantity + 1)}>+</button>
                    </div>
                  </div>
                  <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', justifyContent: 'space-between' }}>
                    <button className="cart-remove" onClick={() => removeItem(item.key)}>✕</button>
                    <span className="cart-item-price">{formatPrice(item.price * item.quantity)}</span>
                  </div>
                </div>
              )
            })
          )}
        </div>

        {items.length > 0 && (
          <div className="cart-footer">
            <div className="cart-total">
              <span>Subtotal</span>
              <span className="cart-total-amount">{formatPrice(subtotal)}</span>
            </div>
            <button className="btn btn-primary btn-full" onClick={goCheckout}>Checkout</button>
          </div>
        )}
      </aside>
    </>
  )
}
