import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'
import { useToast } from '../context/ToastContext.jsx'

export default function Login() {
  const { signIn, signUp } = useAuth()
  const { toast } = useToast()
  const navigate = useNavigate()
  const [mode, setMode] = useState('login')
  const [form, setForm] = useState({ name: '', email: '', password: '' })
  const [busy, setBusy] = useState(false)

  const set = (k) => (e) => setForm((f) => ({ ...f, [k]: e.target.value }))

  async function submit(e) {
    e.preventDefault()
    setBusy(true)
    if (mode === 'login') {
      const { error } = await signIn(form.email, form.password)
      setBusy(false)
      if (error) return toast(error.message, 'error')
      toast('Welcome back!', 'success')
      navigate('/account')
    } else {
      const { error } = await signUp(form.email, form.password, form.name)
      setBusy(false)
      if (error) return toast(error.message, 'error')
      toast('Account created! Check your email to confirm, then log in.', 'success')
      setMode('login')
    }
  }

  return (
    <section className="section">
      <div className="modal" style={{ maxWidth: 420, margin: '0 auto' }}>
        <div className="modal-header">
          <h2>{mode === 'login' ? 'Log In' : 'Sign Up'}</h2>
        </div>
        <form className="modal-body" onSubmit={submit}>
          {mode === 'signup' && (
            <div className="form-group">
              <label className="form-label">Full Name</label>
              <input className="form-control" required value={form.name} onChange={set('name')} />
            </div>
          )}
          <div className="form-group">
            <label className="form-label">Email</label>
            <input className="form-control" type="email" required value={form.email} onChange={set('email')} />
          </div>
          <div className="form-group">
            <label className="form-label">Password</label>
            <input className="form-control" type="password" required minLength={6}
              value={form.password} onChange={set('password')} />
          </div>
          <button className="btn btn-primary btn-full" disabled={busy}>
            {busy ? 'Please wait…' : mode === 'login' ? 'Log In' : 'Create Account'}
          </button>
          <p className="text-center text-muted mt-3" style={{ fontSize: '0.85rem' }}>
            {mode === 'login' ? "No account? " : 'Already have an account? '}
            <button type="button" className="text-accent" style={{ fontWeight: 600 }}
              onClick={() => setMode(mode === 'login' ? 'signup' : 'login')}>
              {mode === 'login' ? 'Sign up' : 'Log in'}
            </button>
          </p>
        </form>
      </div>
    </section>
  )
}
