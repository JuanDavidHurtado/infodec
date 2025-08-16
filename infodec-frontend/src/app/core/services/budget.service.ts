import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import {
  BudgetGetResponse,
  BudgetSaveRequest,
  BudgetSaveResponse
} from '../models/budget.dto';

@Injectable({ providedIn: 'root' })
export class BudgetService {
  private base = '/api';

  constructor(private http: HttpClient) {}

  saveBudget(amountCOP: number): Observable<BudgetSaveResponse> {
    const body: BudgetSaveRequest = { presupuesto: amountCOP };
    return this.http.post<BudgetSaveResponse>(`${this.base}/budget`, body);
  }
}
