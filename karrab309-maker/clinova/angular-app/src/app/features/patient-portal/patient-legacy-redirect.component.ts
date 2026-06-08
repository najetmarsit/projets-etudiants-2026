import { Component, OnInit, inject } from '@angular/core';
import { Router, ActivatedRoute } from '@angular/router';

/** Ancienne URL `/patient-use-mobile` → portail `/patient/*` (conserve les query Stripe). */
@Component({
  standalone: true,
  template: '',
})
export class PatientLegacyRedirectComponent implements OnInit {
  private router = inject(Router);
  private route = inject(ActivatedRoute);

  ngOnInit(): void {
    const q = this.route.snapshot.queryParamMap;
    const params: Record<string, string> = {};
    q.keys.forEach((k) => {
      const v = q.get(k);
      if (v != null) params[k] = v;
    });
    void this.router.navigate(['/patient', 'payments'], { queryParams: Object.keys(params).length ? params : undefined, replaceUrl: true });
  }
}
