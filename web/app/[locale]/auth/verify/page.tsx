'use client';

import { useEffect, useRef, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useLocale } from 'next-intl';

const CODE_LENGTH = 6;

export default function VerifyOtpPage() {
  const router = useRouter();
  const locale = useLocale();
  const params = useSearchParams();
  const identifier = params.get('identifier') ?? '';
  const identifierType = params.get('identifier_type') ?? 'phone';

  const [digits, setDigits] = useState<string[]>(Array(CODE_LENGTH).fill(''));
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [countdown, setCountdown] = useState(60);
  const inputRefs = useRef<(HTMLInputElement | null)[]>([]);

  // Countdown timer
  useEffect(() => {
    if (countdown <= 0) return;
    const t = setTimeout(() => setCountdown((c) => c - 1), 1000);
    return () => clearTimeout(t);
  }, [countdown]);

  function handleInput(index: number, value: string) {
    const char = value.replace(/\D/g, '').slice(-1);
    const next = [...digits];
    next[index] = char;
    setDigits(next);
    setError('');

    if (next.every((d) => d !== '')) {
      void handleVerify(next.join(''));
    }

    if (char && index < CODE_LENGTH - 1) {
      inputRefs.current[index + 1]?.focus();
    }
  }

  function handleKeyDown(index: number, e: React.KeyboardEvent<HTMLInputElement>) {
    if (e.key === 'Backspace' && !digits[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
      const next = [...digits];
      next[index - 1] = '';
      setDigits(next);
    }
  }

  function handlePaste(e: React.ClipboardEvent) {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, CODE_LENGTH);
    if (pasted.length === CODE_LENGTH) {
      const next = pasted.split('');
      setDigits(next);
      void handleVerify(next.join(''));
    }
  }

  async function handleVerify(code: string) {
    if (code.length < CODE_LENGTH) return;
    setIsLoading(true);
    setError('');

    try {
      const res = await fetch('/api/v1/auth/verify-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          identifier,
          identifier_type: identifierType,
          code,
          purpose: 'login',
        }),
      });
      const json = await res.json();

      if (!res.ok) {
        setError(json.message ?? 'Invalid or expired code.');
        setDigits(Array(CODE_LENGTH).fill(''));
        inputRefs.current[0]?.focus();
        return;
      }

      if (json.data?.is_new) {
        const p = new URLSearchParams({
          identifier,
          identifier_type: identifierType,
        });
        router.push(`/${locale}/auth/register?${p.toString()}`);
      } else {
        // Store token and redirect
        if (typeof window !== 'undefined') {
          localStorage.setItem('grabber_token', json.data.token);
        }
        router.push(`/${locale}/dashboard`);
      }
    } catch {
      setError('Network error. Please try again.');
    } finally {
      setIsLoading(false);
    }
  }

  async function handleResend() {
    if (countdown > 0) return;
    setError('');
    try {
      await fetch('/api/v1/auth/send-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          identifier,
          identifier_type: identifierType,
          purpose: 'login',
        }),
      });
      setCountdown(60);
      setDigits(Array(CODE_LENGTH).fill(''));
      inputRefs.current[0]?.focus();
    } catch {
      setError('Failed to resend. Please try again.');
    }
  }

  const masked =
    identifierType === 'phone'
      ? identifier.slice(0, 3) + '••••' + identifier.slice(-3)
      : identifier.slice(0, 2) + '••••' + identifier.slice(identifier.indexOf('@'));

  return (
    <main className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 px-4">
      <div className="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-8">
        <button
          type="button"
          onClick={() => router.back()}
          className="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 mb-6 flex items-center gap-1"
        >
          ← Back
        </button>

        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-1">
          Enter verification code
        </h2>
        <p className="text-sm text-gray-500 dark:text-gray-400 mb-8">
          We sent a 6-digit code to <span className="font-medium text-gray-700 dark:text-gray-200">{masked}</span>
        </p>

        {/* 6-digit OTP input */}
        <div className="flex gap-3 justify-center mb-6" onPaste={handlePaste}>
          {digits.map((digit, i) => (
            <input
              key={i}
              ref={(el) => { inputRefs.current[i] = el; }}
              type="text"
              aria-label={`Verification digit ${i + 1}`}
              inputMode="numeric"
              maxLength={1}
              value={digit}
              onChange={(e) => handleInput(i, e.target.value)}
              onKeyDown={(e) => handleKeyDown(i, e)}
              disabled={isLoading}
              className={`w-12 h-14 text-center text-xl font-bold rounded-xl border-2 outline-none transition
                bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white
                ${error ? 'border-red-500' : digit ? 'border-[#1B6CA8]' : 'border-gray-200 dark:border-gray-700'}
                focus:border-[#1B6CA8] dark:focus:border-[#4DA3E8]
                disabled:opacity-50
              `}
            />
          ))}
        </div>

        {error && (
          <p className="text-center text-sm text-red-500 mb-4">{error}</p>
        )}

        {isLoading && (
          <p className="text-center text-sm text-gray-400 mb-4">Verifying…</p>
        )}

        <button
          type="button"
          onClick={handleResend}
          disabled={countdown > 0}
          className={`w-full text-center text-sm font-medium transition
            ${countdown > 0
              ? 'text-gray-400 cursor-not-allowed'
              : 'text-[#1B6CA8] hover:text-[#155a8a] underline'
            }
          `}
        >
          {countdown > 0 ? `Resend code in ${countdown}s` : 'Resend code'}
        </button>
      </div>
    </main>
  );
}
