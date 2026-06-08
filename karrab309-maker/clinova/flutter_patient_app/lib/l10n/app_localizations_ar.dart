// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Arabic (`ar`).
class AppLocalizationsAr extends AppLocalizations {
  AppLocalizationsAr([String locale = 'ar']) : super(locale);

  @override
  String get appTitle => 'Clinova';

  @override
  String get appSubtitle => 'المتابعة قبل وبعد العمليات';

  @override
  String get loginUsername => 'اسم المستخدم';

  @override
  String get loginPassword => 'كلمة المرور';

  @override
  String get loginButton => 'تسجيل الدخول';

  @override
  String get loginRequired => 'مطلوب';

  @override
  String get loginErrorApi =>
      'الـ API غير متاح. شغّل Laravel API:\ncd medical-api && php artisan serve';

  @override
  String get loginErrorServer => 'خطأ في الخادم.';

  @override
  String get followUp => 'المتابعة';

  @override
  String get tabPatientRecord => 'الملف';

  @override
  String get tabFinance => 'الفواتير';

  @override
  String get tabAppointments => 'المواعيد';

  @override
  String get tabReports => 'التقارير';

  @override
  String get tabMore => 'المزيد';

  @override
  String get photos => 'الصور';

  @override
  String get analyses => 'التحاليل';

  @override
  String get messages => 'الرسائل';

  @override
  String get profile => 'الملف الشخصي';

  @override
  String get postOpFollowUp => 'المتابعة بعد العملية';

  @override
  String get logout => 'تسجيل الخروج';

  @override
  String get registerTitle => 'إنشاء حساب مريض';

  @override
  String get registerSubtitle =>
      'أدخل بياناتك. سيربط الطبيب ملفك الطبي لاحقاً.';

  @override
  String get registerName => 'الاسم الكامل';

  @override
  String get registerEmail => 'البريد الإلكتروني';

  @override
  String get registerUsername => 'اسم المستخدم';

  @override
  String get registerPassword => 'كلمة المرور';

  @override
  String get registerPasswordConfirm => 'تأكيد كلمة المرور';

  @override
  String get registerPasswordHint => '8 أحرف على الأقل، مع حرف ورقم.';

  @override
  String get registerButton => 'تسجيل';

  @override
  String get loginAccountsByAdmin =>
      'يتم إنشاء الحسابات من قبل المسؤول. تواصل مع الإدارة للحصول على بيانات الدخول.';

  @override
  String get loginToRegister => 'لا حساب؟ سجّل';

  @override
  String get registerToLogin => 'لديك حساب؟ سجّل الدخول';

  @override
  String get registerPasswordMismatch => 'كلمتا المرور غير متطابقتين.';

  @override
  String get noPatientDossier =>
      'لا توجد سجلات مريض لهذا الحساب. بعد التسجيل، يجب أن يربطك الطبيب من لوحة الويب لتفعيل المتابعة.';

  @override
  String get dossierTitle => 'ملفي الطبي';

  @override
  String get dossierSubtitle => 'رمز QR ورابط آمن لملخص ملفك.';

  @override
  String get financeTitle => 'الفواتير والرصيد';

  @override
  String get financeSubtitle => 'المبالغ، تفاصيل الرسوم وسجل الدفعات.';

  @override
  String get appointmentsTitle => 'المواعيد';

  @override
  String get appointmentsSubtitle => 'التدخلات والمواعيد المرتبطة بملفك.';

  @override
  String get reportsTitle => 'التقارير الطبية';

  @override
  String get reportsSubtitle => 'تقارير ومستندات متعلقة بمتابعتك.';

  @override
  String get moreTabTitle => 'خدمات أخرى';

  @override
  String get moreTabSubtitle => 'تحاليل، رسائل وملف شخصي.';

  @override
  String get moreAnalysesDesc => 'عرض تحاليل PDF';

  @override
  String get moreMessagesDesc => 'التواصل مع الفريق الطبي';

  @override
  String get moreProfileDesc => 'الحساب والصورة الشخصية';

  @override
  String get balanceTotalDue => 'الإجمالي المستحق';

  @override
  String get balancePaid => 'المدفوع';

  @override
  String get balanceRemaining => 'المتبقي';

  @override
  String get feeDetailsTitle => 'تفاصيل الرسوم';

  @override
  String get billingNotesTitle => 'ملاحظات المحاسبة';

  @override
  String get paymentHistoryTitle => 'سجل الدفعات';

  @override
  String get noPaymentsYet => 'لا توجد دفعات مسجّلة حتى الآن.';

  @override
  String get receiptPdfTooltip => 'تنزيل إيصال PDF';

  @override
  String get identitySection => 'الهوية';

  @override
  String get hospitalizationSection => 'الإقامة';

  @override
  String get doctorSection => 'الطبيب المعالج';

  @override
  String get medicalSection => 'المعلومات الطبية';

  @override
  String get fieldFullName => 'الاسم';

  @override
  String get fieldAgeGender => 'العمر / الجنس';

  @override
  String get fieldPhone => 'الهاتف';

  @override
  String get fieldAddress => 'العنوان';

  @override
  String get fieldAdmission => 'الدخول';

  @override
  String get fieldDischarge => 'الخروج';

  @override
  String get labelDiagnosis => 'التشخيص';

  @override
  String get labelTreatment => 'العلاج الموصوف';

  @override
  String get labelIllness => 'السبب / التطور';

  @override
  String get labelHistory => 'السوابق';

  @override
  String get labelObservations => 'ملاحظات';

  @override
  String get labelPreOp => 'تقرير ما قبل العملية';

  @override
  String get labelPostOp => 'تقرير ما بعد العملية';

  @override
  String get medicalSectionEmpty => 'لا توجد معلومات طبية مسجّلة بعد.';

  @override
  String get noAppointments => 'لا توجد مواعيد مسجّلة.';

  @override
  String get noReports => 'لا توجد تقارير حالياً.';

  @override
  String get errorRetryHint => 'تحقق من الاتصال وحاول مرة أخرى.';

  @override
  String get actionRetry => 'إعادة المحاولة';

  @override
  String get errorGeneric => 'حدث خطأ ما.';

  @override
  String doctorLine(String name) {
    return 'الطبيب: $name';
  }

  @override
  String get followUpSubtitle => 'عرض المؤشرات المسجّلة من طرف الممرض والسجل.';

  @override
  String get followUpEnterVitals => 'إدخال المؤشرات';

  @override
  String get followUpTempLabel => 'درجة الحرارة (°م)';

  @override
  String get followUpTempHint => '37.0';

  @override
  String get followUpPainLabel => 'الألم (0–10)';

  @override
  String get followUpSave => 'حفظ';

  @override
  String get followUpSaving => 'جاري الحفظ…';

  @override
  String get followUpInvalidTemp => 'درجة حرارة غير صالحة (30–45 °م).';

  @override
  String get followUpSavedOk => 'تم حفظ المؤشرات.';

  @override
  String get followUpPainLow => 'خفيف';

  @override
  String get followUpPainModerate => 'متوسط';

  @override
  String get followUpPainSevere => 'شديد';

  @override
  String get followUpDoctorObs => 'ملاحظات الطبيب';

  @override
  String get followUpDoctorObsEmpty =>
      'لا توجد ملاحظات بعد. تُزامن قراءاتك مع الفريق.';

  @override
  String get followUpLastReadings => 'آخر القياسات';

  @override
  String get followUpNoData => 'لا توجد قياسات مسجّلة بعد.';

  @override
  String get followUpReadOnlyVitalsHint =>
      'إدخال المؤشرات (نبض، حرارة، سكر، ضغط) يقوم به الممرض. هذه الشاشة للعرض فقط.';

  @override
  String get followUpStatTemp => 'درجة الحرارة';

  @override
  String get followUpStatPain => 'الألم';

  @override
  String get followUpStatHeart => 'نبض القلب';

  @override
  String get followUpStatGlucose => 'السكر';

  @override
  String get followUpStatBp => 'الضغط';

  @override
  String get followUpStatTempShort => 'حرارة';

  @override
  String get followUpStatGlucoseShort => 'سكر';

  @override
  String get followUpStatPainShort => 'ألم';

  @override
  String get followUpHistoryRecent => 'سجل حديث';

  @override
  String followUpLastUpdated(String when) {
    return 'آخر تحديث: $when';
  }

  @override
  String get assistantTitle => 'مساعد Clinova';

  @override
  String get assistantTooltip => 'مساعد';

  @override
  String get assistantPlaceholder => 'سؤالك…';

  @override
  String get assistantSend => 'موافق';

  @override
  String get assistantWelcome =>
      'مرحباً. يمكنني إرشادك بخصوص المتابعة (الألم، الحمى). للاستشارة الطبية، تواصل مع طبيبك.';

  @override
  String get assistantNetworkError => 'خطأ في الشبكة. حاول لاحقاً.';

  @override
  String get labTitle => 'المختبر';

  @override
  String get labSubtitle => 'طلب ومتابعة مواعيد المختبر.';

  @override
  String get labCalendarTitle => 'التقويم';

  @override
  String get labCalendarPrev => 'الشهر السابق';

  @override
  String get labCalendarNext => 'الشهر التالي';

  @override
  String get labCalendarLegend => 'موعد مختبر';

  @override
  String get labWhenLabel => 'متى؟';

  @override
  String get labWhenPick => 'اختر التاريخ والوقت';

  @override
  String get labNoteLabel => 'ملاحظة';

  @override
  String get labNotePlaceholder => 'مثال: صائم، ألم، توفر…';

  @override
  String get labSubmit => 'إرسال الطلب';

  @override
  String get labListTitle => 'طلباتي';

  @override
  String get labPublicHint => 'رابط عام (ويب):';

  @override
  String get commonNoData => 'لا بيانات';

  @override
  String get commonCancel => 'إلغاء';

  @override
  String get doctorAppointmentsTitle => 'مواعيد الطبيب';

  @override
  String get openPublicLink => 'فتح الرابط العام';
}
