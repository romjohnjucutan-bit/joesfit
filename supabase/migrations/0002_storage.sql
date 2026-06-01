-- Public storage bucket for product images.
-- Frontend reads images at:
--   {SUPABASE_URL}/storage/v1/object/public/products/<filename>
-- (see productImage() in src/lib/format.js)

insert into storage.buckets (id, name, public)
values ('products', 'products', true)
on conflict (id) do nothing;

-- Drop first so this file is safe to re-run.
drop policy if exists "public read product images" on storage.objects;
drop policy if exists "authenticated manage product images" on storage.objects;

-- Anyone can read product images.
create policy "public read product images"
  on storage.objects for select
  using (bucket_id = 'products');

-- Only authenticated users (e.g. you, via Studio/CLI) can upload/change them.
create policy "authenticated manage product images"
  on storage.objects for all
  using (bucket_id = 'products' and auth.role() = 'authenticated')
  with check (bucket_id = 'products' and auth.role() = 'authenticated');
