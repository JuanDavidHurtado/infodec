import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { CountryApiResponse } from '../models/country.dto';

@Injectable({ providedIn: 'root' })
export class CountryService {
  // Si no se usa proxy, 'http://127.0.0.1:8000/api'
  private base = '/api';

  constructor(private http: HttpClient) {}

  /** GET /api/country */
  getCountries(): Observable<CountryApiResponse> {
    return this.http.get<CountryApiResponse>(`${this.base}/country`);
  }
}
