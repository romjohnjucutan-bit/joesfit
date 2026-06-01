import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { supabase } from '../lib/supabase.js'
import { useAdminAuth } from './AdminAuthContext.jsx'

export default function AdminLogin() {
  const { signIn, signOut, staffUser, loading } = useAdminAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [busy, setBusy] = useState(false)

  // If already a valid staff session, skip straight to the dashboard.
  useEffect(() => {
    if (!loading && staffUser) navigate('/admin', { replace: true })
  }, [loading, staffUser, navigate])

  async function submit(e) {
    e.preventDefault()
    setError(''); setBusy(true)
    const { data, error: signErr } = await signIn(email.trim(), password)
    if (signErr) { setBusy(false); setError(signErr.message); return }

    // Verify the account is actually staff before letting them in.
    const { data: staff } = await supabase
      .from('staff').select('is_active').eq('email', data.user.email).maybeSingle()
    setBusy(false)
    if (!staff || !staff.is_active) {
      await signOut()
      setError('This account does not have staff access.')
      return
    }
    navigate('/admin', { replace: true })
  }

  return (
    <div className="admin-login">
      <form className="admin-login-card" onSubmit={submit}>
        <div className="admin-login-logo">JOE'S<span>FIT</span></div>
        <div className="admin-login-sub">Staff &amp; Admin Portal</div>

        {error && <div className="admin-alert error">{error}</div>}

        <label className="form-label">Email</label>
        <input className="form-control" type="email" required value={email}
          onChange={(e) => setEmail(e.target.value)} autoFocus />

        <label className="form-label" style={{ marginTop: '1rem' }}>Password</label>
        <input className="form-control" type="password" required value={password}
          onChange={(e) => setPassword(e.target.value)} />

        <button className="btn btn-primary btn-full" style={{ marginTop: '1.5rem' }} disabled={busy}>
          {busy ? 'Signing in…' : 'Sign In'}
        </button>
      </form>
    </div>
  )
}
