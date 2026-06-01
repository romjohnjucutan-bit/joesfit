import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { supabase } from '../lib/supabase.js'
import ProductCard from '../components/ProductCard.jsx'

export default function Home() {
  const [featured, setFeatured] = useState([])
  const [categories, setCategories] = useState([])
  const [reviews, setReviews] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    async function load() {
      const [{ data: prods }, { data: cats }, { data: revs }] = await Promise.all([
        supabase
          .from('products')
          .select('*, categories(name)')
          .eq('is_active', true)
          .eq('is_featured', true)
          .limit(8),
        supabase.from('categories').select('*').eq('is_active', true),
        supabase
          .from('reviews')
          .select('*')
          .eq('is_approved', true)
          .order('created_at', { ascending: false })
          .limit(3),
      ])
      setFeatured((prods || []).map((p) => ({ ...p, category_name: p.categories?.name })))
      setCategories(cats || [])
      setReviews(revs || [])
      setLoading(false)
    }
    load()
  }, [])

  return (
    <>
      <section className="hero">
        <div className="hero-content">
          <div className="hero-eyebrow">New Season · 2026</div>
          <h1 className="hero-title">
            WEAR<br />
            <span className="line-accent">THE</span> <span className="line-stroke">BOLD</span>
          </h1>
          <p className="hero-desc">
            Premium varsity, bomber, windbreaker and leather jackets — crafted
            for those who stand out. Built to last, designed to turn heads.
          </p>
          <div className="hero-cta">
            <Link to="/shop" className="btn btn-primary btn-lg">Shop Now</Link>
            <Link to="/track" className="btn btn-outline btn-lg">Track Order</Link>
          </div>
        </div>
        <div className="hero-visual">
          <div className="img-placeholder" style={{ minHeight: '100%' }}>
            <span style={{ fontSize: '4rem' }}>🧥</span>
            JOE'S FIT
          </div>
          <div className="hero-badge">
            8+ STYLES
            <small>Premium Jackets</small>
          </div>
        </div>
      </section>

      <div className="categories-strip">
        {categories.map((c) => (
          <Link key={c.id} to={`/shop?category=${c.slug}`} className="cat-chip">{c.name}</Link>
        ))}
      </div>

      <section className="section">
        <div className="section-header">
          <div>
            <h2 className="section-title">FEATURED <span>DROPS</span></h2>
          </div>
          <Link to="/shop" className="btn btn-outline btn-sm">View All</Link>
        </div>
        {loading ? (
          <div className="loading-spinner" />
        ) : (
          <div className="products-grid">
            {featured.map((p) => <ProductCard key={p.id} product={p} />)}
          </div>
        )}
      </section>

      <section className="section-sm">
        <div className="features-grid">
          <Feature icon="🚚" title="Fast Delivery" desc="Standard & express shipping nationwide." />
          <Feature icon="↩️" title="Easy Returns" desc="7-day hassle-free return policy." />
          <Feature icon="🔒" title="Secure Checkout" desc="COD, GCash, Maya & card supported." />
          <Feature icon="⭐" title="Verified Reviews" desc="Real reviews from real buyers." />
        </div>
      </section>

      {reviews.length > 0 && (
        <section className="section">
          <div className="section-header">
            <h2 className="section-title">WHAT THEY <span>SAY</span></h2>
          </div>
          <div className="reviews-grid">
            {reviews.map((r) => (
              <div key={r.id} className="review-card">
                <div className="review-stars">{'★'.repeat(r.rating)}{'☆'.repeat(5 - r.rating)}</div>
                <p className="review-text">"{r.body}"</p>
                <div className="review-author">
                  <div className="review-avatar">{r.customer_name.charAt(0)}</div>
                  <div>
                    <div className="review-name">{r.customer_name}</div>
                    {r.title && <div className="review-product">{r.title}</div>}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>
      )}
    </>
  )
}

function Feature({ icon, title, desc }) {
  return (
    <div className="feature-card">
      <div className="feature-icon">{icon}</div>
      <div className="feature-title">{title}</div>
      <div className="feature-desc">{desc}</div>
    </div>
  )
}
