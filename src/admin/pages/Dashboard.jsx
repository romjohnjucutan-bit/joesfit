import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { supabase } from '../../lib/supabase.js'
import { formatPrice } from '../../lib/format.js'

export default function Dashboard() {
  const [stats, setStats] = useState(null)
  const [recent, setRecent] = useState([])

  useEffect(() => {
    async function load() {
      const [orders, products] = await Promise.all([
        supabase.from('orders').select('id,total,status,tracking_code,customer_name,created_at')
          .order('created_at', { ascending: false }),
        supabase.from('products').select('id,stock,is_active'),
      ])
      const o = orders.data || []
      const p = products.data || []
      const revenue = o.filter((x) => x.status !== 'cancelled').reduce((s, x) => s + Number(x.total), 0)
      setStats({
        revenue,
        orders: o.length,
        pending: o.filter((x) => ['pending', 'confirmed', 'processing'].includes(x.status)).length,
        products: p.length,
        lowStock: p.filter((x) => x.stock <= 5).length,
      })
      setRecent(o.slice(0, 8))
    }
    load()
  }, [])

  if (!stats) return <div className="loading-spinner" />

  return (
    <>
      <h1 className="admin-page-title">DASH<span>BOARD</span></h1>

      <div className="admin-stats">
        <Stat label="Total Revenue" value={formatPrice(stats.revenue)} accent />
        <Stat label="Total Orders" value={stats.orders} />
        <Stat label="Pending Orders" value={stats.pending} />
        <Stat label="Products" value={stats.products} />
        <Stat label="Low Stock" value={stats.lowStock} />
      </div>

      <div className="admin-card">
        <div className="flex justify-between items-center" style={{ marginBottom: '1rem' }}>
          <h3 style={{ fontWeight: 700 }}>Recent Orders</h3>
          <Link to="/admin/orders" className="text-accent" style={{ fontSize: '0.85rem', fontWeight: 600 }}>
            View all →
          </Link>
        </div>
        <div className="admin-table-wrap">
          <table className="admin-table">
            <thead>
              <tr><th>Tracking</th><th>Customer</th><th>Date</th><th>Status</th><th>Total</th></tr>
            </thead>
            <tbody>
              {recent.map((o) => (
                <tr key={o.id}>
                  <td className="font-mono">{o.tracking_code}</td>
                  <td>{o.customer_name}</td>
                  <td>{new Date(o.created_at).toLocaleDateString()}</td>
                  <td><span className={`status-badge status-${o.status}`}>{o.status}</span></td>
                  <td>{formatPrice(o.total)}</td>
                </tr>
              ))}
              {recent.length === 0 && (
                <tr><td colSpan="5" className="text-muted">No orders yet.</td></tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </>
  )
}

function Stat({ label, value, accent }) {
  return (
    <div className="admin-stat">
      <div className="admin-stat-label">{label}</div>
      <div className={`admin-stat-value ${accent ? 'accent' : ''}`}>{value}</div>
    </div>
  )
}
