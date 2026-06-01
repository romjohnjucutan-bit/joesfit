-- ============================================================
-- Joe's Fit — Admin/staff roles + RLS write access
-- - Storefront is now guest-only (no customer self-signup)
-- - Staff accounts are auth users; the `staff` table holds their role
-- - is_staff()/is_admin() drive write policies for the admin panel
-- Safe to re-run.
-- ============================================================

-- Guest-only storefront: drop the auto customer-profile trigger.
drop trigger if exists on_auth_user_created on auth.users;
drop function if exists handle_new_user() cascade;

-- Role helpers. SECURITY DEFINER so they can read `staff` without tripping
-- that table's own RLS (avoids recursion). Matched by the JWT email claim.
create or replace function is_staff() returns boolean
  language sql security definer stable set search_path = public as $$
  select exists (
    select 1 from staff where email = (auth.jwt() ->> 'email') and is_active
  );
$$;

create or replace function is_admin() returns boolean
  language sql security definer stable set search_path = public as $$
  select exists (
    select 1 from staff
    where email = (auth.jwt() ->> 'email') and role = 'admin' and is_active
  );
$$;

grant execute on function is_staff() to anon, authenticated;
grant execute on function is_admin() to anon, authenticated;

-- ---------- Drop old policies if re-running ----------
drop policy if exists "admin manage staff"        on staff;
drop policy if exists "staff read self"           on staff;
drop policy if exists "staff manage products"     on products;
drop policy if exists "staff manage categories"   on categories;
drop policy if exists "staff read orders"         on orders;
drop policy if exists "staff update orders"       on orders;
drop policy if exists "staff read order items"    on order_items;
drop policy if exists "staff read history"        on order_history;
drop policy if exists "staff insert history"      on order_history;
drop policy if exists "staff read reviews"        on reviews;
drop policy if exists "staff update reviews"      on reviews;
drop policy if exists "staff delete reviews"      on reviews;
drop policy if exists "staff read notifications"  on notifications;
drop policy if exists "staff update notifications" on notifications;
drop policy if exists "staff delete notifications" on notifications;
drop policy if exists "staff read coupons"        on coupons;
drop policy if exists "admin manage coupons"      on coupons;

-- ---------- Staff table ----------
create policy "admin manage staff" on staff for all
  using (is_admin()) with check (is_admin());
create policy "staff read self" on staff for select
  using (email = (auth.jwt() ->> 'email'));

-- ---------- Catalog write access (public read already exists) ----------
create policy "staff manage products" on products for all
  using (is_staff()) with check (is_staff());
create policy "staff manage categories" on categories for all
  using (is_staff()) with check (is_staff());

-- ---------- Orders ----------
create policy "staff read orders" on orders for select using (is_staff());
create policy "staff update orders" on orders for update
  using (is_staff()) with check (is_staff());
create policy "staff read order items" on order_items for select using (is_staff());
create policy "staff read history" on order_history for select using (is_staff());
create policy "staff insert history" on order_history for insert with check (is_staff());

-- ---------- Reviews (moderation) ----------
create policy "staff read reviews" on reviews for select using (is_staff());
create policy "staff update reviews" on reviews for update
  using (is_staff()) with check (is_staff());
create policy "staff delete reviews" on reviews for delete using (is_staff());

-- ---------- Notifications ----------
create policy "staff read notifications" on notifications for select using (is_staff());
create policy "staff update notifications" on notifications for update
  using (is_staff()) with check (is_staff());
create policy "staff delete notifications" on notifications for delete using (is_staff());

-- ---------- Coupons (staff read, admin manage) ----------
create policy "staff read coupons" on coupons for select using (is_staff());
create policy "admin manage coupons" on coupons for all
  using (is_admin()) with check (is_admin());
