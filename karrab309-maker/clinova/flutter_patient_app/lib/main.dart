import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import 'theme/app_theme.dart';
import 'screens/splash_screen.dart';
import 'services/locale_service.dart';
import 'config/api_config.dart';
import 'services/api_service.dart';
import 'services/theme_preferences_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.light,
      systemNavigationBarColor: AppTheme.surface,
      systemNavigationBarIconBrightness: Brightness.dark,
    ),
  );
  await ApiService.init();
  await ThemePreferencesService.instance.load();
  if (kDebugMode) {
    debugPrint('[Clinova] API baseUrl = ${ApiConfig.baseUrl}');
  }
  runApp(const MyApp());
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  Locale? _locale;

  @override
  void initState() {
    super.initState();
    _loadLocale();
    LocaleService.localeNotifier.addListener(_onLocaleChanged);
    ThemePreferencesService.instance.themeMode.addListener(_rebuild);
    ThemePreferencesService.instance.textScale.addListener(_rebuild);
  }

  @override
  void dispose() {
    LocaleService.localeNotifier.removeListener(_onLocaleChanged);
    ThemePreferencesService.instance.themeMode.removeListener(_rebuild);
    ThemePreferencesService.instance.textScale.removeListener(_rebuild);
    super.dispose();
  }

  void _rebuild() {
    if (mounted) setState(() {});
  }

  void _onLocaleChanged() {
    if (mounted) setState(() => _locale = LocaleService.localeNotifier.value);
  }

  Future<void> _loadLocale() async {
    final locale = await LocaleService.getSavedLocale();
    if (mounted) setState(() => _locale = locale);
  }

  @override
  Widget build(BuildContext context) {
    final isRtl = _locale?.languageCode == 'ar';
    final prefs = ThemePreferencesService.instance;
    return MaterialApp(
      title: 'Clinova',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: prefs.themeMode.value,
      locale: _locale,
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      supportedLocales: AppLocalizations.supportedLocales,
      builder: (context, child) {
        return MediaQuery(
          data: MediaQuery.of(context).copyWith(
            textScaler: TextScaler.linear(prefs.textScale.value),
          ),
          child: Directionality(
            textDirection: isRtl ? TextDirection.rtl : TextDirection.ltr,
            child: child ?? const SizedBox.shrink(),
          ),
        );
      },
      home: const SplashScreen(),
    );
  }
}
