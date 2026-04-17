import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:grabber_shared/shared.dart';

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({
    super.key,
    required this.identifier,
    required this.identifierType,
  });

  final String identifier;
  final String identifierType;

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  String _role = 'customer';
  bool _isSubmitting = false;

  @override
  void dispose() {
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      await ref.read(authServiceProvider).register(
            identifier: widget.identifier,
            identifierType: widget.identifierType,
            fullName: _nameController.text.trim(),
            role: _role,
          );

      if (!mounted) {
        return;
      }

      context.go('/home');
    } catch (error) {
      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Registration failed: $error')),
      );
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Complete registration')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Finish your Grabber profile',
                style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 8),
              Text(
                widget.identifier,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.onSurface.withOpacity(0.65),
                ),
              ),
              const SizedBox(height: 24),
              TextFormField(
                controller: _nameController,
                decoration: const InputDecoration(
                  labelText: 'Full name',
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.trim().length < 3) {
                    return 'Enter your full name';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 18),
              DropdownButtonFormField<String>(
                value: _role,
                decoration: const InputDecoration(
                  labelText: 'Account type',
                  border: OutlineInputBorder(),
                ),
                items: const [
                  DropdownMenuItem(value: 'customer', child: Text('Customer')),
                  DropdownMenuItem(value: 'provider', child: Text('Provider')),
                ],
                onChanged: (value) {
                  if (value != null) {
                    setState(() => _role = value);
                  }
                },
              ),
              const Spacer(),
              SizedBox(
                width: double.infinity,
                child: FilledButton(
                  onPressed: _isSubmitting ? null : _submit,
                  child: Text(_isSubmitting ? 'Creating account...' : 'Create account'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}