import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:grabber_shared/shared.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _inputController = TextEditingController();

  String _mode = 'phone'; // 'phone' | 'email'
  bool _isLoading = false;

  @override
  void dispose() {
    _inputController.dispose();
    super.dispose();
  }

  bool get _isPhoneMode => _mode == 'phone';

  Future<void> _sendOtp() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);

    try {
      await ref.read(authServiceProvider).sendOtp(
            identifier: _inputController.text.trim(),
            identifierType: _mode,
          );
    } catch (error) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to send OTP: $error')),
      );
      return;
    }

    if (!mounted) return;
    setState(() => _isLoading = false);

    context.go('/auth/verify', extra: {
      'identifier': _inputController.text.trim(),
      'identifier_type': _mode,
      'is_new_user': false,
    });
  }

  String? _validateInput(String? value) {
    if (value == null || value.trim().isEmpty) {
      return _isPhoneMode ? 'Enter your phone number' : 'Enter your email';
    }
    if (_isPhoneMode) {
      if (!RegExp(r'^\+?\d{9,15}$').hasMatch(value.trim())) {
        return 'Enter a valid phone number';
      }
    } else {
      if (!RegExp(r'^[\w.+-]+@[\w-]+\.\w+$').hasMatch(value.trim())) {
        return 'Enter a valid email address';
      }
    }
    return null;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: theme.colorScheme.surface,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 32),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: theme.colorScheme.primary,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(Icons.location_pin, color: Colors.white, size: 32),
              ),
              const SizedBox(height: 32),
              Text('Welcome to Grabber',
                  style: theme.textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                  )),
              const SizedBox(height: 8),
              Text('Sign in or create an account',
                  style: theme.textTheme.bodyLarge?.copyWith(
                    color: theme.colorScheme.onSurface.withOpacity(0.6),
                  )),
              const SizedBox(height: 40),
              // Phone/Email toggle
              Container(
                decoration: BoxDecoration(
                  color: theme.colorScheme.surfaceContainerHighest,
                  borderRadius: BorderRadius.circular(12),
                ),
                padding: const EdgeInsets.all(4),
                child: Row(
                  children: [
                    _buildToggle(context, 'Phone', 'phone'),
                    _buildToggle(context, 'Email', 'email'),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              Form(
                key: _formKey,
                child: TextFormField(
                  controller: _inputController,
                  keyboardType: _isPhoneMode
                      ? TextInputType.phone
                      : TextInputType.emailAddress,
                  inputFormatters: _isPhoneMode
                      ? [FilteringTextInputFormatter.allow(RegExp(r'[0-9+]'))]
                      : [],
                  validator: _validateInput,
                  decoration: InputDecoration(
                    labelText: _isPhoneMode ? 'Phone number' : 'Email address',
                    prefixIcon: Icon(
                      _isPhoneMode ? Icons.phone_outlined : Icons.email_outlined,
                    ),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                    filled: true,
                    fillColor: theme.colorScheme.surface,
                  ),
                ),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 52,
                child: FilledButton(
                  onPressed: _isLoading ? null : _sendOtp,
                  style: FilledButton.styleFrom(
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  child: _isLoading
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.5,
                            color: Colors.white,
                          ),
                        )
                      : const Text('Continue',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
                ),
              ),
              const Spacer(),
              Center(
                child: Text(
                  'By continuing you agree to our Terms of Service\nand Privacy Policy.',
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurface.withOpacity(0.45),
                    height: 1.5,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildToggle(BuildContext context, String label, String value) {
    final selected = _mode == value;
    final theme = Theme.of(context);
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() {
          _mode = value;
          _inputController.clear();
        }),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: selected ? theme.colorScheme.primary : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(
              color: selected ? Colors.white : theme.colorScheme.onSurface,
              fontWeight: FontWeight.w600,
              fontSize: 14,
            ),
          ),
        ),
      ),
    );
  }
}
