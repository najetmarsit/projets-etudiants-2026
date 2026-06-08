import { Component, OnDestroy, OnInit, inject, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../../core/services/api.service';
import { User } from '../../../core/models/user.model';
import { Subscription, interval, startWith, switchMap } from 'rxjs';
import { NotificationRealtimeService } from '../../../core/services/notification-realtime.service';

@Component({
  selector: 'app-doctor-list',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './doctor-list.component.html',
  styleUrl: './doctor-list.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class DoctorListComponent implements OnInit, OnDestroy {
  private api = inject(ApiService);
  private realtime = inject(NotificationRealtimeService);
  private cdr = inject(ChangeDetectorRef);

  doctors: User[] = [];
  loading = true;
  error = '';
  private sub?: Subscription;
  private realtimeSub?: Subscription;

  ngOnInit(): void {
    // Polling léger pour statut "temps réel" (disponible/occupé/offline).
    this.sub = interval(30000)
      .pipe(
        startWith(0),
        switchMap(() => this.api.getDoctors())
      )
      .subscribe({
        next: (r) => {
          this.doctors = r.data ?? [];
          this.loading = false;
          this.error = '';
          this.cdr.markForCheck();
        },
        error: (err) => {
          this.error = err.error?.message ?? 'Erreur';
          this.loading = false;
          this.cdr.markForCheck();
        },
      });

    // Temps réel: un changement de statut mobile (PATCH) broadcast une notification SSE → refresh immédiat.
    this.realtime.start();
    this.realtimeSub = this.realtime.notifications$.subscribe((n) => {
      if (n.type === 'doctor.availability.updated') {
        this.api.getDoctors(true).subscribe({
          next: (r) => {
            this.doctors = r.data ?? [];
            this.cdr.markForCheck();
          },
          error: () => {},
        });
      }
    });
  }

  ngOnDestroy(): void {
    this.sub?.unsubscribe();
    this.realtimeSub?.unsubscribe();
  }

  isBusy(d: User): boolean {
    return d.availability_status === 'busy' || d.availability_status === 'on_call' || (d.active_patients_count ?? 0) > 0;
  }

  isAvailable(d: User): boolean {
    return d.availability_status === 'available' && (d.active_patients_count ?? 0) === 0;
  }

  isOffline(d: User): boolean {
    return d.availability_status === 'offline';
  }
}
