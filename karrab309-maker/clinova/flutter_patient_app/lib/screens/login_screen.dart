import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import '../services/api_service.dart';
import '../services/locale_service.dart';
import '../theme/app_theme.dart';
import '../widgets/clinova_brand_logo.dart';
import 'patients_list_screen.dart';
import 'patient_home_screen.dart';
import 'doctor_home_screen.dart';
import 'nurse_home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _loading = false;
  String? _error;

  Future<void> _login() async {
    if (_loading) return;
    setState(() {
      _error = null;
      _loading = true;
    });
    try {
      final res = await ApiService.login(
        _usernameController.text.trim(),
        _passwordController.text,
      );
      if (!mounted) return;
      final user = res['user'] as Map<String, dynamic>?;
      final role = user?['role'] as String?;
      if (role != 'Patient' && role != 'Doctor' && role != 'Nurse') {
        // Version mobile : uniquement Patient / Médecin / Infirmier(ère)
        await ApiService.logout();
        if (!mounted) return;
        setState(() {
          _error = 'Ce rôle n’est pas disponible sur l’application mobile.';
          _loading = false;
        });
        return;
      }

      if (role == 'Patient') {
        final locale = user?['locale'] as String?;
        await LocaleService.applyLocaleFromUser(locale);
        final patients = await ApiService.getPatients();
        if (patients.isNotEmpty && mounted) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(
                builder: (_) =>
                    PatientHomeScreen(patientId: patients.first.id)),
          );
        } else if (mounted) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(
                builder: (_) =>
                    const PatientsListScreen(autoOpenSinglePatient: true)),
          );
        }
      } else {
        final locale = user?['locale'] as String?;
        await LocaleService.applyLocaleFromUser(locale);
        if (!mounted) return;
        if (role == 'Doctor') {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const DoctorHomeScreen()),
          );
        } else if (role == 'Nurse') {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const NurseHomeScreen()),
          );
        }
      }
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.message;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      final msg = e.toString().replaceFirst('Exception: ', '');
      final isNetwork = msg.contains('Connection') ||
          msg.contains('Socket') ||
          msg.contains('Failed host') ||
          msg.contains('Network');
      final l10n = AppLocalizations.of(context)!;
      setState(() {
        _error = isNetwork
            ? l10n.loginErrorApi
            : (msg.length > 80 ? l10n.loginErrorServer : msg);
        _loading = false;
      });
    }
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: AppTheme.heroGradient,
        ),
        child: SafeArea(
          child: LayoutBuilder(
            builder: (context, constraints) {
              return SingleChildScrollView(
                padding: const EdgeInsets.all(28),
                child: ConstrainedBox(
                  constraints: BoxConstraints(minHeight: constraints.maxHeight),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                    const Center(child: ClinovaBrandLogo()),
                    const SizedBox(height: 28),
                    Text(
                      AppLocalizations.of(context)!.appTitle,
                      style:
                          Theme.of(context).textTheme.headlineMedium?.copyWith(
                                fontWeight: FontWeight.w800,
                                color: Colors.white,
                                letterSpacing: -0.5,
                              ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      AppLocalizations.of(context)!.appSubtitle,
                      style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                            color: Colors.white.withValues(alpha: 0.9),
                            fontWeight: FontWeight.w500,
                          ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 36),
                    Container(
                      padding: const EdgeInsets.all(24),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(24),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.15),
                            blurRadius: 30,
                            offset: const Offset(0, 10),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          if (_error != null) ...[
                            Container(
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: Colors.red.shade50,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: Colors.red.shade200),
                              ),
                              child: Text(_error!,
                                  style: TextStyle(
                                      color: Colors.red.shade700,
                                      fontSize: 14)),
                            ),
                            const SizedBox(height: 20),
                          ],
                          TextFormField(
                            controller: _usernameController,
                            decoration: InputDecoration(
                              labelText:
                                  AppLocalizations.of(context)!.loginUsername,
                              prefixIcon: const Icon(
                                  Icons.person_outline_rounded,
                                  color: AppTheme.primary),
                              filled: true,
                              fillColor: AppTheme.background,
                              border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12)),
                            ),
                            textInputAction: TextInputAction.next,
                            validator: (v) => (v == null || v.trim().isEmpty)
                                ? AppLocalizations.of(context)!.loginRequired
                                : null,
                          ),
                          const SizedBox(height: 18),
                          TextFormField(
                            controller: _passwordController,
                            decoration: InputDecoration(
                              labelText:
                                  AppLocalizations.of(context)!.loginPassword,
                              prefixIcon: const Icon(Icons.lock_outline_rounded,
                                  color: AppTheme.primary),
                              filled: true,
                              fillColor: AppTheme.background,
                              border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12)),
                            ),
                            obscureText: true,
                            onFieldSubmitted: (_) => _login(),
                            validator: (v) => (v == null || v.isEmpty)
                                ? AppLocalizations.of(context)!.loginRequired
                                : null,
                          ),
                          const SizedBox(height: 28),
                          FilledButton(
                            onPressed: _loading
                                ? null
                                : () {
                                    if (_formKey.currentState?.validate() ??
                                        false) _login();
                                  },
                            style: FilledButton.styleFrom(
                              backgroundColor: AppTheme.primary,
                              padding: const EdgeInsets.symmetric(vertical: 18),
                              shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14)),
                              elevation: 0,
                            ),
                            child: _loading
                                ? const SizedBox(
                                    height: 24,
                                    width: 24,
                                    child: CircularProgressIndicator(
                                        strokeWidth: 2, color: Colors.white),
                                  )
                                : Text(
                                    AppLocalizations.of(context)!.loginButton,
                                    style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                        fontSize: 16)),
                          ),
                          const SizedBox(height: 16),
                          Text(
                            AppLocalizations.of(context)!.loginAccountsByAdmin,
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 13,
                              color: Colors.grey.shade700,
                              height: 1.35,
                            ),
                          ),
                        ],
                      ),
                    ),
                      ],
                    ),
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
