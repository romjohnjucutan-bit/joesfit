# Joe's Fit — Deployment Guide (React + Supabase + GitHub + Cloudflare)

This app is now a **Vite + React** single-page storefront backed by **Supabase**
(Postgres + Auth + Storage + Edge Functions) and hosted on **Cloudflare Pages**,
with source on **GitHub**.

```
Browser ──► Cloudflare Pages (static React build)
                 │
                 ├─ reads catalog/reviews ─► Supabase Postgres (RLS)
                 ├─ login/register ────────► Supabase Auth
                 ├─ product images ────────► Supabase Storage (bucket: products)
                 └─ checkout/coupon ───────► Supabase Edge Functions (service role)
```

You only need to do four things: **(1) create the Supabase project & run the schema,
(2) deploy the Edge Functions, (3) push to GitHub, (4) connect Cloudflare Pages.**

---

## Prerequisites
- Node.js 18+ (you have v22) and npm
- A free [Supabase](https://supabase.com) account
- A [GitHub](https://github.com) account
- A [Cloudflare](https://dash.cloudflare.com) account
- (Optional but recommended) the Supabase CLI — run it with `npx supabase ...`

---

## 1 · Create the Supabase project & database

1. Go to **app.supabase.com → New project**. Pick a name (e.g. `joesfit`), set a
   strong database password, choose the region closest to you (e.g. Singapore).
2. Wait ~2 minutes for it to provision.
3. Open **SQL Editor → New query**, paste the **entire contents** of
   [`supabase/migrations/0001_init.sql`](supabase/migrations/0001_init.sql), click **Run**.
4. New query again, paste [`supabase/migrations/0002_storage.sql`](supabase/migrations/0002_storage.sql), **Run**.
   - This creates all tables, RLS policies, the `track_order` function, seed data,
     and the public `products` image bucket.
5. Go to **Project Settings → API** and copy:
   - **Project URL** → this is `VITE_SUPABASE_URL`
   - **anon public** key → this is `VITE_SUPABASE_ANON_KEY`

### Configure Auth (so customer signup works)
- **Authentication → Providers → Email**: keep **Email** enabled.
- For testing you can turn **"Confirm email"** OFF (Authentication → Providers → Email)
  so new accounts work instantly. Turn it back on for production.
- **Authentication → URL Configuration**: once you have your Cloudflare URL, add it
  to **Site URL** and **Redirect URLs** (e.g. `https://joesfit.pages.dev`).

---

## 2 · Deploy the Edge Functions (checkout + coupon)

These run the order/coupon logic with the service-role key, so totals and stock
can't be tampered with from the browser. Run from the project folder:

```bash
# Log in (opens a browser to get an access token)
npx supabase login

# Link this repo to your project (grab the ref from the dashboard URL or Settings)
npx supabase link --project-ref YOUR-PROJECT-REF

# Deploy both functions
npx supabase functions deploy checkout
npx supabase functions deploy coupon
```

`SUPABASE_URL` and `SUPABASE_SERVICE_ROLE_KEY` are injected automatically into
deployed functions — you do **not** need to set them manually.

> Prefer not to use the CLI? In the dashboard go to **Edge Functions → Deploy a
> new function**, name it `checkout`, and paste the contents of
> `supabase/functions/checkout/index.ts` (repeat for `coupon`). The shared
> `_shared/cors.ts` import works automatically with CLI deploys; if pasting
> manually, inline those two helpers into each file.

---

## 3 · Run it locally first (recommended sanity check)

```bash
cp .env.example .env       # then edit .env with your real URL + anon key
npm install
npm run dev                # opens http://localhost:5173
```

You should see products load on the homepage. Try: browse shop → add to cart →
checkout (creates a real order in Supabase) → copy the tracking code → Track Order.

---

## 4 · Push to GitHub

```bash
git init
git add .
git commit -m "Joe's Fit: React + Supabase storefront"
git branch -M main
# create an empty repo on github.com first (no README), then:
git remote add origin https://github.com/YOUR-USERNAME/joesfit.git
git push -u origin main
```

> `.env` is git-ignored, so your keys are NOT pushed. The anon key is safe to
> expose in the browser anyway, but we still inject it via Cloudflare env vars.

---

## 5 · Deploy to Cloudflare Pages

1. **Cloudflare dashboard → Workers & Pages → Create → Pages → Connect to Git**.
2. Authorize GitHub and pick your `joesfit` repo.
3. Build settings:
   | Setting | Value |
   |---|---|
   | Framework preset | **Vite** |
   | Build command | `npm run build` |
   | Build output directory | `dist` |
4. **Environment variables** (add both, for "Production" and "Preview"):
   - `VITE_SUPABASE_URL` = your Project URL
   - `VITE_SUPABASE_ANON_KEY` = your anon public key
5. Click **Save and Deploy**. First build takes ~1–2 min.
6. You'll get a URL like `https://joesfit.pages.dev`. Add it to Supabase
   **Authentication → URL Configuration** (Site URL + Redirect URLs).

Every `git push` to `main` now auto-deploys. Pull requests get preview URLs.

---

## Managing the store (your "admin" for now)

Until the custom React admin panel is built, manage everything in **Supabase Studio**:
- **Table Editor → products / categories / coupons**: add/edit items, set `stock`,
  `sale_price`, `is_featured`, etc.
- **Table Editor → orders**: change an order's `status`, then add a row in
  `order_history` (same `order_id`, new `status`, a `note`) so the customer's
  Track page timeline updates.
- **Storage → products**: upload a product image, then copy its file name into the
  product's `image` column (the app builds the public URL automatically).

The original PHP admin panel (`admin/`) and pages remain in this folder as
reference only — they are not part of the deployed app.

---

## Project structure (new)
```
src/
  lib/         supabase client, formatting helpers
  context/     Auth, Cart, Toast providers
  components/  Header, Footer, CartSidebar, ProductCard
  pages/       Home, Shop, Product, Checkout, Track, Login, Account
supabase/
  migrations/  0001_init.sql (schema+RLS+seed), 0002_storage.sql
  functions/   checkout/, coupon/  (Deno Edge Functions)
public/        _redirects (SPA fallback), favicon
```

## Troubleshooting
- **Blank page / "supabaseUrl is required"** → `.env` (local) or Cloudflare env
  vars (prod) are missing. Add `VITE_SUPABASE_URL` and `VITE_SUPABASE_ANON_KEY`,
  then rebuild/redeploy.
- **Products don't load** → the migration didn't run, or RLS blocked them. Re-run
  `0001_init.sql`; confirm products have `is_active = true`.
- **Checkout fails** → the `checkout` function isn't deployed, or CORS. Check
  **Edge Functions → Logs** in the dashboard.
- **Signup "Email not confirmed"** → turn off "Confirm email" for testing, or
  confirm via the link Supabase emails.
- **Routes 404 on refresh in production** → ensure `public/_redirects` shipped
  (it routes all paths to `index.html`).

## Next phase: custom React admin
The admin panel (orders, products CRUD, inventory, reports, etc.) can be added as
a protected `/admin` route set that uses the `staff` table + Supabase Auth roles.
Ask and it can be scaffolded next.
