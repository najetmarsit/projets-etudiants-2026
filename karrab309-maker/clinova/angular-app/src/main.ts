import { bootstrapApplication } from '@angular/platform-browser';
import { appConfig } from './app/app.config';
import { AppComponent } from './app/app.component';

bootstrapApplication(AppComponent, appConfig)
  .catch((err) => {
    console.error('Bootstrap error:', err);
    document.body.innerHTML = '<div style="padding:20px;font-family:sans-serif;"><h1>خطأ في تحميل التطبيق</h1><p>افتح Console (F12) لرؤية التفاصيل.</p><pre>' + (err?.message || err) + '</pre></div>';
  });
