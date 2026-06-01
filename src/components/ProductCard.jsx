import { Link } from 'react-router-dom'
import { formatPrice, productImage, splitList } from '../lib/format.js'
import { useCart } from '../context/CartContext.jsx'
import { useToast } from '../context/ToastContext.jsx'

export default function ProductCard({ product }) {
  const { addItem } = useCart()
  const { toast } = useToast()
  const img = productImage(product.image)
  const onSale = product.sale_price != null
  const colors = splitList(product.colors)

  function quickAdd(e) {
    e.preventDefault()
    const sizes = splitList(product.sizes)
    addItem(product, { size: sizes[0] || '', color: colors[0] || '', quantity: 1 })
    toast(`${product.name} added to cart`)
  }

  return (
    <Link to={`/product/${product.slug}`} className="product-card">
      <div className="product-img-wrap">
        {img ? (
          <img src={img} alt={product.name} loading="lazy" />
        ) : (
          <div className="img-placeholder"><span>🧥</span>{product.name}</div>
        )}
        {onSale && <span className="product-badge badge-sale">Sale</span>}
        {!onSale && product.is_featured && (
          <span className="product-badge badge-featured">Featured</span>
        )}
        <div className="product-actions-overlay">
          <button className="btn btn-primary btn-sm" onClick={quickAdd}>Add to Cart</button>
        </div>
      </div>
      <div className="product-info">
        {product.category_name && (
          <div className="product-category">{product.category_name}</div>
        )}
        <div className="product-name">{product.name}</div>
        <div className="product-price">
          <span className="price-current">
            {formatPrice(onSale ? product.sale_price : product.price)}
          </span>
          {onSale && <span className="price-original">{formatPrice(product.price)}</span>}
        </div>
        {colors.length > 0 && (
          <div className="product-colors">
            {colors.slice(0, 5).map((c, i) => (
              <span key={i} className="color-dot" title={c}
                style={{ background: colorToCss(c) }} />
            ))}
          </div>
        )}
      </div>
    </Link>
  )
}

// Best-effort map of a color label to a CSS color for the swatch dot.
function colorToCss(label) {
  const first = label.split('/')[0].trim().toLowerCase()
  const map = {
    black: '#111', white: '#eee', navy: '#1b2a4a', red: '#e8321a',
    olive: '#5b5a2a', khaki: '#b5a37a', gold: '#c9a84c', champagne: '#e6d2a8',
    burgundy: '#5c1a26', gray: '#888', grey: '#888', forest: '#234a2e',
    brown: '#5b3a29', green: '#2e8b57', blue: '#2563eb',
  }
  for (const key in map) if (first.includes(key)) return map[key]
  return '#999'
}
