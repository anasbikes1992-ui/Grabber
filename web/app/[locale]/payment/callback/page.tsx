'use client';

import Link from 'next/link';
import { useLocale } from 'next-intl';
import { useSearchParams } from 'next/navigation';

function getStatusMessage(statusCode: string | null) {
  if (statusCode === '2') {
    return {
      title: 'Payment approved',
      tone: 'bg-emerald-50 text-emerald-900 border-emerald-200',
      body: 'WebxPay confirmed the transaction and the booking can move to paid status.',
    };
  }

  return {
    title: 'Payment pending or failed',
    tone: 'bg-amber-50 text-amber-900 border-amber-200',
    body: 'The gateway did not return a success code. Check the booking timeline or retry the payment.',
  };
}

export default function PaymentCallbackPage() {
  const locale = useLocale();
  const params = useSearchParams();
  const statusCode = params.get('status_code');
  const paymentId = params.get('payment_id');
  const bookingId = params.get('booking_id');
  const message = getStatusMessage(statusCode);

  return (
    <main className="min-h-screen bg-slate-950 px-4 py-10 text-white">
      <div className="mx-auto max-w-3xl rounded-[2rem] border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur-sm">
        <div className={`rounded-[1.5rem] border px-5 py-5 ${message.tone}`}>
          <p className="text-xs uppercase tracking-[0.25em]">WebxPay callback</p>
          <h1 className="mt-3 text-3xl font-semibold">{message.title}</h1>
          <p className="mt-3 max-w-2xl text-sm leading-6">{message.body}</p>
        </div>

        <div className="mt-6 grid gap-4 md:grid-cols-2">
          <div className="rounded-[1.5rem] border border-white/10 bg-slate-900/60 p-5">
            <p className="text-xs uppercase tracking-[0.25em] text-slate-400">Payment ID</p>
            <p className="mt-2 text-lg font-medium">{paymentId ?? 'Not provided'}</p>
          </div>
          <div className="rounded-[1.5rem] border border-white/10 bg-slate-900/60 p-5">
            <p className="text-xs uppercase tracking-[0.25em] text-slate-400">Booking ID</p>
            <p className="mt-2 text-lg font-medium">{bookingId ?? 'Not provided'}</p>
          </div>
        </div>

        <div className="mt-8 flex flex-wrap gap-3">
          <Link href={`/${locale}/dashboard`} className="rounded-full bg-cyan-300 px-5 py-3 text-sm font-semibold text-slate-950">
            Go to dashboard
          </Link>
          <Link href={`/${locale}/stays`} className="rounded-full border border-white/15 px-5 py-3 text-sm font-semibold text-white">
            Browse stays
          </Link>
        </div>
      </div>
    </main>
  );
}