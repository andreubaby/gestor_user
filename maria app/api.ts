import { Worker, TimeEntry, CompensationEntry } from './types';

const BASE = '/api/timeguard';

async function req<T>(url: string, options?: RequestInit): Promise<T> {
  const res = await fetch(url, {
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    ...options,
  });
  if (!res.ok) {
    const text = await res.text();
    throw new Error(`API error ${res.status}: ${text}`);
  }
  return res.json() as Promise<T>;
}

export const api = {
  // ── Workers ───────────────────────────────────────────────────────────────
  getWorkers: () =>
    req<Worker[]>(`${BASE}/workers`),

  createWorker: (worker: Worker) =>
    req<Worker>(`${BASE}/workers`, { method: 'POST', body: JSON.stringify(worker) }),

  updateWorker: (id: string, data: Partial<Worker>) =>
    req<Worker>(`${BASE}/workers/${id}`, { method: 'PUT', body: JSON.stringify(data) }),

  deleteWorker: (id: string) =>
    req<{ ok: boolean }>(`${BASE}/workers/${id}`, { method: 'DELETE' }),

  // ── Time Entries ──────────────────────────────────────────────────────────
  getEntries: (workerId: string) =>
    req<TimeEntry[]>(`${BASE}/entries?worker_id=${encodeURIComponent(workerId)}`),

  createEntry: (entry: TimeEntry) =>
    req<TimeEntry>(`${BASE}/entries`, { method: 'POST', body: JSON.stringify(entry) }),

  updateEntry: (id: string, data: Partial<TimeEntry>) =>
    req<TimeEntry>(`${BASE}/entries/${id}`, { method: 'PUT', body: JSON.stringify(data) }),

  deleteEntry: (id: string) =>
    req<{ ok: boolean }>(`${BASE}/entries/${id}`, { method: 'DELETE' }),

  // ── Compensations ─────────────────────────────────────────────────────────
  getCompensations: (workerId: string) =>
    req<CompensationEntry[]>(`${BASE}/compensations?worker_id=${encodeURIComponent(workerId)}`),

  createCompensation: (comp: CompensationEntry) =>
    req<CompensationEntry>(`${BASE}/compensations`, { method: 'POST', body: JSON.stringify(comp) }),

  deleteCompensation: (id: string) =>
    req<{ ok: boolean }>(`${BASE}/compensations/${id}`, { method: 'DELETE' }),

  // ── Bulk import desde localStorage ────────────────────────────────────────
  import: (payload: { workers: Worker[]; entries: TimeEntry[]; compensations: CompensationEntry[] }) =>
    req<{ ok: boolean; imported: Record<string, number> }>(`${BASE}/import`, {
      method: 'POST',
      body: JSON.stringify(payload),
    }),
};

