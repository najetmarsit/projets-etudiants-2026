import { ChangeDetectorRef, Component, ElementRef, ViewChild, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { loadStripe } from '@stripe/stripe-js';
import type { Stripe, StripeElements, StripePaymentElement } from '@stripe/stripe-js';
import { TranslateModule } from '@ngx-translate/core';
import { ClinPageHeadComponent } from '../../shared/ui/clin-page-head/clin-page-head.component';
import { AuthService } from '../../core/services/auth.service';
import { ApiService, PaymentBalancePayload } from '../../core/services/api.service';

@Component({
  selector: 'app-patient-payments-page',
  standalone: true,
  imports: [CommonModule, FormsModule, TranslateModule, ClinPageHeadComponent],
  templateUrl: './patient-payments-page.component.html',
  styleUrl: './patient-payments-page.component.scss',
})
export class PatientPaymentsPageComponent {
  private auth = inject(AuthService);
  private api = inject(ApiService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private cdr = inject(ChangeDetectorRef);

  @ViewChild('stripeMount') stripeMount?: ElementRef<HTMLDivElement>;

  balance: PaymentBalancePayload | null = null;
  balanceLoading = false;
  /** Erreur API / réseau lors du chargement du solde */
  balanceError = false;
  stripeError: string | null = null;
  stripeSuccessMsg: string | null = null;
  stripeBusy = false;
  stripeSubmitting = false;
  paypalLoading = false;

  showStripeForm = false;
  stripeMountReady = false;
  private stripeInstance: Stripe | null = null;
  private elements: StripeElements | null = null;
  private paymentElement: StripePaymentElement | null = null;
  private stripeReturnHandled = false;

  constructor() {
    void this.loadBalance();
    this.route.queryParamMap.subscribe((q) => {
      if (this.stripeReturnHandled) return;
      const pi = q.get('payment_intent');
      const redirectStatus = q.get('redirect_status');
      if (pi && redirectStatus === 'succeeded') {
        this.stripeReturnHandled = true;
        void this.finalizeStripeReturn(pi);
      } else if (pi && redirectStatus === 'failed') {
        this.stripeReturnHandled = true;
        this.stripeError = 'Le paiement par carte a échoué ou a été annulé.';
      }
    });
  }

  formatMoney(n: number): string {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
  }

  private loadBalance(): void {
    this.balanceLoading = true;
    this.balanceError = false;
    this.api.getPaymentBalance().subscribe({
      next: (r) => {
        this.balanceLoading = false;
        if (r.success && r.data) {
          this.balance = r.data;
          this.balanceError = false;
        } else if (r.success) {
          this.balance = null;
          this.balanceError = false;
        } else {
          this.balance = null;
          this.balanceError = true;
        }
      },
      error: () => {
        this.balanceLoading = false;
        this.balance = null;
        this.balanceError = true;
      },
    });
  }

  startStripePayment(): void {
    this.stripeError = null;
    this.stripeSuccessMsg = null;
    this.resetStripeUi();
    this.stripeBusy = true;
    this.api.postOnlinePaymentIntent({ provider: 'stripe' }).subscribe({
      next: async (res) => {
        this.stripeBusy = false;
        if (!res.success || !res.data?.client_secret || !res.data.publishable_key) {
          this.stripeError = res.message ?? 'Paiement par carte indisponible (vérifiez la configuration Stripe côté serveur).';
          return;
        }
        const stripe = await loadStripe(res.data.publishable_key);
        if (!stripe) {
          this.stripeError = 'Impossible de charger Stripe.';
          return;
        }
        this.stripeInstance = stripe;
        this.elements = stripe.elements({ clientSecret: res.data.client_secret });
        this.showStripeForm = true;
        this.stripeMountReady = true;
        this.cdr.detectChanges();
        setTimeout(() => {
          const el = this.stripeMount?.nativeElement;
          if (!el || !this.elements) return;
          try {
            this.paymentElement?.unmount();
          } catch {
            /* */
          }
          this.paymentElement = this.elements.create('payment');
          this.paymentElement.mount(el);
        }, 0);
      },
      error: () => {
        this.stripeBusy = false;
        this.stripeError = 'Erreur réseau.';
      },
    });
  }

  async submitStripePayment(): Promise<void> {
    if (!this.stripeInstance || !this.elements) return;
    this.stripeSubmitting = true;
    this.stripeError = null;
    const returnUrl = `${window.location.origin}/patient/payments`;
    const { error } = await this.stripeInstance.confirmPayment({
      elements: this.elements,
      confirmParams: { return_url: returnUrl },
    });
    this.stripeSubmitting = false;
    if (error) {
      this.stripeError = error.message ?? 'Paiement refusé.';
    }
  }

  private finalizeStripeReturn(paymentIntentId: string): void {
    this.api.confirmStripePayment(paymentIntentId).subscribe({
      next: (r) => {
        if (r.success) {
          this.stripeSuccessMsg = r.data?.duplicate ? 'Paiement déjà enregistré.' : 'Paiement enregistré. Merci.';
          void this.loadBalance();
          this.resetStripeUi();
          void this.router.navigate(['/patient', 'payments'], { replaceUrl: true });
        } else {
          this.stripeError = r.message ?? 'Confirmation impossible.';
        }
      },
      error: () => {
        this.stripeError = 'Impossible de confirmer le paiement auprès du serveur.';
      },
    });
  }

  private resetStripeUi(): void {
    this.showStripeForm = false;
    this.stripeMountReady = false;
    try {
      this.paymentElement?.unmount();
    } catch {
      /* */
    }
    this.paymentElement = null;
    this.elements = null;
    this.stripeInstance = null;
  }

  startPayPal(): void {
    this.stripeError = null;
    this.paypalLoading = true;
    this.api.postOnlinePaymentIntent({ provider: 'paypal' }).subscribe({
      next: (res) => {
        this.paypalLoading = false;
        if (res.success && res.data?.approval_url) {
          window.location.href = res.data.approval_url;
          return;
        }
        this.stripeError = res.message ?? 'PayPal indisponible (clés ou montant).';
      },
      error: () => {
        this.paypalLoading = false;
        this.stripeError = 'Erreur réseau.';
      },
    });
  }

  logout(): void {
    this.auth.logout().subscribe(() => this.router.navigate(['/login']));
  }
}
