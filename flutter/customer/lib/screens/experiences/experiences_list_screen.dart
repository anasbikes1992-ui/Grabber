import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../services/experience_service.dart';

class ExperiencesListScreen extends StatefulWidget {
  const ExperiencesListScreen({super.key});

  @override
  State<ExperiencesListScreen> createState() => _ExperiencesListScreenState();
}

class _ExperiencesListScreenState extends State<ExperiencesListScreen> {
  bool _loading = true;
  String _error = '';
  List<Map<String, dynamic>> _items = const [];

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
      final list = await ExperienceService.fetchExperiences();
      setState(() => _items = list);
    } catch (_) {
      setState(() => _error = 'Failed to load experiences');
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Experiences')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error.isNotEmpty
              ? Center(child: Text(_error))
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: _items.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 12),
                  itemBuilder: (context, index) {
                    final item = _items[index];
                    return Card(
                      child: ListTile(
                        onTap: () => context.go('/experiences/detail', extra: {'experience_id': item['id']}),
                        title: Text((item['title'] ?? 'Experience').toString()),
                        subtitle: Text((item['city'] ?? 'Sri Lanka').toString()),
                      ),
                    );
                  },
                ),
    );
  }
}
