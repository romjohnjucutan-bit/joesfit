import { useEffect, useState } from 'react'
import { useParams, Link } from 'react-router-dom'
import { supabase } from '../lib/supabase.js'
import { formatPrice, productImage, splitList } from '../lib/format.js'
import { useCart } from '../context/CartContext.jsx'
import { useToast } from '../context/ToastContext.jsx'

export default function Product() {
  const { slug } = useParams()
  const { addItem } = useCart()
  const { toast } = useToast()
  const [product, setProduct] = useState(null)
  const [reviews, setReviews] = useState([])
  const [loading, setLoading] = useState(true)
  const [size, setSize] = useState('')
  const [color, setColor] = useState('')
  const [qty, setQty] = useState(1)
  const [tab, setTab] = useState('description')

  useEffect(() => {
    async function load() {
      setLoading(true)
      const { data } = await supabase
        .from('products')
        .select('*, categories(name, slug)')
        .eq('slug', slug)
        .eq('is_active', true)
        .maybeSingle()
      if (data) {
        setProduct(data)
        const sizes = splitList(data.sizes)
        const colors = splitList(data.colors)
        setSize(sizes[0] || '')
        setColor(colors[0] || '')
        const { data: revs } = await supabase
          .from('reviews').select('*').eq('product_id', data.id).eq('is_approved', true)
          .order('created_at', { ascending: false })
        setReviews(revs || [])
      }
      setLoading(false)
    }
    load()
  }, [slug])

  if (loading) return <div className="section"><div className="loading-spinner" /></div>
  if (!product) {
    return (
      <div className="section text-center">
        <h2 className="section-title">Not <span>Found</span></h2>
        <Link to="/shop" className="btn btn-primary mt-2">Back to Shop</Link>
      </div>
    )
  }

  const sizes = splitList(product.sizes)
  const colors = splitList(product.colors)
  const onSale = product.sale_price != null
  const price = onSale ? product.sale_price : product.price
  const img = productImage(product.image)
  const outOfStock = product.stock <= 0

  function add() {
    addItem(product, { size, color, quantity: qty })
    toast(`${product.name} added to cart`)
  }

  const avgRating = reviews.length
    ? (reviews.reduce((s, r) => s + r.rating, 0) / reviews.length).toFixed(1)
    : null

  return (
    <section className="section">
      <div className="product-detail-grid">
        <div className="product-gallery">
          <div className="gallery-main">
            {img ? <img src={img} alt={product.name} /> : <div className="img-placeholder"><span style={{ fontSize: '4rem' }}>🧥</span>{product.name}</div>}
          </div>
        </div>

        <div>
          {product.categories?.name && (
            <div className="product-category">{product.categories.name}</div>
          )}
          <h1 className="section-title" style={{ fontSize: '3rem', marginBottom: '1rem' }}>{product.name}</h1>

          <div className="product-price mb-3" style={{ fontSize: '1.4rem' }}>
            <span className="price-current" style={{ fontSize: '1.8rem' }}>{formatPrice(price)}</span>
            {onSale && <span className="price-original">{formatPrice(product.price)}</span>}
          </div>

          {avgRating && (
            <div className="review-stars mb-2">
              {'★'.repeat(Math.round(avgRating))}{'☆'.repeat(5 - Math.round(avgRating))}
              <span className="text-muted" style={{ marginLeft: 8, fontSize: '0.85rem' }}>
                {avgRating} ({reviews.length} review{reviews.length > 1 ? 's' : ''})
              </span>
            </div>
          )}

          <p className="hero-desc" style={{ marginBottom: '2rem' }}>{product.description}</p>

          {sizes.length > 0 && (
            <>
              <div className="form-label">Size</div>
              <div className="product-sizes">
                {sizes.map((s) => (
                  <button key={s} className={`size-btn ${size === s ? 'selected' : ''}`}
                    onClick={() => setSize(s)}>{s}</button>
                ))}
              </div>
            </>
          )}

          {colors.length > 0 && (
            <>
              <div className="form-label">Color</div>
              <div className="color-select">
                {colors.map((c) => (
                  <button key={c} className={`color-option ${color === c ? 'selected' : ''}`}
                    onClick={() => setColor(c)}>{c}</button>
                ))}
              </div>
            </>
          )}

          <div className="flex items-center gap-2 mb-3">
            <div className="cart-qty">
              <button className="qty-btn" onClick={() => setQty((q) => Math.max(1, q - 1))}>−</button>
              <span className="qty-num">{qty}</span>
              <button className="qty-btn" onClick={() => setQty((q) => Math.min(product.stock, q + 1))}>+</button>
            </div>
            <span className="text-muted" style={{ fontSize: '0.85rem' }}>
              {outOfStock ? 'Out of stock' : `${product.stock} in stock`}
            </span>
          </div>

          <button className="btn btn-primary btn-lg btn-full" disabled={outOfStock} onClick={add}>
            {outOfStock ? 'Out of Stock' : 'Add to Cart'}
          </button>
        </div>
      </div>

      <div className="tabs">
        <div className="tab-nav">
          <button className={`tab-btn ${tab === 'description' ? 'active' : ''}`} onClick={() => setTab('description')}>Description</button>
          <button className={`tab-btn ${tab === 'reviews' ? 'active' : ''}`} onClick={() => setTab('reviews')}>Reviews ({reviews.length})</button>
        </div>
        <div className={`tab-content ${tab === 'description' ? 'active' : ''}`}>
          <p className="text-muted">{product.description}</p>
          {product.sku && <p className="text-muted mt-2">SKU: {product.sku}</p>}
        </div>
        <div className={`tab-content ${tab === 'reviews' ? 'active' : ''}`}>
          {reviews.length === 0 ? (
            <p className="text-muted">No reviews yet.</p>
          ) : (
            <div className="reviews-grid">
              {reviews.map((r) => (
                <div key={r.id} className="review-card">
                  <div className="review-stars">{'★'.repeat(r.rating)}{'☆'.repeat(5 - r.rating)}</div>
                  {r.title && <div className="review-name mb-1">{r.title}</div>}
                  <p className="review-text">"{r.body}"</p>
                  <div className="review-author">
                    <div className="review-avatar">{r.customer_name.charAt(0)}</div>
                    <div className="review-name">{r.customer_name}</div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </section>
  )
}
