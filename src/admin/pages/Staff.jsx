import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabase.js'
import { useAdminAuth } from '../AdminAuthContext.jsx'

export default function Staff() {
  const { isAdmin, staffUser } = useAdminAuth()
  const [rows, setRows] = useState([])
  const [loading, setLoading] = useState(true)
  const [msg, setMsg] = useState('')
  const [adding, setAdding] = useState(false)

  async function load() {
    setLoading(true)
    const { data } = await supabase.from('staff').select('*').order('created_at')
    setRows(data || [])
    setLoading(false)
  }
  useEffect(() => { load() }, [])

  async function toggleActive(s) {
    const { error } = await supabase.from('staff').update({ is_active: !s.is_active }).eq('id', s.id)
    if (error) return setMsg(error.message)
    setMsg(`${s.name} ${s.is_active ? 'deactivated' : 'reactivated'}`); load()
  }

  if (!isAdmin) {
    return (
      <>
        <h1 className="admin-page-title">STAFF</h1>
        <div className="admin-alert info">Only admins can manage staff accounts.</div>
      </>
    )
  }

  return (
    <>
      <h1 className="admin-page-title">ST<span>AFF</span></h1>
      {msg && <div className="admin-alert success">{msg}</div>}
      <div className="admin-toolbar">
        <button className="btn btn-primary btn-sm" onClick={() => setAdding(true)}>+ Add Staff</button>
      </div>

      <div className="admin-card">
        {loading ? <div className="loading-spinner" /> : (
          <div className="admin-table-wrap">
            <table className="admin-table">
              <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Status</th><th></th></tr></thead>
              <tbody>
                {rows.map((s) => (
                  <tr key={s.id}>
                    <td>{s.name}</td>
                    <td>{s.email}</td>
                    <td><span className={`admin-role-badge ${s.role}`}>{s.role}</span></td>
                    <td>{s.phone || '—'}</td>
                    <td>{s.is_active ? '✅ Active' : '🚫 Inactive'}</td>
                    <td>
                      {s.email === staffUser.email
                        ? <span className="text-muted" style={{ fontSize: '0.78rem' }}>You</span>
                        : <button className="btn btn-outline btn-sm" onClick={() => toggleActive(s)}>
                            {s.is_active ? 'Deactivate' : 'Reactivate'}
                          </button>}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {adding && (
        <AddStaffModal onClose={() => setAdding(false)}
          onSaved={(m) => { setAdding(false); setMsg(m); load() }} />
      )}
    </>
  )
}

function AddStaffModal({ onClose, onSaved }) {
  const [form, setForm] = useState({ name: '', email: '', password: '', role: 'staff', phone: '' })
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }))

  async function save(e) {
    e.preventDefault(); setError(''); setSaving(true)
    const { data, error: fnErr } = await supabase.functions.invoke('create-staff', {
      body: {
        name: form.name, email: form.email.trim().toLowerCase(),
        password: form.password, role: form.role, phone: form.phone || null,
      },
    })
    setSaving(false)
    if (fnErr || !data?.success) {
      setError(data?.error || fnErr?.message || 'Could not create staff account')
      return
    }
    onSaved(`Created ${form.role} account for ${form.email}`)
  }

  return (
    <div className="admin-modal-overlay" onClick={onClose}>
      <form className="admin-modal" onClick={(e) => e.stopPropagation()} onSubmit={save}>
        <div className="admin-modal-header">
          <h3>Add Staff Account</h3>
          <button type="button" className="cart-close" onClick={onClose}>✕</button>
        </div>
        <div className="admin-modal-body">
          {error && <div className="admin-alert error">{error}</div>}
          <div className="form-group">
            <label className="form-label">Full Name *</label>
            <input className="form-control" required value={form.name} onChange={set('name')} />
          </div>
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Email *</label>
              <input className="form-control" type="email" required value={form.email} onChange={set('email')} />
            </div>
            <div className="form-group">
              <label className="form-label">Role</label>
              <select className="form-control" value={form.role} onChange={set('role')}>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Password * (min 6)</label>
              <input className="form-control" type="text" required minLength={6} value={form.password} onChange={set('password')} />
            </div>
            <div className="form-group">
              <label className="form-label">Phone</label>
              <input className="form-control" value={form.phone} onChange={set('phone')} />
            </div>
          </div>
          <p className="text-muted" style={{ fontSize: '0.8rem' }}>
            The account is created instantly and the person can log in at <strong>/admin</strong> with this email &amp; password.
          </p>
        </div>
        <div className="admin-modal-footer">
          <button type="button" className="btn btn-outline btn-sm" onClick={onClose}>Cancel</button>
          <button className="btn btn-primary btn-sm" disabled={saving}>{saving ? 'Creating…' : 'Create Account'}</button>
        </div>
      </form>
    </div>
  )
}
