import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { RouterModule, Router } from '@angular/router';
import { CountryService } from '../../../../core/services/country.service';
import { CountryDto, CountryApiResponse, CityDto } from '../../../../core/models/country.dto';
import { AppMenuComponent } from '../../../../shared/components/app-menu/app-menu.component';

type Lang = 'es'|'de';
const STR = {
  es: { title:'Selecciona país y ciudad', country:'País', city:'Ciudad', next:'Siguiente', back:'Atrás', req:'Este campo es obligatorio' },
  de: { title:'Land und Stadt auswählen', country:'Land', city:'Stadt', next:'Weiter', back:'Zurück', req:'Pflichtfeld' }
} as const;

@Component({
  selector: 'app-country-select',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterModule, AppMenuComponent],
  templateUrl: './country-select.component.html',
  styleUrls: ['./country-select.component.scss']
})
export class CountrySelectComponent implements OnInit {
  form!: FormGroup;

  countries: CountryDto[] = [];
  cities: CityDto[] = [];

  loading = false;
  infoMsg: string | null = null;
  errorMsg: string | null = null;

  lang: Lang = (localStorage.getItem('lang') as Lang) || 'es';
  t = (k: keyof typeof STR['es']) => STR[this.lang][k];

  constructor(
    private fb: FormBuilder,
    private api: CountryService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.form = this.fb.group({
      idCountry: ['', Validators.required],
      idCity: ['', Validators.required],
    });

    this.form.get('idCountry')!.valueChanges.subscribe((id: number) => {
      const sel = this.countries.find(c => c.idCountry === +id);
      this.cities = sel?.cities ?? [];
      this.form.get('idCity')!.reset('');
    });

    this.fetchCountries();
    this.restoreSelection();
  }

  onLangChange(lang: Lang) {
    this.lang = lang;
    localStorage.setItem('lang', this.lang);
  }

  private fetchCountries() {
    this.loading = true;
    this.infoMsg = null;
    this.errorMsg = null;

    this.api.getCountries().subscribe({
      next: (res: CountryApiResponse) => {
        if (res?.statusCode === 200) {
          this.countries = res.countries || [];
          this.infoMsg = res?.message || null;
          this.syncCities();
          return;
        }
        this.errorMsg = res?.error || res?.message || 'No se pudo cargar la lista de países.';
      },
      error: (e) => {
        const payload = e?.error || {};
        this.errorMsg = payload?.error || payload?.message || 'No se pudo cargar la lista de países.';
      },
      complete: () => { this.loading = false; }
    });
  }

  private restoreSelection() {
    const saved = sessionStorage.getItem('trip.sel');
    if (!saved) return;
    const { idCountry, idCity } = JSON.parse(saved);
    this.form.patchValue({ idCountry });
    setTimeout(() => this.form.patchValue({ idCity }), 0);
  }

  private syncCities() {
    const id = this.form.get('idCountry')!.value;
    const sel = this.countries.find(c => c.idCountry === +id);
    this.cities = sel?.cities ?? [];
  }

  countryName(c: CountryDto) { return this.lang === 'es' ? c.nameSpa : c.nameGer; }
  cityName(ci: CityDto) { return this.lang === 'es' ? ci.nameSpa : ci.nameGer; }

  trackCountry = (_: number, c: CountryDto) => c.idCountry;
  trackCity = (_: number, ci: CityDto) => ci.idCity;

  goNext() {
    this.errorMsg = null;

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    sessionStorage.setItem('trip.sel', JSON.stringify(this.form.value));
    this.router.navigate(['/trip/budget-form']);
  }

  goBack() { this.router.navigateByUrl('/'); }
}
