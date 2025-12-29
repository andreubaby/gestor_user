let AUS = {
    nombre: '',
    data: null,
    bucketYear: null,
    tab: 'vacaciones',
    selected: {
        vacaciones: new Set(),
        permiso: new Set(),
        baja: new Set(),
    },
    rangeStart: null,
    rangeEnd: null,
};

let CAL = { year: null };

function setLoading(isLoading){
    const el = document.getElementById('loading');
    const btn = document.getElementById('btnBuscar');
    if (!el) return;
    if (isLoading) {
        el.classList.remove('hidden');
        if (btn) btn.disabled = true;
    } else {
        el.classList.add('hidden');
        if (btn) btn.disabled = false;
    }
}

function toastCopied(){
    const t = document.getElementById('toast');
    if (!t) return;
    t.classList.remove('hidden');
    setTimeout(()=>t.classList.add('hidden'), 1000);
}

function toastMsg(msg){
    const t = document.getElementById('toast');
    if (!t) return alert(msg);

    t.textContent = msg;
    t.classList.remove('hidden');

    // posición tipo alerta
    t.classList.remove('bottom-6','right-6');
    t.classList.add('top-6','left-1/2','-translate-x-1/2');

    setTimeout(()=>{
        t.classList.add('hidden');
        t.classList.remove('top-6','left-1/2','-translate-x-1/2');
        t.classList.add('bottom-6','right-6');
    }, 1600);
}

async function copyToClipboard(text){
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
        toastCopied();
    } catch (e) {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        toastCopied();
    }
}

function goRow(url, ev){
    const tag = (ev?.target?.tagName || '').toLowerCase();
    if (tag === 'a' || tag === 'button' || ev?.target?.closest('a,button')) return;
    setLoading(true);
    window.location.href = url;
}

// ---------------- MODAL ----------------

async function openAusenciasModal(btnEl, tab){
    const tr = btnEl.closest('tr');
    const nombre = tr?.dataset?.nombre || 'Trabajador';
    const workerId = tr?.dataset?.worker;

    AUS = {
        nombre,
        data: { vacaciones:{count:0,items:[]}, permiso:{count:0,items:[]}, baja:{count:0,items:[]} },
        tab: tab || 'vacaciones',
        selected: { vacaciones:new Set(), permiso:new Set(), baja:new Set() },
        rangeStart: null,
        rangeEnd: null,
        workerId: workerId,
    };

    document.getElementById('ausTitle').textContent = `Ausencias · ${nombre}`;

    const m = document.getElementById('ausModal');
    m.classList.remove('hidden');
    m.classList.add('flex');


    initYearPickers();
    await fetchAusenciasYear(CAL.year, workerId);   // ✅ trae datos del usuario
    switchAusTab(AUS.tab);
}

function initYearPickers(){
    const now = new Date();
    // Año que se ve
    CAL.year = (CAL.year ?? now.getFullYear());
    // Año al que se imputan los días (por defecto, el mismo que ves)
    AUS.bucketYear = (AUS.bucketYear ?? CAL.year);

    syncYearLabels();
}

function syncYearLabels(){
    const calLbl = document.getElementById('calYearLabel');
    const buckLbl = document.getElementById('bucketYearLabel');
    if (calLbl) calLbl.textContent = String(CAL.year);
    if (buckLbl) buckLbl.textContent = String(AUS.bucketYear);
}

/* ===== Año calendario (solo visual) ===== */
async function prevCalendarYear(){
    CAL.year--;
    await fetchAusenciasYear(CAL.year, AUS.workerId);
    renderAusencias();
    syncYearLabels();
}

async function nextCalendarYear(){
    CAL.year++;
    await fetchAusenciasYear(CAL.year, AUS.workerId);
    renderAusencias();
    syncYearLabels();
}

/* ===== Año bucket (solo imputación/guardado) ===== */
function prevBucketYear(){
    AUS.bucketYear--;
    syncYearLabels();
}

function nextBucketYear(){
    AUS.bucketYear++;
    syncYearLabels();
}
async function fetchAusenciasYear(year, workerId){
    const url = window.APP.routes.getDays.replace('__ID__', workerId) + `?calendar_year=${year}`;

    const res = await fetch(url, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
    });

    const json = await res.json();
    if (!json.ok) {
        toastMsg('⚠️ No se pudieron cargar las ausencias');
        return;
    }

    AUS.data = json.data;
}

function closeAusenciasModal(){
    const m = document.getElementById('ausModal');
    m.classList.add('hidden');
    m.classList.remove('flex');

    AUS = {
        nombre: '',
        data: null,
        tab: 'vacaciones',
        selected: {
            vacaciones: new Set(),
            permiso: new Set(),
            baja: new Set(),
        },
        rangeStart: null,
        rangeEnd: null,
    };
    CAL = { year: null };
}

function countDaysInItems(items){
    const set = buildBusySet(items || []);
    return set.size;
}

function updateTabBadges(){
    const vacBack = countDaysInItems(AUS.data?.vacaciones?.items);
    const perBack = countDaysInItems(AUS.data?.permiso?.items);
    const bajBack = countDaysInItems(AUS.data?.baja?.items);

    const tabVac = document.getElementById('tabVac');
    const tabPer = document.getElementById('tabPer');
    const tabBaj = document.getElementById('tabBaj');

    if (tabVac) tabVac.innerHTML = `🏖 Vacaciones <span class="ml-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-white/70 ring-1 ring-black/10">${vacBack}</span>`;
    if (tabPer) tabPer.innerHTML = `📝 Permisos <span class="ml-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-white/70 ring-1 ring-black/10">${perBack}</span>`;
    if (tabBaj) tabBaj.innerHTML = `🏥 Bajas <span class="ml-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-white/70 ring-1 ring-black/10">${bajBack}</span>`;
}

function applyTabStyles(){
    const tabVac = document.getElementById('tabVac');
    const tabPer = document.getElementById('tabPer');
    const tabBaj = document.getElementById('tabBaj');

    const base = "px-3 py-1.5 rounded-lg text-sm font-medium transition focus:outline-none";

    const vacInactive = "bg-blue-50 text-blue-700 ring-1 ring-blue-200 hover:bg-blue-100";
    const vacActive   = "bg-blue-100 text-blue-800 ring-2 ring-blue-300";

    const perInactive = "bg-amber-50 text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100";
    const perActive   = "bg-amber-100 text-amber-900 ring-2 ring-amber-300";

    const bajInactive = "bg-red-50 text-red-700 ring-1 ring-red-200 hover:bg-red-100";
    const bajActive   = "bg-red-100 text-red-800 ring-2 ring-red-300";

    if (tabVac) tabVac.className = `${base} ${AUS.tab==='vacaciones' ? vacActive : vacInactive}`;
    if (tabPer) tabPer.className = `${base} ${AUS.tab==='permiso'    ? perActive : perInactive}`;
    if (tabBaj) tabBaj.className = `${base} ${AUS.tab==='baja'       ? bajActive : bajInactive}`;
}

function switchAusTab(tab){
    AUS.tab = tab;
    AUS.rangeStart = null;
    AUS.rangeEnd = null;

    applyTabStyles();
    updateTabBadges?.(); // si lo tienes
    renderAusencias();
}

// ---------------- CALENDARIO ANUAL ----------------

function initCalendarControls(){
    const ySel = document.getElementById('calYear');

    const now = new Date();
    CAL.year = (CAL.year ?? now.getFullYear());

    const start = now.getFullYear() - 2;
    const end   = now.getFullYear() + 2;

    ySel.innerHTML = '';
    for (let y = start; y <= end; y++){
        const opt = document.createElement('option');
        opt.value = String(y);
        opt.textContent = String(y);
        if (y === CAL.year) opt.selected = true;
        ySel.appendChild(opt);
    }

    ySel.onchange = async () => {
        CAL.year = parseInt(ySel.value, 10);
        await fetchAusenciasYear(CAL.year, AUS.workerId);
        renderAusencias();
    };
}

async function prevYear(){
    CAL.year--;
    syncYearSelect();
    await fetchAusenciasYear(CAL.year, AUS.workerId);
    renderAusencias();
}
async function nextYear(){
    CAL.year++;
    syncYearSelect();
    await fetchAusenciasYear(CAL.year, AUS.workerId);
    renderAusencias();
}

function syncYearSelect(){
    const ySel = document.getElementById('calYear');
    if (ySel) ySel.value = String(CAL.year);
}

function renderAusencias(){
    const sub = document.getElementById('ausSub');
    const yearGrid = document.getElementById('calYearGrid');
    if (!yearGrid) return;

    const t = AUS.tab;

    const items = (AUS.data && AUS.data[t] && AUS.data[t].items) ? AUS.data[t].items : [];
    const count = (AUS.data && AUS.data[t] && typeof AUS.data[t].count !== 'undefined') ? AUS.data[t].count : 0;

    const label = (t === 'vacaciones') ? 'Vacaciones' : (t === 'permiso' ? 'Permisos' : 'Bajas');
    if (sub) sub.textContent = `${label} · ${count} día(s) · ${CAL.year}`;

    // ✅ IMPORTANTE: busy de TODAS las categorías (no solo del tab actual)
    const busyMap = buildGlobalBusyMap();

    const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    updateTabBadges();

    yearGrid.innerHTML = '';
    for (let month = 0; month < 12; month++){
        const card = document.createElement('div');
        card.className = "rounded-xl ring-1 ring-gray-200 bg-white overflow-hidden";

        card.innerHTML = `
                <div class="px-4 py-2.5 bg-gray-50 flex items-center justify-between">
                    <div class="text-base font-semibold text-gray-800">${monthNames[month]}</div>
                    <div class="text-xs text-gray-500">${CAL.year}</div>
                </div>
                <div class="grid grid-cols-7 bg-white text-xs font-semibold text-gray-500 border-t">
                    <div class="p-2 text-center">L</div><div class="p-2 text-center">M</div><div class="p-2 text-center">X</div>
                    <div class="p-2 text-center">J</div><div class="p-2 text-center">V</div><div class="p-2 text-center">S</div><div class="p-2 text-center">D</div>
                </div>
                <div class="grid grid-cols-7" data-month-grid="${month}"></div>
            `;

        yearGrid.appendChild(card);

        const grid = card.querySelector(`[data-month-grid="${month}"]`);
        // ✅ pasamos busyMap en vez de busySet
        renderMonthGrid(grid, CAL.year, month, busyMap, t);
    }
    renderVacPdfButtons();
    renderPerPdfButtons();
    renderBajPdfButtons();
}

function tabToTipo(tab){
    if (tab === 'vacaciones') return 'V';
    if (tab === 'permiso') return 'P';
    return 'B';
}

async function saveRangeToServer({workerId, year, tab, from, to, mode}) {
    const url = window.APP.routes.storeDays.replace('__ID__', workerId);

    const payload = {
        calendar_year: year,        // año que estás viendo (CAL.year)
        tipo: tabToTipo(tab),
        from,
        to,
        mode,
    };

    // ✅ Solo en vacaciones permitimos imputar a otro año
    if (tab === 'vacaciones') {
        payload.bucket_year = AUS.bucketYear ?? year; // 2025 o 2026 (lo que elijas)
    }

    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.APP.csrf,
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });

    const json = await res.json();
    if (!json.ok) {
        toastMsg('⚠️ Error guardando el rango');
        return false;
    }

    AUS.data = json.data;
    if (AUS.bucketYear !== CAL.year) {
        // opcional: aquí podrías guardar en un cache por año si luego lo implementas
    }
    updateRowCounts(workerId, json.data);
    return true;
}

function downloadCalendarPdf(){
    const tipo = tabToTipo(AUS.tab);
    const routeKey = (tipo === 'V') ? 'pdfVac' : (tipo === 'P') ? 'pdfPer' : 'pdfBaj';
    const urlBase = window.APP.routes[routeKey].replace('__ID__', AUS.workerId);

    // OJO: el PDF debe ir con el AÑO DE ASIGNACIÓN, no el visual
    const url = `${urlBase}?vacation_year=${encodeURIComponent(AUS.bucketYear)}&tipo=${encodeURIComponent(tipo)}`;
    window.open(url, '_blank', 'noopener');
}

function renderMonthGrid(gridEl, year, month, busyMap, tab){
    const first = new Date(year, month, 1);
    const last  = new Date(year, month + 1, 0);
    const daysInMonth = last.getDate();

    const jsDay = first.getDay();
    const offset = (jsDay + 6) % 7;

    const totalCells = Math.ceil((offset + daysInMonth) / 7) * 7;

    // ✅ sets backend
    const busyVac = busyMap.vacaciones;
    const busyPer = busyMap.permiso;
    const busyBaj = busyMap.baja;

    // ✅ set backend del tab actual (para permitir quitar)
    const busyCurrent =
        tab === 'vacaciones' ? busyVac :
            tab === 'permiso'    ? busyPer :
                busyBaj;

    // ✅ backend de otras tabs (para bloquear)
    const busyOther = new Set();
    ['vacaciones','permiso','baja'].forEach(t => {
        if (t === tab) return;
        busyMap[t].forEach(d => busyOther.add(d));
    });

    gridEl.innerHTML = '';
    for (let i = 0; i < totalCells; i++){
        const dayNum = i - offset + 1;

        const cell = document.createElement('button');
        cell.type = 'button';
        cell.className =
            "h-12 sm:h-12 border-t border-gray-100 flex items-center justify-center text-sm " +
            "hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary/30";

        if (dayNum < 1 || dayNum > daysInMonth){
            cell.disabled = true;
            cell.className += " text-gray-300 cursor-default bg-white";
            cell.textContent = "";
            gridEl.appendChild(cell);
            continue;
        }

        const dateStr = formatDate(year, month+1, dayNum);

        // selecciones (se pintan siempre)
        const selVac = AUS.selected.vacaciones.has(dateStr);
        const selPer = AUS.selected.permiso.has(dateStr);
        const selBaj = AUS.selected.baja.has(dateStr);

        const isSelectedAny = selVac || selPer || selBaj;
        const isSelectedCurrent = AUS.selected[tab].has(dateStr);
        const isBlockedByOtherSelection = isSelectedAny && !isSelectedCurrent;

        // ✅ busy (backend) para las 3 categorías (se pintan siempre)
        const isBusyVac = busyVac.has(dateStr);
        const isBusyPer = busyPer.has(dateStr);
        const isBusyBaj = busyBaj.has(dateStr);

        // ✅ ocupado por OTRAS categorías (backend)
        const isBusyOtherBackend = busyOther.has(dateStr);

        // ✅ ocupado por tab actual (backend) -> permitir click para quitar
        const isBusyCurrentBackend = busyCurrent.has(dateStr);

        const isRangeMark = (dateStr === AUS.rangeStart);

        let pillCls = "w-9 h-9 sm:w-9 sm:h-9 rounded-full flex items-center justify-center ";

        // prioridad visual:
        // 1) seleccionado (tu selección actual en UI)
        if (selVac) pillCls += selectedColorForTab('vacaciones');
        else if (selPer) pillCls += selectedColorForTab('permiso');
        else if (selBaj) pillCls += selectedColorForTab('baja');

        // 2) si no hay selección, pintar busy EXISTENTE de cualquier tipo
        else if (isBusyVac) pillCls += busyColorForTab('vacaciones');
        else if (isBusyPer) pillCls += busyColorForTab('permiso');
        else if (isBusyBaj) pillCls += busyColorForTab('baja');
        else pillCls += "bg-white text-gray-800";

        // ✅ si está bloqueado por otra selección o por backend de otra categoría
        const blockedMark = (isBlockedByOtherSelection || isBusyOtherBackend)
            ? "opacity-80 ring-1 ring-black/10"
            : "";

        // ✅ si está ocupado por otra categoría (backend) lo deshabilito totalmente
        // (si prefieres que deje clicar y muestre toast, quita este disabled y lo controlas en onclick)
        if (isBusyOtherBackend) {
            cell.disabled = true;
            cell.className += " cursor-not-allowed opacity-70";
        }

        cell.innerHTML = `<span class="${pillCls} ${blockedMark} ${isRangeMark ? 'ring-2 ring-primary' : ''}">${dayNum}</span>`;

        cell.onclick = (ev) => {
            ev.stopPropagation();

            if (isBlockedByOtherSelection){
                toastMsg('⚠️ Ese día ya está seleccionado en otra categoría.');
                return;
            }

            // (si no lo deshabilitas arriba) bloquea backend de otra categoría:
            if (isBusyOtherBackend){
                toastMsg('⚠️ Ese día está ocupado por otra ausencia.');
                return;
            }

            handleDayClick(dateStr);
            renderAusencias();
        };

        gridEl.appendChild(cell);
    }
}

function clearSelection(){
    AUS.selected[AUS.tab] = new Set();
    AUS.rangeStart = null;
    AUS.rangeEnd = null;
    updateTabBadges();
    renderAusencias();
}

function debugSelection(){
    const tab = AUS.tab;
    const arr = Array.from(AUS.selected[tab]).sort();
    alert(`Seleccionados ${tab} (${arr.length}):\n` + arr.join('\n'));
}

function renderVacPdfButtons(){
    const wrap = document.getElementById('vacPdfWrap');
    const box  = document.getElementById('vacPdfButtons');
    if (!wrap || !box) return;

    // Solo visible en tab vacaciones
    if (AUS.tab !== 'vacaciones'){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    const items = AUS.data?.vacaciones?.items || [];
    if (!items.length){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    // calcula primer y último día real de vacaciones
    const days = new Set();
    items.forEach(it => {
        const from = it.from ?? it.fecha;
        const to   = it.to   ?? it.fecha;
        if (!from) return;

        const s = parseISO(from);
        const e = parseISO(to || from);
        if (!s || !e) return;

        const cur = new Date(s.getTime());
        while (cur <= e){
            days.add(formatDate(cur.getFullYear(), cur.getMonth()+1, cur.getDate()));
            cur.setDate(cur.getDate() + 1);
        }
    });

    const sorted = Array.from(days).sort();
    if (!sorted.length){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    const from = sorted[0];
    const to   = sorted[sorted.length - 1];

    wrap.classList.remove('hidden');
    box.innerHTML = '';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = "px-3 py-2 rounded-lg text-sm font-medium bg-blue-50 text-blue-700 ring-1 ring-blue-200 hover:bg-blue-100 transition";
    btn.textContent = `📄 PDF (todas) (${fmtDM(from)}–${fmtDM(to)})`;

    btn.onclick = (ev) => {
        ev.stopPropagation();

        const urlBase = window.APP.routes.pdfVac.replace('__ID__', AUS.workerId);

        // 👇 no pases rangos aquí: que el backend coja TODOS los rangos del año
        const url = `${urlBase}?vacation_year=${encodeURIComponent(AUS.bucketYear)}&tipo=V`;
        window.open(url, '_blank', 'noopener');
    };

    box.appendChild(btn);
}

function renderPerPdfButtons(){
    const wrap = document.getElementById('perPdfWrap');
    const box  = document.getElementById('perPdfButtons');
    if (!wrap || !box) return;

    // Solo visible en tab permisos
    if (AUS.tab !== 'permiso'){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    const items = AUS.data?.permiso?.items || [];
    if (!items.length){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    // calcula primer y último día real de permisos
    const days = new Set();
    items.forEach(it => {
        const from = it.from ?? it.fecha;
        const to   = it.to   ?? it.fecha;
        if (!from) return;

        const s = parseISO(from);
        const e = parseISO(to || from);
        if (!s || !e) return;

        const cur = new Date(s.getTime());
        while (cur <= e){
            days.add(formatDate(cur.getFullYear(), cur.getMonth()+1, cur.getDate()));
            cur.setDate(cur.getDate() + 1);
        }
    });

    const sorted = Array.from(days).sort();
    if (!sorted.length){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    const from = sorted[0];
    const to   = sorted[sorted.length - 1];

    wrap.classList.remove('hidden');
    box.innerHTML = '';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = "px-3 py-2 rounded-lg text-sm font-medium bg-amber-50 text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100 transition";
    btn.textContent = `📄 PDF (todas) (${fmtDM(from)}–${fmtDM(to)})`;

    btn.onclick = (ev) => {
        ev.stopPropagation();

        const urlBase = window.APP.routes.pdfPer.replace('__ID__', AUS.workerId);

        // backend coge TODOS los rangos del año
        const url = `${urlBase}?vacation_year=${encodeURIComponent(CAL.year)}&tipo=P`;
        window.open(url, '_blank', 'noopener');
    };

    box.appendChild(btn);
}

function renderBajPdfButtons(){
    const wrap = document.getElementById('bajPdfWrap');
    const box  = document.getElementById('bajPdfButtons');
    if (!wrap || !box) return;

    // Solo visible en tab bajas
    if (AUS.tab !== 'baja'){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    const items = AUS.data?.baja?.items || [];
    if (!items.length){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    // calcula primer y último día real de bajas
    const days = new Set();
    items.forEach(it => {
        const from = it.from ?? it.fecha;
        const to   = it.to   ?? it.fecha;
        if (!from) return;

        const s = parseISO(from);
        const e = parseISO(to || from);
        if (!s || !e) return;

        const cur = new Date(s.getTime());
        while (cur <= e){
            days.add(formatDate(cur.getFullYear(), cur.getMonth()+1, cur.getDate()));
            cur.setDate(cur.getDate() + 1);
        }
    });

    const sorted = Array.from(days).sort();
    if (!sorted.length){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    const from = sorted[0];
    const to   = sorted[sorted.length - 1];

    wrap.classList.remove('hidden');
    box.innerHTML = '';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = "px-3 py-2 rounded-lg text-sm font-medium bg-red-50 text-red-700 ring-1 ring-red-200 hover:bg-red-100 transition";
    btn.textContent = `📄 PDF (todas) (${fmtDM(from)}–${fmtDM(to)})`;

    btn.onclick = (ev) => {
        ev.stopPropagation();

        const urlBase = window.APP.routes.pdfBaj.replace('__ID__', AUS.workerId);

        // backend coge TODOS los rangos del año
        const url = `${urlBase}?vacation_year=${encodeURIComponent(CAL.year)}&tipo=B`;
        window.open(url, '_blank', 'noopener');
    };

    box.appendChild(btn);
}

function computeFromToFromItems(items){
    const days = new Set();

    (items || []).forEach(it => {
        const from = it.from ?? it.fecha;
        const to   = it.to   ?? it.fecha;
        if (!from) return;

        const s = parseISO(from);
        const e = parseISO(to || from);
        if (!s || !e) return;

        const cur = new Date(s.getTime());
        while (cur <= e){
            days.add(formatDate(cur.getFullYear(), cur.getMonth()+1, cur.getDate()));
            cur.setDate(cur.getDate() + 1);
        }
    });

    const sorted = Array.from(days).sort();
    if (!sorted.length) return null;

    return { from: sorted[0], to: sorted[sorted.length - 1] };
}

function renderPdfBlock({tab, wrapId, boxId, routeKey, tipoParam, btnClass, titlePrefix}){
    const wrap = document.getElementById(wrapId);
    const box  = document.getElementById(boxId);
    if (!wrap || !box) return;

    if (AUS.tab !== tab){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    const items = AUS.data?.[tab]?.items || [];
    if (!items.length){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    const range = computeFromToFromItems(items);
    if (!range){
        wrap.classList.add('hidden');
        box.innerHTML = '';
        return;
    }

    wrap.classList.remove('hidden');
    box.innerHTML = '';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = btnClass;
    btn.textContent = `📄 PDF (todas) (${fmtDM(range.from)}–${fmtDM(range.to)})`;

    btn.onclick = (ev) => {
        ev.stopPropagation();
        const urlBase = window.APP.routes[routeKey].replace('__ID__', AUS.workerId);
        const url = `${urlBase}?vacation_year=${encodeURIComponent(CAL.year)}&tipo=${encodeURIComponent(tipoParam)}`;
        window.open(url, '_blank', 'noopener');
    };

    box.appendChild(btn);
}

function splitItemsInto7DayChunks(items){
    // items: [{from,to} o {fecha}] -> lista de bloques de 7 días max
    const days = new Set();

    (items || []).forEach(it => {
        const from = it.from ?? it.fecha;
        const to   = it.to   ?? it.fecha;
        if (!from) return;

        const s = parseISO(from);
        const e = parseISO(to || from);
        if (!s || !e) return;

        const cur = new Date(s.getTime());
        while (cur <= e){
            days.add(formatDate(cur.getFullYear(), cur.getMonth()+1, cur.getDate()));
            cur.setDate(cur.getDate() + 1);
        }
    });

    const sorted = Array.from(days).sort(); // YYYY-MM-DD ordena bien
    if (!sorted.length) return [];

    const out = [];
    let i = 0;

    while (i < sorted.length){
        // Intentar hacer grupo de 7 días consecutivos
        const start = sorted[i];
        let end = start;

        let count = 1;
        let prev = start;

        while (count < 7 && (i + 1) < sorted.length){
            const next = sorted[i + 1];
            if (!isNextDay(prev, next)) break;
            i++;
            prev = next;
            end = next;
            count++;
        }

        out.push({ from: start, to: end });
        i++;
    }

    return out;
}

function isNextDay(a, b){
    const da = parseISO(a);
    const db = parseISO(b);
    if (!da || !db) return false;
    const x = new Date(da.getTime()); x.setDate(x.getDate() + 1);
    return formatDate(x.getFullYear(), x.getMonth()+1, x.getDate()) === b;
}

function fmtDM(iso){
    const d = parseISO(iso);
    if (!d) return iso;
    const dd = String(d.getDate()).padStart(2,'0');
    const mm = String(d.getMonth()+1).padStart(2,'0');
    return `${dd}/${mm}`;
}

// ---- Helpers ----

function updateRowCounts(workerId, data){
    const vac = data?.vacaciones?.count ?? 0;
    const per = data?.permiso?.count ?? 0;
    const baj = data?.baja?.count ?? 0;

    const vacEl = document.getElementById(`vacCount-${workerId}`);
    const perEl = document.getElementById(`perCount-${workerId}`);
    const bajEl = document.getElementById(`bajCount-${workerId}`);

    if (vacEl) vacEl.textContent = vac;
    if (perEl) perEl.textContent = per;
    if (bajEl) bajEl.textContent = baj;
}

function buildBusySet(items){
    const set = new Set();
    (items || []).forEach(it => {
        const from = it.from ?? it.fecha;
        const to   = it.to ?? it.fecha;
        if (!from) return;

        const start = parseISO(from);
        const end   = parseISO(to || from);
        if (!start || !end) return;

        const cur = new Date(start.getTime());
        while (cur <= end){
            set.add(formatDate(cur.getFullYear(), cur.getMonth()+1, cur.getDate()));
            cur.setDate(cur.getDate() + 1);
        }
    });
    return set;
}

function buildBusySetForTab(tab){
    const items = (AUS.data && AUS.data[tab] && AUS.data[tab].items) ? AUS.data[tab].items : [];
    return buildBusySet(items);
}

function buildGlobalBusyMap(){
    return {
        vacaciones: buildBusySetForTab('vacaciones'),
        permiso:    buildBusySetForTab('permiso'),
        baja:       buildBusySetForTab('baja'),
    };
}

function occupiedByOtherTabs(currentTab){
    const busy = buildGlobalBusyMap();

    const occ = new Set();
    ['vacaciones','permiso','baja'].forEach(t => {
        if (t === currentTab) return;
        busy[t].forEach(d => occ.add(d));          // existente backend
        AUS.selected[t].forEach(d => occ.add(d));  // seleccionado sesión
    });

    return occ;
}

function rangeDates(a, b){
    const start = parseISO(a);
    const end   = parseISO(b);
    if (!start || !end) return [];

    const s = start <= end ? start : end;
    const e = start <= end ? end : start;

    const out = [];
    const cur = new Date(s.getTime());
    while (cur <= e){
        out.push(formatDate(cur.getFullYear(), cur.getMonth()+1, cur.getDate()));
        cur.setDate(cur.getDate() + 1);
    }
    return out;
}

function selectedColorForTab(tab){
    if (tab === 'vacaciones') return "bg-emerald-200 text-emerald-900 font-semibold";
    if (tab === 'permiso')    return "bg-yellow-200 text-yellow-900 font-semibold";
    return "bg-red-200 text-red-900 font-semibold";
}

function unionBusyAllTabs(){
    const m = buildGlobalBusyMap(); // {vacaciones:Set, permiso:Set, baja:Set}
    const all = new Set();
    Object.values(m).forEach(set => set.forEach(d => all.add(d)));
    return all;
}

// “ocupado global” = cualquier ausencia ya existente (backend)
function occupiedByAnyBackend(){
    return unionBusyAllTabs();
}

function initBucketYearControl(){
    const wrap = document.getElementById('bucketYearWrap');
    const sel  = document.getElementById('bucketYear');
    if (!wrap || !sel) return;

    // Solo visible en vacaciones
    if (AUS.tab !== 'vacaciones'){
        wrap.classList.add('hidden');
        return;
    }

    wrap.classList.remove('hidden');
    sel.innerHTML = '';

    // Opciones típicas: año actual del calendario y el anterior
    const options = [CAL.year - 1, CAL.year];

    options.forEach(y => {
        const opt = document.createElement('option');
        opt.value = String(y);
        opt.textContent = String(y);
        sel.appendChild(opt);
    });

    // Por defecto: imputar al año del calendario
    AUS.bucketYear = AUS.bucketYear ?? CAL.year;
    sel.value = String(AUS.bucketYear);

    sel.onchange = () => {
        AUS.bucketYear = parseInt(sel.value, 10);
        renderAusencias();
    };
}

// modo rango con toggle:
// - si TODO el rango ya estaba seleccionado en la pestaña actual -> borra
// - si no -> añade
async function handleDayClick(dateStr){
    const tab = AUS.tab;

    const busyMap = buildGlobalBusyMap();
    const busyCurrent =
        tab === 'vacaciones' ? busyMap.vacaciones :
            tab === 'permiso'    ? busyMap.permiso :
                busyMap.baja;

    // backend de OTRAS categorías
    const busyOther = new Set();
    ['vacaciones','permiso','baja'].forEach(t => {
        if (t === tab) return;
        busyMap[t].forEach(d => busyOther.add(d));
    });

    // selección en sesión de OTRAS categorías
    const otherSel = occupiedByOtherTabs(tab);

    // 🚫 no permitir empezar en un día ocupado por OTRA categoría (backend o selección)
    if (busyOther.has(dateStr) || otherSel.has(dateStr)) {
        toastMsg(`⚠️ Día inválido: ${dateStr} está ocupado por otra ausencia.`);
        return;
    }

    // 1er click: inicio
    if (!AUS.rangeStart || (AUS.rangeStart && AUS.rangeEnd)){
        AUS.rangeStart = dateStr;
        AUS.rangeEnd = null;
        renderAusencias();
        return;
    }

    // 2º click: fin
    AUS.rangeEnd = dateStr;

    const dates = rangeDates(AUS.rangeStart, AUS.rangeEnd);
    if (!dates.length) return;

    // 🚫 el rango NO puede pisar otras categorías (backend o selección)
    const conflictOther = dates.find(d => busyOther.has(d) || otherSel.has(d));
    if (conflictOther){
        toastMsg(`⚠️ Rango inválido: ${conflictOther} está ocupado por otra ausencia.`);
        AUS.rangeEnd = null;
        renderAusencias();
        return;
    }

    const set = AUS.selected[tab];

    // ✅ decide remove si TODO el rango ya existe en backend del tab actual
    // (o está seleccionado localmente)
    const allPresentInThisTab = dates.every(d => busyCurrent.has(d) || set.has(d));
    const mode = allPresentInThisTab ? 'remove' : 'add';

    // 🚫 si es add, NO puede pisar días ya ocupados en el tab actual (backend)
    if (mode === 'add'){
        const conflictAdd = dates.find(d => busyCurrent.has(d));
        if (conflictAdd){
            toastMsg(`⚠️ Rango inválido: ${conflictAdd} ya tiene ${tab}.`);
            AUS.rangeEnd = null;
            renderAusencias();
            return;
        }
    }

    const from = dates[0];
    const to   = dates[dates.length - 1];

    try {
        const ok = await saveRangeToServer({
            workerId: AUS.workerId,
            year: AUS.bucketYear,
            tab,
            from,
            to,
            mode
        });

        if (!ok){
            toastMsg('⚠️ No se pudo guardar el rango.');
            AUS.rangeEnd = null;
            renderAusencias();
            return;
        }

        // opcional: mantener selección local consistente
        if (mode === 'remove') dates.forEach(d => set.delete(d));
        else dates.forEach(d => set.add(d));

        AUS.rangeStart = null;
        AUS.rangeEnd = null;

        updateTabBadges();
        renderAusencias();

    } catch (e){
        console.error(e);
        toastMsg('⚠️ Error de red guardando el rango.');
        AUS.rangeEnd = null;
        renderAusencias();
    }
}

function parseISO(s){
    if (!s || typeof s !== 'string') return null;
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!m) return null;
    const y = parseInt(m[1],10), mo = parseInt(m[2],10), d = parseInt(m[3],10);
    return new Date(y, mo-1, d);
}

function formatDate(y, m, d){
    return `${String(y).padStart(4,'0')}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
}

// colores para AUSENCIAS EXISTENTES (backend) en la pestaña activa
function busyColorForTab(tab){
    if (tab === 'vacaciones') return "bg-blue-200 text-blue-900 font-semibold";
    if (tab === 'permiso')    return "bg-amber-200 text-amber-900 font-semibold";
    return "bg-red-200 text-red-900 font-semibold";
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAusenciasModal();
});
