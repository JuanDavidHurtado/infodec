export interface CityDto {
  idCity: number;
  nameSpa: string;
  nameGer: string;
}

export interface CountryDto {
  idCountry: number;
  nameSpa: string;
  nameGer: string;
  cities: CityDto[];
}

export interface CountryApiResponse {
  statusCode: number;
  message: string;
  countries: CountryDto[];
  error?: string;
  details?: string;
}
