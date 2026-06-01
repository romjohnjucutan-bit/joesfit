import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { supabase } from '../lib/supabase.js'
import { useCart } from '../context/CartContext.jsx'
import { useAuth } from '../context/AuthContext.jsx'
import { useToast } from '../context/ToastContext.jsx'
import { formatPrice } from '../lib/format.js'

const DELIVERY = {
  standard: { label: 'Standard', fee: 150, note: '3–5 days' },
  express: { label: 'Express', fee: 250, note: '1–2 days' },
  pickup: { label: 'Free Pickup', fee: 0, note: 'At store' },
}
const PAYMENTS = [
  { id: 'cod', label: 'Cash on Delivery', icon: '💵' },
  { id: 'gcash', label: 'GCash', icon: '📱' },
  { id: 'maya', label: 'Maya', icon: '💳' },
  { id: 'card', label: 'Card', icon: '💳' },
]

export default function Checkout() {
  const { items, subtotal, clear } = useCart()
  const { user } = useAuth()
  const { toast } = useToast()

  const [form, setForm] = useState({
    name: '', email: '', phone: '', address: '', city: '', province: '', zip: '', notes: '',
  })
  const [delivery, setDelivery] = useState('standard')
  const [payment, setPayment] = useState('cod')
  const [couponCode, setCouponCode] = useState('')
  const [discount, setDiscount] = useState(0)
  const [appliedCode, setAppliedCode] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [result, setResult] = useState(null)

  useEffect(() => {
    if (!user) return
    supabase.from('customers').select('*').eq('id', user.id).maybeSingle()
      .then(({ data }) => {
        if (data) setForm((f) => ({
          ...f,
          name: data.name || '', email: data.email || user.email || '',
          phone: data.phone || '', address: data.address || '',
          city: data.city || '', province: data.province || '', zip: data.zip || '',
        }))
      })
  }, [user])

  const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }))
  const shippingFee = DELIVERY[delivery].fee
  const total = Math.max(0, subtotal + shippingFee - discount)

  async function applyCoupon() {
    if (!couponCode.trim()) return
    const { data, error } = await supabase.functions.invoke('coupon', {
      body: { code: couponCode, subtotal },
    })
    if (error || !data?.success) {
      setDiscount(0); setAppliedCode('')
      toast(data?.error || 'Invalid coupon', 'error')
      return
    }
    setDiscount(data.discount); setAppliedCode(data.code)
    toast(`Coupon ${data.code} applied`, 'success')
  }

  async function placeOrder(e) {
    e.preventDefault()
    if (items.length === 0) return
    setSubmitting(true)
    const { data, error } = await supabase.functions.invoke('checkout', {
      body: {
        customer: form,
        delivery_method: delivery,
        payment_method: payment,
        coupon_code: appliedCode || undefined,
        notes: form.notes,
        items: items.map((i) => ({
          product_id: i.product_id, size: i.size, color: i.color, quantity: i.quantity,
        })),
      },
    })
    setSubmitting(false)
    if (error || !data?.success) {
      toast(data?.error || 'Checkout failed', 'error')
      return
    }
    setResult(data)
    clear()
  }

  if (result) {
    return (
      <section className="section">
        <div className="modal success-modal" style={{ maxWidth: 520, margin: '0 auto' }}>
          <div className="success-icon">✓</div>
          <h2 className="section-title">Order <span>Placed!</span></h2>
          <p className="text-muted mt-2">Save your tracking code to follow your order.</p>
          <div className="tracking-code-display">{result.tracking_code}</div>
          <p className="mb-3">Total paid: <strong>{formatPrice(result.total)}</strong></p>
          <div className="flex gap-2" style={{ justifyContent: 'center' }}>
            <Link to={`/track?code=${result.tracking_code}`} className="btn btn-primary">Track Order</Link>
            <Link to="/shop" className="btn btn-outline">Continue Shopping</Link>
          </div>
        </div>
      </section>
    )
  }

  if (items.length === 0) {
    return (
      <section className="section text-center">
        <h2 className="section-title">Your Cart Is <span>Empty</span></h2>
        <Link to="/shop" className="btn btn-primary mt-3">Shop Now</Link>
      </section>
    )
  }

  return (
    <section className="section">
      <h2 className="section-title mb-3">CHECK<span>OUT</span></h2>
      <form className="shop-layout" onSubmit={placeOrder}>
        <div>
          <h3 className="filter-title">Shipping Details</h3>
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Full Name *</label>
              <input className="form-control" required value={form.name} onChange={set('name')} />
            </div>
            <div className="form-group">
              <label className="form-label">Email *</label>
              <input className="form-control" type="email" required value={form.email} onChange={set('email')} />
            </div>
            <div className="form-group">
              <label className="form-label">Phone</label>
              <input className="form-control" value={form.phone} onChange={set('phone')} />
            </div>
            <div className="form-group">
              <label className="form-label">ZIP</label>
              <input className="form-control" value={form.zip} onChange={set('zip')} />
            </div>
          </div>
          <div className="form-group">
            <label className="form-label">Address *</label>
            <input className="form-control" required value={form.address} onChange={set('address')} />
          </div>
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">City *</label>
              <input className="form-control" required value={form.city} onChange={set('city')} />
            </div>
            <div className="form-group">
              <label className="form-label">Province *</label>
              <input className="form-control" required value={form.province} onChange={set('province')} />
            </div>
          </div>

          <h3 className="filter-title mt-2">Delivery</h3>
          <div className="delivery-options mb-3">
            {Object.entries(DELIVERY).map(([id, d]) => (
              <div key={id} className={`delivery-option ${delivery === id ? 'selected' : ''}`}
                onClick={() => setDelivery(id)}>
                <div>{d.label}</div>
                <div className="text-muted" style={{ fontSize: '0.72rem' }}>{d.note}</div>
                <div className="delivery-price">{d.fee ? formatPrice(d.fee) : 'FREE'}</div>
              </div>
            ))}
          </div>

          <h3 className="filter-title">Payment Method</h3>
          <div className="payment-options">
            {PAYMENTS.map((p) => (
              <div key={p.id} className={`payment-option ${payment === p.id ? 'selected' : ''}`}
                onClick={() => setPayment(p.id)}>
                <span className="payment-icon">{p.icon}</span> {p.label}
              </div>
            ))}
          </div>

          <div className="form-group mt-3">
            <label className="form-label">Order Notes</label>
            <textarea className="form-control" rows="2" value={form.notes} onChange={set('notes')} />
          </div>
        </div>

        <aside className="filter-panel">
          <div className="filter-title">Order Summary</div>
          {items.map((i) => (
            <div key={i.key} className="flex justify-between mb-1" style={{ fontSize: '0.85rem' }}>
              <span>{i.name} ×{i.quantity}</span>
              <span>{formatPrice(i.price * i.quantity)}</span>
            </div>
          ))}
          <hr className="divider" />

          <div className="form-group">
            <label className="form-label">Coupon Code</label>
            <div className="flex gap-1">
              <input className="form-control" value={couponCode}
                onChange={(e) => setCouponCode(e.target.value)} placeholder="e.g. JOESFIT10" />
              <button type="button" className="btn btn-outline btn-sm" onClick={applyCoupon}>Apply</button>
            </div>
          </div>

          <div className="flex justify-between mb-1"><span>Subtotal</span><span>{formatPrice(subtotal)}</span></div>
          <div className="flex justify-between mb-1"><span>Shipping</span><span>{shippingFee ? formatPrice(shippingFee) : 'FREE'}</span></div>
          {discount > 0 && (
            <div className="flex justify-between mb-1 text-accent">
              <span>Discount {appliedCode && `(${appliedCode})`}</span><span>−{formatPrice(discount)}</span>
            </div>
          )}
          <hr className="divider" />
          <div className="cart-total"><span>Total</span><span className="cart-total-amount">{formatPrice(total)}</span></div>
          <button className="btn btn-primary btn-full" disabled={submitting}>
            {submitting ? 'Placing Order…' : 'Place Order'}
          </button>
        </aside>
      </form>
    </section>
  )
}
