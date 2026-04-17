import 'package:dio/dio.dart';

class ExperienceService {
  static final _dio = Dio(
    BaseOptions(
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 20),
    ),
  );

  static String _apiBaseUrl = const String.fromEnvironment(
    'GRABBER_API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  static void setApiBaseUrl(String url) => _apiBaseUrl = url;

  static Future<List<Map<String, dynamic>>> fetchExperiences() async {
    try {
      final res = await _dio.get('$_apiBaseUrl/experiences');
      final body = res.data;
      final list = body['data'] is Map ? body['data']['data'] : body['data'];
      if (list is List) {
        return list.whereType<Map>().map((e) => Map<String, dynamic>.from(e)).toList();
      }
      return <Map<String, dynamic>>[];
    } on DioException catch (e) {
      throw Exception('Failed to load experiences: ${e.message}');
    }
  }

  static Future<Map<String, dynamic>> getExperience(String experienceId) async {
    if (experienceId.isEmpty) {
      throw ArgumentError('Experience ID cannot be empty');
    }

    try {
      final res = await _dio.get('$_apiBaseUrl/experiences/$experienceId');
      final body = res.data;
      return Map<String, dynamic>.from(body['data'] ?? <String, dynamic>{});
    } on DioException catch (e) {
      throw Exception('Failed to load experience: ${e.message}');
    }
  }
}
