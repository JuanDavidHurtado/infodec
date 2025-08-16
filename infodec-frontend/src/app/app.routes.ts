import { Routes } from '@angular/router';
import { CountrySelectComponent } from './modules/trip/pages/country-select/country-select.component';
import { BudgetFormComponent } from './modules/trip/pages/budget-form/budget-form.component';
import { SummaryComponent } from './modules/trip/pages/summary/summary.component';
import { HistoryComponent } from './modules/trip/pages/history/history.component';


export const routes: Routes = [
    { path: 'trip/country-select', component: CountrySelectComponent },
    { path: 'trip/budget-form', component: BudgetFormComponent },
    { path: 'trip/summary', component: SummaryComponent },
    { path: 'trip/history', component: HistoryComponent },
    { path: '', redirectTo: 'trip/country-select', pathMatch: 'full' },
    { path: '**', redirectTo: 'trip/country-select' },
];
