import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AppMenuComponent } from '../../../../shared/components/app-menu/app-menu.component';

import { BudgetService } from '../../../../core/services/budget.service';
import { BudgetSaveResponse } from '../../../../core/models/budget.dto';

type Lang = 'es' | 'de';
const STR = {
  es: {
    title: 'Presupuesto de viaje (COP)',
    amount: 'Presupuesto en pesos colombianos (COP)',
    placeholder: 'Ej: 2.500.000',
    next: 'Siguiente',
    back: 'Atrás',
    req: 'Este campo es obligatorio',
    num: 'Ingresa un monto válido mayor a 0',
    saving: 'Guardando presupuesto...',
    errGeneric: 'No se pudo guardar el presupuesto. Intenta de nuevo.'
  },
  de: {
    title: 'Reisebudget (COP)',
    amount: 'Budget in kolumbianischen Pesos (COP)',
    placeholder: 'Z.B.: 2.500.000',
    next: 'Weiter',
    back: 'Zurück',
    req: 'Pflichtfeld',
    num: 'Gib einen gültigen Betrag größer als 0 ein',
    saving: 'Budget wird gespeichert...',
    errGeneric: 'Budget konnte nicht gespeichert werden. Bitte erneut versuchen.'
  }
} as const;

@Component({
  selector: 'app-budget-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterModule, AppMenuComponent],
  templateUrl: './budget-form.component.html',
  styleUrls: ['./budget-form.component.scss']
})
export class BudgetFormComponent implements OnInit {
  form!: FormGroup;
  saving = false;
  errorMsg: string | null = null;

  lang: Lang = (localStorage.getItem('lang') as Lang) || 'es';
  t = (k: keyof typeof STR['es']) => STR[this.lang][k];

  constructor(
    private fb: FormBuilder,
    private router: Router,
    private budget: BudgetService
  ) { }

  ngOnInit(): void {
    const sel = sessionStorage.getItem('trip.sel');
    if (!sel) {
      this.router.navigate(['/trip/country-select']);
      return;
    }

    this.form = this.fb.group({
      amountCOP: ['', [Validators.required, this.amountValidator]]
    });

    // Arranca vacío siempre.
    this.form.get('amountCOP')!.setValue('', { emitEvent: false });
  }

  onLangChange(lang: Lang) {
    this.lang = lang;
    localStorage.setItem('lang', this.lang);
  }

  private parseAmount(raw: string): number {
    if (raw == null) return NaN as any;
    const trimmed = String(raw).trim();

    const hasComma = trimmed.includes(',');
    const hasDot = trimmed.includes('.');
    let normalized = trimmed;

    if (hasComma && hasDot) {
      const lastSep = Math.max(trimmed.lastIndexOf(','), trimmed.lastIndexOf('.'));
      const intPart = trimmed.slice(0, lastSep).replace(/[.,]/g, '');
      const decPart = trimmed.slice(lastSep + 1).replace(/[^\d]/g, '');
      normalized = decPart ? `${intPart}.${decPart}` : intPart;
    } else if (hasComma && !hasDot) {
      normalized = trimmed.replace(/\./g, '').replace(',', '.');
    } else if (hasDot && !hasComma) {
      normalized = trimmed.replace(/\./g, '');
    }

    normalized = normalized.replace(/[^\d.]/g, '');
    const n = parseFloat(normalized);
    return isNaN(n) ? (NaN as any) : n;
  }

  amountValidator = (control: any) => {
    const n = this.parseAmount(String(control.value ?? ''));
    if (isNaN(n) || n <= 0) return { amountInvalid: true };
    return null;
  };

  normalizeAmount() {
    const raw = this.form.get('amountCOP')!.value ?? '';
    const n = this.parseAmount(String(raw));
    if (!isNaN(n) && n > 0) {
      const formatted = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 }).format(n);
      this.form.get('amountCOP')!.setValue(formatted, { emitEvent: false });
    }
  }

  goBack() {
    this.router.navigate(['/trip/country-select']);
  }

  goNext() {
    this.errorMsg = null;

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const raw = this.form.get('amountCOP')!.value ?? '';
    const amountNumber = this.parseAmount(String(raw));
    if (isNaN(amountNumber) || amountNumber <= 0) {
      this.form.get('amountCOP')!.setErrors({ amountInvalid: true });
      return;
    }

    this.saving = true;

    this.budget.saveBudget(amountNumber).subscribe({
      next: (res: BudgetSaveResponse) => {
        this.saving = false;

        if (res?.statusCode !== 200) {
          this.errorMsg = res?.message || res?.error || this.t('errGeneric');
          return;
        }

        this.router.navigate(['/trip/summary']);
      },
      error: (e) => {
        this.saving = false;
        const payload = e?.error || {};
        if (e?.status === 422 && payload?.errors) {
          const key = Object.keys(payload.errors)[0];
          if (key && payload.errors[key]?.length) {
            this.errorMsg = payload.errors[key][0];
            return;
          }
        }
        this.errorMsg = payload?.message || payload?.error || this.t('errGeneric');
      }
    });
  }
}
