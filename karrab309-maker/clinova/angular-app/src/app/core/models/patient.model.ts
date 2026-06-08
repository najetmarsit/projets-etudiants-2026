import { User } from './user.model';

export interface Patient {
  id: number;
  user_id: number;
  assigned_doctor_id?: number | null;
  assigned_nurse_id?: number | null;
  assigned_at?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  birth_date?: string | null;
  age: number;
  gender: string;
  national_id?: string | null;
  phone?: string | null;
  address?: string | null;
  /** Numéro de chambre (saisi à l’admission, visible staff) */
  chamber_number?: string | null;
  room_number?: string | null;
  bed_number?: string | null;
  /** Statut du patient (ex: admitted, discharged, ...) */
  status?: string | null;
  medical_history?: string | null;
  diagnosis?: string | null;
  current_illness?: string | null;
  prescribed_treatment?: string | null;
  /** Commentaires du médecin (mis à jour par le médecin, visible par le patient en temps réel) */
  doctor_observations?: string | null;
  pre_op_report?: string | null;
  post_op_report?: string | null;
  /** Jeton pour lien public / QR (dossier synthétique) */
  qr_public_token?: string | null;
  admission_at?: string | null;
  /** Rendez-vous / prochaine visite prévue */
  appointment_at?: string | null;
  discharge_at?: string | null;
  billing_notes?: string | null;
  /** Montant total facturé (caisse) */
  billing_total_due?: number | null;
  /** Détail des lignes de frais : { label, amount }[] */
  billing_breakdown?: { label: string; amount: number }[];
  user?: User;
  assignedDoctor?: User;
  operations?: Operation[];
  /** API can return health_indicators (snake) or healthIndicators (camel) */
  health_indicators?: HealthIndicator[];
  healthIndicators?: HealthIndicator[];
  alerts?: Alert[];
  reports?: unknown[];
  lab_documents?: LabDocument[];
}

export interface LabDocument {
  id: number;
  patient_id: number;
  uploaded_by: number;
  title: string;
  original_filename: string;
  mime_type?: string;
  created_at?: string;
  patient?: Patient;
  uploader?: User;
}

export interface Operation {
  id: number;
  patient_id: number;
  doctor_id: number;
  operation_type: string;
  operation_date: string;
  notes?: string;
  patient?: Patient;
  doctor?: User;
}

export interface HealthIndicator {
  id: number;
  patient_id: number;
  /** Fréquence cardiaque (bpm) */
  heart_rate?: number | null;
  /** Glycémie (mmol/L) */
  blood_glucose?: number | null;
  blood_pressure_systolic?: number | null;
  blood_pressure_diastolic?: number | null;
  pain_level?: number;
  temperature?: number;
  dressing_status?: string;
  recorded_at: string;
  image_path?: string | null;
  /** URL complète de la photo de suivi (rempli par l'API) */
  image_url?: string | null;
  recorded_by_user_id?: number | null;
  recordedBy?: Pick<User, 'id' | 'name' | 'username'>;
  patient?: Patient;
}

export interface Alert {
  id: number;
  patient_id: number;
  indicator_type?: string;
  value?: string | number;
  message: string;
  status: string;
  patient?: Patient;
}

export interface Message {
  id: number;
  sender_id: number;
  receiver_id: number;
  content: string;
  read_status: boolean;
  attachment_path?: string | null;
  attachment_url?: string | null;
  sender?: User;
  receiver?: User;
  created_at?: string;
}

export interface Report {
  id: number;
  patient_id: number;
  generated_by: number;
  report_type: string;
  content: string | null;
  created_at?: string;
  patient?: Patient;
  generatedBy?: User;
}
