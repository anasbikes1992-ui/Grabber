import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class EventService {
  static final _dio = Dio(
    BaseOptions(
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 20),
    ),
  );
  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'grabber_token';

  static String _apiBaseUrl = const String.fromEnvironment(
    'GRABBER_API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  static void setApiBaseUrl(String url) => _apiBaseUrl = url;

  static Future<List<Map<String, dynamic>>> fetchEvents() async {
    try {
      final res = await _dio.get('$_apiBaseUrl/events');
      final body = res.data;
      final list = body['data'] is Map ? body['data']['data'] : body['data'];
      if (list is List) {
        return list.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return <Map<String, dynamic>>[];
    } on DioException catch (e) {
      throw Exception('Failed to load events: ${e.message}');
    }
  }

  static Future<Map<String, dynamic>> getEvent(String eventId) async {
    if (eventId.isEmpty) {
      throw ArgumentError('Event ID cannot be empty');
    }

    try {
      final res = await _dio.get('$_apiBaseUrl/events/$eventId');
      final body = res.data;
      return Map<String, dynamic>.from(body['data'] ?? <String, dynamic>{});
    } on DioException catch (e) {
      throw Exception('Failed to load event: ${e.message}');
    }
  }

  static Future<Map<String, dynamic>> purchaseTicket({
    required String eventId,
    required String ticketTypeId,
    int quantity = 1,
  }) async {
    try {
      final token = await _storage.read(key: _tokenKey);

      final res = await _dio.post(
        '$_apiBaseUrl/events/$eventId/tickets/purchase',
        data: {
          'ticket_type_id': ticketTypeId,
          'quantity': quantity,
        },
        options: Options(
          headers: {
            'Content-Type': 'application/json',
            if (token != null && token.isNotEmpty) 'Authorization': 'Bearer $token',
          },
        ),
      );

      return Map<String, dynamic>.from(res.data ?? <String, dynamic>{});
    } on DioException catch (e) {
      throw Exception('Failed to purchase ticket: ${e.message}');
    }
  }
}
