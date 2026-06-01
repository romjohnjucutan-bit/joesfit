export const CURRENCY = '₱'

export function formatPrice(value) {
  const n = Number(value) || 0
  return CURRENCY + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Resolve a product image reference to a usable URL.
// Supports full URLs, Supabase Storage public paths, or the legacy uploads/ folder.
export function productImage(image) {
  if (!image) return null
  if (/^https?:\/\//.test(image)) return image
  const base = import.meta.env.VITE_SUPABASE_URL
  if (base) return `${base}/storage/v1/object/public/products/${image}`
  return image
}

export function splitList(str) {
  if (!str) return []
  return str.split(',').map((s) => s.trim()).filter(Boolean)
}

export function timeAgo(datetime) {
  const then = new Date(datetime).getTime()
  const diff = Math.max(0, Date.now() - then)
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins} minute${mins > 1 ? 's' : ''} ago`
  const hrs = Math.floor(mins / 60)
  if (hrs < 24) return `${hrs} hour${hrs > 1 ? 's' : ''} ago`
  const days = Math.floor(hrs / 24)
  return `${days} day${days > 1 ? 's' : ''} ago`
}
