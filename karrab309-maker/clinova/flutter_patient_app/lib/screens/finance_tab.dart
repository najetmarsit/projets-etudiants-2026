import 'package:flutter/material.dart';
import 'package:flutter_patient_app/l10n/app_localizations.dart';
import '../services/api_service.dart';
import '../models/payment_models.dart';
import '../theme/app_theme.dart';
import '../utils/clinova_formatters.dart';
import '../utils/error_message.dart';
import '../widgets/clinova_ui.dart';
import '../widgets/skeleton_widgets.dart';
import '../config/app_assets.dart';

/// Solde, lignes de facturation (frais) et historique des paiements / reçus.
class FinanceTab extends StatefulWidget {
  final int patientId;

  const FinanceTab({super.key, required this.patientId});

  @override
  State<FinanceTab> createState() => _FinanceTabState();
}

class _FinanceTabState extends State<FinanceTab> {
  PaymentBalanceModel? _balance;
  List<PaymentModel> _allPayments = [];
  bool _loading = true;
  String? _error;
  int? _openingId;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final bal = await ApiService.getPaymentBalance();
      if (mounted) {
        setState(() {
          _balance = bal;
          _allPayments = bal.recentPayments;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = userFacingError(e);
          _loading = false;
        });
      }
    }
  }

  Future<void> _openReceipt(PaymentModel p) async {
    setState(() => _openingId = p.id);
    try {
      await ApiService.openPaymentReceiptPdf(p.id, p.receiptNumber);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(userFacingError(e)), behavior: SnackBarBehavior.floating),
        );
      }
    } finally {
      if (mounted) setState(() => _openingId = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = AppLocalizations.of(context)!;
    return RefreshIndicator(
      color: AppTheme.primary,
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        children: [
          ClinovaPageHeader(
            title: l.financeTitle,
            subtitle: l.financeSubtitle,
            icon: Icons.receipt_long_rounded,
            trailing: ClinovaHeaderThumb(
              assetPath: AppAssets.illustrationSmall,
              semanticLabel: l.financeTitle,
            ),
          ),
          const SizedBox(height: 18),
          if (_loading)
            const SkeletonList(itemCount: 4)
          else if (_error != null)
            ClinovaModernCard(
              child: Column(
                children: [
                  Text(_error!, textAlign: TextAlign.center, style: TextStyle(color: Colors.red.shade800, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 12),
                  Text(l.errorRetryHint, textAlign: TextAlign.center, style: TextStyle(color: AppTheme.textMuted, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 16),
                  FilledButton(onPressed: _load, child: Text(l.actionRetry)),
                ],
              ),
            )
          else if (_balance != null)
            ..._buildBody(context, l, _balance!),
        ],
      ),
    );
  }

  List<Widget> _buildBody(BuildContext context, AppLocalizations l, PaymentBalanceModel b) {
    final currency = b.currency;
    return [
      ClinovaModernCard(
        padding: EdgeInsets.zero,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const ClinovaCardTopMedia(
              assetPath: AppAssets.illustrationDocuments,
              height: 68,
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 14, 12, 12),
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            color: AppTheme.background.withValues(alpha: 0.9),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppTheme.border.withValues(alpha: 0.75)),
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(l.balanceTotalDue, style: TextStyle(color: AppTheme.textMuted, fontSize: 12)),
                                const SizedBox(height: 4),
                                Text(
                                  ClinovaFormatters.money(context, b.totalDue, currency),
                                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            color: AppTheme.background.withValues(alpha: 0.9),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppTheme.border.withValues(alpha: 0.75)),
                          ),
                          child: Padding(
                            padding: const EdgeInsets.all(12),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(l.balancePaid, style: TextStyle(color: AppTheme.textMuted, fontSize: 12)),
                                const SizedBox(height: 4),
                                Text(
                                  ClinovaFormatters.money(context, b.totalPaid, currency),
                                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: Colors.green.shade700),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  DecoratedBox(
                    decoration: BoxDecoration(
                      color: AppTheme.primaryLight.withValues(alpha: 0.45),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: AppTheme.primary.withValues(alpha: 0.2)),
                    ),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(l.balanceRemaining, style: const TextStyle(fontWeight: FontWeight.w800)),
                          Text(
                            ClinovaFormatters.money(context, b.remaining, currency),
                            style: TextStyle(fontWeight: FontWeight.w900, fontSize: 17, color: AppTheme.primaryDark),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      if (b.billingBreakdown.isNotEmpty) ...[
        const SizedBox(height: 18),
        Text(
          l.feeDetailsTitle,
          style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800),
        ),
        const SizedBox(height: 10),
        ClinovaModernCard(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
          child: Column(
            children: [
              for (var i = 0; i < b.billingBreakdown.length; i++) ...[
                if (i > 0) Divider(height: 1, color: AppTheme.border.withValues(alpha: 0.9)),
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 10),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          b.billingBreakdown[i].label,
                          style: const TextStyle(height: 1.3),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        ClinovaFormatters.money(context, b.billingBreakdown[i].amount, currency),
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
      if (b.billingNotes != null && b.billingNotes!.trim().isNotEmpty) ...[
        const SizedBox(height: 12),
        ClinovaModernCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(l.billingNotesTitle, style: TextStyle(color: AppTheme.textMuted, fontSize: 12, fontWeight: FontWeight.w600)),
              const SizedBox(height: 8),
              Text(b.billingNotes!, style: const TextStyle(height: 1.4)),
            ],
          ),
        ),
      ],
      const SizedBox(height: 22),
      Text(
        l.paymentHistoryTitle,
        style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w800),
      ),
      const SizedBox(height: 10),
      if (_allPayments.isEmpty)
        ClinovaModernCard(
          child: ClinovaEmptyState(
            title: 'Aucun paiement',
            text: l.noPaymentsYet,
            icon: Icons.payments_outlined,
            illustrationAsset: AppAssets.illustrationDocuments,
            showIconBadge: false,
          ),
        )
      else
        ..._allPayments.map(
          (p) => Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: ClinovaModernCard(
              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
              child: ListTile(
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.green.shade50,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(Icons.payments_rounded, color: Colors.green.shade700, size: 26),
                ),
                title: Text(
                  ClinovaFormatters.money(context, p.amount, p.currency),
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
                subtitle: Text(
                  [
                    p.receiptNumber,
                    if (p.paidAt != null) ClinovaFormatters.formatIsoDateTime(context, p.paidAt),
                    if (p.provider != null && p.provider!.isNotEmpty) p.provider,
                  ].join(' · '),
                  style: TextStyle(color: AppTheme.textMuted, fontSize: 11, height: 1.25),
                ),
                isThreeLine: false,
                trailing: _openingId == p.id
                    ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(strokeWidth: 2))
                    : IconButton(
                        icon: const Icon(Icons.picture_as_pdf_outlined),
                        tooltip: l.receiptPdfTooltip,
                        onPressed: () => _openReceipt(p),
                      ),
              ),
            ),
          ),
        ),
    ];
  }
}
