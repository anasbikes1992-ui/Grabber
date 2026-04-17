import 'package:flutter/material.dart';

import '../../services/experience_service.dart';

class ExperienceDetailScreen extends StatefulWidget {
  const ExperienceDetailScreen({super.key, required this.experienceId});

  final String experienceId;

  @override
  State<ExperienceDetailScreen> createState() => _ExperienceDetailScreenState();
}

class _ExperienceDetailScreenState extends State<ExperienceDetailScreen> {
  bool _loading = true;
  String _error = '';
  Map<String, dynamic>? _experience;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = '';
    });

    try {
      final data = await ExperienceService.getExperience(widget.experienceId);
      setState(() => _experience = data);
    } catch (_) {
      setState(() => _error = 'Failed to load experience details');
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    final experience = _experience;
    if (experience == null || experience.isEmpty) {
      return Scaffold(
        appBar: AppBar(title: const Text('Experience')),
        body: Center(child: Text(_error.isEmpty ? 'Experience not found' : _error)),
      );
    }

    return Scaffold(
      appBar: AppBar(title: Text((experience['title'] ?? 'Experience').toString())),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text((experience['description'] ?? 'No description available').toString()),
          const SizedBox(height: 16),
          Text('City: ${(experience['city'] ?? 'Sri Lanka').toString()}'),
          const SizedBox(height: 8),
          Text('Price: LKR ${(experience['base_price'] ?? 0).toString()}'),
        ],
      ),
    );
  }
}
