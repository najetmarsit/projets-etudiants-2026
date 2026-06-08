export interface User {
  id: number;
  name: string;
  username: string;
  email: string;
  role: 'Admin' | 'Doctor' | 'Nurse' | 'Secretary' | 'Patient' | 'Laboratory' | 'Accountant';
  /** Spécialité (comptes médecin), renseignée à la création par l’admin. */
  specialty?: string | null;
  locale?: 'en' | 'fr' | 'ar' | null;
  profile_photo_path?: string | null;
  profile_photo_url?: string | null;

  /** Statut temps réel (API /doctors) */
  availability_status?: 'available' | 'busy' | 'offline' | 'on_call' | string;
  availability_last_seen_at?: string | null;
  active_patients_count?: number;
}
