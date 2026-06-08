<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\HealthIndicatorController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DressingPhotoController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\LabDocumentController;
use App\Http\Controllers\PublicPatientDossierController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LabAppointmentController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationStreamController;
use App\Http\Controllers\SpecialistAppointmentController;
use App\Http\Controllers\NursingNoteController;
use App\Http\Controllers\PatientBillingItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/public/patient-dossier/{token}', [PublicPatientDossierController::class, 'show']);
Route::get('/public/patient-dossier/{token}/lab-documents/{id}/download', [PublicPatientDossierController::class, 'downloadLabDocument']);
// SSE notifications stream: EventSource ne supporte pas Authorization header → JWT via query (?token=...)
Route::get('notifications/stream', [NotificationStreamController::class, 'stream']);

// Protected routes
Route::middleware(['auth:api', 'audit'])->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/my-patient', [AuthController::class, 'myPatient']);
    Route::patch('/auth/locale', [AuthController::class, 'updateLocale']);
    Route::post('/auth/profile-photo', [AuthController::class, 'uploadProfilePhoto']);
    Route::post('/auth/profile-photo/crop', [\App\Http\Controllers\ProfilePhotoController::class, 'uploadCropped']);
    Route::delete('/auth/profile-photo', [\App\Http\Controllers\ProfilePhotoController::class, 'delete']);
    Route::get('/users/{id}/photo', [\App\Http\Controllers\ProfilePhotoController::class, 'show']);

    Route::post('/admin/users', [AdminUserController::class, 'store']);
    Route::post('/admin/patients/{id}/assign-doctor', [PatientController::class, 'assignDoctor']);

    // Doctor availability (for escalation routing)
    Route::patch('/doctor/availability', [DoctorAvailabilityController::class, 'update']);
    Route::get('/doctor/availability', [DoctorAvailabilityController::class, 'show']);
    Route::post('/chat', [ChatController::class, 'message']);

    // Dashboard stats & chart (médecin / admin)
    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics']);
    Route::get('/dashboard/analytics/doctor', [DashboardController::class, 'doctorAnalytics']);
    Route::get('/dashboard/analytics/lab', [DashboardController::class, 'labAnalytics']);
    Route::get('/dashboard/analytics/secretary', [DashboardController::class, 'secretaryAnalytics']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData']);
    Route::get('/dashboard/recent-appointments', [DashboardController::class, 'recentAppointments']);
    Route::get('/dashboard/nurses', [DashboardController::class, 'nurses']);
    Route::get('/dashboard/admissions-stats', [DashboardController::class, 'admissionsStats']);
    Route::get('/dashboard/financial-overview', [DashboardController::class, 'financialOverview']);
    Route::get('/doctors', [DashboardController::class, 'doctors']);

    // AI Design System — UI JSON + images contextuelles (règles + cache ; Pexels optionnel)
    Route::post('/ai-ui/generate', [\App\Http\Controllers\AIUIController::class, 'generate'])->middleware('throttle:30,1');
    Route::post('/ai-ui/context', [\App\Http\Controllers\AIUIController::class, 'context'])->middleware('throttle:60,1');
    Route::get('/ai-images/screen', [\App\Http\Controllers\AIUIController::class, 'screenImages'])->middleware('throttle:60,1');

    // Patient routes
    Route::get('patients/users-for-assignment', [PatientController::class, 'usersForAssignment']);
    Route::apiResource('patients', PatientController::class);
    Route::post('patients/{id}/urgent-notify', [PatientController::class, 'urgentNotify']);
    Route::post('patients/{id}/notify-staff', [PatientController::class, 'notifyStaffAboutPatient']);
    Route::post('patients/{id}/notify-doctor', [PatientController::class, 'notifyStaffAboutPatient']);

    // Operation routes
    Route::apiResource('operations', OperationController::class);

    // Health Indicator routes
    Route::apiResource('health-indicators', HealthIndicatorController::class);

    // Message routes
    Route::apiResource('messages', MessageController::class);
    Route::patch('messages/{id}/read', [MessageController::class, 'markAsRead']);

    // Alert routes
    Route::apiResource('alerts', AlertController::class);
    Route::patch('alerts/{id}/acknowledge', [AlertController::class, 'acknowledge']);

    // Notifications (staff web + patient mobile)
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::patch('notifications/{id}/acknowledge', [NotificationController::class, 'acknowledge']);

    // Reports
    Route::get('reports', [ReportController::class, 'index']);
    Route::post('reports', [ReportController::class, 'store']);
    Route::get('reports/{id}', [ReportController::class, 'show']);

    // Dressing photo upload (patient or doctor)
    Route::post('patients/{patientId}/dressing-photo', [DressingPhotoController::class, 'store']);

    // Laboratoire : analyses PDF vers le dossier patient
    Route::get('lab-documents', [LabDocumentController::class, 'index']);
    Route::post('lab-documents', [LabDocumentController::class, 'store']);
    Route::get('lab-documents/{id}/download', [LabDocumentController::class, 'download']);

    // Rendez-vous laboratoire (patient : demande ; labo / admin : gestion)
    Route::get('lab-appointments', [LabAppointmentController::class, 'index']);
    Route::post('lab-appointments', [LabAppointmentController::class, 'store']);
    Route::patch('lab-appointments/{id}', [LabAppointmentController::class, 'update']);
    Route::post('lab-appointments/{id}/cancel', [LabAppointmentController::class, 'cancel']);

    // Rendez-vous spécialiste (secrétaire/admin : création/gestion ; patient : lecture)
    Route::get('specialist-appointments', [SpecialistAppointmentController::class, 'index']);
    Route::post('specialist-appointments', [SpecialistAppointmentController::class, 'store']);
    Route::patch('specialist-appointments/{id}', [SpecialistAppointmentController::class, 'update']);
    Route::post('specialist-appointments/{id}/cancel', [SpecialistAppointmentController::class, 'cancel']);

    // Observations infirmières + signalement urgent
    Route::get('patients/{patientId}/nursing-notes', [NursingNoteController::class, 'index']);
    Route::post('patients/{patientId}/nursing-notes', [NursingNoteController::class, 'store']);
    Route::post('patients/{patientId}/nursing-notes/signal-urgent', [NursingNoteController::class, 'signalUrgent']);

    // Mouvements stock / matériel (admin : écriture ; comptable : lecture)
    Route::get('inventory-movements', [InventoryMovementController::class, 'index']);
    Route::post('inventory-movements', [InventoryMovementController::class, 'store']);
    Route::patch('inventory-movements/{id}', [InventoryMovementController::class, 'update']);
    Route::delete('inventory-movements/{id}', [InventoryMovementController::class, 'destroy']);

    // Paiements + reçu PDF
    Route::get('payments/balance', [PaymentController::class, 'balance']);
    Route::get('payments/cashier/discharge-pending', [PaymentController::class, 'cashierDischargePending']);
    Route::post('payments/online/intent', [PaymentController::class, 'onlineIntent']);
    Route::post('payments/online/confirm-stripe', [PaymentController::class, 'confirmStripe']);
    Route::get('payments', [PaymentController::class, 'index']);
    Route::post('payments', [PaymentController::class, 'store']);
    Route::get('payments/{id}/receipt', [PaymentController::class, 'receipt']);

    // Bilan automatique (actes facturables)
    Route::post('patients/{patientId}/billing/items', [PatientBillingItemController::class, 'store']);
});
