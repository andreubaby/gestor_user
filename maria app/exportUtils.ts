import * as XLSX from 'xlsx';
import { TimeEntry, CompensationEntry } from './types';
import { calculateDay, formatMinutes } from './utils';

const MONTH_NAMES = [
  "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
  "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
];

interface MonthlyData {
  entries: TimeEntry[];
  compensations: CompensationEntry[];
  year: number;
  month: number;
}

export const exportToExcel = (
  workerName: string,
  entries: TimeEntry[],
  compensations: CompensationEntry[]
) => {
  const wb = XLSX.utils.book_new();

  // 1. Group data by month
  const groupedData: Record<string, MonthlyData> = {};

  entries.forEach(entry => {
    const date = new Date(entry.date);
    const key = `${date.getFullYear()}-${date.getMonth()}`;
    if (!groupedData[key]) {
      groupedData[key] = {
        entries: [],
        compensations: [],
        year: date.getFullYear(),
        month: date.getMonth()
      };
    }
    groupedData[key].entries.push(entry);
  });

  compensations.forEach(comp => {
    const date = new Date(comp.date);
    const key = `${date.getFullYear()}-${date.getMonth()}`;
    if (!groupedData[key]) {
      groupedData[key] = {
        entries: [],
        compensations: [],
        year: date.getFullYear(),
        month: date.getMonth()
      };
    }
    groupedData[key].compensations.push(comp);
  });

  // Sort keys to have months in order
  const sortedKeys = Object.keys(groupedData).sort((a, b) => {
    const [y1, m1] = a.split('-').map(Number);
    const [y2, m2] = b.split('-').map(Number);
    return y1 === y2 ? m1 - m2 : y1 - y2;
  });

  const summaryRows: any[] = [];

  // 2. Create sheets for each month
  sortedKeys.forEach(key => {
    const data = groupedData[key];
    const sheetName = `${MONTH_NAMES[data.month]} ${data.year}`;
    
    // Sort entries by date
    data.entries.sort((a, b) => a.date.localeCompare(b.date));
    data.compensations.sort((a, b) => a.date.localeCompare(b.date));

    // Calculate monthly stats
    let totalExtra = 0;
    let totalDeficit = 0;
    let totalPaid = 0;
    let totalRested = 0;
    let totalDays = 0;

    const entryRows = data.entries.map(entry => {
      const calc = calculateDay(entry.hoursBrutas, entry.lunchDiscount, entry.isFreeDay);
      totalExtra += calc.extra;
      totalDeficit += calc.deficit;

      return {
        Fecha: entry.date,
        'Horas Brutas': formatMinutes(entry.hoursBrutas),
        'Descanso': formatMinutes(entry.lunchDiscount),
        'Horas Netas': formatMinutes(calc.netas),
        'Horas Ordinarias': formatMinutes(calc.ordinarias),
        'Horas Extra': formatMinutes(calc.extra),
        'Déficit': formatMinutes(calc.deficit),
        'Notas': entry.notes || '',
        'Tipo': entry.isFreeDay ? 'Día Libre / Festivo' : 'Jornada'
      };
    });

    data.compensations.forEach(comp => {
      if (comp.type === 'PAYMENT') totalPaid += comp.minutes;
      else {
        totalRested += comp.minutes;
        if (comp.type === 'FREE_DAY') totalDays++;
      }
    });

    const balance = totalExtra - totalDeficit - totalPaid - totalRested;

    // Add to global summary
    summaryRows.push({
      Periodo: sheetName,
      'Total Extra': formatMinutes(totalExtra),
      'Total Déficit': formatMinutes(totalDeficit),
      'Pagado': formatMinutes(totalPaid),
      'Descansado': formatMinutes(totalRested),
      'Balance Mensual': formatMinutes(balance)
    });

    // Create worksheet data
    const wsData = [
      [`Reporte Mensual: ${sheetName}`],
      [`Trabajador: ${workerName}`],
      [],
      ['REGISTRO DE JORNADAS'],
      ...XLSX.utils.json_to_sheet(entryRows).data || [], // This might be tricky with json_to_sheet, let's use aoa
    ];

    // Let's build AOA (Array of Arrays) manually for better control
    const wsAOA: any[][] = [
      [`Reporte Mensual: ${sheetName}`],
      [`Trabajador: ${workerName}`],
      [],
      ['REGISTRO DE JORNADAS'],
      ['Fecha', 'Tipo', 'Horas Brutas', 'Descanso', 'Horas Netas', 'Horas Ordinarias', 'Horas Extra', 'Déficit', 'Notas']
    ];

    entryRows.forEach(r => {
      wsAOA.push([
        r.Fecha,
        r.Tipo,
        r['Horas Brutas'],
        r.Descanso,
        r['Horas Netas'],
        r['Horas Ordinarias'],
        r['Horas Extra'],
        r.Déficit,
        r.Notas
      ]);
    });

    wsAOA.push([]);
    wsAOA.push(['COMPENSACIONES Y PAGOS']);
    wsAOA.push(['Fecha', 'Tipo', 'Cantidad', 'Notas']);

    data.compensations.forEach(comp => {
      wsAOA.push([
        comp.date,
        comp.type === 'PAYMENT' ? 'PAGO' : (comp.type === 'FREE_DAY' ? 'DÍA LIBRE' : 'DESCANSO'),
        formatMinutes(comp.minutes),
        comp.notes || ''
      ]);
    });

    wsAOA.push([]);
    wsAOA.push(['RESUMEN DEL MES']);
    wsAOA.push(['Concepto', 'Horas']);
    wsAOA.push(['Total Horas Extra Generadas', formatMinutes(totalExtra)]);
    wsAOA.push(['Total Déficit Generado', formatMinutes(totalDeficit)]);
    wsAOA.push(['Total Horas Pagadas', formatMinutes(totalPaid)]);
    wsAOA.push(['Total Horas Descansadas', formatMinutes(totalRested)]);
    wsAOA.push(['BALANCE FINAL MES', formatMinutes(balance)]);

    const ws = XLSX.utils.aoa_to_sheet(wsAOA);
    
    // Set column widths
    ws['!cols'] = [
      { wch: 12 }, // Date
      { wch: 20 }, // Type
      { wch: 12 }, // Brutas
      { wch: 12 }, // Lunch
      { wch: 12 }, // Netas
      { wch: 15 }, // Ordinarias
      { wch: 12 }, // Extra
      { wch: 12 }, // Deficit
      { wch: 30 }  // Notes
    ];

    XLSX.utils.book_append_sheet(wb, ws, sheetName.substring(0, 31)); // Sheet names max 31 chars
  });

  // 3. Create Summary Sheet
  const summaryWS = XLSX.utils.json_to_sheet(summaryRows);
  
  // Calculate Grand Totals
  let grandExtra = 0;
  let grandDeficit = 0;
  let grandPaid = 0;
  let grandRested = 0;

  sortedKeys.forEach(key => {
    const data = groupedData[key];
    data.entries.forEach(e => {
      const c = calculateDay(e.hoursBrutas, e.lunchDiscount, e.isFreeDay);
      grandExtra += c.extra;
      grandDeficit += c.deficit;
    });
    data.compensations.forEach(c => {
      if (c.type === 'PAYMENT') grandPaid += c.minutes;
      else grandRested += c.minutes;
    });
  });

  const grandBalance = grandExtra - grandDeficit - grandPaid - grandRested;

  XLSX.utils.sheet_add_aoa(summaryWS, [
    [],
    ['TOTALES GLOBALES'],
    ['Total Extra Acumulado', formatMinutes(grandExtra)],
    ['Total Déficit Acumulado', formatMinutes(grandDeficit)],
    ['Total Pagado', formatMinutes(grandPaid)],
    ['Total Descansado', formatMinutes(grandRested)],
    ['BALANCE TOTAL', formatMinutes(grandBalance)]
  ], { origin: -1 });

  summaryWS['!cols'] = [
    { wch: 20 }, // Periodo
    { wch: 15 }, // Extra
    { wch: 15 }, // Deficit
    { wch: 15 }, // Paid
    { wch: 15 }, // Rested
    { wch: 15 }  // Balance
  ];

  // Add Summary sheet at the beginning
  XLSX.utils.book_append_sheet(wb, summaryWS, "RESUMEN TOTAL");

  // Move Summary sheet to first position
  wb.SheetNames.unshift(wb.SheetNames.pop()!);

  // 4. Write file
  XLSX.writeFile(wb, `Reporte_Horas_${workerName.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`);
};
