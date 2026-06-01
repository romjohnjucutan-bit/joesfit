// Supabase Edge Function: coupon
// Validates a coupon against a subtotal (ports api/coupon.php) so the cart can
// preview the discount before checkout. Coupons are not publicly readable via
// RLS, so this runs with the service_role key.
// Body: { code: string, subtotal: number }
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { corsHeaders, json } from '../_shared/cors.ts'

Deno.serve(async (req) => {
  if (req.method === 'OPTIONS') return new Response('ok', { headers: corsHeaders })
  if (req.method !== 'POST') return json({ success: false, error: 'Invalid request' }, 405)

  const { code: rawCode, subtotal: rawSubtotal } = await req.json().catch(() => ({}))
  const code = String(rawCode ?? '').toUpperCase().trim()
  const subtotal = Number(rawSubtotal) || 0
  if (!code) return json({ success: false, error: 'Please enter a coupon code' }, 400)

  const admin = createClient(
    Deno.env.get('SUPABASE_URL')!,
    Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!,
  )

  const { data: coupon } = await admin
    .from('coupons').select('*').eq('code', code).eq('is_active', true).maybeSingle()

  const today = new Date().toISOString().slice(0, 10)
  const usable = coupon &&
    (!coupon.expires_at || coupon.expires_at >= today) &&
    (coupon.max_uses == null || coupon.used_count < coupon.max_uses)

  if (!usable) return json({ success: false, error: 'Invalid or expired coupon code' }, 400)
  if (subtotal < Number(coupon.min_order)) {
    return json({ success: false, error: `Minimum order of ₱${Number(coupon.min_order).toFixed(2)} required` }, 400)
  }

  const discount = coupon.type === 'percent'
    ? Math.round(subtotal * (Number(coupon.value) / 100) * 100) / 100
    : Math.min(Number(coupon.value), subtotal)

  return json({ success: true, discount, code })
})
