import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabase.js'
import { formatPrice, productImage } from '../../lib/format.js'

const slugify = (s) => s.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')

const EMPTY = {
  name: '', slug: '', category_id: '', description: '', price: '', sale_price: '',
  stock: 0, sku: '', sizes: '', colors: '', image: '', is_featured: false, is_active: true,
}

export default function Products() {
  const [products, setProducts] = useState([])
  const [categories, setCategories] = useState([])
  const [editing, setEditing] = useState(null)
  const [loading, setLoading] = useState(true)
  const [msg, setMsg] = useState('')

  async function load() {
    setLoading(true)
    const [p, c] = await Promise.all([
      supabase.from('products').select('*, categories(name)').order('id'),
      supabase.from('categories').select('id,name').order('name'),
    ])
    setProducts(p.data || [])
    setCategories(c.data || [])
    setLoading(false)
  }
  useEffect(() => { load() }, [])

  async function remove(p) {
    if (!confirm(`Delete "${p.name}"? This cannot be undone.`)) return
    const { error } = await supabase.from('products').delete().eq('id', p.id)
    if (error) return setMsg(error.message)
    setMsg(`Deleted ${p.name}`)
    load()
  }

  return (
    <>
      <h1 className="admin-page-title">PROD<span>UCTS</span></h1>
      {msg && <div className="admin-alert success">{msg}</div>}

      <div className="admin-toolbar">
        <button className="btn btn-primary btn-sm" onClick={() => setEditing({ ...EMPTY })}>
          + Add Product
        </button>
        <div className="admin-spacer" />
        <span className="text-muted" style={{ fontSize: '0.85rem' }}>{products.length} products</span>
      </div>

      <div className="admin-card">
        {loading ? <div className="loading-spinner" /> : (
          <div className="admin-table-wrap">
            <table className="admin-table">
              <thead>
                <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Active</th><th></th></tr>
              </thead>
              <tbody>
                {products.map((p) => {
                  const img = productImage(p.image)
                  return (
                    <tr key={p.id}>
                      <td>{img ? <img className="thumb" src={img} alt="" /> : <div className="thumb" />}</td>
                      <td>{p.name}{p.is_featured && ' ⭐'}</td>
                      <td>{p.categories?.name}</td>
                      <td>{formatPrice(p.sale_price ?? p.price)}</td>
                      <td style={{ color: p.stock <= 5 ? 'var(--accent)' : 'inherit', fontWeight: p.stock <= 5 ? 700 : 400 }}>{p.stock}</td>
                      <td>{p.is_active ? '✅' : '—'}</td>
                      <td style={{ whiteSpace: 'nowrap' }}>
                        <button className="icon-btn" title="Edit" onClick={() => setEditing(p)}>✏️</button>
                        <button className="icon-btn" title="Delete" onClick={() => remove(p)}>🗑️</button>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {editing && (
        <ProductModal
          product={editing}
          categories={categories}
          onClose={() => setEditing(null)}
          onSaved={(m) => { setEditing(null); setMsg(m); load() }}
        />
      )}
    </>
  )
}

function ProductModal({ product, categories, onClose, onSaved }) {
  const [form, setForm] = useState({
    ...EMPTY, ...product,
    category_id: product.category_id || (categories[0]?.id ?? ''),
  })
  const [saving, setSaving] = useState(false)
  const [uploading, setUploading] = useState(false)
  const [error, setError] = useState('')
  const isNew = !product.id

  const set = (k) => (e) => {
    const v = e.target.type === 'checkbox' ? e.target.checked : e.target.value
    setForm((f) => ({ ...f, [k]: v }))
  }

  async function upload(e) {
    const file = e.target.files?.[0]
    if (!file) return
    setUploading(true); setError('')
    const ext = file.name.split('.').pop()
    const path = `${Date.now()}_${slugify(form.name || 'product')}.${ext}`
    const { error: upErr } = await supabase.storage.from('products').upload(path, file, { upsert: true })
    setUploading(false)
    if (upErr) return setError(upErr.message)
    setForm((f) => ({ ...f, image: path }))
  }

  async function save(e) {
    e.preventDefault()
    setError(''); setSaving(true)
    const payload = {
      name: form.name,
      slug: form.slug || slugify(form.name),
      category_id: Number(form.category_id),
      description: form.description || null,
      price: Number(form.price),
      sale_price: form.sale_price === '' || form.sale_price == null ? null : Number(form.sale_price),
      stock: Number(form.stock) || 0,
      sku: form.sku || null,
      sizes: form.sizes || null,
      colors: form.colors || null,
      image: form.image || null,
      is_featured: !!form.is_featured,
      is_active: !!form.is_active,
    }
    const res = isNew
      ? await supabase.from('products').insert(payload)
      : await supabase.from('products').update(payload).eq('id', product.id)
    setSaving(false)
    if (res.error) return setError(res.error.message)
    onSaved(isNew ? 'Product created' : 'Product updated')
  }

  const img = productImage(form.image)

  return (
    <div className="admin-modal-overlay" onClick={onClose}>
      <form className="admin-modal" onClick={(e) => e.stopPropagation()} onSubmit={save}>
        <div className="admin-modal-header">
          <h3>{isNew ? 'Add Product' : 'Edit Product'}</h3>
          <button type="button" className="cart-close" onClick={onClose}>✕</button>
        </div>
        <div className="admin-modal-body">
          {error && <div className="admin-alert error">{error}</div>}
          <div className="form-group">
            <label className="form-label">Name *</label>
            <input className="form-control" required value={form.name} onChange={set('name')} />
          </div>
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Category *</label>
              <select className="form-control" required value={form.category_id} onChange={set('category_id')}>
                {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
            </div>
            <div className="form-group">
              <label className="form-label">SKU</label>
              <input className="form-control" value={form.sku || ''} onChange={set('sku')} />
            </div>
          </div>
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Price *</label>
              <input className="form-control" type="number" step="0.01" required value={form.price} onChange={set('price')} />
            </div>
            <div className="form-group">
              <label className="form-label">Sale Price</label>
              <input className="form-control" type="number" step="0.01" value={form.sale_price ?? ''} onChange={set('sale_price')} />
            </div>
          </div>
          <div className="form-grid">
            <div className="form-group">
              <label className="form-label">Stock</label>
              <input className="form-control" type="number" value={form.stock} onChange={set('stock')} />
            </div>
            <div className="form-group">
              <label className="form-label">Sizes (comma-separated)</label>
              <input className="form-control" value={form.sizes || ''} onChange={set('sizes')} placeholder="S,M,L,XL" />
            </div>
          </div>
          <div className="form-group">
            <label className="form-label">Colors (comma-separated)</label>
            <input className="form-control" value={form.colors || ''} onChange={set('colors')} placeholder="Black,Navy,Red" />
          </div>
          <div className="form-group">
            <label className="form-label">Description</label>
            <textarea className="form-control" rows="3" value={form.description || ''} onChange={set('description')} />
          </div>
          <div className="form-group">
            <label className="form-label">Image</label>
            <div className="flex items-center gap-2">
              {img && <img className="thumb" src={img} alt="" style={{ width: 48, height: 60 }} />}
              <input type="file" accept="image/*" onChange={upload} />
              {uploading && <span className="text-muted" style={{ fontSize: '0.8rem' }}>Uploading…</span>}
            </div>
          </div>
          <div className="flex gap-2">
            <label className="check-option"><input type="checkbox" checked={!!form.is_featured} onChange={set('is_featured')} /> Featured</label>
            <label className="check-option"><input type="checkbox" checked={!!form.is_active} onChange={set('is_active')} /> Active</label>
          </div>
        </div>
        <div className="admin-modal-footer">
          <button type="button" className="btn btn-outline btn-sm" onClick={onClose}>Cancel</button>
          <button className="btn btn-primary btn-sm" disabled={saving || uploading}>
            {saving ? 'Saving…' : 'Save Product'}
          </button>
        </div>
      </form>
    </div>
  )
}
