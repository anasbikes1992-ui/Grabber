-- Supabase migration: core tables
-- Mirrors Laravel migrations for realtime/edge function access

-- Enable UUID extension
create extension if not exists "uuid-ossp";
create extension if not exists "pgcrypto";

-- Users (mirrors Laravel users table)
create table if not exists public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  full_name text,
  avatar_url text,
  phone text unique,
  role text not null default 'customer' check (
    role in ('customer','provider','driver','property_owner','event_organiser',
             'experience_host','sme_owner','social_influencer','cash_agent',
             'super_admin','finance_admin','marketing_admin')
  ),
  preferred_lang text not null default 'en',
  preferred_currency text not null default 'LKR',
  account_status text not null default 'pending_verification' check (
    account_status in ('active','suspended','pending_verification','deactivated')
  ),
  referral_code text unique,
  is_online boolean not null default false,
  last_lat numeric(10,7),
  last_lng numeric(10,7),
  provider_tier text check (provider_tier in ('standard','gold','platinum')),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

-- Row Level Security
alter table public.profiles enable row level security;

create policy "Users can view their own profile"
  on public.profiles for select
  using (auth.uid() = id);

create policy "Users can update their own profile"
  on public.profiles for update
  using (auth.uid() = id);

-- Platform config (read-only for clients)
create table if not exists public.platform_config (
  id uuid primary key default gen_random_uuid(),
  category text not null,
  key text not null,
  value text not null,
  type text not null default 'string',
  is_sensitive boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (category, key)
);

alter table public.platform_config enable row level security;

create policy "Public can read non-sensitive config"
  on public.platform_config for select
  using (is_sensitive = false);

-- Feature flags (public read)
create table if not exists public.feature_flags (
  id uuid primary key default gen_random_uuid(),
  key text unique not null,
  enabled boolean not null default true,
  description text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

alter table public.feature_flags enable row level security;

create policy "Public can read feature flags"
  on public.feature_flags for select
  using (true);

-- Updated_at trigger function
create or replace function public.handle_updated_at()
returns trigger as $$
begin
  new.updated_at = now();
  return new;
end;
$$ language plpgsql;

create trigger profiles_updated_at before update on public.profiles
  for each row execute procedure public.handle_updated_at();

create trigger platform_config_updated_at before update on public.platform_config
  for each row execute procedure public.handle_updated_at();

create trigger feature_flags_updated_at before update on public.feature_flags
  for each row execute procedure public.handle_updated_at();
