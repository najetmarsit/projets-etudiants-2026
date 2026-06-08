import 'package:flutter/widgets.dart';
import 'package:intl/intl.dart';

/// Formats monétaires et dates selon la locale de l’interface.
abstract final class ClinovaFormatters {
  ClinovaFormatters._();

  static String _normalizeIsoish(String s) {
    // Laravel renvoie souvent `YYYY-MM-DD HH:mm:ss` (non ISO).
    // DateTime.parse attend un format ISO (`YYYY-MM-DDTHH:mm:ss`).
    if (s.contains(' ') && !s.contains('T')) {
      return s.replaceFirst(' ', 'T');
    }
    return s;
  }

  /// Affiche un montant avec 2 décimales et le code devise (ex. `1 234,56 TND`).
  static String money(BuildContext context, double value, String currencyCode) {
    final locale = Localizations.localeOf(context).toString();
    final nf = NumberFormat.decimalPattern(locale)
      ..minimumFractionDigits = 2
      ..maximumFractionDigits = 2;
    return '${nf.format(value)} $currencyCode'.trim();
  }

  /// Tente de formater une date/heure ISO en texte localisé ; sinon renvoie la chaîne brute.
  static String formatIsoDateTime(BuildContext context, String? iso) {
    if (iso == null || iso.isEmpty) return '—';
    try {
      final dt = DateTime.parse(_normalizeIsoish(iso)).toLocal();
      final locale = Localizations.localeOf(context).toString();
      return DateFormat.yMMMd(locale).add_Hm().format(dt);
    } catch (_) {
      return iso;
    }
  }

  /// Date courte (jour) pour sous-titres compacts.
  static String formatIsoDateShort(BuildContext context, String? iso) {
    if (iso == null || iso.isEmpty) return '—';
    try {
      final dt = DateTime.parse(_normalizeIsoish(iso)).toLocal();
      final locale = Localizations.localeOf(context).toString();
      return DateFormat.yMMMd(locale).format(dt);
    } catch (_) {
      return iso;
    }
  }

  static String formatDateTime(BuildContext context, DateTime dt) {
    final locale = Localizations.localeOf(context).toString();
    return DateFormat.yMMMd(locale).add_Hm().format(dt.toLocal());
  }

  static String formatMonthYear(BuildContext context, DateTime dt) {
    final locale = Localizations.localeOf(context).toString();
    return DateFormat.yMMMM(locale).format(DateTime(dt.year, dt.month, 1));
  }

  /// Retire un suffixe entre parenthèses (ex. translittération darija) laissé par erreur dans une traduction.
  static String vitalLabelWithoutParenSuffix(String label) {
    return label.replaceAll(RegExp(r'\s*\([^)]*\)\s*$'), '').trim();
  }

  /// En-têtes des jours (L M M J V S D) selon la locale.
  static List<String> weekdayShortHeaders(BuildContext context) {
    final locale = Localizations.localeOf(context).toString();
    // Commence par lundi (1) jusqu'à dimanche (7)
    final fmt = DateFormat.E(locale);
    final base = DateTime(2024, 1, 1); // lundi
    return List.generate(7, (i) => fmt.format(base.add(Duration(days: i))).substring(0, 1).toUpperCase());
  }
}
