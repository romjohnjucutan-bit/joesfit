import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { supabase } from '../lib/supabase.js'
import ProductCard from '../components/ProductCard.jsx'

const PER_PAGE = 9

export default function Shop() {
  const [searchParams, setSearchParams] = useSearchParams()
  const [categories, setCategories] = useState([])
  const [products, setProducts] = useState([])
  const [total, setTotal] = useState(0)
  const [loading, setLoading] = useState(true)

  const activeCat = searchParams.get('category') || ''
  const search = searchParams.get('q') || ''
  const sort = searchParams.get('sort') || 'newest'
  const page = Math.max(1, parseInt(searchParams.get('page') || '1', 10))

  useEffect(() => {
    supabase.from('categories').select('*').eq('is_active', true)
      .then(({ data }) => setCategories(data || []))
  }, [])

  useEffect(() => {
    async function load() {
      setLoading(true)
      let query = supabase
        .from('products')
        .select('*, categories(name, slug)', { count: 'exact' })
        .eq('is_active', true)

      if (activeCat) {
        const cat = await supabase.from('categories').select('id').eq('slug', activeCat).maybeSingle()
        if (cat.data) query = query.eq('category_id', cat.data.id)
      }
      if (search) query = query.ilike('name', `%${search}%`)

      if (sort === 'price_low') query = query.order('price', { ascending: true })
      else if (sort === 'price_high') query = query.order('price', { ascending: false })
      else if (sort === 'name') query = query.order('name', { ascending: true })
      else query = query.order('created_at', { ascending: false })

      const from = (page - 1) * PER_PAGE
      query = query.range(from, from + PER_PAGE - 1)

      const { data, count } = await query
      setProducts((data || []).map((p) => ({ ...p, category_name: p.categories?.name })))
      setTotal(count || 0)
      setLoading(false)
    }
    load()
  }, [activeCat, search, sort, page])

  function update(patch) {
    const next = new URLSearchParams(searchParams)
    Object.entries(patch).forEach(([k, v]) => {
      if (v) next.set(k, v)
      else next.delete(k)
    })
    if (!('page' in patch)) next.set('page', '1')
    setSearchParams(next)
  }

  const pages = Math.ceil(total / PER_PAGE)

  return (
    <section className="section">
      <div className="section-header">
        <h2 className="section-title">SHOP <span>ALL</span></h2>
        <input
          className="form-control"
          style={{ maxWidth: 240 }}
          placeholder="Search jackets…"
          defaultValue={search}
          onKeyDown={(e) => { if (e.key === 'Enter') update({ q: e.target.value }) }}
        />
      </div>

      <div className="shop-layout">
        <aside className="filter-panel">
          <div className="filter-title">Filters</div>
          <div className="filter-group">
            <div className="filter-group-label">Category</div>
            <label className="check-option">
              <input type="radio" name="cat" checked={!activeCat}
                onChange={() => update({ category: '' })} /> All
            </label>
            {categories.map((c) => (
              <label key={c.id} className="check-option">
                <input type="radio" name="cat" checked={activeCat === c.slug}
                  onChange={() => update({ category: c.slug })} /> {c.name}
              </label>
            ))}
          </div>
          <div className="filter-group">
            <div className="filter-group-label">Sort By</div>
            <select className="form-control" value={sort} onChange={(e) => update({ sort: e.target.value })}>
              <option value="newest">Newest</option>
              <option value="price_low">Price: Low to High</option>
              <option value="price_high">Price: High to Low</option>
              <option value="name">Name A–Z</option>
            </select>
          </div>
        </aside>

        <div>
          {loading ? (
            <div className="loading-spinner" />
          ) : products.length === 0 ? (
            <p className="text-muted text-center mt-3">No products found.</p>
          ) : (
            <>
              <div className="products-grid">
                {products.map((p) => <ProductCard key={p.id} product={p} />)}
              </div>
              {pages > 1 && (
                <div className="pagination">
                  {Array.from({ length: pages }, (_, i) => i + 1).map((n) => (
                    <button key={n} className={`page-btn ${n === page ? 'active' : ''}`}
                      onClick={() => update({ page: String(n) })}>{n}</button>
                  ))}
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </section>
  )
}
