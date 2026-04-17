import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../services/taxi_driver_service.dart';

class QuestsScreen extends ConsumerStatefulWidget {
  const QuestsScreen({super.key});

  @override
  ConsumerState<QuestsScreen> createState() => _QuestsScreenState();
}

class _QuestsScreenState extends ConsumerState<QuestsScreen> {
  List<Map<String, dynamic>> _activeQuests = [];
  List<Map<String, dynamic>> _completedQuests = [];
  String _scoreTier = '';
  int _totalScore = 0;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadQuests();
  }

  Future<void> _loadQuests() async {
    setState(() => _loading = true);
    try {
      final data = await TaxiDriverService.getQuests();
      setState(() {
        _activeQuests = List<Map<String, dynamic>>.from(data['active'] ?? []);
        _completedQuests = List<Map<String, dynamic>>.from(data['completed'] ?? []);
        _scoreTier = data['tier'] as String? ?? 'Bronze';
        _totalScore = (data['total_score'] as num?)?.toInt() ?? 0;
      });
    } catch (_) {
    } finally {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final tierColor = {
      'Bronze': Colors.brown[400],
      'Silver': Colors.grey[400],
      'Gold': Colors.amber[600],
      'Diamond': Colors.cyan[400],
    }[_scoreTier] ?? Colors.brown[400];

    return Scaffold(
      appBar: AppBar(
        title: const Text('Quests & Scoring'),
        centerTitle: true,
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Tier badge
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [tierColor!, tierColor.withOpacity(0.5)],
                      ),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Column(
                      children: [
                        Text(
                          _scoreTier.toUpperCase(),
                          style: const TextStyle(
                            fontSize: 28,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Total Score: $_totalScore',
                          style: const TextStyle(color: Colors.white, fontSize: 14),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Active quests
                  const Text('Active Quests', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const SizedBox(height: 12),
                  if (_activeQuests.isEmpty)
                    Center(
                      child: Padding(
                        padding: const EdgeInsets.symmetric(vertical: 32),
                        child: Text('No active quests', style: TextStyle(color: Colors.grey[600])),
                      ),
                    )
                  else
                    ..._activeQuests.map((quest) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _QuestCard(quest: quest),
                    )),

                  const SizedBox(height: 20),

                  // Completed quests
                  if (_completedQuests.isNotEmpty) ...[
                    const Text('Completed', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    const SizedBox(height: 12),
                    ..._completedQuests.map((quest) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _QuestCard(quest: quest, completed: true),
                    )),
                  ],
                ],
              ),
            ),
    );
  }
}

class _QuestCard extends StatelessWidget {
  const _QuestCard({required this.quest, this.completed = false});

  final Map<String, dynamic> quest;
  final bool completed;

  @override
  Widget build(BuildContext context) {
    final title = quest['title'] as String? ?? '';
    final metric = quest['metric'] as String? ?? '';
    final progress = (quest['progress'] as num?)?.toInt() ?? 0;
    final target = (quest['target'] as num?)?.toInt() ?? 1;
    final reward = (quest['reward'] as num?)?.toDouble() ?? 0;
    final progressPct = (progress / target).clamp(0, 1).toDouble();

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: completed ? Colors.green[50] : Colors.amber[50],
        border: Border.all(color: completed ? Colors.green[200]! : Colors.amber[200]!),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 2),
                    Text(metric, style: TextStyle(fontSize: 12, color: Colors.grey[600])),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.amber,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  'LKR ${reward.toStringAsFixed(0)}',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: LinearProgressIndicator(
                  value: progressPct,
                  minHeight: 6,
                  backgroundColor: Colors.grey[300],
                  color: completed ? Colors.green : Colors.amber,
                  borderRadius: BorderRadius.circular(3),
                ),
              ),
              const SizedBox(width: 8),
              Text('$progress/$target', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
            ],
          ),
        ],
      ),
    );
  }
}
