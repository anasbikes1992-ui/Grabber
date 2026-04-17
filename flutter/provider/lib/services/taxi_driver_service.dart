import 'package:dio/dio.dart';

class TaxiDriverService {
  static final _dio = Dio();

  // Set this during app init with your actual API base URL
  static String _apiBaseUrl = 'https://api.grabber-hub-lk.local/api/v1';

  static void setApiBaseUrl(String url) => _apiBaseUrl = url;

  // ── Driver status & location ────────────────────────────────────────────

  static Future<void> setOnlineStatus(bool isOnline) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/status',
        data: {'is_online': isOnline},
        options: _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<void> updateLocation({
    required double latitude,
    required double longitude,
  }) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/location',
        data: {'latitude': latitude, 'longitude': longitude},
        options: _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  // ── Ride actions ───────────────────────────────────────────────────────

  static Future<void> acceptRide(String rideId) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/rides/$rideId/accept',
        options: _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<void> markArrived(String rideId) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/rides/$rideId/arrive',
        options: _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<void> startRide(String rideId) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/rides/$rideId/start',
        options: _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<void> completeRide(String rideId) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/rides/$rideId/complete',
        options: _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<Map<String, dynamic>> getTrip(String tripId) async {
    try {
      final res = await _dio.get(
        '$_apiBaseUrl/taxi/rides/$tripId',
        options: _authOptions(),
      );
      return res.data;
    } catch (e) {
      rethrow;
    }
  }

  // ── Quests & commissions ──────────────────────────────────────────────

  static Future<Map<String, dynamic>> getQuests() async {
    try {
      final res = await _dio.get(
        '$_apiBaseUrl/taxi/driver/quests',
        options: _authOptions(),
      );
      return res.data;
    } catch (e) {
      rethrow;
    }
  }

  static Future<Map<String, dynamic>> getCommissionInvoices() async {
    try {
      final res = await _dio.get(
        '$_apiBaseUrl/taxi/driver/commission-invoices',
        options: _authOptions(),
      );
      return res.data;
    } catch (e) {
      rethrow;
    }
  }

  static Future<Map<String, dynamic>> getTodayEarnings() async {
    try {
      final res = await _dio.get(
        '$_apiBaseUrl/taxi/driver/earnings',
        options: _authOptions(),
      );
      return res.data;
    } catch (e) {
      rethrow;
    }
  }

  // ── Helper ───────────────────────────────────────────────────────────

  static Options _authOptions() {
    // TODO: retrieve actual token from secure storage / auth provider
    // final token = await _secureStorage.read(key: 'auth_token');
    return Options(
      headers: {
        'Authorization': 'Bearer YOUR_TOKEN_HERE',
        'Content-Type': 'application/json',
      },
    );
  }
}
