import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'api_service.dart';

final authServiceProvider = Provider<AuthService>((ref) {
  return AuthService(apiService: ref.watch(apiServiceProvider));
});

class AuthService {
  AuthService({required ApiService apiService}) : _apiService = apiService;

  final ApiService _apiService;

  Future<void> sendOtp({
    required String identifier,
    required String identifierType,
    String purpose = 'login',
  }) async {
    await _apiService.post(
      '/auth/send-otp',
      data: {
        'identifier': identifier,
        'identifier_type': identifierType,
        'purpose': purpose,
      },
    );
  }

  Future<Map<String, dynamic>> verifyOtp({
    required String identifier,
    required String identifierType,
    required String code,
    String purpose = 'login',
  }) async {
    final response = await _apiService.post(
      '/auth/verify-otp',
      data: {
        'identifier': identifier,
        'identifier_type': identifierType,
        'code': code,
        'purpose': purpose,
      },
    );

    final payload = (response['data'] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
    final token = payload['token'] as String?;
    if (token != null && token.isNotEmpty) {
      await _apiService.persistToken(token);
    }

    return payload;
  }

  Future<Map<String, dynamic>> register({
    required String identifier,
    required String identifierType,
    required String fullName,
    required String role,
  }) async {
    final response = await _apiService.post(
      '/auth/register',
      data: {
        'identifier': identifier,
        'identifier_type': identifierType,
        'full_name': fullName,
        'role': role,
      },
    );

    final payload = (response['data'] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
    final token = payload['token'] as String?;
    if (token != null && token.isNotEmpty) {
      await _apiService.persistToken(token);
    }

    return payload;
  }

  Future<void> logout() async {
    try {
      await _apiService.post('/auth/logout');
    } on DioException {
      // Best-effort logout; clear the local token either way.
    } finally {
      await _apiService.clearToken();
    }
  }
}