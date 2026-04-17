import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

final secureStorageProvider = Provider<FlutterSecureStorage>((ref) {
  return const FlutterSecureStorage();
});

final apiServiceProvider = Provider<ApiService>((ref) {
  return ApiService(storage: ref.watch(secureStorageProvider));
});

class ApiService {
  ApiService({
    required FlutterSecureStorage storage,
    Dio? dio,
  })  : _storage = storage,
        _dio = dio ?? Dio(
          BaseOptions(
            baseUrl: const String.fromEnvironment(
              'GRABBER_API_BASE_URL',
              defaultValue: 'http://10.0.2.2:8000/api/v1',
            ),
            connectTimeout: const Duration(seconds: 15),
            receiveTimeout: const Duration(seconds: 20),
            headers: const {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          ),
        ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _storage.read(key: _tokenKey);
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  static const _tokenKey = 'grabber_token';

  final Dio _dio;
  final FlutterSecureStorage _storage;

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) async {
    final response = await _dio.get<Map<String, dynamic>>(path, queryParameters: query);
    return response.data ?? <String, dynamic>{};
  }

  Future<Map<String, dynamic>> post(String path, {Object? data}) async {
    final response = await _dio.post<Map<String, dynamic>>(path, data: data);
    return response.data ?? <String, dynamic>{};
  }

  Future<Map<String, dynamic>> patch(String path, {Object? data}) async {
    final response = await _dio.patch<Map<String, dynamic>>(path, data: data);
    return response.data ?? <String, dynamic>{};
  }

  Future<void> persistToken(String token) {
    return _storage.write(key: _tokenKey, value: token);
  }

  Future<String?> readToken() {
    return _storage.read(key: _tokenKey);
  }

  Future<void> clearToken() {
    return _storage.delete(key: _tokenKey);
  }
}