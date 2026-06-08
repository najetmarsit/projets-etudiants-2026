import { Component, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { CommonModule } from '@angular/common';
import { ChatbotWidgetComponent } from '../chatbot/chatbot-widget.component';
import { ThemeService } from '../../core/services/theme.service';
import { AppTranslateService } from '../../core/services/translate.service';

@Component({
  selector: 'app-patient-shell',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterLink, RouterLinkActive, TranslateModule, ChatbotWidgetComponent],
  templateUrl: './patient-shell.component.html',
  styleUrl: './patient-shell.component.scss',
})
export class PatientShellComponent {
  theme = inject(ThemeService);
  translate = inject(AppTranslateService);
}
