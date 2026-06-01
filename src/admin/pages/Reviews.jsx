import { useEffect, useState } from 'react'
import { supabase } from '../../lib/supabase.js'

export default function Reviews() {
  const [rows, setRows] = useState([])
  const [loading, setLoading] = useState(true)
  const [msg, setMsg] = useState('')
  const [filter, setFilter] = useState('all')

  async function load() {
    setLoading(true)
    const { data } = await supabase
      .from('reviews')
      .select('*, products(name)')
      .order('created_at', { ascending: false })
    setRows(data || [])
    setLoading(false)
  }
  useEffect(() => { load() }, [])

  async function setApproved(r, approved) {
    const { error } = await supabase.from('reviews').update({ is_approved: approved }).eq('id', r.id)
    if (error) return setMsg(error.message)
    setRows((rs) => rs.map((x) => (x.id === r.id ? { ...x, is_approved: approved } : x)))
    setMsg(approved ? 'Review approved' : 'Review unapproved')
  }
  async function remove(r) {
    if (!confirm('Delete this review?')) return
    const { error } = await supabase.from('reviews').delete().eq('id', r.id)
    if (error) return setMsg(error.message)
    setMsg('Review deleted'); load()
  }

  const shown = rows.filter((r) =>
    filter === 'all' ? true : filter === 'approved' ? r.is_approved : !r.is_approved)

  return (
    <>
      <h1 className="admin-page-title">RE<span>VIEWS</span></h1>
      {msg && <div className="admin-alert success">{msg}</div>}

      <div className="admin-toolbar">
        <select className="form-control" value={filter} onChange={(e) => setFilter(e.target.value)}>
          <option value="all">All</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
        </select>
        <div className="admin-spacer" />
        <span className="text-muted" style={{ fontSize: '0.85rem' }}>{shown.length} reviews</span>
      </div>

      <div className="admin-card">
        {loading ? <div className="loading-spinner" /> : (
          <div className="admin-table-wrap">
            <table className="admin-table">
              <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Review</th><th>Status</th><th></th></tr></thead>
              <tbody>
                {shown.map((r) => (
                  <tr key={r.id}>
                    <td>{r.products?.name}</td>
                    <td>{r.customer_name}</td>
                    <td style={{ color: 'var(--gold)' }}>{'★'.repeat(r.rating)}{'☆'.repeat(5 - r.rating)}</td>
                    <td style={{ maxWidth: 320 }}>
                      {r.title && <strong>{r.title}<br /></strong>}
                      <span className="text-muted">{r.body}</span>
                    </td>
                    <td>{r.is_approved ? '✅ Approved' : '⏳ Pending'}</td>
                    <td style={{ whiteSpace: 'nowrap' }}>
                      {r.is_approved
                        ? <button className="icon-btn" title="Unapprove" onClick={() => setApproved(r, false)}>↩️</button>
                        : <button className="icon-btn" title="Approve" onClick={() => setApproved(r, true)}>✅</button>}
                      <button className="icon-btn" title="Delete" onClick={() => remove(r)}>🗑️</button>
                    </td>
                  </tr>
                ))}
                {shown.length === 0 && <tr><td colSpan="6" className="text-muted">No reviews.</td></tr>}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  )
}
