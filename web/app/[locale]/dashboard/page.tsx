'use client';

import Link from 'next/link';
import { CalendarRange, CarFront, Hotel, Sparkles, Ticket, Wallet } from 'lucide-react';
import { useLocale } from 'next-intl';

const shortcuts = [
  { href: '/stays', label: 'Book a stay', icon: Hotel, tone: 'from-emerald-500 to-teal-700' },
  { href: '/payment/callback?status_code=2&payment_id=demo-paid', label: 'Payment status', icon: Wallet, tone: 'from-sky-500 to-blue-700' },
  { href: '/stays', label: 'Airport transfer', icon: CarFront, tone: 'from-amber-500 to-orange-700' },
  { href: '/stays', label: 'Experiences', icon: Sparkles, tone: 'from-fuchsia-500 to-pink-700' },
];

export default function DashboardPage() {
  const locale = useLocale();

  return (
    <main className="min-h-screen bg-[#f6f3ee] px-4 py-8 text-slate-900 md:px-8">
      <div className="mx-auto max-w-6xl space-y-8">
        <section className="overflow-hidden rounded-[2rem] bg-[#0f172a] text-white shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
          <div className="grid gap-8 px-6 py-8 md:grid-cols-[1.35fr_0.9fr] md:px-10 md:py-10">
            <div>
              <p className="text-sm uppercase tracking-[0.25em] text-cyan-200">Dashboard</p>
              <h1 className="mt-4 max-w-xl text-4xl font-semibold leading-tight">Everything you have booked, paid, and planned in one Sri Lanka travel board.</h1>
              <p className="mt-4 max-w-xl text-sm leading-6 text-slate-300">
                Track stays, taxi pickups, event tickets, provider payouts, and WebxPay confirmations without switching products.
              </p>
            </div>
            <div className="rounded-[1.75rem] border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
              <p className="text-xs uppercase tracking-[0.25em] text-slate-400">Next confirmed trip</p>
              <h2 className="mt-3 text-2xl font-semibold">Bentota Lagoon Escape</h2>
              <p className="mt-2 text-sm text-slate-300">18 Apr 2026 to 20 Apr 2026</p>
              <div className="mt-6 flex items-center justify-between rounded-2xl bg-white/8 px-4 py-3 text-sm">
                <span className="flex items-center gap-2"><CalendarRange className="h-4 w-4" /> Check-in tomorrow</span>
                <span className="rounded-full bg-emerald-400/20 px-3 py-1 text-emerald-200">Paid</span>
              </div>
            </div>
          </div>
        </section>

        <section className="grid gap-4 md:grid-cols-4">
          {shortcuts.map(({ href, label, icon: Icon, tone }) => (
            <Link
              key={label}
              href={`/${locale}${href}`}
              className={`group rounded-[1.75rem] bg-gradient-to-br ${tone} p-[1px] shadow-lg transition hover:-translate-y-0.5`}
            >
              <div className="flex h-full min-h-40 flex-col justify-between rounded-[1.7rem] bg-white/90 p-5 text-slate-900">
                <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950 text-white">
                  <Icon className="h-5 w-5" />
                </div>
                <p className="max-w-[12rem] text-lg font-semibold leading-tight">{label}</p>
              </div>
            </Link>
          ))}
        </section>

        <section className="grid gap-6 md:grid-cols-[1.1fr_0.9fr]">
          <div className="rounded-[2rem] bg-white p-6 shadow-sm">
            <div className="flex items-center justify-between">
              <h2 className="text-xl font-semibold">Recent activity</h2>
              <span className="text-sm text-slate-500">Last 7 days</span>
            </div>
            <div className="mt-5 space-y-4">
              {[
                ['WebxPay payment approved', 'Booking GHLK-2026-0417-8841', 'Success'],
                ['Airport taxi estimate ready', 'Colombo to Bentota', 'Pending'],
                ['Pearl points updated', '500 points credited', 'Reward'],
              ].map(([title, subtitle, status]) => (
                <div key={title} className="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-4">
                  <div>
                    <p className="font-medium">{title}</p>
                    <p className="text-sm text-slate-500">{subtitle}</p>
                  </div>
                  <span className="rounded-full bg-slate-900 px-3 py-1 text-xs text-white">{status}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="rounded-[2rem] bg-[#e7ded1] p-6 shadow-sm">
            <div className="flex items-center gap-3 text-slate-800">
              <Ticket className="h-5 w-5" />
              <h2 className="text-xl font-semibold">Live totals</h2>
            </div>
            <div className="mt-6 grid gap-4">
              {[
                ['Upcoming bookings', '03'],
                ['Pending provider payout', 'LKR 12,450'],
                ['Pearl points balance', '1,240'],
              ].map(([label, value]) => (
                <div key={label} className="rounded-2xl bg-white/80 px-4 py-4">
                  <p className="text-sm text-slate-500">{label}</p>
                  <p className="mt-2 text-2xl font-semibold text-slate-900">{value}</p>
                </div>
              ))}
            </div>
          </div>
        </section>
      </div>
    </main>
  );
}