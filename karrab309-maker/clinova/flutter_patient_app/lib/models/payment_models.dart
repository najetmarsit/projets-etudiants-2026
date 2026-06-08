/// Ligne de facture (détail des frais).
class BillingLineModel {
  final String label;
  final double amount;

  BillingLineModel({required this.label, required this.amount});

  factory BillingLineModel.fromJson(Map<String, dynamic> json) {
    final a = json['amount'];
    double parseAmt(dynamic x) {
      if (x == null) return 0;
      if (x is num) return x.toDouble();
      return double.tryParse(x.toString()) ?? 0;
    }

    return BillingLineModel(
      label: json['label'] as String? ?? '',
      amount: parseAmt(a),
    );
  }
}

/// Paiement enregistré (liste ou inclus dans le solde).
class PaymentModel {
  final int id;
  final double amount;
  final String currency;
  final String receiptNumber;
  final String? paidAt;
  final String? provider;

  PaymentModel({
    required this.id,
    required this.amount,
    required this.currency,
    required this.receiptNumber,
    this.paidAt,
    this.provider,
  });

  factory PaymentModel.fromJson(Map<String, dynamic> json) {
    final amt = json['amount'];
    double parseAmt(dynamic a) {
      if (a == null) return 0;
      if (a is num) return a.toDouble();
      return double.tryParse(a.toString()) ?? 0;
    }

    return PaymentModel(
      id: (json['id'] as num).toInt(),
      amount: parseAmt(amt),
      currency: json['currency'] as String? ?? 'TND',
      receiptNumber: json['receipt_number'] as String? ?? '',
      paidAt: json['paid_at'] as String?,
      provider: json['provider'] as String?,
    );
  }
}

/// Réponse GET /payments/balance.
class PaymentBalanceModel {
  final double totalDue;
  final double totalPaid;
  final double remaining;
  final String currency;
  final List<BillingLineModel> billingBreakdown;
  final String? billingNotes;
  final List<PaymentModel> recentPayments;

  PaymentBalanceModel({
    required this.totalDue,
    required this.totalPaid,
    required this.remaining,
    required this.currency,
    required this.billingBreakdown,
    this.billingNotes,
    required this.recentPayments,
  });

  factory PaymentBalanceModel.fromJson(Map<String, dynamic> json) {
    final breakdown = json['billing_breakdown'];
    final lines = <BillingLineModel>[];
    if (breakdown is List) {
      for (final e in breakdown) {
        if (e is Map<String, dynamic>) {
          lines.add(BillingLineModel.fromJson(e));
        }
      }
    }
    final pays = json['payments'];
    final plist = <PaymentModel>[];
    if (pays is List) {
      for (final e in pays) {
        if (e is Map<String, dynamic>) {
          plist.add(PaymentModel.fromJson(e));
        }
      }
    }
    final td = json['total_due'];
    final tp = json['total_paid'];
    final rem = json['remaining'];
    return PaymentBalanceModel(
      totalDue: td is num ? td.toDouble() : double.tryParse('$td') ?? 0,
      totalPaid: tp is num ? tp.toDouble() : double.tryParse('$tp') ?? 0,
      remaining: rem is num ? rem.toDouble() : double.tryParse('$rem') ?? 0,
      currency: json['currency'] as String? ?? 'TND',
      billingBreakdown: lines,
      billingNotes: json['billing_notes'] as String?,
      recentPayments: plist,
    );
  }
}
