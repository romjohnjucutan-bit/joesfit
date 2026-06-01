import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabase.js'
import { formatPrice } from '../../lib/format.js'
import { useAdminAuth } from '../AdminAuthContext.jsx'

export default function Coupons() {
  const { isAdmin } = useAdminAuth()
  const [rows, setRows] = useState([])
  const [editing, setEditing] = useState(null)
  const [loading, setLoading] = useState(true)
  const [msg, setMsg] = useState('')

  async function load() {
    setLoading(true)
    const { data } = await supabase.from('coupons').select('*').order('id')
    setRows(data || [])
    setLoading(false)
  }
  useEffect(() => { load() }, [])

  async function remove(c) {
    if (!confirm(`Delete coupon ${c.code}?`)) return
    const { error } = await supabase.from('coupons').delete().eq('id', c.id)
    if (error) return setMsg(error.message)
    setMsg(`Deleted ${c.code}`); load()
  }

  return (
    <>
      <h1 className="admin-page-title">COU<span>PONS</span></h1>
      {msg && <div className="admin-alert success">{msg}</div>}
      {!isAdmin && <div className="admin-alert info">Coupons are managed by admins. You have read-only access.</div>}

      {isAdmin && (
        <div className="admin-toolbar">
          <button className="btn btn-primary btn-sm"
            onClick={() => setEditing({ code: '', type: 'percent', value: '', min_order: 0, max_uses: '', expires_at: '', is_active: true })}>
            + Add Coupon
          </button>
        </div>
      )}

      <div className="admin-card">
        {loading ? <div className="loading-spinner" /> : (
          <div className="admin-table-wrap">
            <table className="admin-table">
              <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Used / Max</th><th>Expires</th><th>Active</th>{isAdmin && <th></th>}</tr></thead>
              <tbody>
                {rows.map((c) => (
                  <tr key={c.id}>
                    <td className="font-mono" style={{ fontWeight: 700 }}>{c.code}</td>
                    <td>{c.type}</td>
                    <td>{c.type === 'percent' ? `${c.value}%` : formatPrice(c.value)}</td>
                    <td>{formatPrice(c.min_order)}</td>
                    <td>{c.used_count}{c.max_uses ? ` / ${c.max_uses}` : ''}</td>
                    <td>{c.expires_at || '—'}</td>
                    <td>{c.is_active ? '✅' : '—'}</td>
                    {isAdmin && (
                      <td style={{ whiteSpace: 'nowrap' }}>
                        <button className="icon-btn" onClick={() => setEditing(c)}>✏️</button>
                        <button className="icon-btn" onClick={() => remove(c)}>🗑️</button>
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {editing && (
        <CouponModal coupon={editing} onClose={() => setEditing(null)}
          onSaved={(m) => { setEditing(null); setMsg(m); load() }} />
      )}
    </>
  )
}

function CouponModal({ coupon, onClose, onSaved }) {
  const [form, setForm] = useState({ ...coupon })
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const isNew = !coupon.id
  const set = (k) => (e) => {
    const v = e.target.type === 'checkbox' ? e.target.checked : e.target.value
    setForm((f) => ({ ...f, [k]: v }))
  }
  async function save(e) {
    e.preventDefault(); setError(''); setSaving(true)
    const payload = {
      code: String(form.code).toUpperCase().trim(),
      type: form.type,
      value: Number(form.value),
      min_order: Number(form.min_order) || 0,
      max_uses: form.max_uses === '' || form.max_uses == null ? null : Number(form.max_uses),
      expires_at: form.expires_at || null,
      is_active: !!form.is_active,
    }
    const res = isNew
      ? await supabase.from('coupons').insert(payload)
      : await supabase.from('coupons').update(payload).eq('id', coupon.id)
    setSaving(false)
    if (res.error) return setError(res.error.message)
    onSaved(isNew ? 'Coupon created' : 'Coupon updated')
  }
  return (
    <div className="admin-modal-overlay" onClick={onClose}>
      <form className="admin-modal" onClick={(e) => e.stopPropagation()} onSubmit={save}>
        <div className="admin-modal-header">
          <h3>{isNew ? 'Add Coupon' : 'Edit Coupon'}</h3>
          <button type="button" className="cart-close" onClick={onClose}>✕</button>
        </div>
        <div className="admin-modal-body">
          {error && <div className="admin-alert error">{error}</div>}
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Code *</label>
              <input className="form-control" required value={form.code} onChange={set('code')} style={{ textTransform: 'uppercase' }} />
            </div>
            <div className="form-group">
              <label className="form-label">Type</label>
              <select className="form-control" value={form.type} onChange={set('type')}>
                <option value="percent">Percent (%)</option>
                <option value="fixed">Fixed (₱)</option>
              </select>
            </div>
          </div>
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Value *</label>
              <input className="form-control" type="number" step="0.01" required value={form.value} onChange={set('value')} />
            </div>
            <div className="form-group">
              <label className="form-label">Min Order</label>
              <input className="form-control" type="number" step="0.01" value={form.min_order} onChange={set('min_order')} />
            </div>
          </div>
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Max Uses</label>
              <input className="form-control" type="number" value={form.max_uses ?? ''} onChange={set('max_uses')} placeholder="unlimited" />
            </div>
            <div className="form-group">
              <label className="form-label">Expires</label>
              <input className="form-control" type="date" value={form.expires_at || ''} onChange={set('expires_at')} />
            </div>
          </div>
          <label className="check-option"><input type="checkbox" checked={!!form.is_active} onChange={set('is_active')} /> Active</label>
        </div>
        <div className="admin-modal-footer">
          <button type="button" className="btn btn-outline btn-sm" onClick={onClose}>Cancel</button>
          <button className="btn btn-primary btn-sm" disabled={saving}>{saving ? 'Saving…' : 'Save'}</button>
        </div>
      </form>
    </div>
  )
}
