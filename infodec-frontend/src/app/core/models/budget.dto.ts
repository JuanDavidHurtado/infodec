export interface BudgetGetResponse {
  statusCode: number;
  presupuesto: number | null;
  message?: string;
  error?: string;
  details?: string;
}

export interface BudgetSaveRequest {
  presupuesto: number;
}

export interface BudgetSaveResponse {
  statusCode: number;
  message?: string;
  presupuesto?: number;
  error?: string;
  details?: string;
}
