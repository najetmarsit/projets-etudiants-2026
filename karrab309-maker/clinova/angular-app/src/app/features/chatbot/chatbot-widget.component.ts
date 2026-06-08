import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ApiService } from '../../core/services/api.service';

@Component({
  selector: 'app-chatbot-widget',
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <div class="chatbot-fab">
      @if (!open()) {
        <button type="button" class="btn btn-primary rounded-circle shadow chatbot-toggle" (click)="open.set(true)" title="Assistant">
          <i class="bi bi-chat-dots-fill"></i>
        </button>
      } @else {
        <div class="chatbot-panel card shadow">
          <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="small fw-semibold">Assistant Clinova</span>
            <button type="button" class="btn-close btn-sm" (click)="open.set(false)"></button>
          </div>
          <div class="card-body chatbot-messages small">
            @for (m of messages(); track $index) {
              <div class="mb-2" [class.text-end]="m.from === 'user'">
                <span class="d-inline-block px-2 py-1 rounded-3" [ngClass]="m.from === 'user' ? 'bg-primary text-white' : 'bg-light border'">
                  {{ m.text }}
                </span>
              </div>
            }
            @if (loading()) {
              <div class="text-muted">…</div>
            }
          </div>
          <div class="card-footer p-2">
            <div class="input-group input-group-sm">
              <input type="text" class="form-control" [(ngModel)]="draft" placeholder="Votre question…" (keydown.enter)="send()" />
              <button class="btn btn-primary" type="button" (click)="send()" [disabled]="loading()">OK</button>
            </div>
          </div>
        </div>
      }
    </div>
  `,
  styles: [
    `
      .chatbot-fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 1050;
      }
      .chatbot-toggle {
        width: 56px;
        height: 56px;
      }
      .chatbot-panel {
        width: min(100vw - 32px, 360px);
      }
      .chatbot-messages {
        max-height: 220px;
        overflow-y: auto;
      }
    `,
  ],
})
export class ChatbotWidgetComponent {
  private api = inject(ApiService);

  open = signal(false);
  loading = signal(false);
  draft = '';
  messages = signal<{ from: 'user' | 'bot'; text: string }[]>([
    {
      from: 'bot',
      text: 'Bonjour. Je peux orienter sur le suivi (douleur, fièvre). Pour un avis médical, contactez votre médecin.',
    },
  ]);

  send(): void {
    const t = this.draft.trim();
    if (!t || this.loading()) return;
    this.draft = '';
    this.messages.update((m) => [...m, { from: 'user', text: t }]);
    this.loading.set(true);
    this.api.chatMessage(t).subscribe({
      next: (r) => {
        this.loading.set(false);
        if (r.success && r.reply) {
          this.messages.update((m) => [...m, { from: 'bot', text: r.reply }]);
        }
      },
      error: () => {
        this.loading.set(false);
        this.messages.update((m) => [...m, { from: 'bot', text: 'Erreur réseau. Réessayez plus tard.' }]);
      },
    });
  }
}
