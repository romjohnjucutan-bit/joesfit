import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { supabase } from '../lib/supabase.js'
import { formatPrice } from '../lib/format.js'

const STEPS = ['pending', 'confirmed', 'processing', 'shipped', 'delivered']
const LABELS = {
  pending: 'Order Placed', confirmed: 'Confirmed', processing: 'Processing',
  shipped: 'Shipped', delivered: 'Delivered',
}

export default function Track() {
  const [searchParams] = useSearchParams()
  const [code, setCode] = useState(searchParams.get('code') || '')
  const [data, setData] = useState(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  async function lookup(c) {
    const trimmed = (c ?? code).trim()
    if (!trimmed) return
    setLoading(true); setError(''); setData(null)
    const { data: res, error: err } = await supabase.rpc('track_order', { p_code: trimmed })
    setLoading(false)
    if (err || !res || !res.order) {
      setError('No order found with that tracking code.')
      return
    }
    setData(res)
  }

  useEffect(() => {
    if (searchParams.get('code')) lookup(searchParams.get('code'))
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const order = data?.order
  const currentIndex = order ? STEPS.indexOf(order.status) : -1

  return (
    <section className="section">
      <div style={{ maxWidth: 640, margin: '0 auto' }}>
        <h2 className="section-title text-center mb-3">TRACK <span>ORDER</span></h2>

        <div className="flex gap-1 mb-3">
          <input className="form-control" placeholder="Enter tracking code (e.g. JF-20240001)"
            value={code} onChange={(e) => setCode(e.target.value.toUpperCase())}
            onKeyDown={(e) => { if (e.key === 'Enter') lookup() }} />
          <button className="btn btn-primary" onClick={() => lookup()}>Track</button>
        </div>

        {loading && <div className="loading-spinner" />}
        {error && <p className="text-center text-muted">{error}</p>}

        {order && (
          <div className="review-card">
            <div className="flex justify-between mb-2">
              <div>
                <div className="product-category">Tracking Code</div>
                <div className="font-mono" style={{ fontWeight: 700 }}>{order.tracking_code}</div>
              </div>
              <div style={{ textAlign: 'right' }}>
                <div className="product-category">Total</div>
                <div style={{ fontWeight: 700 }} className="text-accent">{formatPrice(order.total)}</div>
              </div>
            </div>

            {order.status === 'cancelled' || order.status === 'returned' ? (
              <p className="text-accent" style={{ fontWeight: 700 }}>Order {order.status}</p>
            ) : (
              <div className="tracking-steps">
                {STEPS.map((step, i) => {
                  const done = i <= currentIndex
                  const current = i === currentIndex
                  const hist = (data.history || []).find((h) => h.status === step)
                  return (
                    <div key={step} className={`tracking-step ${done ? 'done' : ''} ${current ? 'current' : ''}`}>
                      <div className="step-icon">{done ? '✓' : i + 1}</div>
                      <div className="step-info">
                        <div className="step-status">{LABELS[step]}</div>
                        {hist && <div className="step-date">{new Date(hist.created_at).toLocaleString()}</div>}
                        {hist?.note && <div className="step-note">{hist.note}</div>}
                      </div>
                    </div>
                  )
                })}
              </div>
            )}

            <hr className="divider" />
            <div className="filter-group-label">Items</div>
            {(data.items || []).map((it) => (
              <div key={it.id} className="flex justify-between mb-1" style={{ fontSize: '0.85rem' }}>
                <span>{it.product_name} {it.size && `(${it.size})`} ×{it.quantity}</span>
                <span>{formatPrice(it.subtotal)}</span>
              </div>
            ))}
          </div>
        )}
      </div>
    </section>
  )
}
