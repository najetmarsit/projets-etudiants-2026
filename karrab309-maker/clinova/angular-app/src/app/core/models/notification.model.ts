export type NotificationAudience = 'admin' | 'doctor' | 'nurse' | 'laboratory' | 'accountant' | 'patient';
export type NotificationChannel = 'staff_web' | 'patient_mobile';
export type NotificationPriority = 'normal' | 'urgent';

export interface NotificationItem {
  id: number;
  patient_id?: number | null;
  audience: NotificationAudience;
  recipient_user_id?: number | null;
  channel: NotificationChannel;
  type: string;
  title: string;
  body?: string | null;
  priority: NotificationPriority;
  data?: Record<string, unknown> | null;
  read_at?: string | null;
  acknowledged_at?: string | null;
  created_at: string;
}

