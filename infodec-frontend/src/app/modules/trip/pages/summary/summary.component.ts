import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { AppMenuComponent } from '../../../../shared/components/app-menu/app-menu.component';
import { TripService } from '../../../../core/services/trip.service';
import { FinalizeResponse } from '../../../../core/models/trip.dto';

type Lang = 'es' | 'de';
const STR = {
  es: {
    title: 'Resumen',
    country: 'País',
    city: 'Ciudad',
    budget: 'Presupuesto (COP)',
    currency: 'Moneda',
    rate: 'Tasa aplicada (1 COP →)',
    converted: 'Presupuesto convertido',
    weather: 'Clima hoy',
    back: 'Atrás',
    restart: 'Volver al inicio'
  },
  de: {
    title: 'Zusammenfassung',
    country: 'Land',
    city: 'Stadt',
    budget: 'Budget (COP)',
    currency: 'Währung',
    rate: 'Wechselkurs (1 COP →)',
    converted: 'Umgerechnetes Budget',
    weather: 'Wetter heute',
    back: 'Zurück',
    restart: 'Zum Start zurückkehren'
  }
} as const;

@Component({
  selector: 'app-summary',
  standalone: true,
  imports: [CommonModule, RouterModule, AppMenuComponent],
  templateUrl: './summary.component.html',
  styleUrls: ['./summary.component.scss']
})
export class SummaryComponent implements OnInit {
  loading = false;
  errorMsg: string | null = null;

  data: FinalizeResponse | null = null;

  lang: Lang = (localStorage.getItem('lang') as Lang) || 'es';
  t = (k: keyof typeof STR['es']) => STR[this.lang][k];

  constructor(
    private trip: TripService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.callFinalize(true);
  }

  onLangChange(lang: Lang) {
    this.lang = lang;
    localStorage.setItem('lang', this.lang);
    this.callFinalize(false);
  }

  private callFinalize(log: boolean) {
    this.errorMsg = null;
    const selRaw = sessionStorage.getItem('trip.sel');
    if (!selRaw) { this.router.navigate(['/trip/country-select']); return; }

    const sel = JSON.parse(selRaw) as { idCity: number };
    if (!sel?.idCity) { this.router.navigate(['/trip/country-select']); return; }

    this.loading = true;
    this.trip.finalize({ idCity: +sel.idCity, lang: this.lang, log }).subscribe({
      next: (res) => {
        this.loading = false;
        if (res?.statusCode !== 200) {
          this.errorMsg = res?.error || res?.message || 'No se pudo generar el resumen.';
          return;
        }
        this.data = res;
      },
      error: (e) => {
        this.loading = false;
        const payload = e?.error || {};
        this.errorMsg = payload?.error || payload?.message || 'No se pudo generar el resumen.';
      }
    });
  }

  back() { this.router.navigate(['/trip/budget-form']); }
  restart() { this.router.navigate(['/trip/country-select']); }
}
