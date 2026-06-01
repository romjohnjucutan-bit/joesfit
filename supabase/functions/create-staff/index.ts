// Supabase Edge Function: create-staff
// Lets an ADMIN create staff/admin accounts (auth user + staff row).
// Public signup is disabled, so this is the only way new staff are created.
//
// Auth model:
//  - If no active admin exists yet -> the FIRST call may create an admin (bootstrap).
//  - Otherwise the caller must present an admin's JWT (Authorization: Bearer ...).
//
// Body: { name, email, password, role: 'admin'|'staff', phone? }
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
import { corsHeaders, json } from '../_shared/cors.ts'

Deno.serve(async (req) => {
  if (req.method === 'OPTIONS') return new Response('ok', { headers: corsHeaders })
  if (req.method !== 'POST') return json({ success: false, error: 'Invalid request' }, 405)

  const admin = createClient(
    Deno.env.get('SUPABASE_URL')!,
    Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!,
  )

  const body = await req.json().catch(() => ({}))
  const name = String(body.name ?? '').trim()
  const email = String(body.email ?? '').trim().toLowerCase()
  const password = String(body.password ?? '')
  const role = body.role === 'admin' ? 'admin' : 'staff'
  const phone = body.phone ? String(body.phone) : null

  if (!name || !email || !password) {
    return json({ success: false, error: 'Name, email and password are required' }, 400)
  }
  if (password.length < 6) {
    return json({ success: false, error: 'Password must be at least 6 characters' }, 400)
  }

  // Is there already an active admin? Determines bootstrap vs. authenticated mode.
  const { count: adminCount } = await admin
    .from('staff').select('id', { count: 'exact', head: true })
    .eq('role', 'admin').eq('is_active', true)

  const bootstrap = (adminCount ?? 0) === 0

  if (!bootstrap) {
    // Require an admin caller.
    const authHeader = req.headers.get('Authorization')
    if (!authHeader) return json({ success: false, error: 'Not authorized' }, 401)
    const { data: { user } } = await admin.auth.getUser(authHeader.replace('Bearer ', ''))
    if (!user) return json({ success: false, error: 'Not authorized' }, 401)
    const { data: caller } = await admin
      .from('staff').select('role,is_active').eq('email', user.email).maybeSingle()
    if (!caller || !caller.is_active || caller.role !== 'admin') {
      return json({ success: false, error: 'Only admins can create staff accounts' }, 403)
    }
  }

  // Create the auth user (auto-confirmed) — service role bypasses public signup lock.
  const { data: created, error: cErr } = await admin.auth.admin.createUser({
    email, password, email_confirm: true, user_metadata: { name },
  })
  if (cErr) return json({ success: false, error: cErr.message }, 400)

  // Insert/upsert the staff profile row (password column is unused; auth owns it).
  const { error: sErr } = await admin.from('staff').upsert(
    { name, email, password: 'managed-by-supabase-auth', role, phone, is_active: true },
    { onConflict: 'email' },
  )
  if (sErr) {
    // Roll back the auth user so we don't orphan it.
    if (created?.user?.id) await admin.auth.admin.deleteUser(created.user.id)
    return json({ success: false, error: sErr.message }, 400)
  }

  return json({ success: true, email, role })
})
