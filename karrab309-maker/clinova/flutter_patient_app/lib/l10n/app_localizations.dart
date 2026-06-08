import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_ar.dart';
import 'app_localizations_en.dart';
import 'app_localizations_fr.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'l10n/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
      : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations? of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
    delegate,
    GlobalMaterialLocalizations.delegate,
    GlobalCupertinoLocalizations.delegate,
    GlobalWidgetsLocalizations.delegate,
  ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('ar'),
    Locale('en'),
    Locale('fr')
  ];

  /// No description provided for @appTitle.
  ///
  /// In en, this message translates to:
  /// **'Clinova'**
  String get appTitle;

  /// No description provided for @appSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Pre and post-operative follow-up'**
  String get appSubtitle;

  /// No description provided for @loginUsername.
  ///
  /// In en, this message translates to:
  /// **'Username'**
  String get loginUsername;

  /// No description provided for @loginPassword.
  ///
  /// In en, this message translates to:
  /// **'Password'**
  String get loginPassword;

  /// No description provided for @loginButton.
  ///
  /// In en, this message translates to:
  /// **'Sign in'**
  String get loginButton;

  /// No description provided for @loginRequired.
  ///
  /// In en, this message translates to:
  /// **'Required'**
  String get loginRequired;

  /// No description provided for @loginErrorApi.
  ///
  /// In en, this message translates to:
  /// **'API unreachable. Start Laravel API:\ncd medical-api && php artisan serve'**
  String get loginErrorApi;

  /// No description provided for @loginErrorServer.
  ///
  /// In en, this message translates to:
  /// **'Server error.'**
  String get loginErrorServer;

  /// No description provided for @followUp.
  ///
  /// In en, this message translates to:
  /// **'Follow-up'**
  String get followUp;

  /// No description provided for @tabPatientRecord.
  ///
  /// In en, this message translates to:
  /// **'Record'**
  String get tabPatientRecord;

  /// No description provided for @tabFinance.
  ///
  /// In en, this message translates to:
  /// **'Billing'**
  String get tabFinance;

  /// No description provided for @tabAppointments.
  ///
  /// In en, this message translates to:
  /// **'Visits'**
  String get tabAppointments;

  /// No description provided for @tabReports.
  ///
  /// In en, this message translates to:
  /// **'Reports'**
  String get tabReports;

  /// No description provided for @tabMore.
  ///
  /// In en, this message translates to:
  /// **'More'**
  String get tabMore;

  /// No description provided for @photos.
  ///
  /// In en, this message translates to:
  /// **'Photos'**
  String get photos;

  /// No description provided for @analyses.
  ///
  /// In en, this message translates to:
  /// **'Analyses'**
  String get analyses;

  /// No description provided for @messages.
  ///
  /// In en, this message translates to:
  /// **'Messages'**
  String get messages;

  /// No description provided for @profile.
  ///
  /// In en, this message translates to:
  /// **'Profile'**
  String get profile;

  /// No description provided for @postOpFollowUp.
  ///
  /// In en, this message translates to:
  /// **'Post-operative follow-up'**
  String get postOpFollowUp;

  /// No description provided for @logout.
  ///
  /// In en, this message translates to:
  /// **'Logout'**
  String get logout;

  /// No description provided for @registerTitle.
  ///
  /// In en, this message translates to:
  /// **'Create a patient account'**
  String get registerTitle;

  /// No description provided for @registerSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Enter your details. Your doctor will then link your medical record.'**
  String get registerSubtitle;

  /// No description provided for @registerName.
  ///
  /// In en, this message translates to:
  /// **'Full name'**
  String get registerName;

  /// No description provided for @registerEmail.
  ///
  /// In en, this message translates to:
  /// **'Email'**
  String get registerEmail;

  /// No description provided for @registerUsername.
  ///
  /// In en, this message translates to:
  /// **'Login username'**
  String get registerUsername;

  /// No description provided for @registerPassword.
  ///
  /// In en, this message translates to:
  /// **'Password'**
  String get registerPassword;

  /// No description provided for @registerPasswordConfirm.
  ///
  /// In en, this message translates to:
  /// **'Confirm password'**
  String get registerPasswordConfirm;

  /// No description provided for @registerPasswordHint.
  ///
  /// In en, this message translates to:
  /// **'At least 8 characters, with one letter and one number.'**
  String get registerPasswordHint;

  /// No description provided for @registerButton.
  ///
  /// In en, this message translates to:
  /// **'Sign up'**
  String get registerButton;

  /// No description provided for @loginAccountsByAdmin.
  ///
  /// In en, this message translates to:
  /// **'Accounts are created by the administrator. Contact them for your credentials.'**
  String get loginAccountsByAdmin;

  /// No description provided for @loginToRegister.
  ///
  /// In en, this message translates to:
  /// **'No account? Sign up'**
  String get loginToRegister;

  /// No description provided for @registerToLogin.
  ///
  /// In en, this message translates to:
  /// **'Already have an account? Sign in'**
  String get registerToLogin;

  /// No description provided for @registerPasswordMismatch.
  ///
  /// In en, this message translates to:
  /// **'Passwords do not match.'**
  String get registerPasswordMismatch;

  /// No description provided for @noPatientDossier.
  ///
  /// In en, this message translates to:
  /// **'No patient record for this account. After sign-up, a doctor must link you from the web dashboard to enable follow-up.'**
  String get noPatientDossier;

  /// No description provided for @dossierTitle.
  ///
  /// In en, this message translates to:
  /// **'My record'**
  String get dossierTitle;

  /// No description provided for @dossierSubtitle.
  ///
  /// In en, this message translates to:
  /// **'QR code and secure link to your summary dossier.'**
  String get dossierSubtitle;

  /// No description provided for @financeTitle.
  ///
  /// In en, this message translates to:
  /// **'Billing and balance'**
  String get financeTitle;

  /// No description provided for @financeSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Amounts, fee details and payment history.'**
  String get financeSubtitle;

  /// No description provided for @appointmentsTitle.
  ///
  /// In en, this message translates to:
  /// **'Appointments'**
  String get appointmentsTitle;

  /// No description provided for @appointmentsSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Procedures and visits on your file.'**
  String get appointmentsSubtitle;

  /// No description provided for @reportsTitle.
  ///
  /// In en, this message translates to:
  /// **'Medical reports'**
  String get reportsTitle;

  /// No description provided for @reportsSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Reports and documents related to your care.'**
  String get reportsSubtitle;

  /// No description provided for @moreTabTitle.
  ///
  /// In en, this message translates to:
  /// **'More services'**
  String get moreTabTitle;

  /// No description provided for @moreTabSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Lab results, messages and profile.'**
  String get moreTabSubtitle;

  /// No description provided for @moreAnalysesDesc.
  ///
  /// In en, this message translates to:
  /// **'View lab PDF reports'**
  String get moreAnalysesDesc;

  /// No description provided for @moreMessagesDesc.
  ///
  /// In en, this message translates to:
  /// **'Message your care team'**
  String get moreMessagesDesc;

  /// No description provided for @moreProfileDesc.
  ///
  /// In en, this message translates to:
  /// **'Account and profile photo'**
  String get moreProfileDesc;

  /// No description provided for @balanceTotalDue.
  ///
  /// In en, this message translates to:
  /// **'Total due'**
  String get balanceTotalDue;

  /// No description provided for @balancePaid.
  ///
  /// In en, this message translates to:
  /// **'Already paid'**
  String get balancePaid;

  /// No description provided for @balanceRemaining.
  ///
  /// In en, this message translates to:
  /// **'Remaining balance'**
  String get balanceRemaining;

  /// No description provided for @feeDetailsTitle.
  ///
  /// In en, this message translates to:
  /// **'Fee breakdown'**
  String get feeDetailsTitle;

  /// No description provided for @billingNotesTitle.
  ///
  /// In en, this message translates to:
  /// **'Billing notes'**
  String get billingNotesTitle;

  /// No description provided for @paymentHistoryTitle.
  ///
  /// In en, this message translates to:
  /// **'Payment history'**
  String get paymentHistoryTitle;

  /// No description provided for @noPaymentsYet.
  ///
  /// In en, this message translates to:
  /// **'No payments recorded yet.'**
  String get noPaymentsYet;

  /// No description provided for @receiptPdfTooltip.
  ///
  /// In en, this message translates to:
  /// **'Download receipt PDF'**
  String get receiptPdfTooltip;

  /// No description provided for @identitySection.
  ///
  /// In en, this message translates to:
  /// **'Identity'**
  String get identitySection;

  /// No description provided for @hospitalizationSection.
  ///
  /// In en, this message translates to:
  /// **'Stay'**
  String get hospitalizationSection;

  /// No description provided for @doctorSection.
  ///
  /// In en, this message translates to:
  /// **'Attending physician'**
  String get doctorSection;

  /// No description provided for @medicalSection.
  ///
  /// In en, this message translates to:
  /// **'Medical information'**
  String get medicalSection;

  /// No description provided for @fieldFullName.
  ///
  /// In en, this message translates to:
  /// **'Name'**
  String get fieldFullName;

  /// No description provided for @fieldAgeGender.
  ///
  /// In en, this message translates to:
  /// **'Age / gender'**
  String get fieldAgeGender;

  /// No description provided for @fieldPhone.
  ///
  /// In en, this message translates to:
  /// **'Phone'**
  String get fieldPhone;

  /// No description provided for @fieldAddress.
  ///
  /// In en, this message translates to:
  /// **'Address'**
  String get fieldAddress;

  /// No description provided for @fieldAdmission.
  ///
  /// In en, this message translates to:
  /// **'Admission'**
  String get fieldAdmission;

  /// No description provided for @fieldDischarge.
  ///
  /// In en, this message translates to:
  /// **'Discharge'**
  String get fieldDischarge;

  /// No description provided for @labelDiagnosis.
  ///
  /// In en, this message translates to:
  /// **'Diagnosis'**
  String get labelDiagnosis;

  /// No description provided for @labelTreatment.
  ///
  /// In en, this message translates to:
  /// **'Prescribed treatment'**
  String get labelTreatment;

  /// No description provided for @labelIllness.
  ///
  /// In en, this message translates to:
  /// **'Reason / progress'**
  String get labelIllness;

  /// No description provided for @labelHistory.
  ///
  /// In en, this message translates to:
  /// **'Medical history'**
  String get labelHistory;

  /// No description provided for @labelObservations.
  ///
  /// In en, this message translates to:
  /// **'Observations'**
  String get labelObservations;

  /// No description provided for @labelPreOp.
  ///
  /// In en, this message translates to:
  /// **'Pre-operative report'**
  String get labelPreOp;

  /// No description provided for @labelPostOp.
  ///
  /// In en, this message translates to:
  /// **'Post-operative report'**
  String get labelPostOp;

  /// No description provided for @medicalSectionEmpty.
  ///
  /// In en, this message translates to:
  /// **'No medical information has been entered yet.'**
  String get medicalSectionEmpty;

  /// No description provided for @noAppointments.
  ///
  /// In en, this message translates to:
  /// **'No appointments recorded.'**
  String get noAppointments;

  /// No description provided for @noReports.
  ///
  /// In en, this message translates to:
  /// **'No reports yet.'**
  String get noReports;

  /// No description provided for @errorRetryHint.
  ///
  /// In en, this message translates to:
  /// **'Check your connection and try again.'**
  String get errorRetryHint;

  /// No description provided for @actionRetry.
  ///
  /// In en, this message translates to:
  /// **'Try again'**
  String get actionRetry;

  /// No description provided for @errorGeneric.
  ///
  /// In en, this message translates to:
  /// **'Something went wrong.'**
  String get errorGeneric;

  /// No description provided for @doctorLine.
  ///
  /// In en, this message translates to:
  /// **'Physician: {name}'**
  String doctorLine(String name);

  /// No description provided for @followUpSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Vitals recorded by nursing staff and history.'**
  String get followUpSubtitle;

  /// No description provided for @followUpEnterVitals.
  ///
  /// In en, this message translates to:
  /// **'Enter your vitals'**
  String get followUpEnterVitals;

  /// No description provided for @followUpTempLabel.
  ///
  /// In en, this message translates to:
  /// **'Temperature (°C)'**
  String get followUpTempLabel;

  /// No description provided for @followUpTempHint.
  ///
  /// In en, this message translates to:
  /// **'37.0'**
  String get followUpTempHint;

  /// No description provided for @followUpPainLabel.
  ///
  /// In en, this message translates to:
  /// **'Pain (0–10)'**
  String get followUpPainLabel;

  /// No description provided for @followUpSave.
  ///
  /// In en, this message translates to:
  /// **'Save'**
  String get followUpSave;

  /// No description provided for @followUpSaving.
  ///
  /// In en, this message translates to:
  /// **'Saving…'**
  String get followUpSaving;

  /// No description provided for @followUpInvalidTemp.
  ///
  /// In en, this message translates to:
  /// **'Invalid temperature (30–45 °C).'**
  String get followUpInvalidTemp;

  /// No description provided for @followUpSavedOk.
  ///
  /// In en, this message translates to:
  /// **'Readings saved.'**
  String get followUpSavedOk;

  /// No description provided for @followUpPainLow.
  ///
  /// In en, this message translates to:
  /// **'Low'**
  String get followUpPainLow;

  /// No description provided for @followUpPainModerate.
  ///
  /// In en, this message translates to:
  /// **'Moderate'**
  String get followUpPainModerate;

  /// No description provided for @followUpPainSevere.
  ///
  /// In en, this message translates to:
  /// **'Severe'**
  String get followUpPainSevere;

  /// No description provided for @followUpDoctorObs.
  ///
  /// In en, this message translates to:
  /// **'Physician notes'**
  String get followUpDoctorObs;

  /// No description provided for @followUpDoctorObsEmpty.
  ///
  /// In en, this message translates to:
  /// **'No notes yet. Your readings are synced with the care team.'**
  String get followUpDoctorObsEmpty;

  /// No description provided for @followUpLastReadings.
  ///
  /// In en, this message translates to:
  /// **'Latest readings'**
  String get followUpLastReadings;

  /// No description provided for @followUpNoData.
  ///
  /// In en, this message translates to:
  /// **'No vitals recorded yet.'**
  String get followUpNoData;

  /// No description provided for @followUpReadOnlyVitalsHint.
  ///
  /// In en, this message translates to:
  /// **'Data entry (heart rate, temperature, blood glucose, blood pressure) is done by nursing staff. This screen is read-only.'**
  String get followUpReadOnlyVitalsHint;

  /// No description provided for @followUpStatTemp.
  ///
  /// In en, this message translates to:
  /// **'Temperature'**
  String get followUpStatTemp;

  /// No description provided for @followUpStatPain.
  ///
  /// In en, this message translates to:
  /// **'Pain'**
  String get followUpStatPain;

  /// No description provided for @followUpStatHeart.
  ///
  /// In en, this message translates to:
  /// **'Heart rate'**
  String get followUpStatHeart;

  /// No description provided for @followUpStatGlucose.
  ///
  /// In en, this message translates to:
  /// **'Blood glucose'**
  String get followUpStatGlucose;

  /// No description provided for @followUpStatBp.
  ///
  /// In en, this message translates to:
  /// **'Blood pressure'**
  String get followUpStatBp;

  /// No description provided for @followUpStatTempShort.
  ///
  /// In en, this message translates to:
  /// **'Temp.'**
  String get followUpStatTempShort;

  /// No description provided for @followUpStatGlucoseShort.
  ///
  /// In en, this message translates to:
  /// **'Glucose'**
  String get followUpStatGlucoseShort;

  /// No description provided for @followUpStatPainShort.
  ///
  /// In en, this message translates to:
  /// **'Pain'**
  String get followUpStatPainShort;

  /// No description provided for @followUpHistoryRecent.
  ///
  /// In en, this message translates to:
  /// **'Recent history'**
  String get followUpHistoryRecent;

  /// No description provided for @followUpLastUpdated.
  ///
  /// In en, this message translates to:
  /// **'Last updated: {when}'**
  String followUpLastUpdated(String when);

  /// No description provided for @assistantTitle.
  ///
  /// In en, this message translates to:
  /// **'Clinova Assistant'**
  String get assistantTitle;

  /// No description provided for @assistantTooltip.
  ///
  /// In en, this message translates to:
  /// **'Assistant'**
  String get assistantTooltip;

  /// No description provided for @assistantPlaceholder.
  ///
  /// In en, this message translates to:
  /// **'Your question…'**
  String get assistantPlaceholder;

  /// No description provided for @assistantSend.
  ///
  /// In en, this message translates to:
  /// **'OK'**
  String get assistantSend;

  /// No description provided for @assistantWelcome.
  ///
  /// In en, this message translates to:
  /// **'Hello. I can help orient you about follow-up (pain, fever). For medical advice, contact your physician.'**
  String get assistantWelcome;

  /// No description provided for @assistantNetworkError.
  ///
  /// In en, this message translates to:
  /// **'Network error. Please try again later.'**
  String get assistantNetworkError;

  /// No description provided for @labTitle.
  ///
  /// In en, this message translates to:
  /// **'Laboratory'**
  String get labTitle;

  /// No description provided for @labSubtitle.
  ///
  /// In en, this message translates to:
  /// **'Request and track lab appointments.'**
  String get labSubtitle;

  /// No description provided for @labCalendarTitle.
  ///
  /// In en, this message translates to:
  /// **'Calendar'**
  String get labCalendarTitle;

  /// No description provided for @labCalendarPrev.
  ///
  /// In en, this message translates to:
  /// **'Previous month'**
  String get labCalendarPrev;

  /// No description provided for @labCalendarNext.
  ///
  /// In en, this message translates to:
  /// **'Next month'**
  String get labCalendarNext;

  /// No description provided for @labCalendarLegend.
  ///
  /// In en, this message translates to:
  /// **'Lab appointment'**
  String get labCalendarLegend;

  /// No description provided for @labWhenLabel.
  ///
  /// In en, this message translates to:
  /// **'When?'**
  String get labWhenLabel;

  /// No description provided for @labWhenPick.
  ///
  /// In en, this message translates to:
  /// **'Pick date and time'**
  String get labWhenPick;

  /// No description provided for @labNoteLabel.
  ///
  /// In en, this message translates to:
  /// **'Note'**
  String get labNoteLabel;

  /// No description provided for @labNotePlaceholder.
  ///
  /// In en, this message translates to:
  /// **'e.g. fasting, pain, availability…'**
  String get labNotePlaceholder;

  /// No description provided for @labSubmit.
  ///
  /// In en, this message translates to:
  /// **'Send request'**
  String get labSubmit;

  /// No description provided for @labListTitle.
  ///
  /// In en, this message translates to:
  /// **'My requests'**
  String get labListTitle;

  /// No description provided for @labPublicHint.
  ///
  /// In en, this message translates to:
  /// **'Public link (web):'**
  String get labPublicHint;

  /// No description provided for @commonNoData.
  ///
  /// In en, this message translates to:
  /// **'No data'**
  String get commonNoData;

  /// No description provided for @commonCancel.
  ///
  /// In en, this message translates to:
  /// **'Cancel'**
  String get commonCancel;

  /// No description provided for @doctorAppointmentsTitle.
  ///
  /// In en, this message translates to:
  /// **'Doctor appointments'**
  String get doctorAppointmentsTitle;

  /// No description provided for @openPublicLink.
  ///
  /// In en, this message translates to:
  /// **'Open public link'**
  String get openPublicLink;
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['ar', 'en', 'fr'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'ar':
      return AppLocalizationsAr();
    case 'en':
      return AppLocalizationsEn();
    case 'fr':
      return AppLocalizationsFr();
  }

  throw FlutterError(
      'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
      'an issue with the localizations generation tool. Please file an issue '
      'on GitHub with a reproducible sample app and the gen-l10n configuration '
      'that was used.');
}
