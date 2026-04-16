
export const parseHHMM = (hhmm: string): number => {
  const [h, m] = hhmm.split(':').map(Number);
  return (h || 0) * 60 + (m || 0);
};

export const formatMinutes = (minutes: number): string => {
  const isNegative = minutes < 0;
  const absMins = Math.abs(minutes);
  const h = Math.floor(absMins / 60);
  const m = absMins % 60;
  return `${isNegative ? '-' : ''}${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
};

export const calculateDay = (brutas: number, lunch: number, isFreeDay: boolean = false): any => {
  if (isFreeDay) {
    return {
      brutas: 0,
      lunch: 0,
      netas: 0,
      ordinarias: 0,
      extra: 0,
      deficit: 0,
      explanation: 'Día marcado como libre / no laborable (sin impacto en balance).'
    };
  }

  const netas = Math.max(0, brutas - lunch);
  const ordinarias = Math.min(480, netas); // 8h = 480m
  const extra = Math.max(0, netas - 480);
  const deficit = Math.max(0, 480 - netas);

  const explanation = `${formatMinutes(netas)} netas → ${formatMinutes(ordinarias)} ordinarias + ${formatMinutes(extra)} extra; comida ${formatMinutes(lunch)} aplicada.`;

  return { brutas, lunch, netas, ordinarias, extra, deficit, explanation };
};
