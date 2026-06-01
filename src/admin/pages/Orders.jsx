import { useEffect, useState, Fragment } from 'react'
import { supabase } from '../../lib/supabase.js'
import { formatPrice } from '../../lib/format.js'

const STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned']

export default function Orders() {
  const [orders, setOrders] = useState([])
  const [filter, setFilter] = useState('')
  const [expanded, setExpanded] = useState(null)
  const [detail, setDetail] = useState({}) // orderId -> {items, history}
  const [loading, setLoading] = useState(true)
  const [msg, setMsg] = useState('')

  async function load() {
    setLoading(true)
    let q = supabase.from('orders').select('*').order('created_at', { ascending: false })
    if (filter) q = q.eq('status', filter)
    const { data } = await q
    setOrders(data || [])
    setLoading(false)
  }
  useEffect(() => { load() }, [filter])

  async function toggle(order) {
    if (expanded === order.id) { setExpanded(null); return }
    setExpanded(order.id)
    if (!detail[order.id]) {
      const [items, history] = await Promise.all([
        supabase.from('order_items').select('*').eq('order_id', order.id),
        supabase.from('order_history').select('*').eq('order_id', order.id).order('created_at'),
      ])
      setDetail((d) => ({ ...d, [order.id]: { items: items.data || [], history: history.data || [] } }))
    }
  }

  async function updateStatus(order, status) {
    if (status === order.status) return
    const { error } = await supabase.from('orders').update({ status }).eq('id', order.id)
    if (error) { setMsg(error.message); return }
    await supabase.from('order_history').insert({
      order_id: order.id, status, note: `Status changed to ${status}`,
    })
    setMsg(`Order ${order.tracking_code} updated to "${status}"`)
    setDetail((d) => ({ ...d, [order.id]: undefined }))
    if (expanded === order.id) {
      const history = await supabase.from('order_history').select('*').eq('order_id', order.id).order('created_at')
      const items = await supabase.from('order_items').select('*').eq('order_id', order.id)
      setDetail((d) => ({ ...d, [order.id]: { items: items.data || [], history: history.data || [] } }))
    }
    setOrders((os) => os.map((o) => (o.id === order.id ? { ...o, status } : o)))
  }

  return (
    <>
      <h1 className="admin-page-title">OR<span>DERS</span></h1>
      {msg && <div className="admin-alert success">{msg}</div>}

      <div className="admin-toolbar">
        <select className="form-control" value={filter} onChange={(e) => setFilter(e.target.value)}>
          <option value="">All statuses</option>
          {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
        </select>
        <div className="admin-spacer" />
        <span className="text-muted" style={{ fontSize: '0.85rem' }}>{orders.length} orders</span>
      </div>

      <div className="admin-card">
        {loading ? <div className="loading-spinner" /> : (
          <div className="admin-table-wrap">
            <table className="admin-table">
              <thead>
                <tr><th></th><th>Tracking</th><th>Customer</th><th>Date</th><th>Payment</th><th>Total</th><th>Status</th></tr>
              </thead>
              <tbody>
                {orders.map((o) => (
                  <Fragment key={o.id}>
                    <tr style={{ cursor: 'pointer' }} onClick={() => toggle(o)}>
                      <td>{expanded === o.id ? '▾' : '▸'}</td>
                      <td className="font-mono">{o.tracking_code}</td>
                      <td>{o.customer_name}</td>
                      <td>{new Date(o.created_at).toLocaleDateString()}</td>
                      <td style={{ textTransform: 'uppercase', fontSize: '0.78rem' }}>{o.payment_method}</td>
                      <td>{formatPrice(o.total)}</td>
                      <td><span className={`status-badge status-${o.status}`}>{o.status}</span></td>
                    </tr>
                    {expanded === o.id && (
                      <tr>
                        <td colSpan="7" style={{ background: 'var(--bg)' }}>
                          <OrderDetail order={o} data={detail[o.id]} onStatus={updateStatus} />
                        </td>
                      </tr>
                    )}
                  </Fragment>
                ))}
                {orders.length === 0 && <tr><td colSpan="7" className="text-muted">No orders.</td></tr>}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  )
}

function OrderDetail({ order, data, onStatus }) {
  if (!data) return <div className="loading-spinner" style={{ margin: '1rem auto' }} />
  return (
    <div style={{ padding: '1rem', display: 'grid', gap: '1.2rem', gridTemplateColumns: '1fr 1fr' }}>
      <div>
        <h4 className="filter-group-label">Items</h4>
        {data.items.map((it) => (
          <div key={it.id} className="flex justify-between mb-1" style={{ fontSize: '0.85rem' }}>
            <span>{it.product_name} {it.size && `(${it.size}${it.color ? ', ' + it.color : ''})`} ×{it.quantity}</span>
            <span>{formatPrice(it.subtotal)}</span>
          </div>
        ))}
        <hr className="divider" />
        <div className="flex justify-between" style={{ fontSize: '0.82rem' }}><span>Subtotal</span><span>{formatPrice(order.subtotal)}</span></div>
        <div className="flex justify-between" style={{ fontSize: '0.82rem' }}><span>Shipping</span><span>{formatPrice(order.shipping_fee)}</span></div>
        {order.discount > 0 && <div className="flex justify-between text-accent" style={{ fontSize: '0.82rem' }}><span>Discount</span><span>−{formatPrice(order.discount)}</span></div>}
        <div className="flex justify-between" style={{ fontWeight: 700, marginTop: 4 }}><span>Total</span><span>{formatPrice(order.total)}</span></div>

        <h4 className="filter-group-label" style={{ marginTop: '1rem' }}>Shipping</h4>
        <div style={{ fontSize: '0.82rem' }} className="text-muted">
          {order.customer_name} · {order.customer_phone}<br />
          {order.customer_email}<br />
          {order.shipping_address}, {order.shipping_city}, {order.shipping_province} {order.shipping_zip}<br />
          Delivery: {order.delivery_method}
        </div>
      </div>
      <div>
        <h4 className="filter-group-label">Update Status</h4>
        <select className="form-control" value={order.status}
          onChange={(e) => onStatus(order, e.target.value)}>
          {STATUSES.map((s) => <option key={s} value={s}>{s}</option>)}
        </select>

        <h4 className="filter-group-label" style={{ marginTop: '1rem' }}>History</h4>
        {data.history.map((h) => (
          <div key={h.id} className="mb-1" style={{ fontSize: '0.8rem' }}>
            <span className={`status-badge status-${h.status}`}>{h.status}</span>{' '}
            <span className="text-muted">{new Date(h.created_at).toLocaleString()}</span>
            {h.note && <div className="text-muted" style={{ marginLeft: 4 }}>{h.note}</div>}
          </div>
        ))}
      </div>
    </div>
  )
}
