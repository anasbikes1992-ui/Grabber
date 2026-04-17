'use client';

import { useState } from 'react';
import { useLocale } from 'next-intl';
import { useRouter, useSearchParams } from 'next/navigation';

export default function RegisterPage() {
  const router = useRouter();
  const locale = useLocale();
  const params = useSearchParams();
  const identifier = params.get('identifier') ?? '';
  const identifierType = params.get('identifier_type') ?? 'phone';

  const [fullName, setFullName] = useState('');
  const [role, setRole] = useState<'customer' | 'provider'>('customer');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();

    if (fullName.trim().length < 3) {
      setError('Enter your full name.');
      return;
    }

    setIsLoading(true);
    setError('');

    try {
      const res = await fetch('/api/v1/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          identifier,
          identifier_type: identifierType,
          full_name: fullName.trim(),
          role,
        }),
      });
      const json = await res.json();

      if (!res.ok) {
        setError(json.message ?? 'Failed to create account.');
        return;
      }

      if (typeof window !== 'undefined' && json.data?.token) {
        localStorage.setItem('grabber_token', json.data.token);
      }

      router.push(`/${locale}/dashboard`);
    } catch {
      setError('Network error. Please try again.');
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <main className="min-h-screen bg-[radial-gradient(circle_at_top_left,_#0f766e,_#082f49_55%,_#02111b)] px-4 py-10 text-white">
      <div className="mx-auto w-full max-w-lg rounded-[2rem] border border-white/10 bg-white/8 p-8 shadow-2xl backdrop-blur-sm">
        <p className="text-sm uppercase tracking-[0.3em] text-cyan-200/80">Grabber Hub LK</p>
        <h1 className="mt-4 text-3xl font-semibold">Create your account</h1>
        <p className="mt-2 text-sm text-slate-200/80">Finish setup for {identifier || 'your account'}.</p>

        <form className="mt-8 space-y-5" onSubmit={handleSubmit}>
          <div>
            <label className="mb-2 block text-sm text-slate-100">Full name</label>
            <input
              value={fullName}
              onChange={(e) => {
                setFullName(e.target.value);
                setError('');
              }}
              className="w-full rounded-2xl border border-white/10 bg-slate-950/30 px-4 py-3 outline-none ring-0 transition focus:border-cyan-300"
              placeholder="Kasun Perera"
            />
          </div>

          <div>
            <label className="mb-2 block text-sm text-slate-100">Account type</label>
            <div className="grid grid-cols-2 gap-3">
              {(['customer', 'provider'] as const).map((option) => (
                <button
                  key={option}
                  type="button"
                  onClick={() => setRole(option)}
                  className={`rounded-2xl border px-4 py-3 text-sm font-medium transition ${
                    role === option
                      ? 'border-cyan-300 bg-cyan-300/20 text-white'
                      : 'border-white/10 bg-white/5 text-slate-200'
                  }`}
                >
                  {option === 'customer' ? 'Customer' : 'Provider'}
                </button>
              ))}
            </div>
          </div>

          {error ? <p className="text-sm text-rose-300">{error}</p> : null}

          <button
            type="submit"
            disabled={isLoading}
            className="w-full rounded-2xl bg-cyan-300 px-4 py-3 font-semibold text-slate-950 transition hover:bg-cyan-200 disabled:opacity-60"
          >
            {isLoading ? 'Creating account...' : 'Create account'}
          </button>
        </form>
      </div>
    </main>
  );
}