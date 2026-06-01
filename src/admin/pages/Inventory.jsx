import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabase.js'

export default function Inventory() {
  const [rows, setRows] = useState([])
  const [edits, setEdits] = useState({})
  const [loading, setLoading] = useState(true)
  const [msg, setMsg] = useState('')
  const [saving, setSaving] = useState(false)

  async function load() {
    setLoading(true)
    const { data } = await supabase.from('products').select('id,name,sku,stock').order('name')
    setRows(data || [])
    setEdits({})
    setLoading(false)
  }
  useEffect(() => { load() }, [])

  const changed = Object.keys(edits).filter((id) => {
    const r = rows.find((x) => x.id === Number(id))
    return r && String(r.stock) !== String(edits[id])
  })

  async function saveAll() {
    setSaving(true); setMsg('')
    for (const id of changed) {
      await supabase.from('products').update({ stock: Number(edits[id]) }).eq('id', Number(id))
    }
    setSaving(false)
    setMsg(`Updated stock for ${changed.length} product(s)`)
    load()
  }

  return (
    <>
      <h1 className="admin-page-title">INVEN<span>TORY</span></h1>
      {msg && <div className="admin-alert success">{msg}</div>}

      <div className="admin-toolbar">
        <button className="btn btn-primary btn-sm" disabled={changed.length === 0 || saving} onClick={saveAll}>
          {saving ? 'Saving…' : `Save Changes${changed.length ? ` (${changed.length})` : ''}`}
        </button>
        <div className="admin-spacer" />
        <span className="text-muted" style={{ fontSize: '0.85rem' }}>Low-stock rows highlighted</span>
      </div>

      <div className="admin-card">
        {loading ? <div className="loading-spinner" /> : (
          <div className="admin-table-wrap">
            <table className="admin-table">
              <thead><tr><th>Product</th><th>SKU</th><th>Current</th><th>New Stock</th></tr></thead>
              <tbody>
                {rows.map((r) => {
                  const val = edits[r.id] ?? r.stock
                  const low = Number(val) <= 5
                  return (
                    <tr key={r.id}>
                      <td>{r.name}</td>
                      <td className="font-mono text-muted">{r.sku}</td>
                      <td style={{ color: low ? 'var(--accent)' : 'inherit', fontWeight: low ? 700 : 400 }}>{r.stock}</td>
                      <td>
                        <input className="form-control" type="number" style={{ maxWidth: 110 }}
                          value={val}
                          onChange={(e) => setEdits((s) => ({ ...s, [r.id]: e.target.value }))} />
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  )
}
