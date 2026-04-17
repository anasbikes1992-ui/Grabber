import 'package:flutter/material.dart';

class AppTheme {
  static const _primaryColor = Color(0xFF1B6CA8);
  static const _secondaryColor = Color(0xFFF4A200);

  static ThemeData light() {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: _primaryColor,
        secondary: _secondaryColor,
        brightness: Brightness.light,
      ),
      fontFamily: 'Poppins',
    );
  }

  static ThemeData dark() {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(
        seedColor: _primaryColor,
        secondary: _secondaryColor,
        brightness: Brightness.dark,
      ),
      fontFamily: 'Poppins',
    );
  }
}
