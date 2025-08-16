export interface FinalizeRequest {
  idCity: number;
  lang: 'es' | 'de';
  log?: boolean;
}

export interface FinalizeResponse {
  statusCode: number;
  message?: string;
  country?: string;
  city?: string;
  budget_cop?: number;
  currency_code?: 'GBP' | 'JPY' | 'INR' | 'DKK';
  currency_symbol?: string;
  rate?: number;
  converted?: number;
  converted_fmt?: string;
  weather_c?: number | null;
  weather_desc?: string | null;

  country_es?: string;
  country_de?: string;
  city_es?: string;
  city_de?: string;
  weather_desc_es?: string | null;
  weather_desc_de?: string | null;

  error?: string;
  details?: string;
}

export interface HistoryEntry {
  when: string;
  country: string;
  city: string;
  budget_cop: number;
  converted: number;
  converted_fmt?: string;
  currency: 'GBP' | 'JPY' | 'INR' | 'DKK';
  symbol: string;
  rate: number;
  temp_c: number | null;
  weather_desc: string | null;
}

export interface HistoryResponse {
  statusCode: number;
  message?: string;
  history?: HistoryEntry[];
  error?: string;
  details?: string;
}
