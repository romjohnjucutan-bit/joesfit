// Supabase Edge Function: checkout
// Ports the logic of the original api/checkout.php.
// Runs with the service_role key, so prices/stock are validated server-side
// and the client can never tamper with totals.
//
// Body: {
//   customer: { name, email, phone, address, city, province, zip },
//   payment_method, delivery_method, coupon_code?,
//   notes?, items: [{ product_id, size, color, quantity }]
// }
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { corsHeaders, json } from '../_shared/cors.ts'

const SHIPPING: Record<string, number> = { standard: 150, express: 250, pickup: 0 }
const VALID_PAYMENTS = ['cod', 'gcash', 'maya', 'card']

function trackingCode() {
  return 'JF-' + new Date().getFullYear() +
    String(Math.floor(1 + Math.random() * 9999)).padStart(4, '0')
}

Deno.serve(async (req) => {
  if (req.method === 'OPTIONS') return new Response('ok', { headers: corsHeaders })
  if (req.method !== 'POST') return json({ success: false, error: 'Invalid request' }, 405)

  const admin = createClient(
    Deno.env.get('SUPABASE_URL')!,
    Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!,
  )

  let payload: any
  try {
    payload = await req.json()
  } catch {
    return json({ success: false, error: 'Invalid JSON body' }, 400)
  }

  const c = payload.customer ?? {}
  const items = Array.isArray(payload.items) ? payload.items : []
  if (items.length === 0) return json({ success: false, error: 'Cart is empty' }, 400)
  if (!c.name || !c.email || !c.address || !c.city || !c.province) {
    return json({ success: false, error: 'Please fill all required fields' }, 400)
  }

  let payment = String(payload.payment_method ?? 'cod')
  if (!VALID_PAYMENTS.includes(payment)) payment = 'cod'
  let delivery = String(payload.delivery_method ?? 'standard')
  if (!(delivery in SHIPPING)) delivery = 'standard'
  const shippingFee = SHIPPING[delivery]

  // Identify the logged-in customer (if a JWT was forwarded)
  let customerId: string | null = null
  const authHeader = req.headers.get('Authorization')
  if (authHeader) {
    const { data } = await admin.auth.getUser(authHeader.replace('Bearer ', ''))
    customerId = data.user?.id ?? null
  }

  // Re-fetch real prices/stock from DB (never trust the client)
  const ids = [...new Set(items.map((i: any) => Number(i.product_id)))]
  const { data: products, error: pErr } = await admin
    .from('products')
    .select('id,name,price,sale_price,stock,image,is_active')
    .in('id', ids)
  if (pErr) return json({ success: false, error: pErr.message }, 500)

  const byId = new Map(products!.map((p) => [p.id, p]))
  let subtotal = 0
  const lineItems = []
  for (const it of items) {
    const p = byId.get(Number(it.product_id))
    const qty = Math.max(1, Number(it.quantity) || 1)
    if (!p || !p.is_active) return json({ success: false, error: 'A product is unavailable' }, 400)
    if (p.stock < qty) return json({ success: false, error: `Insufficient stock for ${p.name}` }, 400)
    const price = Number(p.sale_price ?? p.price)
    subtotal += price * qty
    lineItems.push({
      product_id: p.id, product_name: p.name, product_image: p.image,
      size: it.size ?? null, color: it.color ?? null, quantity: qty,
      price, subtotal: price * qty,
    })
  }

  // Coupon validation (ports api/coupon.php)
  let discount = 0
  let couponRow: any = null
  if (payload.coupon_code) {
    const code = String(payload.coupon_code).toUpperCase().trim()
    const { data: coupon } = await admin
      .from('coupons').select('*').eq('code', code).eq('is_active', true).maybeSingle()
    const valid = coupon &&
      (!coupon.expires_at || coupon.expires_at >= new Date().toISOString().slice(0, 10)) &&
      (coupon.max_uses == null || coupon.used_count < coupon.max_uses) &&
      subtotal >= Number(coupon.min_order)
    if (valid) {
      couponRow = coupon
      discount = coupon.type === 'percent'
        ? Math.round(subtotal * (Number(coupon.value) / 100) * 100) / 100
        : Math.min(Number(coupon.value), subtotal)
    }
  }

  const total = Math.max(0, subtotal + shippingFee - discount)

  // Unique tracking code
  let code = trackingCode()
  for (let i = 0; i < 5; i++) {
    const { data: existing } = await admin.from('orders').select('id').eq('tracking_code', code).maybeSingle()
    if (!existing) break
    code = trackingCode()
  }

  // Insert order
  const { data: order, error: oErr } = await admin.from('orders').insert({
    tracking_code: code, customer_id: customerId,
    customer_name: c.name, customer_email: c.email, customer_phone: c.phone ?? null,
    shipping_address: c.address, shipping_city: c.city, shipping_province: c.province, shipping_zip: c.zip ?? null,
    payment_method: payment, delivery_method: delivery, status: 'pending', payment_status: 'pending',
    subtotal, shipping_fee: shippingFee, discount, total, notes: payload.notes ?? null,
  }).select('id').single()
  if (oErr) return json({ success: false, error: 'Order processing failed: ' + oErr.message }, 500)

  const orderId = order!.id

  // Insert items + decrement stock + low-stock notifications
  await admin.from('order_items').insert(lineItems.map((li) => ({ ...li, order_id: orderId })))
  for (const li of lineItems) {
    const p = byId.get(li.product_id)!
    const newStock = p.stock - li.quantity
    await admin.from('products').update({ stock: newStock }).eq('id', li.product_id)
    if (newStock <= 5) {
      await admin.from('notifications').insert({
        type: 'low_stock', title: 'Low Stock: ' + p.name,
        message: `Only ${newStock} units remaining`, link: '/admin/products',
      })
    }
  }

  // History + admin notification + coupon usage
  await admin.from('order_history').insert({ order_id: orderId, status: 'pending', note: 'Order placed by ' + c.name })
  await admin.from('notifications').insert({
    type: 'new_order', title: `New Order #${code}`,
    message: `${c.name} placed a new order worth ₱${total.toFixed(2)}`, link: `/admin/orders?id=${orderId}`,
  })
  if (couponRow) {
    await admin.from('coupons').update({ used_count: couponRow.used_count + 1 }).eq('id', couponRow.id)
  }

  return json({ success: true, tracking_code: code, order_id: orderId, total })
})
