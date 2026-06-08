// ignore: unused_import
import 'package:intl/intl.dart' as intl;
import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for French (`fr`).
class AppLocalizationsFr extends AppLocalizations {
  AppLocalizationsFr([String locale = 'fr']) : super(locale);

  @override
  String get appTitle => 'Clinova';

  @override
  String get appSubtitle => 'Suivi pré et post opératoire';

  @override
  String get loginUsername => 'Identifiant';

  @override
  String get loginPassword => 'Mot de passe';

  @override
  String get loginButton => 'Se connecter';

  @override
  String get loginRequired => 'Requis';

  @override
  String get loginErrorApi =>
      'API injoignable. Démarrez l\'API Laravel :\ncd medical-api && php artisan serve';

  @override
  String get loginErrorServer => 'Erreur serveur.';

  @override
  String get followUp => 'Suivi';

  @override
  String get tabPatientRecord => 'Dossier';

  @override
  String get tabFinance => 'Factures';

  @override
  String get tabAppointments => 'RDV';

  @override
  String get tabReports => 'Rapports';

  @override
  String get tabMore => 'Plus';

  @override
  String get photos => 'Photos';

  @override
  String get analyses => 'Analyses';

  @override
  String get messages => 'Messages';

  @override
  String get profile => 'Profil';

  @override
  String get postOpFollowUp => 'Suivi post-opératoire';

  @override
  String get logout => 'Déconnexion';

  @override
  String get registerTitle => 'Créer un compte patient';

  @override
  String get registerSubtitle =>
      'Renseignez vos informations. Votre médecin associera ensuite votre dossier médical.';

  @override
  String get registerName => 'Nom complet';

  @override
  String get registerEmail => 'E-mail';

  @override
  String get registerUsername => 'Identifiant de connexion';

  @override
  String get registerPassword => 'Mot de passe';

  @override
  String get registerPasswordConfirm => 'Confirmer le mot de passe';

  @override
  String get registerPasswordHint =>
      'Au moins 8 caractères, avec une lettre et un chiffre.';

  @override
  String get registerButton => 'S’inscrire';

  @override
  String get loginAccountsByAdmin =>
      'Les comptes sont créés par l’administrateur. Contactez l’administration pour obtenir vos identifiants.';

  @override
  String get loginToRegister => 'Pas encore de compte ? S’inscrire';

  @override
  String get registerToLogin => 'Déjà un compte ? Se connecter';

  @override
  String get registerPasswordMismatch =>
      'Les mots de passe ne correspondent pas.';

  @override
  String get noPatientDossier =>
      'Aucune fiche patient pour ce compte. Après inscription, un médecin doit vous associer depuis le tableau de bord web pour activer le suivi.';

  @override
  String get dossierTitle => 'Mon dossier';

  @override
  String get dossierSubtitle =>
      'QR et lien sécurisé vers la synthèse de votre dossier.';

  @override
  String get financeTitle => 'Facturation et solde';

  @override
  String get financeSubtitle =>
      'Montants, détail des frais et historique des paiements.';

  @override
  String get appointmentsTitle => 'Rendez-vous';

  @override
  String get appointmentsSubtitle =>
      'Interventions et rendez-vous liés à votre dossier.';

  @override
  String get reportsTitle => 'Rapports médicaux';

  @override
  String get reportsSubtitle =>
      'Comptes rendus et documents liés à votre suivi.';

  @override
  String get moreTabTitle => 'Autres services';

  @override
  String get moreTabSubtitle =>
      'Analyses de laboratoire, messagerie et profil.';

  @override
  String get moreAnalysesDesc => 'Consulter les PDF d’analyses';

  @override
  String get moreMessagesDesc => 'Échanger avec l’équipe soignante';

  @override
  String get moreProfileDesc => 'Compte utilisateur et photo de profil';

  @override
  String get balanceTotalDue => 'Total dû';

  @override
  String get balancePaid => 'Déjà payé';

  @override
  String get balanceRemaining => 'Reste à payer';

  @override
  String get feeDetailsTitle => 'Détail des frais';

  @override
  String get billingNotesTitle => 'Notes caisse';

  @override
  String get paymentHistoryTitle => 'Historique des paiements';

  @override
  String get noPaymentsYet => 'Aucun paiement enregistré pour le moment.';

  @override
  String get receiptPdfTooltip => 'Télécharger le reçu PDF';

  @override
  String get identitySection => 'Identité';

  @override
  String get hospitalizationSection => 'Hospitalisation';

  @override
  String get doctorSection => 'Médecin traitant';

  @override
  String get medicalSection => 'Informations médicales';

  @override
  String get fieldFullName => 'Nom';

  @override
  String get fieldAgeGender => 'Âge / genre';

  @override
  String get fieldPhone => 'Téléphone';

  @override
  String get fieldAddress => 'Adresse';

  @override
  String get fieldAdmission => 'Entrée';

  @override
  String get fieldDischarge => 'Sortie';

  @override
  String get labelDiagnosis => 'Diagnostic';

  @override
  String get labelTreatment => 'Traitement prescrit';

  @override
  String get labelIllness => 'Motif / évolution';

  @override
  String get labelHistory => 'Antécédents';

  @override
  String get labelObservations => 'Observations';

  @override
  String get labelPreOp => 'Compte rendu pré-opératoire';

  @override
  String get labelPostOp => 'Compte rendu post-opératoire';

  @override
  String get medicalSectionEmpty =>
      'Aucune information médicale renseignée pour le moment.';

  @override
  String get noAppointments => 'Aucun rendez-vous enregistré.';

  @override
  String get noReports => 'Aucun rapport pour le moment.';

  @override
  String get errorRetryHint => 'Vérifiez votre connexion et réessayez.';

  @override
  String get actionRetry => 'Réessayer';

  @override
  String get errorGeneric => 'Une erreur est survenue.';

  @override
  String doctorLine(String name) {
    return 'Médecin : $name';
  }

  @override
  String get followUpSubtitle =>
      'Consultation des constantes enregistrées par l’infirmier et historique.';

  @override
  String get followUpEnterVitals => 'Saisir mes indicateurs';

  @override
  String get followUpTempLabel => 'Température (°C)';

  @override
  String get followUpTempHint => '37,0';

  @override
  String get followUpPainLabel => 'Douleur (0–10)';

  @override
  String get followUpSave => 'Enregistrer';

  @override
  String get followUpSaving => 'Enregistrement…';

  @override
  String get followUpInvalidTemp => 'Température invalide (30–45 °C).';

  @override
  String get followUpSavedOk => 'Indicateurs enregistrés.';

  @override
  String get followUpPainLow => 'Faible';

  @override
  String get followUpPainModerate => 'Modérée';

  @override
  String get followUpPainSevere => 'Sévère';

  @override
  String get followUpDoctorObs => 'Observations du médecin';

  @override
  String get followUpDoctorObsEmpty =>
      'Aucune observation pour le moment. Vos mesures sont synchronisées avec l’équipe soignante.';

  @override
  String get followUpLastReadings => 'Dernières données';

  @override
  String get followUpNoData => 'Aucune constante enregistrée pour le moment.';

  @override
  String get followUpReadOnlyVitalsHint =>
      'La saisie (fréquence cardiaque, température, glycémie, tension) est réservée à l’infirmier. Cet écran affiche uniquement la consultation.';

  @override
  String get followUpStatTemp => 'Température';

  @override
  String get followUpStatPain => 'Douleur';

  @override
  String get followUpStatHeart => 'Fréquence cardiaque';

  @override
  String get followUpStatGlucose => 'Glycémie';

  @override
  String get followUpStatBp => 'Tension';

  @override
  String get followUpStatTempShort => 'Temp.';

  @override
  String get followUpStatGlucoseShort => 'Gly';

  @override
  String get followUpStatPainShort => 'Douleur';

  @override
  String get followUpHistoryRecent => 'Historique récent';

  @override
  String followUpLastUpdated(String when) {
    return 'Dernière mise à jour : $when';
  }

  @override
  String get assistantTitle => 'Assistant Clinova';

  @override
  String get assistantTooltip => 'Assistant';

  @override
  String get assistantPlaceholder => 'Votre question…';

  @override
  String get assistantSend => 'OK';

  @override
  String get assistantWelcome =>
      'Bonjour. Je peux orienter sur le suivi (douleur, fièvre). Pour un avis médical, contactez votre médecin.';

  @override
  String get assistantNetworkError => 'Erreur réseau. Réessayez plus tard.';

  @override
  String get labTitle => 'Laboratoire';

  @override
  String get labSubtitle => 'Demande et suivi des rendez-vous de laboratoire.';

  @override
  String get labCalendarTitle => 'Calendrier';

  @override
  String get labCalendarPrev => 'Mois précédent';

  @override
  String get labCalendarNext => 'Mois suivant';

  @override
  String get labCalendarLegend => 'Rendez-vous laboratoire';

  @override
  String get labWhenLabel => 'Quand ?';

  @override
  String get labWhenPick => 'Choisir date et heure';

  @override
  String get labNoteLabel => 'Note';

  @override
  String get labNotePlaceholder => 'Ex : à jeun, douleur, disponibilité…';

  @override
  String get labSubmit => 'Envoyer la demande';

  @override
  String get labListTitle => 'Mes demandes';

  @override
  String get labPublicHint => 'Lien public (web) :';

  @override
  String get commonNoData => 'Aucune donnée';

  @override
  String get commonCancel => 'Annuler';

  @override
  String get doctorAppointmentsTitle => 'Rendez-vous médecin';

  @override
  String get openPublicLink => 'Ouvrir le lien public';
}
