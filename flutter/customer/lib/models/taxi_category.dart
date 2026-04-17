class TaxiCategory {
  final String id;
  final String name;
  final String icon;
  final double baseFare;
  final double perKmRate;
  final bool isActive;

  TaxiCategory({
    required this.id,
    required this.name,
    required this.icon,
    required this.baseFare,
    required this.perKmRate,
    required this.isActive,
  });

  factory TaxiCategory.fromJson(Map<String, dynamic> json) {
    return TaxiCategory(
      id: json['id'].toString(),
      name: json['name'] as String,
      icon: _getIconForCategory(json['name'] as String),
      baseFare: (json['base_fare'] as num).toDouble(),
      perKmRate: (json['per_km_rate'] as num).toDouble(),
      isActive: json['is_active'] as bool? ?? true,
    );
  }

  static String _getIconForCategory(String name) {
    final lower = name.toLowerCase();
    if (lower.contains('nano')) return '🚗';
    if (lower.contains('mini')) return '🚙';
    if (lower.contains('sedan')) return '🚗';
    if (lower.contains('suv')) return '🚙';
    if (lower.contains('van')) return '🚐';
    return '🚖';
  }
}
