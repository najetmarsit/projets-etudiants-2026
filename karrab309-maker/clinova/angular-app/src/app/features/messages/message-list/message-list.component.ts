import { Component, OnInit, OnDestroy, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from '../../../core/services/api.service';
import { AuthService } from '../../../core/services/auth.service';
import { Message, Patient } from '../../../core/models/patient.model';
import { User } from '../../../core/models/user.model';

interface ConversationContact {
  user_id: number;
  name: string;
  profile_photo_url?: string | null;
}

@Component({
  selector: 'app-message-list',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './message-list.component.html',
  styleUrl: './message-list.component.scss',
})
export class MessageListComponent implements OnInit, OnDestroy {
  private api = inject(ApiService);
  private auth = inject(AuthService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  messages: Message[] = [];
  contacts: ConversationContact[] = [];
  selectedUserId: number | null = null;
  loading = true;
  loadingMessages = false;
  error = '';
  sendContent = '';
  sendFile: File | null = null;
  sending = false;
  currentUserId: number | null = null;
  private refreshInterval: ReturnType<typeof setInterval> | null = null;

  ngOnInit(): void {
    const u = this.auth.user();
    if (u) this.currentUserId = u.id;

    const withId = this.route.snapshot.queryParamMap.get('with');
    if (withId) this.selectedUserId = +withId;

    this.route.queryParams.subscribe((qp) => {
      const id = qp['with'];
      if (id) this.selectedUserId = +id;
    });

    this.loadAll();
  }

  ngOnDestroy(): void {
    if (this.refreshInterval) clearInterval(this.refreshInterval);
  }

  loadAll(): void {
    this.loading = true;
    this.error = '';
    // Pour le médecin / admin : charger les patients comme contacts. Pour le patient : charger tous les messages puis déduire les contacts.
    if (this.auth.isDoctor() || this.auth.isAdmin()) {
      this.api.getPatients().subscribe({
        next: (r) => {
          const patients = (r.data ?? []) as Patient[];
          this.contacts = patients.map((p) => ({
            user_id: p.user_id,
            name: [p.first_name, p.last_name].filter(Boolean).join(' ') || p.user?.name || `Patient #${p.id}`,
            profile_photo_url: p.user?.profile_photo_url ?? null,
          }));
          this.loading = false;
          if (this.selectedUserId && !this.contacts.find((c) => c.user_id === this.selectedUserId)) {
            this.contacts.push({
              user_id: this.selectedUserId,
              name: `Utilisateur #${this.selectedUserId}`,
              profile_photo_url: null,
            });
          }
          this.loadConversation();
        },
        error: (err) => {
          this.error = err.error?.message ?? 'Erreur';
          this.loading = false;
        },
      });
    } else {
      this.api.getMessages().subscribe({
        next: (r) => {
          const list = r.data ?? [];
          const seen = new Set<number>();
          const contactsList: ConversationContact[] = [];
          list.forEach((m: Message) => {
            const otherId = m.sender_id === this.currentUserId ? m.receiver_id : m.sender_id;
            if (!seen.has(otherId)) {
              seen.add(otherId);
              const other = m.sender_id === this.currentUserId ? m.receiver : m.sender;
              contactsList.push({
                user_id: otherId,
                name: other?.name ?? `Utilisateur #${otherId}`,
                profile_photo_url: other?.profile_photo_url ?? null,
              });
            }
          });
          this.contacts = contactsList;
          this.loading = false;
          this.loadConversation();
        },
        error: (err) => {
          this.error = err.error?.message ?? 'Erreur';
          this.loading = false;
        },
      });
    }
  }

  loadConversation(): void {
    if (!this.selectedUserId) {
      this.messages = [];
      return;
    }
    this.loadingMessages = true;
    this.api.getMessages(this.selectedUserId).subscribe({
      next: (r) => {
        this.messages = (r.data ?? []).sort(
          (a, b) => new Date((a.created_at ?? 0)).getTime() - new Date((b.created_at ?? 0)).getTime()
        );
        this.loadingMessages = false;
        this.markReadIfNeeded();
      },
      error: () => (this.loadingMessages = false),
    });
    // Rafraîchir la conversation toutes les 15 s pour synchronisation
    if (this.refreshInterval) clearInterval(this.refreshInterval);
    this.refreshInterval = setInterval(() => {
      if (this.selectedUserId)
        this.api.getMessages(this.selectedUserId).subscribe({
          next: (r) => {
            this.messages = (r.data ?? []).sort(
              (a, b) => new Date((a.created_at ?? 0)).getTime() - new Date((b.created_at ?? 0)).getTime()
            );
          },
        });
    }, 15000);
  }

  selectContact(userId: number): void {
    this.selectedUserId = userId;
    this.router.navigate([], { queryParams: { with: userId }, queryParamsHandling: 'merge' });
    this.loadConversation();
  }

  isFromMe(m: Message): boolean {
    return m.sender_id === this.currentUserId;
  }

  otherParty(m: Message): User | undefined {
    return this.isFromMe(m) ? m.receiver : m.sender;
  }

  selectedContact(): ConversationContact | undefined {
    return this.contacts.find((c) => c.user_id === this.selectedUserId);
  }

  onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files?.length) this.sendFile = input.files[0];
  }

  clearFile(): void {
    this.sendFile = null;
  }

  send(): void {
    if (!this.selectedUserId || this.sending) return;
    if (!this.sendContent.trim() && !this.sendFile) return;
    this.sending = true;
    this.error = '';
    this.api.sendMessage(this.selectedUserId, this.sendContent.trim(), this.sendFile ?? undefined).subscribe({
      next: (r) => {
        this.messages = [...this.messages, r.data].sort(
          (a, b) => new Date((a.created_at ?? 0)).getTime() - new Date((b.created_at ?? 0)).getTime()
        );
        this.sendContent = '';
        this.sendFile = null;
        this.sending = false;
      },
      error: (err) => {
        this.error = err.error?.message ?? 'Erreur envoi';
        this.sending = false;
      },
    });
  }

  private markReadIfNeeded(): void {
    this.messages.forEach((m) => {
      if (!this.isFromMe(m) && !m.read_status) this.api.markMessageRead(m.id).subscribe();
    });
  }
}
