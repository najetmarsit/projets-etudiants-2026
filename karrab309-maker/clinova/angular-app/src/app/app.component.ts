import { Component, inject, ChangeDetectionStrategy } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { AppTranslateService } from './core/services/translate.service';
import { ToastContainerComponent } from './shared/ui/toast/toast-container.component';
import { GlobalLoaderComponent } from './shared/ui/global-loader/global-loader.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, ToastContainerComponent, GlobalLoaderComponent],
  templateUrl: './app.component.html',
  styleUrl: './app.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AppComponent {
  private translate = inject(AppTranslateService);
  title = 'Clinova';
}
