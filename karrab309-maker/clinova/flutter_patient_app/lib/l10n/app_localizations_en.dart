// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appTitle => 'Clinova';

  @override
  String get appSubtitle => 'Pre and post-operative follow-up';

  @override
  String get loginUsername => 'Username';

  @override
  String get loginPassword => 'Password';

  @override
  String get loginButton => 'Sign in';

  @override
  String get loginRequired => 'Required';

  @override
  String get loginErrorApi =>
      'API unreachable. Start Laravel API:\ncd medical-api && php artisan serve';

  @override
  String get loginErrorServer => 'Server error.';

  @override
  String get followUp => 'Follow-up';

  @override
  String get tabPatientRecord => 'Record';

  @override
  String get tabFinance => 'Billing';

  @override
  String get tabAppointments => 'Visits';

  @override
  String get tabReports => 'Reports';

  @override
  String get tabMore => 'More';

  @override
  String get photos => 'Photos';

  @override
  String get analyses => 'Analyses';

  @override
  String get messages => 'Messages';

  @override
  String get profile => 'Profile';

  @override
  String get postOpFollowUp => 'Post-operative follow-up';

  @override
  String get logout => 'Logout';

  @override
  String get registerTitle => 'Create a patient account';

  @override
  String get registerSubtitle =>
      'Enter your details. Your doctor will then link your medical record.';

  @override
  String get registerName => 'Full name';

  @override
  String get registerEmail => 'Email';

  @override
  String get registerUsername => 'Login username';

  @override
  String get registerPassword => 'Password';

  @override
  String get registerPasswordConfirm => 'Confirm password';

  @override
  String get registerPasswordHint =>
      'At least 8 characters, with one letter and one number.';

  @override
  String get registerButton => 'Sign up';

  @override
  String get loginAccountsByAdmin =>
      'Accounts are created by the administrator. Contact them for your credentials.';

  @override
  String get loginToRegister => 'No account? Sign up';

  @override
  String get registerToLogin => 'Already have an account? Sign in';

  @override
  String get registerPasswordMismatch => 'Passwords do not match.';

  @override
  String get noPatientDossier =>
      'No patient record for this account. After sign-up, a doctor must link you from the web dashboard to enable follow-up.';

  @override
  String get dossierTitle => 'My record';

  @override
  String get dossierSubtitle =>
      'QR code and secure link to your summary dossier.';

  @override
  String get financeTitle => 'Billing and balance';

  @override
  String get financeSubtitle => 'Amounts, fee details and payment history.';

  @override
  String get appointmentsTitle => 'Appointments';

  @override
  String get appointmentsSubtitle => 'Procedures and visits on your file.';

  @override
  String get reportsTitle => 'Medical reports';

  @override
  String get reportsSubtitle => 'Reports and documents related to your care.';

  @override
  String get moreTabTitle => 'More services';

  @override
  String get moreTabSubtitle => 'Lab results, messages and profile.';

  @override
  String get moreAnalysesDesc => 'View lab PDF reports';

  @override
  String get moreMessagesDesc => 'Message your care team';

  @override
  String get moreProfileDesc => 'Account and profile photo';

  @override
  String get balanceTotalDue => 'Total due';

  @override
  String get balancePaid => 'Already paid';

  @override
  String get balanceRemaining => 'Remaining balance';

  @override
  String get feeDetailsTitle => 'Fee breakdown';

  @override
  String get billingNotesTitle => 'Billing notes';

  @override
  String get paymentHistoryTitle => 'Payment history';

  @override
  String get noPaymentsYet => 'No payments recorded yet.';

  @override
  String get receiptPdfTooltip => 'Download receipt PDF';

  @override
  String get identitySection => 'Identity';

  @override
  String get hospitalizationSection => 'Stay';

  @override
  String get doctorSection => 'Attending physician';

  @override
  String get medicalSection => 'Medical information';

  @override
  String get fieldFullName => 'Name';

  @override
  String get fieldAgeGender => 'Age / gender';

  @override
  String get fieldPhone => 'Phone';

  @override
  String get fieldAddress => 'Address';

  @override
  String get fieldAdmission => 'Admission';

  @override
  String get fieldDischarge => 'Discharge';

  @override
  String get labelDiagnosis => 'Diagnosis';

  @override
  String get labelTreatment => 'Prescribed treatment';

  @override
  String get labelIllness => 'Reason / progress';

  @override
  String get labelHistory => 'Medical history';

  @override
  String get labelObservations => 'Observations';

  @override
  String get labelPreOp => 'Pre-operative report';

  @override
  String get labelPostOp => 'Post-operative report';

  @override
  String get medicalSectionEmpty =>
      'No medical information has been entered yet.';

  @override
  String get noAppointments => 'No appointments recorded.';

  @override
  String get noReports => 'No reports yet.';

  @override
  String get errorRetryHint => 'Check your connection and try again.';

  @override
  String get actionRetry => 'Try again';

  @override
  String get errorGeneric => 'Something went wrong.';

  @override
  String doctorLine(String name) {
    return 'Physician: $name';
  }

  @override
  String get followUpSubtitle =>
      'Vitals recorded by nursing staff and history.';

  @override
  String get followUpEnterVitals => 'Enter your vitals';

  @override
  String get followUpTempLabel => 'Temperature (°C)';

  @override
  String get followUpTempHint => '37.0';

  @override
  String get followUpPainLabel => 'Pain (0–10)';

  @override
  String get followUpSave => 'Save';

  @override
  String get followUpSaving => 'Saving…';

  @override
  String get followUpInvalidTemp => 'Invalid temperature (30–45 °C).';

  @override
  String get followUpSavedOk => 'Readings saved.';

  @override
  String get followUpPainLow => 'Low';

  @override
  String get followUpPainModerate => 'Moderate';

  @override
  String get followUpPainSevere => 'Severe';

  @override
  String get followUpDoctorObs => 'Physician notes';

  @override
  String get followUpDoctorObsEmpty =>
      'No notes yet. Your readings are synced with the care team.';

  @override
  String get followUpLastReadings => 'Latest readings';

  @override
  String get followUpNoData => 'No vitals recorded yet.';

  @override
  String get followUpReadOnlyVitalsHint =>
      'Data entry (heart rate, temperature, blood glucose, blood pressure) is done by nursing staff. This screen is read-only.';

  @override
  String get followUpStatTemp => 'Temperature';

  @override
  String get followUpStatPain => 'Pain';

  @override
  String get followUpStatHeart => 'Heart rate';

  @override
  String get followUpStatGlucose => 'Blood glucose';

  @override
  String get followUpStatBp => 'Blood pressure';

  @override
  String get followUpStatTempShort => 'Temp.';

  @override
  String get followUpStatGlucoseShort => 'Glucose';

  @override
  String get followUpStatPainShort => 'Pain';

  @override
  String get followUpHistoryRecent => 'Recent history';

  @override
  String followUpLastUpdated(String when) {
    return 'Last updated: $when';
  }

  @override
  String get assistantTitle => 'Clinova Assistant';

  @override
  String get assistantTooltip => 'Assistant';

  @override
  String get assistantPlaceholder => 'Your question…';

  @override
  String get assistantSend => 'OK';

  @override
  String get assistantWelcome =>
      'Hello. I can help orient you about follow-up (pain, fever). For medical advice, contact your physician.';

  @override
  String get assistantNetworkError => 'Network error. Please try again later.';

  @override
  String get labTitle => 'Laboratory';

  @override
  String get labSubtitle => 'Request and track lab appointments.';

  @override
  String get labCalendarTitle => 'Calendar';

  @override
  String get labCalendarPrev => 'Previous month';

  @override
  String get labCalendarNext => 'Next month';

  @override
  String get labCalendarLegend => 'Lab appointment';

  @override
  String get labWhenLabel => 'When?';

  @override
  String get labWhenPick => 'Pick date and time';

  @override
  String get labNoteLabel => 'Note';

  @override
  String get labNotePlaceholder => 'e.g. fasting, pain, availability…';

  @override
  String get labSubmit => 'Send request';

  @override
  String get labListTitle => 'My requests';

  @override
  String get labPublicHint => 'Public link (web):';

  @override
  String get commonNoData => 'No data';

  @override
  String get commonCancel => 'Cancel';

  @override
  String get doctorAppointmentsTitle => 'Doctor appointments';

  @override
  String get openPublicLink => 'Open public link';
}
