import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { FinalizeRequest, FinalizeResponse, HistoryResponse } from '../models/trip.dto';

@Injectable({ providedIn: 'root' })
export class TripService {
  private base = '/api';

  constructor(private http: HttpClient) {}

  finalize(body: FinalizeRequest): Observable<FinalizeResponse> {
    return this.http.post<FinalizeResponse>(`${this.base}/finalize`, body);
  }

  getHistory(lang: 'es' | 'de'): Observable<HistoryResponse> {
    return this.http.get<HistoryResponse>(`${this.base}/history`, {
      params: { lang }
    });
  }
}
