'use client';

import { useState, useEffect } from 'react';
import Link from 'next/link';
import { useTranslations } from 'next-intl';

export default function TaxiPage() {
  const t = useTranslations();
  const [selectedCategory, setSelectedCategory] = useState('');
  const [pickup, setPickup] = useState('');
  const [dropoff, setDropoff] = useState('');
  const [estimates, setEstimates] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  const [surge, setSurge] = useState(1.0);

  const categories = [
    { id: 'nano', name: 'Nano', rate: 45, icon: '🚗' },
    { id: 'mini', name: 'Mini', rate: 55, icon: '🚙' },
    { id: 'sedan', name: 'Sedan', rate: 75, icon: '🚗' },
    { id: 'suv', name: 'SUV', rate: 100, icon: '🚙' },
    { id: 'van', name: 'Van', rate: 150, icon: '🚐' },
  ];

  useEffect(() => {
    if (categories.length > 0) {
      setSelectedCategory(categories[0].id);
    }
  }, []);

  const getEstimate = async () => {
    if (!pickup || !dropoff || !selectedCategory) {
      return;
    }

    setLoading(true);
    try {
      // Mock fetch — in real app, call API
      // const res = await fetch('/api/v1/taxi/fare/all-categories?...').then(r => r.json());
      setEstimates({
        nano: { base: 45, distance: 12.5, total: 57.5, km: 1 },
        mini: { base: 55, distance: 15, total: 70, km: 1 },
        sedan: { base: 75, distance: 20, total: 95, km: 1 },
        suv: { base: 100, distance: 26, total: 126, km: 1 },
        van: { base: 150, distance: 39, total: 189, km: 1 },
      });
      setSurge(1.2); // Mock surge
    } catch (e) {
      console.error('Failed to get estimate', e);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-amber-50 to-orange-50 pt-8 pb-16">
      <div className="max-w-2xl mx-auto px-4">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-4xl font-bold text-gray-900 mb-2">Grabber Taxi</h1>
          <p className="text-gray-600">Quick, reliable, and affordable rides across Sri Lanka</p>
        </div>

        {/* Category selector */}
        <div className="grid grid-cols-5 gap-3 mb-8">
          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => setSelectedCategory(cat.id)}
              className={`p-4 rounded-lg font-semibold transition-all ${
                selectedCategory === cat.id
                  ? 'bg-amber-500 text-white shadow-lg scale-105'
                  : 'bg-white text-gray-900 border border-gray-200 hover:border-amber-300'
              }`}
            >
              <div className="text-3xl mb-1">{cat.icon}</div>
              <div className="text-xs">{cat.name}</div>
              <div className="text-xs font-normal text-opacity-80">LKR {cat.rate}/km</div>
            </button>
          ))}
        </div>

        {/* Location inputs */}
        <div className="space-y-4 mb-6">
          <input
            type="text"
            placeholder="📍 Pickup location"
            value={pickup}
            onChange={(e) => setPickup(e.target.value)}
            className="w-full px-4 py-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-amber-500"
          />
          <input
            type="text"
            placeholder="📌 Dropoff location"
            value={dropoff}
            onChange={(e) => setDropoff(e.target.value)}
            className="w-full px-4 py-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-amber-500"
          />
        </div>

        {/* Get estimate button */}
        <button
          onClick={getEstimate}
          disabled={!pickup || !dropoff || loading}
          className="w-full py-3 bg-amber-500 text-white rounded-lg font-bold hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed mb-6 transition-all"
        >
          {loading ? 'Getting estimate...' : 'Get Fare Estimate'}
        </button>

        {/* Fare estimates panel */}
        {estimates && (
          <div className="bg-white rounded-xl p-6 mb-8 shadow-md border border-amber-200">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-bold">Fare Estimates</h2>
              {surge > 1.0 && (
                <span className="bg-red-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                  {surge.toFixed(1)}x Surge
                </span>
              )}
            </div>

            <div className="space-y-3">
              {categories.map((cat) => {
                const est = estimates[cat.id];
                if (!est) return null;
                const total = est.total * surge;
                const pearlPts = Math.floor(total / 100);
                return (
                  <div
                    key={cat.id}
                    className={`p-4 rounded-lg border-2 transition-all cursor-pointer ${
                      selectedCategory === cat.id
                        ? 'bg-amber-50 border-amber-500'
                        : 'bg-gray-50 border-gray-200 hover:border-amber-300'
                    }`}
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <div className="font-semibold">{cat.name} {cat.icon}</div>
                        <div className="text-xs text-gray-600">~{est.km} km</div>
                      </div>
                      <div className="text-right">
                        <div className="text-2xl font-bold text-amber-600">LKR {total.toFixed(0)}</div>
                        <div className="text-xs text-gray-500">Earn {pearlPts} Pearl Points</div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>

            <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p className="text-sm text-blue-900">
                <strong>💡 Tip:</strong> Download our app for faster bookings, real-time tracking, and exclusive rewards!
              </p>
            </div>
          </div>
        )}

        {/* CTA */}
        <div className="space-y-4">
          <button className="w-full py-4 bg-black text-white rounded-lg font-bold text-lg hover:bg-gray-900 transition-all">
            Download Grabber App
          </button>
          <p className="text-center text-sm text-gray-600">
            Available on{' '}
            <Link href="#" className="text-amber-600 font-semibold hover:underline">
              iOS
            </Link>{' '}
            and{' '}
            <Link href="#" className="text-amber-600 font-semibold hover:underline">
              Android
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
}
