import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { supabase } from '../lib/supabase.js'
import { useAuth } from '../context/AuthContext.jsx'
import { useToast } from '../context/ToastContext.jsx'
import { formatPrice } from '../lib/format.js'

export default function Account() {
  const { user, loading, signOut } = useAuth()
  const { toast } = useToast()
  const navigate = useNavigate()
  const [profile, setProfile] = useState(null)
  const [orders, setOrders] = useState([])
  const [saving, setSaving] = useState(false)

  useEffect(() => {
    if (!loading && !user) navigate('/login')
  }, [loading, user, navigate])

  useEffect(() => {
    if (!user) return
    supabase.from('customers').select('*').eq('id', user.id).maybeSingle()
      .then(({ data }) => setProfile(data || { id: user.id, email: user.email, name: '' }))
    supabase.from('orders').select('*').eq('customer_id', user.id)
      .order('created_at', { ascending: false })
      .then(({ data }) => setOrders(data || []))
  }, [user])

  async function save(e) {
    e.preventDefault()
    setSaving(true)
    const { error } = await supabase.from('customers').update({
      name: profile.name, phone: profile.phone, address: profile.address,
      city: profile.city, province: profile.province, zip: profile.zip,
    }).eq('id', user.id)
    setSaving(false)
    toast(error ? error.message : 'Profile saved', error ? 'error' : 'success')
  }

  async function logout() {
    await signOut()
    navigate('/')
  }

  if (loading || !profile) return <div className="section"><div className="loading-spinner" /></div>
  const set = (k) => (e) => setProfile((p) => ({ ...p, [k]: e.target.value }))

  return (
    <section className="section">
      <div className="flex justify-between items-center mb-3">
        <h2 className="section-title">MY <span>ACCOUNT</span></h2>
        <button className="btn btn-outline btn-sm" onClick={logout}>Log Out</button>
      </div>

      <div className="shop-layout">
        <form className="filter-panel" onSubmit={save}>
          <div className="filter-title">Profile</div>
          <div className="form-group">
            <label className="form-label">Name</label>
            <input className="form-control" value={profile.name || ''} onChange={set('name')} />
          </div>
          <div className="form-group">
            <label className="form-label">Email</label>
            <input className="form-control" value={profile.email || ''} disabled />
          </div>
          <div className="form-group">
            <label className="form-label">Phone</label>
            <input className="form-control" value={profile.phone || ''} onChange={set('phone')} />
          </div>
          <div className="form-group">
            <label className="form-label">Address</label>
            <input className="form-control" value={profile.address || ''} onChange={set('address')} />
          </div>
          <div className="form-group">
            <label className="form-label">City</label>
            <input className="form-control" value={profile.city || ''} onChange={set('city')} />
          </div>
          <div className="form-group">
            <label className="form-label">Province</label>
            <input className="form-control" value={profile.province || ''} onChange={set('province')} />
          </div>
          <button className="btn btn-primary btn-full" disabled={saving}>
            {saving ? 'Saving…' : 'Save Profile'}
          </button>
        </form>

        <div>
          <div className="filter-title">Order History</div>
          {orders.length === 0 ? (
            <p className="text-muted">No orders yet. <Link to="/shop" className="text-accent">Shop now</Link>.</p>
          ) : (
            orders.map((o) => (
              <div key={o.id} className="cart-item" style={{ alignItems: 'center' }}>
                <div className="cart-item-info">
                  <div className="cart-item-name font-mono">{o.tracking_code}</div>
                  <div className="cart-item-meta">
                    {new Date(o.created_at).toLocaleDateString()} · {o.status}
                  </div>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <div className="cart-item-price">{formatPrice(o.total)}</div>
                  <Link to={`/track?code=${o.tracking_code}`} className="text-accent" style={{ fontSize: '0.8rem' }}>Track →</Link>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </section>
  )
}
