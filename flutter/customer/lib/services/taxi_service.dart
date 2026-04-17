import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../models/taxi_category.dart';

class TaxiService {
  static final _dio = Dio();
  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'grabber_token';

  // Set this during app init with your actual API base URL
  static String _apiBaseUrl = 'https://api.grabber-hub-lk.local/api/v1';

  static void setApiBaseUrl(String url) => _apiBaseUrl = url;

  // ── Public endpoints (no auth) ────────────────────────────────────────────

  static Future<List<TaxiCategory>> fetchCategories() async {
    try {
      final res = await _dio.get('$_apiBaseUrl/taxi/fare/all-categories');
      final categories = res.data['categories'] as List;
      return categories.map((c) => TaxiCategory.fromJson(c)).toList();
    } catch (e) {
      rethrow;
    }
  }

  static Future<Map<String, dynamic>> fareEstimate({
    required double originLat,
    required double originLng,
    required double destLat,
    required double destLng,
    required String categoryId,
  }) async {
    try {
      final res = await _dio.get(
        '$_apiBaseUrl/taxi/fare/estimate',
        queryParameters: {
          'origin_lat': originLat,
          'origin_lng': originLng,
          'dest_lat': destLat,
          'dest_lng': destLng,
          'category_id': categoryId,
        },
      );
      return res.data['estimate'] ?? res.data;
    } catch (e) {
      rethrow;
    }
  }

  // ── Authenticated endpoints (customer) ────────────────────────────────────

  static Future<Map<String, dynamic>> requestRide({
    required String categoryId,
    required double originLat,
    required double originLng,
    required String originAddress,
    required double destLat,
    required double destLng,
    required String destAddress,
    required String paymentMethod,
  }) async {
    try {
      final res = await _dio.post(
        '$_apiBaseUrl/taxi/rides/request',
        data: {
          'taxi_category_id': categoryId,
          'origin_lat': originLat,
          'origin_lng': originLng,
          'origin_address': originAddress,
          'dest_lat': destLat,
          'dest_lng': destLng,
          'dest_address': destAddress,
          'payment_method': paymentMethod,
        },
        options: await _authOptions(),
      );
      return res.data;
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

  static Future<void> cancelRide(String tripId, {String reason = ''}) async {
    try {
      await _dio.patch(
        '$_apiBaseUrl/taxi/rides/$tripId/cancel',
        data: {'reason': reason},
        options: await _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<void> rateRide(String tripId, {required int rating, double tipAmount = 0}) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/rides/$tripId/rate',
        data: {
          'rating': rating,
          'tip_amount': tipAmount,
        },
        options: await _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  static Future<void> triggerSos(String tripId) async {
    try {
      await _dio.post(
        '$_apiBaseUrl/taxi/rides/$tripId/sos',
        options: await _authOptions(),
      );
    } catch (e) {
      rethrow;
    }
  }

  // ── Helper ───────────────────────────────────────────────────────────────

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
