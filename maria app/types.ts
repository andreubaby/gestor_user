
export interface TimeEntry {
  id: string;
  workerId: string;
  date: string;
  hoursBrutas: number; // minutes
  lunchDiscount: number; // minutes
  isLunchManual: boolean;
  isFreeDay?: boolean; // Nueva propiedad
  notes?: string;
  logs: AuditLog[];
}

export interface CompensationEntry {
  id: string;
  workerId: string;
  date: string;
  type: 'PAYMENT' | 'REST_HOURS' | 'FREE_DAY';
  minutes: number;
  notes?: string;
}

export interface AuditLog {
  id: string;
  timestamp: number;
  field: string;
  oldValue: string;
  newValue: string;
  user: string;
  reason?: string;
}

export interface Worker {
  id: string;
  name: string;
  isActive?: boolean; // Controla si está dado de alta o baja
}

export interface DailyCalculation {
  brutas: number;
  lunch: number;
  netas: number;
  ordinarias: number;
  extra: number;
  deficit: number;
  explanation: string;
}
