import React, { useState, useMemo, useEffect, useCallback } from 'react';
import { TimeEntry, CompensationEntry, Worker } from './types';
import { formatMinutes, parseHHMM, calculateDay } from './utils';
import { exportToExcel } from './exportUtils';
import { api } from './api';
import {
  Users,
  Calendar,
  Clock,
  Plus,
  History,
  FileText,
  ArrowRightLeft,
  UserPlus,
  Trash2,
  User,
  Edit2,
  X,
  Coffee,
  Briefcase,
  Power,
  UserX,
  ChevronLeft,
  ChevronRight,
  Filter,
  AlertTriangle,
  Download
} from 'lucide-react';

const WORKDAY_MINUTES = 480; // 8h

const MONTH_NAMES = [
  "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
  "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
];

const generateId = () => {
  return `ID-${Math.random().toString(36).substr(2, 9)}-${Date.now()}`;
};

const App: React.FC = () => {
  // --- ESTADO ---
  const [workers, setWorkers] = useState<Worker[]>([]);
  const [entries, setEntries] = useState<TimeEntry[]>([]);
  const [compensations, setCompensations] = useState<CompensationEntry[]>([]);
  const [selectedWorkerId, setSelectedWorkerId] = useState<string>('');
  const [isLoading, setIsLoading] = useState(true);
  const [isMigrating, setIsMigrating] = useState(false);

  // --- NUEVA GESTIÓN DE FECHAS (AÑO Y MES SEPARADOS) ---
  const currentDate = new Date();
  const [viewYear, setViewYear] = useState<number>(currentDate.getFullYear());
  const [viewMonth, setViewMonth] = useState<number>(currentDate.getMonth()); // 0 = Enero

  const [activeTab, setActiveTab] = useState<'daily' | 'balances' | 'workers' | 'logs'>('daily');

  const [deleteConfirmation, setDeleteConfirmation] = useState<{
    id: string;
    type: 'ENTRY' | 'COMPENSATION' | 'WORKER';
    title: string;
    message: string;
  } | null>(null);

  // Sincronización con LocalStorage (solo preferencia de trabajador)
  useEffect(() => { if (selectedWorkerId) localStorage.setItem('tw_last_worker', selectedWorkerId); }, [selectedWorkerId]);

  // ── Carga inicial: workers ────────────────────────────────────────────────
  useEffect(() => {
    api.getWorkers().then(async (remoteWorkers) => {
      if (remoteWorkers.length === 0) {
        // Intentar migrar datos de localStorage si los hay
        const lsWorkers: Worker[]       = JSON.parse(localStorage.getItem('tw_workers')       || '[]');
        const lsEntries: TimeEntry[]    = JSON.parse(localStorage.getItem('tw_entries')        || '[]');
        const lsComps: CompensationEntry[] = JSON.parse(localStorage.getItem('tw_compensations') || '[]');

        if (lsWorkers.length > 0) {
          setIsMigrating(true);
          try {
            await api.import({ workers: lsWorkers, entries: lsEntries, compensations: lsComps });
            // Limpiar localStorage tras migrar
            localStorage.removeItem('tw_workers');
            localStorage.removeItem('tw_entries');
            localStorage.removeItem('tw_compensations');
            // Recargar datos desde API
            const migrated = await api.getWorkers();
            setWorkers(migrated);
            const lastId = localStorage.getItem('tw_last_worker') || migrated[0]?.id || '';
            setSelectedWorkerId(lastId);
          } finally {
            setIsMigrating(false);
          }
        } else {
          // Base de datos vacía y sin datos locales: crear trabajador inicial
          const initial: Worker = { id: 'W-INITIAL', name: 'Trabajador Principal', isActive: true };
          await api.createWorker(initial);
          setWorkers([initial]);
          setSelectedWorkerId(initial.id);
        }
      } else {
        setWorkers(remoteWorkers);
        const lastId = localStorage.getItem('tw_last_worker') || remoteWorkers[0]?.id || '';
        setSelectedWorkerId(lastId || remoteWorkers[0]?.id);
      }
      setIsLoading(false);
    }).catch(() => setIsLoading(false));
  }, []);

  // ── Carga de entradas y compensaciones al cambiar de trabajador ───────────
  useEffect(() => {
    if (!selectedWorkerId) return;
    Promise.all([
      api.getEntries(selectedWorkerId),
      api.getCompensations(selectedWorkerId),
    ]).then(([remoteEntries, remoteComps]) => {
      setEntries(remoteEntries);
      setCompensations(remoteComps);
    });
  }, [selectedWorkerId]);

  const [entryMode, setEntryMode] = useState<'clock' | 'direct'>('clock');
  const [editingEntryId, setEditingEntryId] = useState<string | null>(null);

  // Form Data
  const [formData, setFormData] = useState({
    date: new Date().toISOString().split('T')[0],
    startTime: '08:00',
    endTime: '17:00',
    hours: '08:00',
    lunch: '01:00',
    isLunchManual: false,
    isFreeDay: false,
    notes: ''
  });

  // Efecto: Cuando cambio de mes/año en el selector, actualizo la fecha por defecto del formulario
  useEffect(() => {
    if (!editingEntryId) {
      // Crear fecha: Día 1 del mes/año seleccionado, ajustado a zona horaria local para evitar saltos
      const newDateStr = new Date(viewYear, viewMonth, 2).toISOString().split('T')[0];
      setFormData(prev => ({ ...prev, date: newDateStr }));
    }
  }, [viewYear, viewMonth, editingEntryId]);

  const [compForm, setCompForm] = useState({
    type: 'PAYMENT' as CompensationEntry['type'],
    minutes: '01:00',
    notes: ''
  });

  const [newWorkerName, setNewWorkerName] = useState('');

  // --- FILTRADO DE DATOS ---

  // 1. GLOBAL (Todo el histórico del trabajador — ya cargado desde API por worker)
  const allWorkerEntries = entries;
  const allWorkerCompensations = compensations;

  // 2. MENSUAL (Filtrado por viewYear y viewMonth)
  const monthlyEntries = useMemo(() =>
    allWorkerEntries
      .filter(e => {
        const d = new Date(e.date);
        return d.getFullYear() === viewYear && d.getMonth() === viewMonth;
      })
      .sort((a,b) => b.date.localeCompare(a.date)),
  [allWorkerEntries, viewYear, viewMonth]);

  const monthlyCompensations = useMemo(() =>
    allWorkerCompensations
      .filter(c => {
        const d = new Date(c.date);
        return d.getFullYear() === viewYear && d.getMonth() === viewMonth;
      })
      .sort((a,b) => b.date.localeCompare(a.date)),
  [allWorkerCompensations, viewYear, viewMonth]);

  // --- CÁLCULOS ESTADÍSTICOS ---
  const calculateStats = (entryList: TimeEntry[], compList: CompensationEntry[]) => {
    let extra = 0, deficit = 0, paid = 0, rested = 0, days = 0;

    entryList.forEach(e => {
      const c = calculateDay(e.hoursBrutas, e.lunchDiscount, e.isFreeDay);
      extra += c.extra;
      deficit += c.deficit;
    });

    compList.forEach(c => {
      if (c.type === 'PAYMENT') paid += c.minutes;
      else {
        rested += c.minutes;
        if(c.type === 'FREE_DAY') days++;
      }
    });

    // Balance: (Lo que he generado de más) - (Lo que me han pagado/descansado) - (Lo que debo por días que trabajé menos)
    const balance = extra - paid - rested - deficit;

    return {
      extraTotal: extra,
      deficitTotal: deficit,
      paid,
      rested,
      days,
      balance
    };
  };

  const globalStats = useMemo(() => calculateStats(allWorkerEntries, allWorkerCompensations), [allWorkerEntries, allWorkerCompensations]);
  const monthlyStats = useMemo(() => calculateStats(monthlyEntries, monthlyCompensations), [monthlyEntries, monthlyCompensations]);

  // --- HANDLERS ---
  const handleDeleteEntry = (id: string) => {
    setDeleteConfirmation({
      id,
      type: 'ENTRY',
      title: '¿Eliminar Jornada?',
      message: 'Esta acción eliminará el registro permanentemente.'
    });
  };

  const handleDeleteComp = (id: string) => {
    setDeleteConfirmation({
      id,
      type: 'COMPENSATION',
      title: '¿Eliminar Compensación?',
      message: 'Se eliminará este registro de la bolsa de horas.'
    });
  };

  const handleDeleteWorker = (id: string) => {
    if (workers.length <= 1) {
      alert("Debe haber al menos un trabajador en el sistema.");
      return;
    }
    setDeleteConfirmation({
      id,
      type: 'WORKER',
      title: '¿Eliminar Definitivamente?',
      message: 'Esta acción borrará el perfil y TODO su historial. Si solo quieres desactivarlo, usa el botón de "Dar de Baja".'
    });
  };

  const executeDelete = async () => {
    if (!deleteConfirmation) return;
    const { id, type } = deleteConfirmation;

    if (type === 'ENTRY') {
      await api.deleteEntry(id);
      setEntries(prev => prev.filter(e => e.id !== id));
      if (editingEntryId === id) setEditingEntryId(null);
    } else if (type === 'COMPENSATION') {
      await api.deleteCompensation(id);
      setCompensations(prev => prev.filter(c => c.id !== id));
    } else if (type === 'WORKER') {
      await api.deleteWorker(id);
      setWorkers(prev => {
        const newWorkers = prev.filter(w => w.id !== id);
        if (selectedWorkerId === id) setSelectedWorkerId(newWorkers[0].id);
        return newWorkers;
      });
      setEntries(prev => prev.filter(e => e.workerId !== id));
      setCompensations(prev => prev.filter(c => c.workerId !== id));
    }
    setDeleteConfirmation(null);
  };

  const handleToggleWorkerStatus = async (id: string) => {
    const worker = workers.find(w => w.id === id);
    if (!worker) return;
    const updated = await api.updateWorker(id, { isActive: !worker.isActive });
    setWorkers(prev => prev.map(w => w.id === id ? updated : w));
  };

  const handleAddEntry = async (e: React.FormEvent) => {
    e.preventDefault();
    let brutas = 0;
    let lunch = 0;

    if (!formData.isFreeDay) {
        brutas = entryMode === 'direct' ? parseHHMM(formData.hours) :
        (() => {
            const s = parseHHMM(formData.startTime), f = parseHHMM(formData.endTime);
            return f >= s ? f - s : (1440 - s) + f;
        })();
        lunch = parseHHMM(formData.lunch);
        if (!formData.isLunchManual && brutas > WORKDAY_MINUTES) lunch = 60;
    }

    if (editingEntryId) {
      const updated = await api.updateEntry(editingEntryId, {
        date: formData.date,
        hoursBrutas: brutas,
        lunchDiscount: lunch,
        isFreeDay: formData.isFreeDay,
        notes: formData.notes,
      });
      setEntries(current => current.map(ent => ent.id === editingEntryId ? updated : ent));
      setEditingEntryId(null);
    } else {
      const newEntry: TimeEntry = {
        id: generateId(),
        workerId: selectedWorkerId,
        date: formData.date,
        hoursBrutas: brutas,
        lunchDiscount: lunch,
        isLunchManual: formData.isLunchManual,
        isFreeDay: formData.isFreeDay,
        notes: formData.notes,
        logs: []
      };
      const created = await api.createEntry(newEntry);
      setEntries(current => [created, ...current]);
    }
    setFormData(prev => ({ ...prev, notes: '' }));
  };

  const handleEditEntry = (entry: TimeEntry) => {
    setEditingEntryId(entry.id);
    // Ajustar vista al mes del registro editado
    const d = new Date(entry.date);
    setViewYear(d.getFullYear());
    setViewMonth(d.getMonth());

    setFormData({
      date: entry.date,
      startTime: '08:00', endTime: '17:00',
      hours: formatMinutes(entry.hoursBrutas),
      lunch: formatMinutes(entry.lunchDiscount),
      isLunchManual: entry.isLunchManual,
      isFreeDay: entry.isFreeDay || false,
      notes: entry.notes || ''
    });
    setEntryMode('direct');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleSaveCompensation = async (e: React.FormEvent) => {
    e.preventDefault();
    const mins = parseHHMM(compForm.minutes);
    if (mins <= 0) return alert("Cantidad no válida.");
    const newComp: CompensationEntry = {
      id: generateId(),
      workerId: selectedWorkerId,
      date: formData.date,
      type: compForm.type,
      minutes: mins,
      notes: compForm.notes
    };
    const created = await api.createCompensation(newComp);
    setCompensations(current => [created, ...current]);
    setCompForm({ ...compForm, minutes: '01:00', notes: '' });
  };

  const handleAddWorker = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newWorkerName.trim()) return;
    const newWorker: Worker = { id: `W-${Date.now()}`, name: newWorkerName.trim(), isActive: true };
    const created = await api.createWorker(newWorker);
    setWorkers(current => [...current, created]);
    setNewWorkerName('');
  };

  const sortedWorkers = useMemo(() => [...workers].sort((a, b) => {
    if (a.isActive === b.isActive) return 0;
    return a.isActive ? -1 : 1;
  }), [workers]);

  return (
    <div className="min-h-screen pb-20 bg-slate-50 font-sans">
      {/* Pantalla de carga / migración */}
      {(isLoading || isMigrating) && (
        <div className="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-slate-900/80 backdrop-blur-sm">
          <div className="bg-white rounded-[2rem] p-10 flex flex-col items-center gap-4 shadow-2xl max-w-sm w-full mx-4">
            <div className="w-16 h-16 border-4 border-blue-900 border-t-transparent rounded-full animate-spin" />
            <p className="font-black text-slate-800 text-lg">
              {isMigrating ? 'Migrando datos...' : 'Cargando...'}
            </p>
            {isMigrating && (
              <p className="text-xs text-slate-400 text-center">
                Importando tus datos de localStorage a la base de datos. Un momento.
              </p>
            )}
          </div>
        </div>
      )}
      <header className="bg-white border-b border-slate-200 sticky top-0 z-40 px-6 py-4 flex flex-col xl:flex-row xl:items-center justify-between gap-4 shadow-sm">
        <div className="flex items-center gap-2">
          <div className="bg-blue-900 p-2 rounded-xl shadow-lg shadow-blue-200"><Clock className="text-white w-6 h-6" /></div>
          <div>
            <h1 className="text-xl font-black text-slate-800 tracking-tight">TimeGuard Pro</h1>
            <p className="text-[9px] text-emerald-600 font-bold uppercase flex items-center gap-1.5">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Sistema de Registro Seguro
            </p>
          </div>
        </div>

        <div className="flex flex-col md:flex-row gap-3 items-center w-full md:w-auto">
            {/* SELECTOR AÑO/MES MEJORADO */}
            <div className="flex items-center gap-2 bg-slate-100 p-1.5 rounded-2xl w-full md:w-auto shadow-inner">
                {/* Selector Año */}
                <div className="flex items-center bg-white rounded-xl px-2 py-1 shadow-sm border border-slate-200">
                  <button onClick={() => setViewYear(prev => prev - 1)} className="p-1 hover:bg-slate-50 text-slate-400 hover:text-blue-900 rounded-lg"><ChevronLeft className="w-4 h-4" /></button>
                  <span className="font-black text-slate-700 mx-2">{viewYear}</span>
                  <button onClick={() => setViewYear(prev => prev + 1)} className="p-1 hover:bg-slate-50 text-slate-400 hover:text-blue-900 rounded-lg"><ChevronRight className="w-4 h-4" /></button>
                </div>

                {/* Selector Mes (Desplegable) */}
                <div className="relative flex-1 md:flex-none">
                  <select
                    value={viewMonth}
                    onChange={(e) => setViewMonth(Number(e.target.value))}
                    className="w-full md:w-40 appearance-none bg-blue-900 text-white font-bold py-2 px-4 rounded-xl text-sm outline-none cursor-pointer hover:bg-blue-800 transition-colors text-center"
                  >
                    {MONTH_NAMES.map((m, idx) => (
                      <option key={idx} value={idx}>{m}</option>
                    ))}
                  </select>
                  <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
                     <Filter className="w-3 h-3 opacity-50" />
                  </div>
                </div>
            </div>

            {/* SELECTOR DE TRABAJADOR */}
            <div className="flex items-center gap-3 bg-slate-50 p-2 rounded-2xl border border-slate-200 w-full md:w-auto">
                <User className="text-slate-400 w-4 h-4 ml-1" />
                <select value={selectedWorkerId} onChange={(e) => setSelectedWorkerId(e.target.value)} className="bg-transparent border-none font-bold text-slate-700 outline-none min-w-[160px] text-sm cursor-pointer w-full">
                    {workers.map(w => (
                        <option key={w.id} value={w.id}>
                            {w.name} {w.isActive === false ? '(BAJA)' : ''}
                        </option>
                    ))}
                </select>
            </div>

            {/* BOTÓN EXPORTAR EXCEL */}
            <button
              onClick={() => {
                const worker = workers.find(w => w.id === selectedWorkerId);
                if (worker) {
                  exportToExcel(worker.name, allWorkerEntries, allWorkerCompensations);
                }
              }}
              className="p-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl shadow-lg shadow-emerald-200 transition-all active:scale-95"
              title="Exportar a Excel"
            >
              <Download className="w-5 h-5" />
            </button>
        </div>
      </header>

      <main className="max-w-7xl mx-auto p-4 md:p-8 space-y-8">

        {/* PANEL DE CONTROL UNIFICADO */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            {/* 1. BOLSA GLOBAL (HISTÓRICO TOTAL) */}
            <div className="bg-slate-800 p-6 rounded-[2rem] shadow-xl text-white relative overflow-hidden flex flex-col justify-between h-full">
                <div className="absolute top-0 right-0 p-6 opacity-5"><Briefcase className="w-32 h-32" /></div>
                <div>
                    <div className="flex items-center gap-2 mb-2">
                        <span className="bg-white/10 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest text-blue-200">Total Histórico</span>
                    </div>
                    <p className="text-4xl font-black tracking-tighter mt-2">{formatMinutes(globalStats.balance)}</p>
                    <p className="text-xs text-slate-400 font-medium mt-1">Bolsa acumulada global</p>
                </div>
                <div className="mt-6 pt-4 border-t border-slate-700/50 grid grid-cols-2 gap-4 text-[10px]">
                    <div>
                        <p className="text-slate-400 uppercase font-bold">Déficit Total</p>
                        <p className="text-rose-400 font-black text-sm">{formatMinutes(globalStats.deficitTotal)}</p>
                    </div>
                    <div>
                        <p className="text-slate-400 uppercase font-bold">Compensado</p>
                        <p className="text-emerald-400 font-black text-sm">{formatMinutes(globalStats.paid + globalStats.rested)}</p>
                    </div>
                </div>
            </div>

            {/* 2. BALANCE MENSUAL (SELECCIONADO) */}
            <div className="bg-white p-6 rounded-[2rem] border-2 border-slate-100 shadow-sm relative flex flex-col justify-between h-full">
                <div>
                    <div className="flex items-center justify-between mb-2">
                         <span className="bg-blue-50 text-blue-900 px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest">
                            {MONTH_NAMES[viewMonth]} {viewYear}
                         </span>
                         {monthlyStats.balance >= 0 ?
                            <span className="text-emerald-500"><ArrowRightLeft className="w-5 h-5" /></span> :
                            <span className="text-rose-500"><AlertTriangle className="w-5 h-5" /></span>
                         }
                    </div>
                    <p className={`text-4xl font-black tracking-tighter mt-2 ${monthlyStats.balance >= 0 ? 'text-emerald-600' : 'text-rose-500'}`}>
                        {monthlyStats.balance > 0 ? '+' : ''}{formatMinutes(monthlyStats.balance)}
                    </p>
                    <p className="text-xs text-slate-400 font-medium mt-1">Balance neto este mes</p>
                </div>
                 <div className="mt-6 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4 text-[10px]">
                    <div>
                        <p className="text-slate-400 uppercase font-bold">Extra Gen.</p>
                        <p className="text-emerald-600 font-black text-sm">+{formatMinutes(monthlyStats.extraTotal)}</p>
                    </div>
                    <div>
                        <p className="text-slate-400 uppercase font-bold">Déficit Gen.</p>
                        <p className="text-rose-500 font-black text-sm">-{formatMinutes(monthlyStats.deficitTotal)}</p>
                    </div>
                </div>
            </div>

            {/* 3. DÉFICIT DEL MES */}
            <div className="bg-rose-50 p-6 rounded-[2rem] border border-rose-100 shadow-sm flex flex-col justify-between h-full">
                 <div>
                    <p className="text-[10px] font-black text-rose-400 uppercase tracking-widest mb-2">Déficit {MONTH_NAMES[viewMonth]}</p>
                    <p className="text-3xl font-black text-rose-600 tracking-tighter">{formatMinutes(monthlyStats.deficitTotal)}</p>
                    <p className="text-[10px] text-rose-400 mt-2 font-medium leading-relaxed">
                        Horas que faltaron por trabajar en {MONTH_NAMES[viewMonth]}. Restan de la bolsa global.
                    </p>
                 </div>
                 <div className="mt-4 flex items-center justify-end">
                    <AlertTriangle className="text-rose-200 w-12 h-12" />
                 </div>
            </div>

            {/* 4. DÍAS LIBRES */}
            <div className="bg-amber-50 p-6 rounded-[2rem] border border-amber-100 shadow-sm flex flex-col justify-between h-full">
                 <div>
                    <p className="text-[10px] font-black text-amber-500 uppercase tracking-widest mb-2">Días Libres / Bajas</p>
                    <div className="flex items-baseline gap-2">
                        <span className="text-3xl font-black text-amber-700 tracking-tighter">{monthlyStats.days}</span>
                        <span className="text-xs font-bold text-amber-600">este mes</span>
                    </div>
                 </div>
                 <div className="mt-4 pt-4 border-t border-amber-200/50">
                     <p className="text-xs font-black text-amber-800 flex justify-between">
                        <span>Total Histórico:</span>
                        <span>{globalStats.days} días</span>
                     </p>
                 </div>
            </div>
        </div>

        {/* Pestañas */}
        <div className="flex bg-white rounded-3xl border border-slate-200 p-1 shadow-sm max-w-fit overflow-x-auto no-scrollbar">
          {[
            { id: 'daily', label: 'Jornadas', icon: Calendar },
            { id: 'balances', label: 'Compensaciones', icon: ArrowRightLeft },
            { id: 'workers', label: 'Equipo', icon: Users },
            { id: 'logs', label: 'Auditoría', icon: History }
          ].map(tab => (
            <button key={tab.id} onClick={() => setActiveTab(tab.id as any)} className={`flex items-center gap-2 px-6 py-3 rounded-2xl font-bold text-xs transition-all ${activeTab === tab.id ? 'bg-blue-900 text-white shadow-lg shadow-blue-200' : 'text-slate-500 hover:bg-slate-50'}`}>
              <tab.icon className="w-4 h-4" /> {tab.label}
            </button>
          ))}
        </div>

        <div className="animate-in fade-in slide-in-from-bottom-4 duration-500">
          {activeTab === 'daily' && (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div className="lg:col-span-1">
                <form onSubmit={handleAddEntry} className={`bg-white p-8 rounded-[2.5rem] border-2 shadow-sm space-y-6 transition-all ${editingEntryId ? 'border-amber-400 bg-amber-50/20' : 'border-slate-100'}`}>
                  <div className="flex justify-between items-center">
                    <h3 className="font-black text-slate-800 flex items-center gap-2 text-lg">
                      {editingEntryId ? <Edit2 className="w-5 h-5 text-amber-500" /> : <Plus className="w-5 h-5 text-blue-900" />}
                      {editingEntryId ? 'Editar Jornada' : 'Nueva Jornada'}
                    </h3>
                    {editingEntryId && <button type="button" onClick={() => setEditingEntryId(null)} className="p-2 text-rose-500 hover:bg-rose-50 rounded-full transition-colors"><X className="w-5 h-5" /></button>}
                  </div>

                  <div className="space-y-4">
                    {/* Alerta de mes */}
                    <div className="bg-blue-50 text-blue-900 px-4 py-3 rounded-xl text-[10px] font-bold uppercase tracking-wide flex items-center justify-between">
                        <span>Registrando para:</span>
                        <span className="bg-white px-2 py-1 rounded shadow-sm">{MONTH_NAMES[viewMonth]} {viewYear}</span>
                    </div>

                    <input type="date" value={formData.date} onChange={e => setFormData({...formData, date: e.target.value})} className="w-full border-2 border-slate-100 p-4 rounded-2xl font-black outline-none focus:border-blue-900 transition-colors" required />

                    {/* TOGGLE DIA LIBRE */}
                    <div
                      onClick={() => setFormData(prev => ({...prev, isFreeDay: !prev.isFreeDay}))}
                      className={`cursor-pointer p-4 rounded-2xl border-2 flex items-center justify-between transition-all ${formData.isFreeDay ? 'bg-slate-100 border-slate-300' : 'bg-white border-slate-100 hover:border-blue-100'}`}
                    >
                      <div className="flex items-center gap-3">
                        <div className={`p-2 rounded-xl ${formData.isFreeDay ? 'bg-slate-300 text-white' : 'bg-blue-50 text-blue-900'}`}>
                           <Coffee className="w-5 h-5" />
                        </div>
                        <div>
                           <p className={`font-black text-sm ${formData.isFreeDay ? 'text-slate-500' : 'text-slate-800'}`}>Día Libre / No Laborable</p>
                           <p className="text-[10px] text-slate-400">No computa horas ni genera déficit</p>
                        </div>
                      </div>
                      <div className={`w-6 h-6 rounded-full border-2 flex items-center justify-center ${formData.isFreeDay ? 'bg-slate-500 border-slate-500' : 'border-slate-200'}`}>
                        {formData.isFreeDay && <div className="w-2.5 h-2.5 rounded-full bg-white" />}
                      </div>
                    </div>

                    {!formData.isFreeDay && (
                        <div className="space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">
                            <div className="flex p-1 bg-slate-100 rounded-2xl">
                            <button type="button" onClick={() => setEntryMode('clock')} className={`flex-1 py-2 text-[10px] font-black rounded-xl transition-all ${entryMode === 'clock' ? 'bg-white shadow-sm text-blue-900' : 'text-slate-500'}`}>RELOJ</button>
                            <button type="button" onClick={() => setEntryMode('direct')} className={`flex-1 py-2 text-[10px] font-black rounded-xl transition-all ${entryMode === 'direct' ? 'bg-white shadow-sm text-blue-900' : 'text-slate-500'}`}>MANUAL</button>
                            </div>
                            {entryMode === 'clock' ? (
                            <div className="grid grid-cols-2 gap-3">
                                <input type="time" value={formData.startTime} onChange={e => setFormData({...formData, startTime: e.target.value})} className="border-2 border-slate-100 p-4 rounded-2xl font-black focus:border-blue-900 outline-none" />
                                <input type="time" value={formData.endTime} onChange={e => setFormData({...formData, endTime: e.target.value})} className="border-2 border-slate-100 p-4 rounded-2xl font-black focus:border-blue-900 outline-none" />
                            </div>
                            ) : (
                            <input type="text" value={formData.hours} onChange={e => setFormData({...formData, hours: e.target.value})} className="w-full border-2 border-slate-100 p-4 rounded-2xl font-black focus:border-blue-900 outline-none" placeholder="hh:mm" />
                            )}
                            <div className="bg-white p-4 rounded-2xl border-2 border-slate-100 flex justify-between items-center">
                            <div><p className="text-[10px] font-black text-slate-400 uppercase mb-1">Comida</p><input type="text" value={formData.lunch} onChange={e => setFormData({...formData, lunch: e.target.value, isLunchManual: true})} className="w-16 bg-transparent text-lg font-black outline-none text-blue-900" /></div>
                            <button type="button" onClick={() => setFormData({...formData, isLunchManual: !formData.isLunchManual})} className={`text-[9px] font-black px-3 py-1.5 rounded-full border-2 transition-all ${formData.isLunchManual ? 'border-amber-200 text-amber-500' : 'border-slate-100 text-slate-400'}`}>{formData.isLunchManual ? 'MANUAL' : 'AUTO'}</button>
                            </div>
                        </div>
                    )}

                    <textarea value={formData.notes} onChange={e => setFormData({...formData, notes: e.target.value})} className="w-full border-2 border-slate-100 p-4 rounded-2xl text-xs h-24 focus:border-blue-900 outline-none resize-none" placeholder="Notas..." />
                    <button type="submit" className={`w-full py-4 rounded-2xl text-white font-black shadow-xl transition-all active:scale-[0.98] ${editingEntryId ? 'bg-amber-500 hover:bg-amber-600 shadow-amber-100' : 'bg-blue-900 hover:bg-blue-950 shadow-blue-200'}`}>
                      {editingEntryId ? 'Guardar Cambios' : 'Registrar Jornada'}
                    </button>
                  </div>
                </form>
              </div>

              <div className="lg:col-span-2 space-y-4">
                <div className="flex justify-between items-center px-4 mb-2">
                  <h3 className="font-black text-slate-800 text-lg flex items-center gap-2">
                    Historial de {MONTH_NAMES[viewMonth]}
                    <span className="bg-slate-100 text-slate-500 text-[10px] px-2 py-1 rounded-full">{monthlyEntries.length}</span>
                  </h3>
                </div>
                {monthlyEntries.length === 0 && <div className="text-center py-20 bg-white border-2 border-dashed rounded-[2.5rem] text-slate-300 font-bold italic shadow-sm">No hay registros en {MONTH_NAMES[viewMonth]} del {viewYear}.</div>}
                {monthlyEntries.map(entry => {
                  const calc = calculateDay(entry.hoursBrutas, entry.lunchDiscount, entry.isFreeDay);
                  return (
                    <div key={entry.id} className={`p-6 rounded-[2rem] border shadow-sm hover:shadow-md transition-all group relative overflow-hidden ${entry.isFreeDay ? 'bg-slate-50 border-slate-200' : 'bg-white border-slate-100'}`}>
                      <div className="flex justify-between items-start mb-4">
                        <div>
                          <p className={`font-black text-lg capitalize ${entry.isFreeDay ? 'text-slate-500' : 'text-slate-800'}`}>{new Date(entry.date).toLocaleDateString('es-ES', {weekday:'long', day:'numeric', month:'long'})}</p>
                          <p className="text-[9px] font-black text-slate-300 tracking-tighter uppercase mt-0.5 font-mono">ID: {entry.id}</p>
                        </div>
                        <div className="flex gap-2">
                          <button
                            type="button"
                            onClick={(e) => {
                              e.preventDefault();
                              e.stopPropagation();
                              handleEditEntry(entry);
                            }}
                            className="p-3 text-amber-500 hover:bg-amber-50 rounded-xl transition-all active:scale-90 relative z-10 cursor-pointer"
                            title="Editar"
                          >
                            <Edit2 className="w-5 h-5 pointer-events-none" />
                          </button>
                          <button
                            type="button"
                            onClick={(e) => {
                              e.preventDefault();
                              e.stopPropagation();
                              handleDeleteEntry(entry.id);
                            }}
                            className="p-3 text-rose-500 hover:bg-rose-100 rounded-xl transition-all active:scale-90 relative z-10 cursor-pointer"
                            title="Eliminar registro"
                          >
                            <Trash2 className="w-5 h-5 pointer-events-none" />
                          </button>
                        </div>
                      </div>

                      {entry.isFreeDay ? (
                          <div className="bg-slate-100 rounded-2xl p-4 flex items-center justify-center gap-2 border border-slate-200 border-dashed">
                              <Coffee className="text-slate-400 w-5 h-5" />
                              <span className="font-black text-slate-400 uppercase text-xs tracking-widest">Día Libre / No Laborable</span>
                          </div>
                      ) : (
                        <div className="grid grid-cols-4 gap-4 text-center bg-slate-50/50 p-4 rounded-2xl border border-slate-50">
                            <div><p className="text-[8px] font-black text-slate-400 uppercase mb-1">Neta</p><p className="font-black text-blue-900 text-sm">{formatMinutes(calc.netas)}</p></div>
                            <div><p className="text-[8px] font-black text-slate-400 uppercase mb-1">Extra</p><p className="font-black text-emerald-600 text-sm">+{formatMinutes(calc.extra)}</p></div>
                            <div><p className="text-[8px] font-black text-slate-400 uppercase mb-1">Déficit</p><p className="font-black text-rose-500 text-sm">-{formatMinutes(calc.deficit)}</p></div>
                            <div><p className="text-[8px] font-black text-slate-400 uppercase mb-1">Bruta</p><p className="font-bold text-slate-400 text-sm">{formatMinutes(entry.hoursBrutas)}</p></div>
                        </div>
                      )}

                      {entry.notes && <div className="mt-4 text-[11px] text-slate-500 italic flex gap-2 bg-white/50 p-3 rounded-xl border border-slate-200/50"><FileText className="w-4 h-4 text-slate-300" /> {entry.notes}</div>}
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {activeTab === 'balances' && (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div className="lg:col-span-1">
                <form onSubmit={handleSaveCompensation} className="bg-white p-8 rounded-[2.5rem] border shadow-sm space-y-6">
                  <h3 className="font-black flex items-center gap-2 text-lg"><ArrowRightLeft className="w-5 h-5 text-blue-900" /> Compensar Bolsa</h3>
                  <div className="bg-blue-50 text-blue-900 p-4 rounded-2xl text-[10px] leading-relaxed">
                     Nota: Al añadir una compensación aquí, se aplicará al mes visible ({MONTH_NAMES[viewMonth]}).
                  </div>
                  <div className="space-y-4">
                    <select value={compForm.type} onChange={e => setCompForm({...compForm, type: e.target.value as any})} className="w-full border-2 p-4 rounded-2xl font-black text-sm outline-none focus:border-blue-900">
                      <option value="PAYMENT">💰 Pago en Nómina</option>
                      <option value="REST_HOURS">🏖️ Descanso (Horas)</option>
                      <option value="FREE_DAY">📅 Día Libre Completo</option>
                    </select>
                    <input type="text" value={compForm.minutes} onChange={e => setCompForm({...compForm, minutes: e.target.value})} className="w-full border-2 p-4 rounded-2xl font-black focus:border-blue-900 outline-none" placeholder="hh:mm" />
                    <textarea value={compForm.notes} onChange={e => setCompForm({...compForm, notes: e.target.value})} className="w-full border-2 p-4 rounded-2xl text-xs h-24 focus:border-blue-900 outline-none resize-none" placeholder="Anotación..." />
                    <button type="submit" className="w-full py-4 bg-slate-800 text-white font-black rounded-2xl shadow-xl hover:bg-slate-900 transition-all active:scale-[0.98]">Aplicar Descuento</button>
                  </div>
                </form>
              </div>
              <div className="lg:col-span-2 bg-white rounded-[2.5rem] border shadow-sm overflow-hidden">
                <h4 className="px-8 pt-6 font-black text-slate-800 text-lg">Compensaciones en {MONTH_NAMES[viewMonth]} {viewYear}</h4>
                <table className="w-full text-left mt-4">
                  <thead className="bg-slate-50 border-b">
                    <tr>
                      <th className="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Fecha</th>
                      <th className="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest">Tipo</th>
                      <th className="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Cantidad</th>
                      <th className="px-8 py-5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Acción</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-50">
                    {monthlyCompensations.map(c => (
                      <tr key={c.id} className="hover:bg-slate-50/50 transition-colors">
                        <td className="px-8 py-5 text-xs font-bold text-slate-500">{new Date(c.date).toLocaleDateString()}</td>
                        <td className="px-8 py-5"><span className="px-3 py-1 bg-blue-50 text-blue-900 rounded-lg text-[10px] font-black uppercase tracking-tight">{c.type}</span></td>
                        <td className="px-8 py-5 text-right font-black text-slate-800">{formatMinutes(c.minutes)}</td>
                        <td className="px-8 py-5 text-right">
                          <button onClick={() => handleDeleteComp(c.id)} className="text-rose-500 hover:bg-rose-50 p-3 rounded-xl transition-all active:scale-90">
                            <Trash2 className="w-5 h-5" />
                          </button>
                        </td>
                      </tr>
                    ))}
                    {monthlyCompensations.length === 0 && <tr><td colSpan={4} className="py-20 text-center text-slate-300 italic font-medium">No hay compensaciones este mes.</td></tr>}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {activeTab === 'workers' && (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div className="lg:col-span-1">
                <form onSubmit={handleAddWorker} className="bg-white p-8 rounded-[2.5rem] border shadow-sm space-y-5">
                  <h3 className="font-black text-slate-800 flex items-center gap-2 text-lg"><UserPlus className="w-5 h-5 text-blue-900" /> Nuevo Trabajador</h3>
                  <input type="text" value={newWorkerName} onChange={e => setNewWorkerName(e.target.value)} className="w-full border-2 p-4 rounded-2xl font-black outline-none focus:border-blue-900" placeholder="Nombre completo..." required />
                  <button type="submit" className="w-full py-4 bg-blue-900 text-white font-black rounded-2xl shadow-xl hover:bg-blue-950 transition-all active:scale-[0.98]">Añadir Perfil</button>
                </form>
              </div>
              <div className="lg:col-span-2 space-y-4">
                {sortedWorkers.map(w => (
                  <div key={w.id} className={`flex items-center justify-between p-6 rounded-[2rem] border-2 transition-all ${selectedWorkerId === w.id ? 'border-blue-900 shadow-lg shadow-blue-100 ring-4 ring-blue-50' : 'border-slate-100 shadow-sm'} ${w.isActive === false ? 'bg-slate-50 opacity-60 grayscale-[0.8] blur-[0.5px] hover:blur-0 hover:grayscale-0 hover:opacity-100' : 'bg-white'}`}>
                    <div className="flex items-center gap-4">
                      <div className={`w-14 h-14 rounded-2xl flex items-center justify-center font-black text-2xl ${selectedWorkerId === w.id ? 'bg-blue-900 text-white' : 'bg-slate-100 text-slate-400'}`}>
                        {w.isActive === false ? <UserX className="w-6 h-6" /> : w.name.charAt(0)}
                      </div>
                      <div>
                        <div className="flex items-center gap-2">
                            <p className="font-black text-slate-800 text-lg">{w.name}</p>
                            {w.isActive === false && <span className="px-2 py-0.5 bg-slate-200 text-slate-500 rounded text-[9px] font-black uppercase">BAJA</span>}
                        </div>
                        <p className="text-[10px] text-slate-400 font-bold uppercase tracking-widest font-mono">ID: {w.id}</p>
                      </div>
                    </div>
                    <div className="flex gap-2">
                      <button onClick={() => setSelectedWorkerId(w.id)} className={`px-6 py-3 rounded-xl text-[10px] font-black uppercase transition-all ${selectedWorkerId === w.id ? 'bg-blue-50 text-blue-900' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'}`}>
                        {selectedWorkerId === w.id ? 'ACTIVO' : 'SELECCIONAR'}
                      </button>

                      {/* Botón Alta/Baja */}
                      <button
                        onClick={() => handleToggleWorkerStatus(w.id)}
                        title={w.isActive ? "Dar de Baja" : "Dar de Alta"}
                        className={`p-3.5 rounded-xl transition-all active:scale-90 ${w.isActive ? 'text-amber-500 hover:bg-amber-50' : 'text-emerald-500 hover:bg-emerald-50'}`}
                      >
                         <Power className="w-5 h-5" />
                      </button>

                      {/* Botón Borrado Definitivo */}
                      <button onClick={() => handleDeleteWorker(w.id)} className="p-3.5 text-rose-500 hover:bg-rose-50 rounded-xl transition-all active:scale-90" title="Borrar Definitivamente">
                        <Trash2 className="w-5 h-5" />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {activeTab === 'logs' && (
            <div className="bg-white rounded-[2.5rem] border p-10 shadow-sm space-y-6">
              <h3 className="font-black text-xl text-slate-800 pb-4 border-b flex items-center gap-2 text-blue-900"><History className="w-6 h-6" /> Auditoría de Cambios</h3>
              <div className="space-y-4">
                {/* Filtrar logs también por mes si pertenecen a una entrada */}
                {allWorkerEntries
                    .filter(e => {
                        const d = new Date(e.date);
                        return d.getFullYear() === viewYear && d.getMonth() === viewMonth;
                    })
                    .flatMap(e => (e.logs || []).map(l => ({...l, date: e.date})))
                    .sort((a,b) => b.timestamp - a.timestamp)
                    .map(log => (
                  <div key={log.id} className="p-5 bg-slate-50 border border-slate-100 rounded-3xl flex gap-5 items-start transition-all hover:bg-slate-100/50">
                    <div className="bg-white p-3 rounded-xl border text-slate-300 shadow-sm"><History className="w-5 h-5" /></div>
                    <div className="flex-1 text-xs">
                      <div className="flex justify-between items-center mb-2">
                        <p className="font-black text-blue-900 uppercase tracking-tight text-[11px]">Jornada: {log.date}</p>
                        <p className="text-[10px] text-slate-400 font-bold">{new Date(log.timestamp).toLocaleString()}</p>
                      </div>
                      <p className="font-medium text-slate-600 leading-relaxed text-[13px]">
                        Campo <span className="text-slate-900 font-black">"{log.field}"</span> modificado.
                        Valor anterior: <span className="text-slate-400 line-through">"{log.oldValue}"</span> → Nuevo valor: <span className="text-slate-900 font-black">"{log.newValue}"</span>.
                      </p>
                    </div>
                  </div>
                ))}
                {allWorkerEntries.filter(e => {
                    const d = new Date(e.date);
                    return d.getFullYear() === viewYear && d.getMonth() === viewMonth;
                }).flatMap(e => e.logs || []).length === 0 && <div className="text-center py-20 text-slate-300 italic font-medium">Sin registros de auditoría en este mes.</div>}
              </div>
            </div>
          )}
        </div>
      </main>

      {/* Menú Móvil */}
      <nav className="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-slate-100 flex justify-around p-4 md:hidden z-50 shadow-2xl">
        {[
          {id:'daily', icon: Calendar, label: 'Jornada'},
          {id:'balances', icon: ArrowRightLeft, label: 'Compensar'},
          {id:'workers', icon: Users, label: 'Equipo'},
          {id:'logs', icon: History, label: 'Logs'}
        ].map(t => (
          <button key={t.id} onClick={() => setActiveTab(t.id as any)} className={`flex flex-col items-center gap-1.5 px-5 py-2 rounded-2xl transition-all ${activeTab === t.id ? 'text-blue-900 bg-blue-50 shadow-inner' : 'text-slate-400'}`}>
            <t.icon className="w-5 h-5" />
            <span className="text-[8px] font-black uppercase tracking-tighter">{t.label}</span>
          </button>
        ))}
      </nav>

      {/* Modal de Confirmación */}
      {deleteConfirmation && (
        <div className="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm animate-in fade-in duration-200">
          <div className="bg-white rounded-[2rem] p-8 max-w-sm w-full shadow-2xl border-2 border-white scale-100 animate-in zoom-in-95 duration-200">
            <div className="w-16 h-16 bg-rose-50 rounded-full flex items-center justify-center mb-6 mx-auto border-4 border-rose-100">
              <Trash2 className="w-8 h-8 text-rose-500" />
            </div>
            <h3 className="text-xl font-black text-slate-800 text-center mb-3 leading-tight">
              {deleteConfirmation.title}
            </h3>
            <p className="text-slate-500 text-center text-xs font-medium leading-relaxed mb-8 px-2">
              {deleteConfirmation.message}
            </p>
            <div className="grid grid-cols-2 gap-3">
              <button
                onClick={() => setDeleteConfirmation(null)}
                className="py-4 px-4 rounded-2xl font-black text-xs uppercase tracking-wider text-slate-500 hover:bg-slate-50 transition-colors"
              >
                Cancelar
              </button>
              <button
                onClick={executeDelete}
                className="py-4 px-4 rounded-2xl font-black text-xs uppercase tracking-wider text-white bg-rose-500 hover:bg-rose-600 shadow-xl shadow-rose-200 transition-all active:scale-95"
              >
                Sí, Eliminar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default App;
