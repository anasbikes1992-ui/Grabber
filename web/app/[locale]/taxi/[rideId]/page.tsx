'use client';

import { useState, useEffect } from 'react';
import { useParams } from 'next/navigation';

export default function RideTrackingPage() {
  const params = useParams();
  const rideId = params.rideId as string;

  const [trip, setTrip] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchTrip = async () => {
      try {
        // In real app: const res = await fetch(`/api/v1/taxi/rides/${rideId}`);
        // Mock response
        setTrip({
          id: rideId,
          status: 'in_transit',
          origin_address: '123 Main Street, Colombo',
          dest_address: '456 Galle Road, Colombo',
          fare: 550,
          distance_km: 8.5,
          driver: {
            profile: { name: 'Rajeev Kumar' },
            driver_rating: 4.8,
            current_lat: 6.9271,
            current_lng: 79.8612,
          },
          customer: {
            profile: { name: 'Customer' },
          },
        });
      } catch (e) {
        console.error('Failed to fetch trip', e);
      } finally {
        setLoading(false);
      }
    };

    if (rideId) {
      fetchTrip();
    }
  }, [rideId]);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-4 border-amber-500 border-t-transparent"></div>
      </div>
    );
  }

  if (!trip) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-gray-600">Ride not found</p>
      </div>
    );
  }

  const statusLabels: Record<string, string> = {
    searching: '🔍 Finding your driver...',
    accepted: '📍 Driver is on the way',
    driver_arrived: '✋ Driver has arrived',
    in_transit: '🚗 On the way to your destination',
    completed: '✅ Ride complete!',
  };

  return (
    <div className="min-h-screen bg-gray-50 pt-6 pb-12">
      <div className="max-w-2xl mx-auto px-4">
        {/* Status header */}
        <div className="bg-white rounded-lg p-6 shadow-sm mb-6">
          <h1 className="text-2xl font-bold mb-2">Live Tracking</h1>
          <p className="text-lg text-amber-600 font-semibold">
            {statusLabels[trip.status] || trip.status}
          </p>
        </div>

        {/* Map placeholder */}
        <div className="bg-gray-300 rounded-lg h-64 mb-6 flex items-center justify-center text-gray-600">
          🗺️ OpenStreetMap would appear here
        </div>

        {/* Driver info */}
        <div className="bg-white rounded-lg p-6 shadow-sm mb-6">
          <h2 className="font-bold text-lg mb-4">Driver Details</h2>
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center text-2xl font-bold">
              {trip.driver?.profile?.name?.[0] || 'D'}
            </div>
            <div className="flex-1">
              <p className="font-semibold text-lg">{trip.driver?.profile?.name}</p>
              <div className="flex items-center gap-1">
                <span>⭐ {trip.driver?.driver_rating}</span>
              </div>
            </div>
            <button className="px-4 py-2 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600">
              📞 Call
            </button>
          </div>
        </div>

        {/* Trip details */}
        <div className="bg-white rounded-lg p-6 shadow-sm">
          <h2 className="font-bold text-lg mb-4">Trip Details</h2>
          <div className="space-y-4">
            <div className="flex justify-between items-center pb-4 border-b">
              <span className="text-gray-600">From</span>
              <span className="font-semibold">{trip.origin_address}</span>
            </div>
            <div className="flex justify-between items-center pb-4 border-b">
              <span className="text-gray-600">To</span>
              <span className="font-semibold">{trip.dest_address}</span>
            </div>
            <div className="flex justify-between items-center pb-4 border-b">
              <span className="text-gray-600">Distance</span>
              <span className="font-semibold">{trip.distance_km} km</span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-gray-600">Estimated Fare</span>
              <span className="text-2xl font-bold text-amber-600">LKR {trip.fare}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
