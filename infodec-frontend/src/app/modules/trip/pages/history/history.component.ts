import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { AppMenuComponent } from '../../../../shared/components/app-menu/app-menu.component';
import { TripService } from '../../../../core/services/trip.service';
import { HistoryEntry, HistoryResponse } from '../../../../core/models/trip.dto';

type Lang = 'es'|'de';
const STR = {
  es: {
    title: 'Historial (últimas 5 consultas)',
    date: 'Fecha',
    country: 'País',
    city: 'Ciudad',
    budget: 'Presupuesto (COP)',
    converted: 'Convertido',
    rate: 'Tasa (1 COP →)',
    weather: 'Clima',
    empty: 'Aún no hay consultas registradas.',
    err: 'No se pudo cargar el historial.'
  },
  de: {
    title: 'Verlauf (letzte 5 Abfragen)',
    date: 'Datum',
    country: 'Land',
    city: 'Stadt',
    budget: 'Budget (COP)',
    converted: 'Umgerechnet',
    rate: 'Kurs (1 COP →)',
    weather: 'Wetter',
    empty: 'Es gibt noch keine Einträge.',
    err: 'Der Verlauf konnte nicht geladen werden.'
  }
} as const;

@Component({
  selector: 'app-history',
  standalone: true,
  imports: [CommonModule, RouterModule, AppMenuComponent],
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.scss']
})
export class HistoryComponent implements OnInit {
  loading = false;
  errorMsg: string | null = null;
  rows: HistoryEntry[] = [];

  lang: Lang = (localStorage.getItem('lang') as Lang) || 'es';
  t = (k: keyof typeof STR['es']) => STR[this.lang][k];

  constructor(private trip: TripService) {}

  ngOnInit(): void { this.load(); }

  onLangChange(lang: Lang) {
    this.lang = lang;
    localStorage.setItem('lang', this.lang);
    this.load(); // recarga del backend con ?lang=
  }

  private load() {
    this.loading = true;
    this.errorMsg = null;
    this.trip.getHistory(this.lang).subscribe({
      next: (res: HistoryResponse) => {
        this.loading = false;
        if (res?.statusCode !== 200) {
          this.errorMsg = res?.error || res?.message || this.t('err');
          return;
        }
        this.rows = res.history ?? [];
      },
      error: (e) => {
        this.loading = false;
        const payload = e?.error || {};
        this.errorMsg = payload?.error || payload?.message || this.t('err');
      }
    });
  }

  fmtCOP(n: number): string {
    return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 }).format(n);
  }

  fmtRate(n: number): string {
    return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 6 }).format(n);
  }
}
