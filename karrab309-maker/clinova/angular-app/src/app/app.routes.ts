import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { roleGuard } from './core/guards/role.guard';
import { homeRedirectGuard } from './core/guards/home-redirect.guard';
import { mobileOnlyGuard } from './core/guards/mobile-only.guard';
import { MainLayoutComponent } from './features/layout/main-layout/main-layout.component';
import { EmptyRedirectComponent } from './features/home/empty-redirect.component';

const adminChildren: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', loadComponent: () => import('./features/dashboard/dashboard.component').then((m) => m.DashboardComponent) },
  { path: 'patients', loadComponent: () => import('./features/patients/patient-list/patient-list.component').then((m) => m.PatientListComponent) },
  { path: 'patients/new', loadComponent: () => import('./features/patients/patient-form/patient-form.component').then((m) => m.PatientFormComponent) },
  { path: 'patients/:id/suivi', loadComponent: () => import('./features/patients/patient-suivi/patient-suivi.component').then((m) => m.PatientSuiviComponent) },
  { path: 'patients/:id/edit', loadComponent: () => import('./features/patients/patient-form/patient-form.component').then((m) => m.PatientFormComponent) },
  { path: 'patients/:id', loadComponent: () => import('./features/patients/patient-detail/patient-detail.component').then((m) => m.PatientDetailComponent) },
  { path: 'operations', loadComponent: () => import('./features/operations/operation-list/operation-list.component').then((m) => m.OperationListComponent) },
  { path: 'health-indicators', loadComponent: () => import('./features/health-indicators/health-indicator-list/health-indicator-list.component').then((m) => m.HealthIndicatorListComponent) },
  { path: 'alerts', loadComponent: () => import('./features/alerts/alert-list/alert-list.component').then((m) => m.AlertListComponent) },
  { path: 'notifications', loadComponent: () => import('./features/notifications/notification-list/notification-list.component').then((m) => m.NotificationListComponent) },
  { path: 'reports', loadComponent: () => import('./features/reports/reports-list/reports-list.component').then((m) => m.ReportsListComponent) },
  { path: 'doctors', loadComponent: () => import('./features/doctors/doctor-list/doctor-list.component').then((m) => m.DoctorListComponent) },
  { path: 'profile', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
  { path: 'settings', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
  { path: 'messages', loadComponent: () => import('./features/messages/message-list/message-list.component').then((m) => m.MessageListComponent) },
  { path: 'users', loadComponent: () => import('./features/users/user-list/user-list.component').then((m) => m.UserListComponent) },
  { path: 'payments', loadComponent: () => import('./features/admin/admin-payments/admin-payments.component').then((m) => m.AdminPaymentsComponent) },
  { path: 'analytics', loadComponent: () => import('./features/admin/admin-analytics/admin-analytics.component').then((m) => m.AdminAnalyticsComponent) },
];

const doctorChildren: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', loadComponent: () => import('./features/dashboard/dashboard.component').then((m) => m.DashboardComponent) },
  { path: 'patients', loadComponent: () => import('./features/patients/patient-list/patient-list.component').then((m) => m.PatientListComponent) },
  { path: 'patients/:id/suivi', loadComponent: () => import('./features/patients/patient-suivi/patient-suivi.component').then((m) => m.PatientSuiviComponent) },
  { path: 'patients/:id', loadComponent: () => import('./features/patients/patient-detail/patient-detail.component').then((m) => m.PatientDetailComponent) },
  { path: 'operations', loadComponent: () => import('./features/operations/operation-list/operation-list.component').then((m) => m.OperationListComponent) },
  { path: 'health-indicators', loadComponent: () => import('./features/health-indicators/health-indicator-list/health-indicator-list.component').then((m) => m.HealthIndicatorListComponent) },
  { path: 'alerts', loadComponent: () => import('./features/alerts/alert-list/alert-list.component').then((m) => m.AlertListComponent) },
  { path: 'notifications', loadComponent: () => import('./features/notifications/notification-list/notification-list.component').then((m) => m.NotificationListComponent) },
  { path: 'reports', loadComponent: () => import('./features/reports/reports-list/reports-list.component').then((m) => m.ReportsListComponent) },
  { path: 'doctors', loadComponent: () => import('./features/doctors/doctor-list/doctor-list.component').then((m) => m.DoctorListComponent) },
  { path: 'profile', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
  { path: 'settings', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
  { path: 'messages', loadComponent: () => import('./features/messages/message-list/message-list.component').then((m) => m.MessageListComponent) },
];

/** Portail infirmier : soins, constantes, alertes (sans messagerie médecin ni rapports admin). */
const nurseChildren: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', loadComponent: () => import('./features/dashboard/dashboard.component').then((m) => m.DashboardComponent) },
  { path: 'patients', loadComponent: () => import('./features/patients/patient-list/patient-list.component').then((m) => m.PatientListComponent) },
  { path: 'patients/:id/suivi', loadComponent: () => import('./features/patients/patient-suivi/patient-suivi.component').then((m) => m.PatientSuiviComponent) },
  { path: 'patients/:id', loadComponent: () => import('./features/patients/patient-detail/patient-detail.component').then((m) => m.PatientDetailComponent) },
  { path: 'operations', loadComponent: () => import('./features/operations/operation-list/operation-list.component').then((m) => m.OperationListComponent) },
  { path: 'health-indicators', loadComponent: () => import('./features/health-indicators/health-indicator-list/health-indicator-list.component').then((m) => m.HealthIndicatorListComponent) },
  { path: 'alerts', loadComponent: () => import('./features/alerts/alert-list/alert-list.component').then((m) => m.AlertListComponent) },
  { path: 'notifications', loadComponent: () => import('./features/notifications/notification-list/notification-list.component').then((m) => m.NotificationListComponent) },
  { path: 'profile', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
  { path: 'settings', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
];

const patientPortalChildren: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', loadComponent: () => import('./features/patient-portal/patient-dashboard-page.component').then((m) => m.PatientDashboardPageComponent) },
  { path: 'appointments', loadComponent: () => import('./features/patient-portal/patient-appointments-page.component').then((m) => m.PatientAppointmentsPageComponent) },
  { path: 'dossier', loadComponent: () => import('./features/patient-portal/patient-dossier-page.component').then((m) => m.PatientDossierPageComponent) },
  { path: 'payments', loadComponent: () => import('./features/patient-portal/patient-payments-page.component').then((m) => m.PatientPaymentsPageComponent) },
  { path: 'notifications', loadComponent: () => import('./features/patient-portal/patient-notifications-page.component').then((m) => m.PatientNotificationsPageComponent) },
];

const secretaryChildren: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  {
    path: 'dashboard',
    loadComponent: () =>
      import('./features/secretary/secretary-dashboard/secretary-dashboard.component').then(
        (m) => m.SecretaryDashboardComponent
      ),
  },
  { path: 'patients', loadComponent: () => import('./features/patients/patient-list/patient-list.component').then((m) => m.PatientListComponent) },
  { path: 'patients/new', loadComponent: () => import('./features/patients/patient-form/patient-form.component').then((m) => m.PatientFormComponent) },
  { path: 'patients/:id/edit', loadComponent: () => import('./features/patients/patient-form/patient-form.component').then((m) => m.PatientFormComponent) },
  { path: 'patients/:id', loadComponent: () => import('./features/patients/patient-detail/patient-detail.component').then((m) => m.PatientDetailComponent) },
  { path: 'doctors', loadComponent: () => import('./features/doctors/doctor-list/doctor-list.component').then((m) => m.DoctorListComponent) },
  { path: 'profile', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
  { path: 'settings', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
  { path: 'notifications', loadComponent: () => import('./features/notifications/notification-list/notification-list.component').then((m) => m.NotificationListComponent) },
];

const labChildren: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', loadComponent: () => import('./features/lab/lab-dashboard/lab-dashboard.component').then((m) => m.LabDashboardComponent) },
  { path: 'notifications', loadComponent: () => import('./features/alerts/alert-list/alert-list.component').then((m) => m.AlertListComponent) },
  { path: 'patients', loadComponent: () => import('./features/patients/patient-list/patient-list.component').then((m) => m.PatientListComponent) },
  { path: 'patients/:id', loadComponent: () => import('./features/patients/patient-detail/patient-detail.component').then((m) => m.PatientDetailComponent) },
  { path: 'documents/new', loadComponent: () => import('./features/lab/lab-upload/lab-upload.component').then((m) => m.LabUploadComponent) },
  { path: 'documents', loadComponent: () => import('./features/lab/lab-documents-list/lab-documents-list.component').then((m) => m.LabDocumentsListComponent) },
  { path: 'appointments', loadComponent: () => import('./features/lab/lab-appointments/lab-appointments.component').then((m) => m.LabAppointmentsComponent) },
  { path: 'profile', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
  { path: 'settings', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
];

const accountantChildren: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', loadComponent: () => import('./features/accountant/accountant-dashboard.component').then((m) => m.AccountantDashboardComponent) },
  { path: 'inventory', loadComponent: () => import('./features/admin/admin-inventory/admin-inventory.component').then((m) => m.AdminInventoryComponent) },
  { path: 'patients', loadComponent: () => import('./features/patients/patient-list/patient-list.component').then((m) => m.PatientListComponent) },
  { path: 'patients/:id', loadComponent: () => import('./features/patients/patient-detail/patient-detail.component').then((m) => m.PatientDetailComponent) },
  { path: 'profile', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
  { path: 'settings', loadComponent: () => import('./features/profile/profile.component').then((m) => m.ProfileComponent) },
];

export const routes: Routes = [
  { path: '', pathMatch: 'full', canActivate: [homeRedirectGuard], component: EmptyRedirectComponent },
  { path: 'login', loadComponent: () => import('./features/auth/login/login.component').then((m) => m.LoginComponent) },
  // Login dédié secrétaire (UI séparée ; le backend reste /auth/login)
  { path: 'secretary/login', loadComponent: () => import('./features/auth/login/login.component').then((m) => m.LoginComponent) },
  {
    path: 'register',
    loadComponent: () =>
      import('./features/auth/register-closed/register-closed.component').then((m) => m.RegisterClosedComponent),
  },
  {
    path: 'public/dossier/:token',
    loadComponent: () => import('./features/public/public-dossier/public-dossier.component').then((m) => m.PublicDossierComponent),
  },
  {
    path: 'admin',
    component: MainLayoutComponent,
    canActivate: [authGuard, roleGuard(['Admin'])],
    children: adminChildren,
  },
  {
    path: 'doctor',
    component: MainLayoutComponent,
    canActivate: [authGuard, roleGuard(['Doctor'])],
    children: doctorChildren,
  },
  {
    path: 'secretary',
    component: MainLayoutComponent,
    canActivate: [authGuard, roleGuard(['Secretary'])],
    children: secretaryChildren,
  },
  {
    path: 'lab',
    component: MainLayoutComponent,
    canActivate: [authGuard, roleGuard(['Laboratory'])],
    children: labChildren,
  },
  {
    path: 'accountant',
    component: MainLayoutComponent,
    canActivate: [authGuard, roleGuard(['Accountant'])],
    children: accountantChildren,
  },
  {
    path: 'nurse-use-mobile',
    loadComponent: () => import('./features/home/nurse-use-mobile.component').then((m) => m.NurseUseMobileComponent),
    canActivate: [authGuard, roleGuard(['Nurse'])],
  },
  {
    path: 'nurse',
    component: MainLayoutComponent,
    canActivate: [authGuard, roleGuard(['Nurse']), mobileOnlyGuard('/nurse-use-mobile')],
    children: nurseChildren,
  },
  {
    path: 'patient-use-mobile',
    loadComponent: () =>
      import('./features/patient-portal/patient-legacy-redirect.component').then((m) => m.PatientLegacyRedirectComponent),
    canActivate: [authGuard, roleGuard(['Patient'])],
  },
  {
    path: 'patient',
    loadComponent: () => import('./features/patient-portal/patient-shell.component').then((m) => m.PatientShellComponent),
    canActivate: [authGuard, roleGuard(['Patient'])],
    children: patientPortalChildren,
  },
  { path: '**', canActivate: [homeRedirectGuard], component: EmptyRedirectComponent },
];
