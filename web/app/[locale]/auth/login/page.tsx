'use client';

import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { useLocale } from 'next-intl';

type Mode = 'phone' | 'email';

export default function LoginPage() {
  const router = useRouter();
  const locale = useLocale();
  const [mode, setMode] = useState<Mode>('phone');
  const [input, setInput] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  function validate(): boolean {
    if (!input.trim()) {
      setError(mode === 'phone' ? 'Enter your phone number.' : 'Enter your email.');
      return false;
    }
    if (mode === 'phone' && !/^\+?\d{9,15}$/.test(input.trim())) {
      setError('Enter a valid phone number.');
      return false;
    }
    if (mode === 'email' && !/^[\w.+-]+@[\w-]+\.\w+$/.test(input.trim())) {
      setError('Enter a valid email address.');
      return false;
    }
    setError('');
    return true;
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!validate()) return;
    setIsLoading(true);

    try {
      const res = await fetch('/api/v1/auth/send-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          identifier: input.trim(),
          identifier_type: mode,
          purpose: 'login',
        }),
      });
      const json = await res.json();

      if (!res.ok) {
        setError(json.message ?? 'Failed to send OTP. Please try again.');
        return;
      }

      const params = new URLSearchParams({
        identifier: input.trim(),
        identifier_type: mode,
      });
      router.push(`/${locale}/auth/verify?${params.toString()}`);
    } catch {
      setError('Network error. Please check your connection.');
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <main className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 px-4">
      <div className="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-8">
        {/* Brand */}
        <div className="flex items-center gap-3 mb-8">
          <div className="w-12 h-12 rounded-xl bg-[#1B6CA8] flex items-center justify-center">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="w-6 h-6 text-white"
              viewBox="0 0 24 24"
              fill="currentColor"
            >
              <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z" />
            </svg>
          </div>
          <div>
            <h1 className="text-xl font-bold text-gray-900 dark:text-white">Grabber Hub LK</h1>
            <p className="text-sm text-gray-500 dark:text-gray-400">Sri Lanka&apos;s all-in-one platform</p>
          </div>
        </div>

        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-1">Welcome back</h2>
        <p className="text-sm text-gray-500 dark:text-gray-400 mb-6">
          Sign in or create an account in seconds.
        </p>

        {/* Mode toggle */}
        <div className="flex bg-gray-100 dark:bg-gray-800 rounded-xl p-1 mb-6">
          {(['phone', 'email'] as Mode[]).map((m) => (
            <button
              key={m}
              type="button"
              onClick={() => { setMode(m); setInput(''); setError(''); }}
              className={`flex-1 py-2 text-sm font-semibold rounded-lg transition-colors ${
                mode === m
                  ? 'bg-[#1B6CA8] text-white shadow-sm'
                  : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
              }`}
            >
              {m === 'phone' ? 'Phone' : 'Email'}
            </button>
          ))}
        </div>

        <form onSubmit={handleSubmit} noValidate>
          <label
            htmlFor="identifier"
            className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
          >
            {mode === 'phone' ? 'Phone number' : 'Email address'}
          </label>
          {mode === 'phone' ? (
            <input
              id="identifier"
              type="tel"
              value={input}
              onChange={(e) => { setInput(e.target.value); setError(''); }}
              placeholder="+94 77 123 4567"
              className={`w-full px-4 py-3 rounded-xl border text-sm outline-none transition
                bg-white dark:bg-gray-800 text-gray-900 dark:text-white
                placeholder:text-gray-400
                ${error ? 'border-red-500 focus:ring-1 focus:ring-red-500' : 'border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-[#1B6CA8]'}
              `}
              autoFocus
              autoComplete="tel"
            />
          ) : (
            <input
              id="identifier"
              type="email"
              value={input}
              onChange={(e) => { setInput(e.target.value); setError(''); }}
              placeholder="you@example.com"
              className={`w-full px-4 py-3 rounded-xl border text-sm outline-none transition
                bg-white dark:bg-gray-800 text-gray-900 dark:text-white
                placeholder:text-gray-400
                ${error ? 'border-red-500 focus:ring-1 focus:ring-red-500' : 'border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-[#1B6CA8]'}
              `}
              autoFocus
              autoComplete="email"
            />
          )}
          {error && <p className="mt-2 text-xs text-red-500">{error}</p>}

          <button
            type="submit"
            disabled={isLoading}
            className="mt-5 w-full py-3 rounded-xl bg-[#1B6CA8] text-white font-semibold text-sm
              hover:bg-[#155a8a] active:scale-[.98] transition-all disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {isLoading ? 'Sending…' : 'Continue'}
          </button>
        </form>

        <p className="mt-6 text-center text-xs text-gray-400 dark:text-gray-500 leading-relaxed">
          By continuing, you agree to our{' '}
          <a href="/terms" className="underline">Terms of Service</a>{' '}
          and{' '}
          <a href="/privacy" className="underline">Privacy Policy</a>.
        </p>
      </div>
    </main>
  );
}
