import { Link } from 'react-router-dom'

export default function NotFound() {
  return (
    <section className="section text-center">
      <h1 className="section-title" style={{ fontSize: '6rem' }}>4<span>0</span>4</h1>
      <p className="text-muted mb-3">This page took a wrong turn.</p>
      <Link to="/" className="btn btn-primary">Back Home</Link>
    </section>
  )
}
