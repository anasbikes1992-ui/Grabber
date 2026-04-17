import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class TaxiDriverService {
  static final _dio = Dio();
  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'grabber_token';

  // Set this during app init with your actual API base URL
  static String _apiBaseUrl = 'https://api.grabber-hub-lk.local/api/v1';

  static void setApiBaseUrl(String url) => _apiBaseUrl = url;

  // ── Driver status & location ────────────────────────────────────────────

  static Future<void> setOnlineStatus(bool isOnline) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/status',
        data: {'is_online': isOnline},
        options: await _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<void> updateLocation({
    required double lat,
    required double lng,
  }) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/location',
        data: {'lat': lat, 'lng': lng},
        options: await _authOptions(),
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
        options: await _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<void> markArrived(String rideId) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/rides/$rideId/arrive',
        options: await _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<void> startRide(String rideId) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/rides/$rideId/start',
        options: await _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<void> completeRide(String rideId) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/driver/rides/$rideId/complete',
        options: await _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<Map<String, dynamic>> getTrip(String tripId) async {
    try {
      final res = await _dio.get(
        '$_apiBaseUrl/taxi/rides/$tripId',
        options: await _authOptions(),
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
        options: await _authOptions(),
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
        options: await _authOptions(),
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
        options: await _authOptions(),
      );
      return res.data;
    } catch (e) {
      rethrow;
    }
  }

  // ── Helper ───────────────────────────────────────────────────────────

  static Future<Options> _authOptions() async {
    final token = await _storage.read(key: _tokenKey);
    final headers = <String, String>{
      'Content-Type': 'application/json',
    };

    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }

    return Options(headers: headers);
  }
}
