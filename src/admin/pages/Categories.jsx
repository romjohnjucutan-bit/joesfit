import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabase.js'

const slugify = (s) => s.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')

export default function Categories() {
  const [rows, setRows] = useState([])
  const [editing, setEditing] = useState(null)
  const [loading, setLoading] = useState(true)
  const [msg, setMsg] = useState('')

  async function load() {
    setLoading(true)
    const { data } = await supabase.from('categories').select('*').order('name')
    setRows(data || [])
    setLoading(false)
  }
  useEffect(() => { load() }, [])

  async function remove(c) {
    if (!confirm(`Delete category "${c.name}"? Products in it must be moved/deleted first.`)) return
    const { error } = await supabase.from('categories').delete().eq('id', c.id)
    if (error) return setMsg(error.message)
    setMsg(`Deleted ${c.name}`); load()
  }

  return (
    <>
      <h1 className="admin-page-title">CATE<span>GORIES</span></h1>
      {msg && <div className="admin-alert success">{msg}</div>}
      <div className="admin-toolbar">
        <button className="btn btn-primary btn-sm" onClick={() => setEditing({ name: '', slug: '', description: '', is_active: true })}>+ Add Category</button>
      </div>
      <div className="admin-card">
        {loading ? <div className="loading-spinner" /> : (
          <div className="admin-table-wrap">
            <table className="admin-table">
              <thead><tr><th>Name</th><th>Slug</th><th>Description</th><th>Active</th><th></th></tr></thead>
              <tbody>
                {rows.map((c) => (
                  <tr key={c.id}>
                    <td>{c.name}</td>
                    <td className="font-mono text-muted">{c.slug}</td>
                    <td className="text-muted" style={{ maxWidth: 320 }}>{c.description}</td>
                    <td>{c.is_active ? '✅' : '—'}</td>
                    <td style={{ whiteSpace: 'nowrap' }}>
                      <button className="icon-btn" onClick={() => setEditing(c)}>✏️</button>
                      <button className="icon-btn" onClick={() => remove(c)}>🗑️</button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
      {editing && (
        <CategoryModal cat={editing} onClose={() => setEditing(null)}
          onSaved={(m) => { setEditing(null); setMsg(m); load() }} />
      )}
    </>
  )
}

function CategoryModal({ cat, onClose, onSaved }) {
  const [form, setForm] = useState({ ...cat })
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const isNew = !cat.id
  const set = (k) => (e) => {
    const v = e.target.type === 'checkbox' ? e.target.checked : e.target.value
    setForm((f) => ({ ...f, [k]: v }))
  }
  async function save(e) {
    e.preventDefault(); setError(''); setSaving(true)
    const payload = {
      name: form.name, slug: form.slug || slugify(form.name),
      description: form.description || null, is_active: !!form.is_active,
    }
    const res = isNew
      ? await supabase.from('categories').insert(payload)
      : await supabase.from('categories').update(payload).eq('id', cat.id)
    setSaving(false)
    if (res.error) return setError(res.error.message)
    onSaved(isNew ? 'Category created' : 'Category updated')
  }
  return (
    <div className="admin-modal-overlay" onClick={onClose}>
      <form className="admin-modal" onClick={(e) => e.stopPropagation()} onSubmit={save}>
        <div className="admin-modal-header">
          <h3>{isNew ? 'Add Category' : 'Edit Category'}</h3>
          <button type="button" className="cart-close" onClick={onClose}>✕</button>
        </div>
        <div className="admin-modal-body">
          {error && <div className="admin-alert error">{error}</div>}
          <div className="form-group">
            <label className="form-label">Name *</label>
            <input className="form-control" required value={form.name} onChange={set('name')} />
          </div>
          <div className="form-group">
            <label className="form-label">Slug</label>
            <input className="form-control" value={form.slug || ''} onChange={set('slug')} placeholder="auto from name" />
          </div>
          <div className="form-group">
            <label className="form-label">Description</label>
            <textarea className="form-control" rows="2" value={form.description || ''} onChange={set('description')} />
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
