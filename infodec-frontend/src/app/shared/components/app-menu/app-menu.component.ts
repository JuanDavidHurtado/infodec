import { Component, EventEmitter, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink, RouterLinkActive } from '@angular/router';

@Component({
  selector: 'app-menu',
  standalone: true,
  imports: [CommonModule, RouterLink, RouterLinkActive],
  templateUrl: './app-menu.component.html',
  styleUrls: ['./app-menu.component.scss']
})
export class AppMenuComponent {
  @Output() changeLang = new EventEmitter<'es' | 'de'>();
  lang: 'es' | 'de' = (localStorage.getItem('lang') as 'es' | 'de') || 'es';

  setLang(lang: 'es' | 'de') {
    if (this.lang === lang) return;
    this.lang = lang;
    localStorage.setItem('lang', this.lang);
    this.changeLang.emit(this.lang);
  }

  // sigue funcionando si algún lugar aún llama a toggle
  toggleLang() {
    this.setLang(this.lang === 'es' ? 'de' : 'es');
  }
}
