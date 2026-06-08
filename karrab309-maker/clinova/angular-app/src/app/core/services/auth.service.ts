import { Injectable, signal, computed, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, tap, catchError, of, map } from 'rxjs';
import { apiConfig } from '../config/api.config';
import { User } from '../models/user.model';
import { AppTranslateService } from './translate.service';
import { ApiCacheService } from './api-cache.service';

export interface LoginRequest {
  username: string;
  password: string;
}

/**
 * Harmonise l’identifiant avec l’API (CIN chiffré ou avec espaces/tirets).
 * Laisse inchangés e-mail, logins classiques (sans séparateurs « type CIN »).
 */
export function normalizeLoginIdentifierForApi(raw: string): string {
  const t = raw.trim();
  if (!t || t.includes('@')) return t;

  const alnum = t.replace(/[^A-Za-z0-9]/g, '');
  if (alnum.length < 6 || alnum.length > 20) return t;

  if (/^\d+$/.test(alnum)) return alnum;

  const digitCount = (alnum.match(/\d/g) ?? []).length;
  const hasSeparator = /[\s\-]/.test(t);
  if (hasSeparator && digitCount >= 4) return alnum.toUpperCase();

  return t;
}

export interface RegisterRequest {
  name: string;
  username: string;
  email: string;
  password: string;
  password_confirmation: string;
  role: 'Admin' | 'Doctor' | 'Nurse' | 'Secretary' | 'Patient' | 'Laboratory' | 'Accountant';
}

export interface AuthResponse {
  success: boolean;
  message?: string;
  user?: User;
  token?: string;
  errors?: Record<string, string[]>;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly tokenKey = 'medical_token';
  private readonly userKey = 'medical_user';

  private currentUser = signal<User | null>(null);
  private token = signal<string | null>(null);

  user = this.currentUser.asReadonly();
  isAuthenticated = computed(() => !!this.token());

  private translate = inject(AppTranslateService);
  private apiCache = inject(ApiCacheService);

  constructor(
    private http: HttpClient,
    private router: Router
  ) {
    const stored = localStorage.getItem(this.tokenKey);
    const userJson = localStorage.getItem(this.userKey);
    if (stored) this.token.set(stored);
    if (userJson) {
      try {
        const parsed = JSON.parse(userJson) as Partial<User> & { role?: unknown };
        this.currentUser.set(this.normalizeUser(parsed));
      } catch {}
    }
  }

  private normalizeRole(role: unknown): User['role'] | null {
    if (typeof role !== 'string') return null;
    const r = role.trim().toLowerCase();
    if (r === 'admin') return 'Admin';
    if (r === 'doctor') return 'Doctor';
    if (r === 'secretary') return 'Secretary';
    if (r === 'patient') return 'Patient';
    if (r === 'laboratory' || r === 'lab') return 'Laboratory';
    if (r === 'accountant') return 'Accountant';
    if (r === 'nurse') return 'Nurse';
    return null;
  }

  private normalizeUser(user: Partial<User> & { role?: unknown }): User | null {
    if (!user) return null;
    const role = this.normalizeRole(user.role) ?? (user.role as User['role'] | undefined);
    if (!role) return user as User; // fallback: ne casse pas l’app si rôle inattendu
    return { ...(user as User), role };
  }

  login(body: LoginRequest): Observable<AuthResponse> {
    const payload: LoginRequest = {
      username: normalizeLoginIdentifierForApi(body.username),
      password: body.password.trim(),
    };
    return this.http
      .post<AuthResponse>(`${apiConfig.baseUrl}/auth/login`, payload)
      .pipe(
        tap((res) => {
          if (res.success && res.token && res.user) {
            const u = this.normalizeUser(res.user as Partial<User> & { role?: unknown }) ?? res.user;
            this.token.set(res.token);
            this.currentUser.set(u as User);
            localStorage.setItem(this.tokenKey, res.token);
            localStorage.setItem(this.userKey, JSON.stringify(u));
            const locale = (u as User | undefined)?.locale as 'en' | 'fr' | 'ar' | undefined;
            if (locale && ['en', 'fr', 'ar'].includes(locale)) {
              this.translate.use(locale);
            }
          }
        })
      );
  }

  register(body: RegisterRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${apiConfig.baseUrl}/auth/register`, body).pipe(
      tap((res) => {
        if (res.success && res.token && res.user) {
          const u = this.normalizeUser(res.user as Partial<User> & { role?: unknown }) ?? res.user;
          this.token.set(res.token!);
          this.currentUser.set(u as User);
          localStorage.setItem(this.tokenKey, res.token!);
          localStorage.setItem(this.userKey, JSON.stringify(u));
          const locale = (u as User | undefined)?.locale as 'en' | 'fr' | 'ar' | undefined;
          if (locale && ['en', 'fr', 'ar'].includes(locale)) {
            this.translate.use(locale);
          }
        }
      })
    );
  }

  logout(): Observable<AuthResponse> {
    const t = this.token();
    this.token.set(null);
    this.currentUser.set(null);
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.userKey);
    if (t) {
      return this.http
        .post<AuthResponse>(`${apiConfig.baseUrl}/auth/logout`, {}, {
          headers: { Authorization: `Bearer ${t}` },
        })
        .pipe(catchError(() => of({ success: true })));
    }
    return of({ success: true });
  }



  /**
   * Déconnexion locale (sans invalider le token côté serveur).
   * À utiliser sur 401 pour éviter d'invalider la session d'un autre client
   * qui partagerait le même token (ex: web + mobile).
   */
  clearLocalSession(): void {
    this.token.set(null);
    this.currentUser.set(null);
    localStorage.removeItem(this.tokenKey);
    localStorage.removeItem(this.userKey);
  }

  getToken(): string | null {
    return this.token();
  }

  refreshUser(forceRefresh = false): Observable<User> {
    const key = 'auth:me';
    if (forceRefresh) this.apiCache.invalidate(key);
    return this.apiCache
      .get(
        key,
        () =>
          this.http.get<{ success: boolean; user: User }>(`${apiConfig.baseUrl}/auth/me`).pipe(
            tap((res) => this.applyUserResponse(res))
          ),
        { staleMs: 90_000, ttlMs: 300_000 }
      )
      .pipe(
        map((res) => {
          if (!res.success || !res.user) throw new Error('Not authenticated');
          return (this.normalizeUser(res.user as Partial<User> & { role?: unknown }) ?? res.user) as User;
        })
      );
  }

  private applyUserResponse(res: { success: boolean; user?: User }): void {
    if (res.success && res.user) {
      const u = this.normalizeUser(res.user as Partial<User> & { role?: unknown }) ?? res.user;
      this.currentUser.set(u as User);
      localStorage.setItem(this.userKey, JSON.stringify(u));
      const locale = (u as User | undefined)?.locale as 'en' | 'fr' | 'ar' | undefined;
      if (locale && ['en', 'fr', 'ar'].includes(locale)) {
        this.translate.use(locale);
      }
    }
  }

  hasRole(role: string): boolean {
    return this.currentUser()?.role === role;
  }

  isAdmin(): boolean {
    return this.hasRole('Admin');
  }

  isDoctor(): boolean {
    return this.hasRole('Doctor');
  }

  isNurse(): boolean {
    const r = this.currentUser()?.role;
    if (!r) return false;
    return r === 'Nurse' || String(r).toLowerCase() === 'nurse';
  }

  isPatient(): boolean {
    return this.hasRole('Patient');
  }

  isLaboratory(): boolean {
    return this.hasRole('Laboratory');
  }

  isAccountant(): boolean {
    return this.hasRole('Accountant');
  }

  isSecretary(): boolean {
    return this.hasRole('Secretary');
  }

  roleLabel(role: User['role'] | null | undefined): string {
    switch (role) {
      case 'Admin':
        return 'Administrateur';
      case 'Doctor':
        return 'Médecin';
      case 'Nurse':
        return 'Infirmier';
      case 'Secretary':
        return 'Réception';
      case 'Patient':
        return 'Patient';
      case 'Laboratory':
        return 'Laboratoire';
      case 'Accountant':
        return 'Comptable';
      default:
        return role ? String(role) : '';
    }
  }
}
