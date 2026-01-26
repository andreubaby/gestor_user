let AUS = {
    nombre: '',
    data: null,
    bucketYear: null,
    tab: 'vacaciones',
    selected: {
        vacaciones: new Set(),
        permiso: new Set(),
        baja: new Set(),
        libre: new Set(),
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
        data: { vacaciones:{count:0,items:[]}, permiso:{count:0,items:[]}, baja:{count:0,items:[]}, libre:{count:0,items:[]} },
        tab: tab || 'vacaciones',
        selected: { vacaciones:new Set(), permiso:new Set(), baja:new Set(), libre:new Set() },
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
            libre: new Set(),
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

function toSafeInt(value) {
    const n = Number(value);
    // Si no es número finito, evita inyectar cosas raras
    if (!Number.isFinite(n)) return 0;
    // Opcional: evita negativos y decimales
    return Math.max(0, Math.trunc(n));
}

function setTabBadge(tabEl, icon, label, rawCount) {
    if (!tabEl) return;

    const count = toSafeInt(rawCount);

    // Limpia el contenido previo sin usar innerHTML
    tabEl.replaceChildren();

    // Texto principal (icono + label)
    tabEl.append(document.createTextNode(`${icon} ${label} `));

    // Badge
    const span = document.createElement("span");
    span.className =
        "ml-2 text-xs font-semibold px-2 py-0.5 rounded-full bg-white/70 ring-1 ring-black/10";
    span.textContent = String(count);

    tabEl.append(span);
}

function updateTabBadges() {
    const vacBack = countDaysInItems(AUS.data?.vacaciones?.items);
    const perBack = countDaysInItems(AUS.data?.permiso?.items);
    const bajBack = countDaysInItems(AUS.data?.baja?.items);
    const libBack = countDaysInItems(AUS.data?.libre?.items);

    setTabBadge(document.getElementById("tabVac"), "🏖", "Vacaciones", vacBack);
    setTabBadge(document.getElementById("tabPer"), "📝", "Permisos", perBack);
    setTabBadge(document.getElementById("tabBaj"), "🏥", "Bajas", bajBack);
    setTabBadge(document.getElementById("tabLib"), "🕒", "Libres", libBack);
}

function applyTabStyles(){
    const tabVac = document.getElementById('tabVac');
    const tabPer = document.getElementById('tabPer');
    const tabBaj = document.getElementById('tabBaj');
    const tabLib = document.getElementById('tabLib');

    const base = "px-3 py-1.5 rounded-lg text-sm font-medium transition focus:outline-none";

    const vacInactive = "bg-blue-50 text-blue-700 ring-1 ring-blue-200 hover:bg-blue-100";
    const vacActive   = "bg-blue-100 text-blue-800 ring-2 ring-blue-300";

    const perInactive = "bg-amber-50 text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100";
    const perActive   = "bg-amber-100 text-amber-900 ring-2 ring-amber-300";

    const bajInactive = "bg-red-50 text-red-700 ring-1 ring-red-200 hover:bg-red-100";
    const bajActive   = "bg-red-100 text-red-800 ring-2 ring-red-300";

    const libInactive = "bg-slate-50 text-slate-700 ring-1 ring-slate-200 hover:bg-slate-100";
    const libActive   = "bg-slate-100 text-slate-900 ring-2 ring-slate-300";

    if (tabVac) tabVac.className = `${base} ${AUS.tab==='vacaciones' ? vacActive : vacInactive}`;
    if (tabPer) tabPer.className = `${base} ${AUS.tab==='permiso'    ? perActive : perInactive}`;
    if (tabBaj) tabBaj.className = `${base} ${AUS.tab==='baja'       ? bajActive : bajInactive}`;
    if (tabLib) tabLib.className = `${base} ${AUS.tab==='libre' ? libActive : libInactive}`;
}

function switchAusTab(tab){
    AUS.tab = tab;
    AUS.rangeStart = null;
    AUS.rangeEnd = null;

    // ✅ Evita bugs de año: bucketYear SOLO aplica a vacaciones
    // En permisos/bajas, forzamos bucketYear = CAL.year para que no “arrastre” otro año.
    if (tab !== 'vacaciones') {
        AUS.bucketYear = CAL.year;
    } else {
        // si entras en vacaciones y no está definido, usa el año visible
        AUS.bucketYear = AUS.bucketYear ?? CAL.year;
    }

    applyTabStyles();
    updateTabBadges?.();
    syncYearLabels?.();      // si existe tu label de año bucket/calendario
    initBucketYearControl?.(); // si usas el selector de bucketYear en vacaciones
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

    const label =
        (t === 'vacaciones') ? 'Vacaciones' :
            (t === 'permiso')    ? 'Permisos' :
                (t === 'baja')       ? 'Bajas' :
                    'Libres';
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
    if (tab === 'baja') return 'B';
    return 'L';
}

async function saveRangeToServer({ workerId, calendarYear, bucketYear, tab, from, to, mode }) {
    const url = window.APP.routes.storeDays.replace('__ID__', workerId);

    // ✅ permisos/bajas NO tienen bucketYear distinto
    const effectiveBucketYear = (tab === 'vacaciones')
        ? (bucketYear ?? calendarYear)
        : calendarYear;

    const payload = {
        calendar_year: calendarYear,        // ✅ SIEMPRE el año visible
        tipo: tabToTipo(tab),
        from,
        to,
        mode,
        // ✅ SIEMPRE manda vacation_year real que quieres guardar
        bucket_year: effectiveBucketYear,
    };

    console.log('[AUS SAVE] payload =>', payload);

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

    const text = await res.text();
    let json = null;
    try { json = JSON.parse(text); } catch(e){
        console.error('[AUS SAVE] NOT JSON', { status: res.status, head: text.slice(0,200) });
        toastMsg('⚠️ Respuesta inválida del servidor');
        return false;
    }

    if (!json.ok) {
        console.error('[AUS SAVE] backend ok=false', json);
        toastMsg('⚠️ Error guardando el rango');
        return false;
    }

    AUS.data = json.data;
    updateRowCounts(workerId, json.data);
    return true;
}

function downloadCalendarPdf() {
    const tipo = tabToTipo(AUS.tab);

    // 1) Validar tipo (whitelist)
    if (!['V','P','B','L'].includes(tipo)) return;

    // 2) Validar workerId (solo números)
    const workerId = String(AUS.workerId ?? '').trim();
    if (!/^\d+$/.test(workerId)) return;

    // 3) Validar bucketYear (solo año razonable)
    const bucketYear = Number(AUS.bucketYear);
    if (!Number.isInteger(bucketYear) || bucketYear < 2000 || bucketYear > 2100) return;

    // ✅ RUTA SEGÚN TIPO (incluye L)
    const routeKey =
        (tipo === 'V') ? 'pdfVac' :
            (tipo === 'P') ? 'pdfPer' :
                (tipo === 'B') ? 'pdfBaj' :
                    'pdfLib';

    const template = window?.APP?.routes?.[routeKey];
    if (!template) return;

    // 4) Construir URL segura con URL() y forzar same-origin
    const urlBaseStr = template.replace('__ID__', workerId);
    const u = new URL(urlBaseStr, window.location.origin);
    if (u.origin !== window.location.origin) return;

    // ✅ Año: vacaciones usa bucketYear, el resto usa CAL.year (más coherente)
    const yearForPdf = (tipo === 'V') ? bucketYear : Number(CAL.year);

    u.searchParams.set('vacation_year', String(yearForPdf));
    u.searchParams.set('tipo', tipo);

    window.open(u.toString(), '_blank', 'noopener,noreferrer');
}


function renderMonthGrid(gridEl, year, month, busyMap, tab){
    const first = new Date(year, month, 1);
    const last  = new Date(year, month + 1, 0);
    const daysInMonth = last.getDate();

    const jsDay = first.getDay();
    const offset = (jsDay + 6) % 7;
    const totalCells = Math.ceil((offset + daysInMonth) / 7) * 7;

    // ✅ sets backend (4)
    const busyVac = busyMap.vacaciones;
    const busyPer = busyMap.permiso;
    const busyBaj = busyMap.baja;
    const busyLib = busyMap.libre;

    // ✅ set backend del tab actual (para permitir quitar)
    const busyCurrent =
        tab === 'vacaciones' ? busyVac :
            tab === 'permiso'    ? busyPer :
                tab === 'baja'       ? busyBaj :
                    busyLib; // libre

    // ✅ backend de otras tabs (para bloquear)
    const busyOther = new Set();
    ['vacaciones','permiso','baja','libre'].forEach(t => {
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

        // selecciones (4)
        const selVac = AUS.selected.vacaciones.has(dateStr);
        const selPer = AUS.selected.permiso.has(dateStr);
        const selBaj = AUS.selected.baja.has(dateStr);
        const selLib = AUS.selected.libre.has(dateStr);

        const isSelectedAny = selVac || selPer || selBaj || selLib;
        const isSelectedCurrent = AUS.selected[tab].has(dateStr);
        const isBlockedByOtherSelection = isSelectedAny && !isSelectedCurrent;

        // ✅ busy backend (4)
        const isBusyVac = busyVac.has(dateStr);
        const isBusyPer = busyPer.has(dateStr);
        const isBusyBaj = busyBaj.has(dateStr);
        const isBusyLib = busyLib.has(dateStr);

        const isBusyOtherBackend = busyOther.has(dateStr);
        const isBusyCurrentBackend = busyCurrent.has(dateStr); // (lo usas si quieres)

        const isRangeMark = (dateStr === AUS.rangeStart);

        let pillCls = "w-9 h-9 sm:w-9 sm:h-9 rounded-full flex items-center justify-center ";

        // prioridad visual:
        // 1) seleccionado
        if (selVac) pillCls += selectedColorForTab('vacaciones');
        else if (selPer) pillCls += selectedColorForTab('permiso');
        else if (selBaj) pillCls += selectedColorForTab('baja');
        else if (selLib) pillCls += selectedColorForTab('libre');

        // 2) busy existente
        else if (isBusyVac) pillCls += busyColorForTab('vacaciones');
        else if (isBusyPer) pillCls += busyColorForTab('permiso');
        else if (isBusyBaj) pillCls += busyColorForTab('baja');
        else if (isBusyLib) pillCls += busyColorForTab('libre');
        else pillCls += "bg-white text-gray-800";

        const blockedMark = (isBlockedByOtherSelection || isBusyOtherBackend)
            ? "opacity-80 ring-1 ring-black/10"
            : "";

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

function openPdfDebug(url) {
    console.log("🧾 PDF URL =>", url);
    toastMsg("PDF URL: " + url);
    window.open(url, "_blank", "noopener,noreferrer");
}

function effectiveVacationYearForPdf(tab){
    if (tab === 'vacaciones') return safeYear(AUS.bucketYear);
    return safeYear(CAL.year);
}

function renderVacPdfButtons() {
    const wrap = document.getElementById("vacPdfWrap");
    const box  = document.getElementById("vacPdfButtons");
    if (!wrap || !box) return;

    if (AUS.tab !== "vacaciones") {
        wrap.classList.add("hidden");
        box.replaceChildren();
        return;
    }

    const items = AUS.data?.vacaciones?.items || [];
    if (!items.length) {
        wrap.classList.add("hidden");
        box.replaceChildren();
        return;
    }

    // calcula primer y último día real de vacaciones
    const days = new Set();
    items.forEach((it) => {
        const from0 = it.from ?? it.fecha;
        const to0   = it.to   ?? it.fecha;
        if (!from0) return;

        const s = parseISO(from0);
        const e = parseISO(to0 || from0);
        if (!s || !e) return;

        const cur = new Date(s.getTime());
        while (cur <= e) {
            days.add(formatDate(cur.getFullYear(), cur.getMonth() + 1, cur.getDate()));
            cur.setDate(cur.getDate() + 1);
        }
    });

    const sorted = Array.from(days).sort();
    if (!sorted.length) {
        wrap.classList.add("hidden");
        box.replaceChildren();
        return;
    }

    const from = sorted[0];
    const to   = sorted[sorted.length - 1];

    wrap.classList.remove("hidden");
    box.replaceChildren();

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className =
        "px-3 py-2 rounded-lg text-sm font-medium bg-blue-50 text-blue-700 ring-1 ring-blue-200 hover:bg-blue-100 transition";
    btn.textContent = `📄 PDF (todas) (${fmtDM(from)}–${fmtDM(to)})`;

    btn.onclick = (ev) => {
        ev.stopPropagation();

        const wid  = safeWorkerId(AUS.workerId);
        const year = effectiveVacationYearForPdf('vacaciones'); // ✅ AUS.bucketYear
        if (!wid || !year) return;

        const rawBase = String(window.APP?.routes?.pdfVac ?? "");
        const urlBase = rawBase.replace("__ID__", wid);

        const finalUrl = buildSafeAppUrl(urlBase, {
            vacation_year: year,
            tipo: "V",
        });

        if (!finalUrl) return;

        console.log('[PDF CLICK]', { tab: AUS.tab, CAL_year: CAL.year, bucketYear: AUS.bucketYear, finalUrl });
        openPdfDebug(finalUrl); // ✅ ya abre
    };

    box.appendChild(btn);
}

function renderPerPdfButtons() {
    const wrap = document.getElementById("perPdfWrap");
    const box  = document.getElementById("perPdfButtons");
    if (!wrap || !box) return;

    if (AUS.tab !== "permiso") {
        wrap.classList.add("hidden");
        box.replaceChildren();
        return;
    }

    const items = AUS.data?.permiso?.items || [];
    if (!items.length) {
        wrap.classList.add("hidden");
        box.replaceChildren();
        return;
    }

    // calcula primer y último día real de permisos
    const days = new Set();
    items.forEach(it => {
        const from0 = it.from ?? it.fecha;
        const to0   = it.to   ?? it.fecha;
        if (!from0) return;

        const s = parseISO(from0);
        const e = parseISO(to0 || from0);
        if (!s || !e) return;

        const cur = new Date(s.getTime());
        while (cur <= e) {
            days.add(formatDate(cur.getFullYear(), cur.getMonth() + 1, cur.getDate()));
            cur.setDate(cur.getDate() + 1);
        }
    });

    const sorted = Array.from(days).sort();
    if (!sorted.length) {
        wrap.classList.add("hidden");
        box.replaceChildren();
        return;
    }

    const from = sorted[0];
    const to   = sorted[sorted.length - 1];

    wrap.classList.remove("hidden");
    box.replaceChildren();

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className =
        "px-3 py-2 rounded-lg text-sm font-medium bg-amber-50 text-amber-800 ring-1 ring-amber-200 hover:bg-amber-100 transition";
    btn.textContent = `📄 PDF (todas) (${fmtDM(from)}–${fmtDM(to)})`;

    btn.onclick = (ev) => {
        ev.stopPropagation();

        const wid  = safeWorkerId(AUS.workerId);
        const year = effectiveVacationYearForPdf('permiso'); // ✅ CAL.year
        if (!wid || !year) return;

        const rawBase = String(window.APP?.routes?.pdfPer ?? "");
        const urlBase = rawBase.replace("__ID__", wid);

        const finalUrl = buildSafeAppUrl(urlBase, {
            vacation_year: year,
            tipo: "P",
        });

        if (!finalUrl) return;

        console.log('[PDF CLICK]', { tab: AUS.tab, CAL_year: CAL.year, bucketYear: AUS.bucketYear, finalUrl });
        openPdfDebug(finalUrl); // ✅ ya abre
    };

    box.appendChild(btn);
}

function renderBajPdfButtons() {
    const wrap = document.getElementById("bajPdfWrap");
    const box  = document.getElementById("bajPdfButtons");
    if (!wrap || !box) return;

    if (AUS.tab !== "baja") {
        wrap.classList.add("hidden");
        box.replaceChildren();
        return;
    }

    const items = AUS.data?.baja?.items || [];
    if (!items.length) {
        wrap.classList.add("hidden");
        box.replaceChildren();
        return;
    }

    // calcula primer y último día real de bajas
    const days = new Set();
    items.forEach(it => {
        const from0 = it.from ?? it.fecha;
        const to0   = it.to   ?? it.fecha;
        if (!from0) return;

        const s = parseISO(from0);
        const e = parseISO(to0 || from0);
        if (!s || !e) return;

        const cur = new Date(s.getTime());
        while (cur <= e) {
            days.add(formatDate(cur.getFullYear(), cur.getMonth() + 1, cur.getDate()));
            cur.setDate(cur.getDate() + 1);
        }
    });

    const sorted = Array.from(days).sort();
    if (!sorted.length) {
        wrap.classList.add("hidden");
        box.replaceChildren();
        return;
    }

    const from = sorted[0];
    const to   = sorted[sorted.length - 1];

    wrap.classList.remove("hidden");
    box.replaceChildren();

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className =
        "px-3 py-2 rounded-lg text-sm font-medium bg-red-50 text-red-700 ring-1 ring-red-200 hover:bg-red-100 transition";
    btn.textContent = `📄 PDF (todas) (${fmtDM(from)}–${fmtDM(to)})`;

    btn.onclick = (ev) => {
        ev.stopPropagation();

        const wid  = safeWorkerId(AUS.workerId);
        const year = effectiveVacationYearForPdf('baja'); // ✅ CAL.year
        if (!wid || !year) return;

        const rawBase = String(window.APP?.routes?.pdfBaj ?? "");
        const urlBase = rawBase.replace("__ID__", wid);

        const finalUrl = buildSafeAppUrl(urlBase, {
            vacation_year: year,
            tipo: "B",
        });

        if (!finalUrl) return;

        console.log('[PDF CLICK]', { tab: AUS.tab, CAL_year: CAL.year, bucketYear: AUS.bucketYear, finalUrl });
        openPdfDebug(finalUrl); // ✅ ya abre
    };

    box.appendChild(btn);
}

function safeWorkerId(id) {
    const s = String(id ?? "");
    // Ajusta a tu formato real (num, uuid, etc.)
    if (!/^[a-zA-Z0-9_-]{1,64}$/.test(s)) return null;
    return s;
}

function safeYear(y) {
    const n = Number(y);
    if (!Number.isInteger(n)) return null;
    if (n < 2000 || n > 2100) return null;
    return n;
}

function buildSafeAppUrl(pathOrUrl, params = {}) {
    const u = new URL(pathOrUrl, window.location.origin);

    // Bloquea redirects fuera de tu dominio
    if (u.origin !== window.location.origin) return null;

    // (Opcional recomendable) limita rutas permitidas
    // Ajusta esto al path real del endpoint de pdf permisos
    // Ej: const allowedPrefixes = ["/ausencias/pdf/"];
    const allowedPrefixes = ["/"];
    if (!allowedPrefixes.some(p => u.pathname.startsWith(p))) return null;

    for (const [k, v] of Object.entries(params)) {
        if (v !== null && v !== undefined) u.searchParams.set(k, String(v));
    }

    return u.toString();
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
    const lib = data?.libre?.count ?? 0;

    const vacEl = document.getElementById(`vacCount-${workerId}`);
    const perEl = document.getElementById(`perCount-${workerId}`);
    const bajEl = document.getElementById(`bajCount-${workerId}`);
    const libEl = document.getElementById(`libCount-${workerId}`);

    if (vacEl) vacEl.textContent = vac;
    if (perEl) perEl.textContent = per;
    if (bajEl) bajEl.textContent = baj;
    if (libEl) libEl.textContent = lib;
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
        libre:      buildBusySetForTab('libre'),
    };
}

function occupiedByOtherTabs(currentTab){
    const busy = buildGlobalBusyMap();

    const occ = new Set();
    ['vacaciones','permiso','baja','libre'].forEach(t => {
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
    if (tab === 'baja')       return "bg-red-200 text-red-900 font-semibold";
    return "bg-slate-200 text-slate-900 font-semibold"; // libre
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

function extractRows(payload) {
    if (!payload) return [];
    if (Array.isArray(payload.data)) return payload.data;
    if (Array.isArray(payload.rows)) return payload.rows;
    if (payload.data && Array.isArray(payload.data.data)) return payload.data.data; // por si viene paginado
    return [];
}

function openFichajesModal(el) {

    const modal = document.getElementById('fichajesModal');
    if (!modal) {
        console.error('🔴 [FICHAJES] #fichajesModal NOT FOUND');
        return;
    }

    // Nodos dentro del modal
    const title = modal.querySelector('#fichajesTitle');
    const sub   = modal.querySelector('#fichajesSub');

    const loadingEls = modal.querySelectorAll('#fichajesLoading');
    const emptyEls   = modal.querySelectorAll('#fichajesEmpty');
    const errorEls   = modal.querySelectorAll('#fichajesError');
    const listEls    = modal.querySelectorAll('#fichajesList');

    const hideAll = (nodes) => nodes.forEach(n => n && n.classList.add('hidden'));
    const showAll = (nodes) => nodes.forEach(n => n && n.classList.remove('hidden'));

    const loading = loadingEls[0] || null;
    const empty   = emptyEls[0] || null;
    const error   = errorEls[0] || null;
    const list    = listEls[0] || null;

    const workerId = el?.dataset?.worker;
    const nombre   = el?.dataset?.nombre || '—';
    const email    = el?.dataset?.email || '—';

    if (title) title.textContent = 'Historial de fichajes';
    if (sub) sub.textContent = `${nombre} · ${email}`;

    // Reset UI
    showAll(loadingEls);
    hideAll(emptyEls);
    hideAll(errorEls);
    hideAll(listEls);
    if (list) list.innerHTML = '';

    // Summary
    let summary = modal.querySelector('#fichajesSummary');
    if (!summary && list && list.parentElement) {
        summary = document.createElement('div');
        summary.id = 'fichajesSummary';
        summary.className = 'mb-4 hidden';
        list.parentElement.insertBefore(summary, list);
    }
    if (summary) {
        summary.classList.add('hidden');
        summary.innerHTML = '';
    }

    // Abrir modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');


    // Validación workerId
    if (!workerId || !/^\d+$/.test(String(workerId))) {
        console.error('🔴 [FICHAJES] workerId inválido', { workerId });
        hideAll(loadingEls);
        showAll(errorEls);
        hideAll(emptyEls);
        hideAll(listEls);
        return;
    }

    const url = window.APP.routes.fichajesUnificado.replace('__ID__', workerId) + '?limit=180';

    fetch(url, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
    })
        .then(async (r) => {

            const text = await r.text();
            let json = null;

            try {
                json = JSON.parse(text);
            } catch (e) {
                console.error('🔴 [FICHAJES] respuesta NO JSON', {
                    status: r.status,
                    contentType: r.headers.get('content-type'),
                    head: text.slice(0, 300)
                });
                return { ok: false, _notJson: true };
            }

            return json;
        })
        .then(payload => {
            hideAll(loadingEls);

            if (!payload || payload.ok !== true) {
                showAll(errorEls);
                hideAll(emptyEls);
                hideAll(listEls);
                return;
            }

            const rows = extractRows(payload);

            if (rows.length === 0) {
                showAll(emptyEls);
                hideAll(errorEls);
                hideAll(listEls);
                return;
            }

            hideAll(emptyEls);
            hideAll(errorEls);
            showAll(listEls);

            // TEST VISUAL (si esto NO aparece, el UL no está en pantalla o se oculta por CSS)
            if (list) {
                const li = document.createElement('li');
                li.className = 'py-2 text-sm text-green-700';
                li.textContent = `✅ TEST: tengo ${rows.length} filas`;
                list.replaceChildren(li);
            }

            // ---------- Render real con try/catch ----------
            try {
                const emojiFor = (b) => (b === 1 ? '🙂' : b === 2 ? '😐' : b === 3 ? '🙁' : b === 4 ? '😡' : '🙂');
                const labelFor = (b) => (b === 1 ? 'Bien' : b === 2 ? 'Regular' : b === 3 ? 'Mal' : b === 4 ? 'Muy mal' : '—');

                const toYMD = (s) => {
                    if (!s) return null;
                    const m = String(s).match(/^(\d{4}-\d{2}-\d{2})/);
                    return m ? m[1] : null;
                };

                const formatDayHeader = (yyyy_mm_dd) => {
                    const [y, m, d] = (yyyy_mm_dd || '').split('-').map(Number);
                    if (!y || !m || !d) return 'Sin fecha';
                    const dt = new Date(y, m - 1, d);

                    const today = new Date();
                    const t0 = new Date(today.getFullYear(), today.getMonth(), today.getDate());
                    const d0 = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
                    const diffDays = Math.round((t0 - d0) / (1000 * 60 * 60 * 24));
                    if (diffDays === 0) return 'Hoy';
                    if (diffDays === 1) return 'Ayer';

                    return dt.toLocaleDateString('es-ES', {
                        weekday: 'long', day: '2-digit', month: 'short', year: 'numeric'
                    });
                };

                // Resumen
                const nums = rows.map(r => Number(r.bienestar)).filter(n => n >= 1 && n <= 4);
                const count = rows.length;
                const avg = nums.length ? (nums.reduce((a, b) => a + b, 0) / nums.length) : null;
                const dist = [1,2,3,4].map(v => nums.filter(n => n === v).length);

                if (summary) {
                    summary.innerHTML = `
                      <div class="rounded-xl ring-1 ring-gray-200 bg-gray-50 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                          <div>
                            <div class="text-sm font-semibold text-gray-900">Resumen</div>
                            <div class="text-xs text-gray-600 mt-0.5">
                              ${count} registro${count !== 1 ? 's' : ''}${avg ? ` · Media: <span class="font-semibold">${avg.toFixed(2)}</span>` : ''}
                            </div>
                          </div>
                          <div class="flex items-center gap-2 text-sm">
                            <span title="Bien">${emojiFor(1)} <span class="text-gray-700">${dist[0]}</span></span>
                            <span title="Regular">${emojiFor(2)} <span class="text-gray-700">${dist[1]}</span></span>
                            <span title="Mal">${emojiFor(3)} <span class="text-gray-700">${dist[2]}</span></span>
                            <span title="Muy mal">${emojiFor(4)} <span class="text-gray-700">${dist[3]}</span></span>
                          </div>
                        </div>
                      </div>
                    `;
                    summary.classList.remove('hidden');
                }

                // Agrupar por fecha
                const groups = new Map();
                for (const r of rows) {
                    const key = toYMD(r.fecha) || 'Sin fecha';
                    if (!groups.has(key)) groups.set(key, []);
                    groups.get(key).push(r);
                }

                const sortedKeys = Array.from(groups.keys()).sort((a, b) => {
                    if (a === 'Sin fecha') return 1;
                    if (b === 'Sin fecha') return -1;
                    return a < b ? 1 : (a > b ? -1 : 0);
                });

                if (list) {
                    list.innerHTML = sortedKeys.map(dateKey => {
                        const items = groups.get(dateKey) || [];
                        items.sort((x, y) => (x.hora || '').localeCompare(y.hora || '')).reverse();

                        const header = (dateKey === 'Sin fecha') ? 'Sin fecha' : formatDayHeader(dateKey);

                        return `
                          <li class="py-3">
                            <div class="sticky top-0 bg-white/80 backdrop-blur py-2">
                              <div class="text-xs font-semibold text-gray-700 uppercase tracking-wide">
                                ${header}
                                <span class="ml-2 text-[11px] font-normal text-gray-500">(${items.length})</span>
                              </div>
                            </div>

                            <div class="mt-2 space-y-2">
                              ${items.map(r => {
                            const b = Number(r.bienestar);
                            const emoji = (b >= 1 && b <= 4) ? emojiFor(b) : '🧾';
                            const label = (b >= 1 && b <= 4)
                                ? labelFor(b)
                                : (r.origen === 'daily' ? 'Daily' : 'Fichaje');

                            const hora = r.hora || '—';
                            const origen = r.origen || '—';

                            const minsVal = r?.meta?.worked_minutes;
                            const mins = (r.origen === 'daily' && typeof minsVal !== 'undefined')
                                ? ` · ${minsVal} min`
                                : '';

                            const origenNorm = String(r.origen || '').toLowerCase();

                            // 🎨 Colores suaves por tipo (entrada/salida)
                            const cardClass =
                                origenNorm === 'entrada'
                                    ? 'bg-emerald-50 ring-emerald-200'
                                    : origenNorm === 'salida'
                                        ? 'bg-red-50 ring-red-200'
                                        : 'bg-white ring-gray-200';

                            const badge =
                                origenNorm === 'entrada'
                                    ? `<span class="ml-2 inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 px-2 py-0.5 text-[11px] font-semibold">Entrada</span>`
                                    : origenNorm === 'salida'
                                        ? `<span class="ml-2 inline-flex items-center rounded-full bg-red-100 text-red-800 px-2 py-0.5 text-[11px] font-semibold">Salida</span>`
                                        : '';

                            return `
                              <div class="flex items-center justify-between gap-3 rounded-xl ring-1 ${cardClass} px-3 py-2">
                                <div class="flex items-center gap-3">
                                  <span class="inline-flex items-center justify-center w-10 h-10 rounded-full ring-1 bg-white/70">
                                    ${emoji}
                                  </span>
                                  <div class="leading-tight">
                                    <div class="text-sm font-semibold text-gray-900">
                                      ${label} ${badge}
                                    </div>
                                    <div class="text-xs text-gray-500">${dateKey} · ${hora} · ${origen}${mins}</div>
                                  </div>
                                </div>
                                <div class="text-xs text-gray-500">
                                  ${b ? `Nivel ${b}` : ''}
                                </div>
                              </div>
                            `;
                        }).join('')}
                            </div>
                          </li>
                        `;
                    }).join('');
                }

            } catch (err) {
                console.error('🔴 [FICHAJES] RENDER ERROR', err);
                showAll(errorEls);
                hideAll(emptyEls);
                hideAll(listEls);
            }
        })
        .catch((e) => {
            console.error('🔴 [FICHAJES] FETCH CATCH', e);
            hideAll(loadingEls);
            showAll(errorEls);
            hideAll(emptyEls);
            hideAll(listEls);
        });
}

function closeFichajesModal() {
    const modal = document.getElementById('fichajesModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// cerrar al clicar fuera
document.addEventListener('click', (e) => {
    const modal = document.getElementById('fichajesModal');
    if (!modal || modal.classList.contains('hidden')) return;
    if (e.target === modal) closeFichajesModal();
});

// cerrar con ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeFichajesModal();
});

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
                tab === 'baja'       ? busyMap.baja :
                    busyMap.libre;

    // backend de OTRAS categorías
    const busyOther = new Set();
    ['vacaciones','permiso','baja','libre'].forEach(t => {
        if (t === tab) return;
        busyMap[t].forEach(d => busyOther.add(d));
    });

    // selección en sesión de OTRAS categorías
    const otherSel = occupiedByOtherTabs(tab); // 👈 asegúrate de que esa función también incluye 'libre'

    if (busyOther.has(dateStr) || otherSel.has(dateStr)) {
        toastMsg(`⚠️ Día inválido: ${dateStr} está ocupado por otra ausencia.`);
        return;
    }

    if (!AUS.rangeStart || (AUS.rangeStart && AUS.rangeEnd)){
        AUS.rangeStart = dateStr;
        AUS.rangeEnd = null;
        renderAusencias();
        return;
    }

    AUS.rangeEnd = dateStr;

    const dates = rangeDates(AUS.rangeStart, AUS.rangeEnd);
    if (!dates.length) return;

    const conflictOther = dates.find(d => busyOther.has(d) || otherSel.has(d));
    if (conflictOther){
        toastMsg(`⚠️ Rango inválido: ${conflictOther} está ocupado por otra ausencia.`);
        AUS.rangeEnd = null;
        renderAusencias();
        return;
    }

    const set = AUS.selected[tab];

    const allPresentInThisTab = dates.every(d => busyCurrent.has(d) || set.has(d));
    const mode = allPresentInThisTab ? 'remove' : 'add';

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
            calendarYear: CAL.year,
            bucketYear: AUS.bucketYear ?? CAL.year,
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
    if (tab === 'baja')       return "bg-red-200 text-red-900 font-semibold";
    return "bg-slate-200 text-slate-900 font-semibold"; // libre
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAusenciasModal();
});
