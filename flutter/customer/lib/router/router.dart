import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../screens/auth/otp_screen.dart';
import '../screens/auth/register_screen.dart';
import '../screens/booking/booking_confirmation_screen.dart';
import '../screens/payment/payment_method_screen.dart';
import '../screens/splash/splash_screen.dart';
import '../screens/home/home_screen.dart';
import '../screens/auth/login_screen.dart';
import '../screens/stays/stay_detail_screen.dart';
import '../screens/stays/stays_search_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/splash',
    routes: [
      GoRoute(path: '/splash', builder: (_, __) => const SplashScreen()),
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(
        path: '/auth/verify',
        builder: (_, state) {
          final extra = (state.extra as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
          return OtpScreen(
            identifier: extra['identifier'] as String? ?? '',
            identifierType: extra['identifier_type'] as String? ?? 'phone',
            isNewUser: extra['is_new_user'] as bool? ?? false,
          );
        },
      ),
      GoRoute(
        path: '/register',
        builder: (_, state) {
          final extra = (state.extra as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
          return RegisterScreen(
            identifier: extra['identifier'] as String? ?? '',
            identifierType: extra['identifier_type'] as String? ?? 'phone',
          );
        },
      ),
      GoRoute(path: '/home', builder: (_, __) => const HomeScreen()),
      GoRoute(path: '/stays/search', builder: (_, __) => const StaysSearchScreen()),
      GoRoute(
        path: '/stays/detail',
        builder: (_, state) {
          final extra = (state.extra as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
          return StayDetailScreen(
            stayId: extra['stay_id'] as String? ?? '',
            title: extra['title'] as String? ?? 'Stay',
            city: extra['city'] as String? ?? '',
            basePrice: (extra['base_price'] as num?)?.toDouble() ?? 0,
            description: extra['description'] as String?,
          );
        },
      ),
      GoRoute(
        path: '/payment/method',
        builder: (_, state) {
          final extra = (state.extra as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
          return PaymentMethodScreen(
            stayTitle: extra['stay_title'] as String? ?? 'Stay',
            subtotal: (extra['subtotal'] as num?)?.toDouble() ?? 0,
            nights: extra['nights'] as int? ?? 1,
            guests: extra['guests'] as int? ?? 1,
            checkIn: extra['check_in'] as String? ?? '',
            checkOut: extra['check_out'] as String? ?? '',
          );
        },
      ),
      GoRoute(path: '/booking/confirmation', builder: (_, __) => const BookingConfirmationScreen()),
    ],
  );
});
