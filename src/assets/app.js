// PixelForge SQL-trainer: frontend
'use strict';

const STORAGE_KEY = 'sql-trainer.v1';

// XP per missie. Een hint kost punten; wie eerst het antwoord bekijkt krijgt weinig.
const XP_FULL = 100;
const XP_WITH_HINT = 75;
const XP_AFTER_PEEK = 25;

// Collega's die de missies geven (sleutel = 'from' in lib/exercises.php).
const PEOPLE = {
    manager: { name: 'Sanne', role: 'Manager', color: '#facc15' },
    inkoop: { name: 'Mo', role: 'Inkoop', color: '#22d3ee' },
    marketing: { name: 'Daan', role: 'Marketing', color: '#c86bfa' },
    webshop: { name: 'Priya', role: 'Webshop', color: '#34e08a' },
    magazijn: { name: 'Kevin', role: 'Magazijn', color: '#fb923c' },
    service: { name: 'Lisa', role: 'Klantenservice', color: '#ff7aa8' },
};

// Badge per onderwerp (level).
const TOPICS = {
    'SELECT': { badge: 'Data-verkenner', icon: '🧭' },
    'WHERE': { badge: 'Filtermeester', icon: '🔍' },
    'ORDER BY': { badge: 'Sorteerkoning', icon: '📊' },
    'LIMIT': { badge: 'Top-lijst Pro', icon: '🏆' },
    'LIKE': { badge: 'Patroonspeurder', icon: '🕵️' },
    'COUNT': { badge: 'Turfkampioen', icon: '🔢' },
    'SUM': { badge: 'Rekenwonder', icon: '🧮' },
    'INSERT': { badge: 'Bouwer', icon: '🧱' },
    'UPDATE': { badge: 'Fixer', icon: '🔧' },
    'DELETE': { badge: 'Opruimer', icon: '🧹' },
};

// Rangen op basis van totaal verdiende XP.
const RANKS = [
    { xp: 0, name: 'Stagiair' },
    { xp: 300, name: 'Junior medewerker' },
    { xp: 900, name: 'Data-medewerker' },
    { xp: 1800, name: 'Database-specialist' },
    { xp: 2800, name: 'SQL-ninja' },
    { xp: 3800, name: 'SQL-legende' },
];

const PRAISE = ['Yes! Helemaal goed!', 'Strak gedaan!', 'Lekker bezig!', 'Top, dat klopt!', 'Nailed it!', 'Missie geslaagd!'];
const ALMOST = ['Bijna!', 'Nog niet helemaal…', 'Net niet!', 'Probeer het nog eens!'];

const state = {
    mode: 'exercises',
    exercises: [],
    topics: [],
    currentId: null,
    progress: loadProgress(),
};

const $ = (selector) => document.querySelector(selector);
const el = {
    topicList: $('#topic-list'),
    badgeList: $('#badge-list'),
    badgeCount: $('#badge-count'),
    rankName: $('#rank-name'),
    xpFill: $('#xp-fill'),
    xpTotal: $('#xp-total'),
    xpNext: $('#xp-next'),
    meta: $('#ex-meta'),
    reward: $('#ex-reward'),
    title: $('#ex-title'),
    chat: $('#ex-chat'),
    avatar: $('#ex-avatar'),
    who: $('#ex-who'),
    story: $('#ex-story'),
    taskLabel: $('#task-label'),
    text: $('#ex-text'),
    hintBox: $('#ex-hint-box'),
    hintSummary: $('#ex-hint-summary'),
    hint: $('#ex-hint'),
    editor: $('#editor'),
    runBtn: $('#run-btn'),
    solutionBtn: $('#solution-btn'),
    nextBtn: $('#next-btn'),
    feedback: $('#feedback'),
    output: $('#output'),
    schema: $('#schema-list'),
};

// ------------------------------------------------------------ opslag (browser)

function emptyProgress() {
    return { done: {}, hints: {}, peeked: {}, drafts: {}, current: null, playground: '' };
}

function loadProgress() {
    try {
        const progress = { ...emptyProgress(), ...JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') };
        // Oudere versie sloeg 'true' op in plaats van verdiende XP.
        for (const id of Object.keys(progress.done)) {
            if (progress.done[id] === true) progress.done[id] = XP_FULL;
        }
        return progress;
    } catch {
        return emptyProgress();
    }
}

function saveProgress() {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state.progress));
    } catch {
        // Opslag niet beschikbaar (bijv. privévenster): de trainer werkt gewoon zonder.
    }
}

// ------------------------------------------------------------------ punten

function isDone(id) {
    return state.progress.done[id] !== undefined;
}

function totalXp() {
    return Object.values(state.progress.done).reduce((sum, xp) => sum + xp, 0);
}

function rankFor(xp) {
    return RANKS.filter((r) => xp >= r.xp).pop();
}

/** XP die je nu nog kunt verdienen met deze missie. */
function availableXp(id) {
    if (state.progress.peeked[id]) return XP_AFTER_PEEK;
    if (state.progress.hints[id]) return XP_WITH_HINT;
    return XP_FULL;
}

function topicComplete(topic) {
    return topic.items.every((e) => isDone(e.id));
}

function renderPlayer() {
    const xp = totalXp();
    const rank = rankFor(xp);
    const next = RANKS[RANKS.indexOf(rank) + 1];
    el.rankName.textContent = rank.name;
    el.xpTotal.textContent = `${xp} XP`;
    if (next) {
        el.xpNext.textContent = `nog ${next.xp - xp} XP tot ${next.name}`;
        el.xpFill.style.width = `${((xp - rank.xp) / (next.xp - rank.xp)) * 100}%`;
    } else {
        el.xpNext.textContent = 'Hoogste rang bereikt!';
        el.xpFill.style.width = '100%';
    }
}

// --------------------------------------------------------------------- API

async function api(action, { method = 'GET', body, params = {} } = {}) {
    const query = new URLSearchParams({ action, ...params });
    const response = await fetch(`api.php?${query}`, {
        method,
        headers: body ? { 'Content-Type': 'application/json' } : {},
        body: body ? JSON.stringify(body) : undefined,
    });
    let data;
    try {
        data = await response.json();
    } catch {
        throw new Error(`De server gaf een onverwacht antwoord (HTTP ${response.status}).`);
    }
    if (!response.ok) {
        throw new Error(data.error || `Er ging iets mis (HTTP ${response.status}).`);
    }
    return data;
}

// ---------------------------------------------------------------- weergave

function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[c]);
}

function pick(list) {
    return list[Math.floor(Math.random() * list.length)];
}

function renderTable(result, caption) {
    if (result.type === 'affected') {
        const n = result.affected;
        return `<div class="result-note">${caption ? `<strong>${escapeHtml(caption)}</strong> ` : ''}`
            + `Statement uitgevoerd: ${n} ${n === 1 ? 'rij' : 'rijen'} aangepast.</div>`;
    }

    const head = result.columns
        .map((c) => `<th class="${c.numeric ? 'num' : ''}">${escapeHtml(c.name)}</th>`)
        .join('');
    const body = result.rows.map((row) => '<tr>' + row.map((value, i) => {
        const cls = result.columns[i].numeric ? 'num' : '';
        return value === null
            ? `<td class="${cls} null">NULL</td>`
            : `<td class="${cls}">${escapeHtml(value)}</td>`;
    }).join('') + '</tr>').join('');

    const count = result.rowCount;
    const summary = `${count} ${count === 1 ? 'rij' : 'rijen'}`
        + (result.truncated ? ` (alleen de eerste ${result.rows.length} worden getoond)` : '');

    return `<div class="result">
        <div class="result-caption">${caption ? `<strong>${escapeHtml(caption)}</strong> · ` : ''}${summary}</div>
        <div class="table-wrap">
            <table><thead><tr>${head}</tr></thead><tbody>${body || ''}</tbody></table>
            ${count === 0 ? '<p class="empty">Geen rijen gevonden.</p>' : ''}
        </div>
    </div>`;
}

function renderError(error) {
    return `<div class="sql-error">
        <strong>Foutmelding${error.statement > 1 ? ` bij statement ${error.statement}` : ''}</strong>
        ${error.hint ? `<p>${escapeHtml(error.hint)}</p>` : ''}
        <code>${escapeHtml(error.message)}</code>
    </div>`;
}

/** Toont de resultaten van (mogelijk meerdere) statements. */
function renderOutcome(outcome) {
    const multiple = outcome.results.length > 1;
    let html = outcome.results
        .map((r, i) => renderTable(r, multiple ? `Statement ${i + 1}` : ''))
        .join('');
    if (outcome.error) {
        html += renderError(outcome.error);
    }
    return html;
}

/** kind: success | error | info. Titel en tekst worden ge-escaped. */
function showFeedback(kind, title, text = '') {
    const icons = { success: '🎉', error: '🤔', info: 'ℹ️' };
    el.feedback.className = `feedback feedback-${kind}`;
    el.feedback.innerHTML = `<span class="fb-icon" aria-hidden="true">${icons[kind]}</span>`
        + `<div><div class="fb-title">${escapeHtml(title)}</div>${text ? `<p>${escapeHtml(text)}</p>` : ''}</div>`;
    el.feedback.hidden = false;
    if (kind === 'error') {
        el.feedback.classList.remove('shake');
        void el.feedback.offsetWidth; // animatie opnieuw starten
        el.feedback.classList.add('shake');
    }
}

function clearOutput() {
    el.feedback.hidden = true;
    el.output.innerHTML = '';
    el.nextBtn.hidden = true;
}

function setBusy(button, busy) {
    button.disabled = busy;
    button.classList.toggle('busy', busy);
}

function toast(text) {
    const node = document.createElement('div');
    node.className = 'toast';
    node.textContent = text;
    $('#toasts').append(node);
    setTimeout(() => node.remove(), 3100);
}

// ------------------------------------------------------- levels en badges

function buildTopics() {
    state.topics = [];
    for (const exercise of state.exercises) {
        let topic = state.topics.find((t) => t.name === exercise.topic);
        if (!topic) {
            topic = { name: exercise.topic, level: state.topics.length + 1, items: [], ...(TOPICS[exercise.topic] || { badge: exercise.topic, icon: '⭐' }) };
            state.topics.push(topic);
        }
        topic.items.push(exercise);
    }
}

function renderNav() {
    el.topicList.innerHTML = state.topics.map((topic) => {
        const done = topic.items.filter((e) => isDone(e.id)).length;
        const items = topic.items.map((e) => `
            <li>
                <button type="button" data-id="${e.id}"
                        class="${e.id === state.currentId ? 'active' : ''} ${isDone(e.id) ? 'done' : ''}">
                    <span class="check" aria-hidden="true"></span>
                    <span class="item-title">${escapeHtml(e.title)}</span>
                    ${isDone(e.id) ? `<span class="item-xp">+${state.progress.done[e.id]}</span>` : ''}
                </button>
            </li>`).join('');
        return `<section class="topic ${topicComplete(topic) ? 'complete' : ''}">
            <div class="topic-head">
                <span class="lvl">LVL ${topic.level}</span>
                <span class="topic-name">${escapeHtml(topic.name)}</span>
                <span class="topic-count">${done}/${topic.items.length}</span>
            </div>
            <div class="topic-bar"><div style="width:${(done / topic.items.length) * 100}%"></div></div>
            <ul>${items}</ul>
        </section>`;
    }).join('');

    const earned = state.topics.filter(topicComplete);
    el.badgeCount.textContent = `${earned.length}/${state.topics.length}`;
    el.badgeList.innerHTML = state.topics.map((topic) => {
        const got = topicComplete(topic);
        const label = got ? `${topic.badge} (behaald)` : `${topic.badge}: maak alle missies van level ${topic.level} (${topic.name})`;
        return `<span class="badge ${got ? 'earned' : ''}" title="${escapeHtml(label)}" aria-label="${escapeHtml(label)}">${topic.icon}</span>`;
    }).join('');

    renderPlayer();
}

function showBadge(topic) {
    $('#badge-modal-icon').textContent = topic.icon;
    $('#badge-modal-title').textContent = topic.badge;
    $('#badge-modal-text').textContent = `Je hebt alle missies van level ${topic.level} (${topic.name}) voltooid!`;
    $('#badge-modal').hidden = false;
    $('#badge-modal-close').focus();
    confetti(260);
}

// ------------------------------------------------------------- missies

function currentExercise() {
    return state.exercises.find((e) => e.id === state.currentId);
}

function renderReward(exercise) {
    el.reward.hidden = false;
    if (isDone(exercise.id)) {
        el.reward.className = 'reward done';
        el.reward.textContent = `✓ Voltooid · +${state.progress.done[exercise.id]} XP`;
    } else {
        el.reward.className = 'reward';
        el.reward.textContent = `Beloning: ${availableXp(exercise.id)} XP`;
    }
    const hintCost = XP_FULL - XP_WITH_HINT;
    el.hintSummary.textContent = isDone(exercise.id) || state.progress.hints[exercise.id] || state.progress.peeked[exercise.id]
        ? '💡 Hint'
        : `💡 Hint bekijken (−${hintCost} XP)`;
}

function openExercise(id) {
    const exercise = state.exercises.find((e) => e.id === id) || state.exercises[0];
    if (!exercise) return;

    state.currentId = exercise.id;
    state.progress.current = exercise.id;
    saveProgress();

    const topic = state.topics.find((t) => t.name === exercise.topic);
    const number = topic.items.indexOf(exercise) + 1;
    el.meta.textContent = `Level ${topic.level} · ${topic.name} · missie ${number} van ${topic.items.length}`;
    el.title.textContent = exercise.title;

    const person = PEOPLE[exercise.from];
    if (person && exercise.story) {
        el.avatar.textContent = person.name[0];
        el.avatar.style.background = person.color;
        el.who.innerHTML = `<strong>${escapeHtml(person.name)}</strong> · ${escapeHtml(person.role)}`;
        el.story.textContent = exercise.story;
        el.chat.hidden = false;
    } else {
        el.chat.hidden = true;
    }

    el.taskLabel.textContent = exercise.type === 'dml'
        ? 'Jouw opdracht · de wijziging wordt na het controleren teruggedraaid'
        : 'Jouw opdracht';
    el.text.innerHTML = exercise.text;
    el.hint.innerHTML = exercise.hint;
    el.hintBox.open = false;
    el.hintBox.hidden = false;
    renderReward(exercise);

    el.editor.value = state.progress.drafts[exercise.id] || '';
    el.editor.placeholder = exercise.type === 'dml' ? `${exercise.topic} ...` : 'SELECT ...';
    clearOutput();
    renderNav();
}

async function checkCurrent() {
    const exercise = currentExercise();
    const sql = el.editor.value.trim();
    if (!sql) {
        showFeedback('info', 'Typ eerst een query in het zwarte vak.');
        return;
    }

    setBusy(el.runBtn, true);
    clearOutput();
    try {
        const result = await api('check', { method: 'POST', body: { id: exercise.id, sql } });

        if (result.correct) {
            const firstTime = !isDone(exercise.id);
            if (firstTime) {
                const topic = state.topics.find((t) => t.name === exercise.topic);
                const rankBefore = rankFor(totalXp());
                const xp = availableXp(exercise.id);
                state.progress.done[exercise.id] = xp;
                saveProgress();

                toast(`+${xp} XP`);
                const rankAfter = rankFor(totalXp());
                if (rankAfter !== rankBefore) {
                    setTimeout(() => toast(`⬆ Nieuwe rang: ${rankAfter.name}!`), 600);
                }
                if (topicComplete(topic)) {
                    setTimeout(() => showBadge(topic), 700);
                } else {
                    confetti(90);
                }
            }
            showFeedback('success', pick(PRAISE), firstTime ? result.message : `${result.message} (Deze missie had je al gehaald.)`);
            el.nextBtn.hidden = !nextExercise();
            renderReward(exercise);
            renderNav();
        } else {
            showFeedback('error', result.student.error ? 'Oeps, een foutmelding' : pick(ALMOST), result.message);
        }

        let html = '';
        if (exercise.type === 'dml' && result.student.state) {
            const affected = result.student.results.reduce((sum, r) => sum + (r.type === 'affected' ? Math.max(r.affected, 0) : 0), 0);
            html += `<div class="result-note">Jouw query heeft ${affected} ${affected === 1 ? 'rij' : 'rijen'} aangepast. `
                + 'Hieronder zie je hoe de tabel er daarna uitzag. Daarna is de wijziging weer teruggedraaid.</div>';
            html += renderTable(result.student.state, 'Tabel na jouw query');
        } else {
            html += renderOutcome(result.student);
        }
        el.output.innerHTML = html;
    } catch (error) {
        showFeedback('error', 'Er ging iets mis', error.message);
    } finally {
        setBusy(el.runBtn, false);
    }
}

async function showSolution() {
    const exercise = currentExercise();
    if (!isDone(exercise.id) && !state.progress.peeked[exercise.id]) {
        const ok = confirm(`Weet je het zeker? Als je het antwoord bekijkt, levert deze missie nog maar ${XP_AFTER_PEEK} XP op. Probeer het eerst zelf (of gebruik de hint)!`);
        if (!ok) return;
    }

    setBusy(el.solutionBtn, true);
    try {
        const data = await api('solution', { params: { id: exercise.id } });
        if (!isDone(exercise.id)) {
            state.progress.peeked[exercise.id] = true;
            saveProgress();
            renderReward(exercise);
        }
        let html = `<div class="solution"><strong>Modelantwoord</strong><pre>${escapeHtml(data.solution)}</pre>`
            + '<p class="muted small">Er zijn vaak meerdere goede antwoorden. Elke query met hetzelfde resultaat wordt goedgekeurd. '
            + 'Typ het antwoord zelf over om de missie af te ronden.</p></div>';
        if (data.expected.error) {
            html += renderError(data.expected.error);
        } else if (exercise.type === 'dml') {
            html += renderTable(data.expected.state, 'Verwachte tabel na de wijziging');
        } else {
            const expected = data.expected.results.filter((r) => r.type === 'rows').pop();
            if (expected) html += renderTable(expected, 'Verwacht resultaat');
        }
        el.output.innerHTML = html;
        el.feedback.hidden = true;
    } catch (error) {
        showFeedback('error', 'Er ging iets mis', error.message);
    } finally {
        setBusy(el.solutionBtn, false);
    }
}

function nextExercise() {
    const index = state.exercises.findIndex((e) => e.id === state.currentId);
    return state.exercises[index + 1] || null;
}

// ------------------------------------------------------------ vrij oefenen

function openPlayground() {
    el.meta.textContent = 'Vrij oefenen';
    el.reward.hidden = true;
    el.title.textContent = 'Probeer zelf queries uit';
    el.chat.hidden = true;
    el.taskLabel.textContent = 'Zo werkt het';
    el.text.innerHTML = '<p>Hier kun je elke query uitvoeren, ook meerdere tegelijk (gescheiden door een <code>;</code>). '
        + '<strong>Let op:</strong> wijzigingen (INSERT, UPDATE, DELETE, DROP …) worden hier écht uitgevoerd. '
        + 'Is er iets stuk? Met <em>Database resetten</em> zet je alles terug.</p>';
    el.hintBox.hidden = true;
    el.editor.value = state.progress.playground || '';
    el.editor.placeholder = 'SELECT * FROM producten LIMIT 10;';
    clearOutput();
}

async function runPlayground() {
    const sql = el.editor.value.trim();
    if (!sql) {
        showFeedback('info', 'Typ eerst een query in het zwarte vak.');
        return;
    }

    setBusy(el.runBtn, true);
    clearOutput();
    try {
        const outcome = await api('run', { method: 'POST', body: { sql } });
        el.output.innerHTML = renderOutcome(outcome);
        if (/\b(create|drop|alter|rename|truncate|insert|update|delete)\b/i.test(sql)) {
            loadSchema();
        }
    } catch (error) {
        showFeedback('error', 'Er ging iets mis', error.message);
    } finally {
        setBusy(el.runBtn, false);
    }
}

// ------------------------------------------------------------------ schema

function renderSchema(schema, error) {
    if (!schema) {
        el.schema.innerHTML = `<div class="sql-error"><strong>Database niet bereikbaar</strong>`
            + `<p>${escapeHtml(error?.hint || error?.message || 'Onbekende fout')}</p></div>`;
        return;
    }
    if (schema.length === 0) {
        el.schema.innerHTML = '<p class="muted small">De database bevat geen tabellen. Klik op "Database resetten".</p>';
        return;
    }
    el.schema.innerHTML = schema.map((table) => `
        <details class="table-def">
            <summary><span class="tname">${escapeHtml(table.name)}</span><span class="muted small">${table.rows} rijen</span></summary>
            <ul>${table.columns.map((c) => `
                <li>
                    <span class="cname ${c.key === 'PRI' ? 'pk' : ''}" title="${c.key === 'PRI' ? 'Primaire sleutel' : c.key === 'MUL' ? 'Verwijst naar een andere tabel' : ''}">
                        ${c.key === 'PRI' ? '🔑 ' : ''}${escapeHtml(c.name)}
                    </span>
                    <span class="ctype">${escapeHtml(c.type)}${c.nullable ? '' : ' · verplicht'}</span>
                </li>`).join('')}
            </ul>
            <button type="button" class="link-btn" data-preview="${escapeHtml(table.name)}">▶ Bekijk de eerste 10 rijen</button>
        </details>`).join('');
}

async function loadSchema() {
    try {
        const data = await api('schema');
        renderSchema(data.schema);
    } catch (error) {
        renderSchema(null, { message: error.message });
    }
}

// ---------------------------------------------------------------- confetti

function confetti(count) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const canvas = $('#confetti');
    const ctx = canvas.getContext('2d');
    const ratio = window.devicePixelRatio || 1;
    canvas.width = innerWidth * ratio;
    canvas.height = innerHeight * ratio;
    ctx.scale(ratio, ratio);

    const colors = ['#22d3ee', '#c86bfa', '#facc15', '#34e08a', '#ff7aa8', '#fb923c'];
    const pieces = Array.from({ length: count }, () => ({
        x: innerWidth / 2 + (Math.random() - .5) * 200,
        y: innerHeight * .55,
        vx: (Math.random() - .5) * 16,
        vy: -Math.random() * 16 - 6,
        size: Math.random() * 7 + 4,
        rotation: Math.random() * Math.PI,
        spin: (Math.random() - .5) * .3,
        color: colors[Math.floor(Math.random() * colors.length)],
    }));

    const start = performance.now();
    (function frame(now) {
        const elapsed = now - start;
        ctx.clearRect(0, 0, innerWidth, innerHeight);
        for (const p of pieces) {
            p.vy += .45;
            p.vx *= .99;
            p.x += p.vx;
            p.y += p.vy;
            p.rotation += p.spin;
            ctx.save();
            ctx.globalAlpha = Math.max(0, 1 - elapsed / 2200);
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rotation);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * .6);
            ctx.restore();
        }
        if (elapsed < 2200) {
            requestAnimationFrame(frame);
        } else {
            ctx.clearRect(0, 0, innerWidth, innerHeight);
        }
    })(start);
}

// ------------------------------------------------------------------- modus

function setMode(mode) {
    state.mode = mode;
    document.querySelectorAll('.tabs button').forEach((b) => {
        b.setAttribute('aria-selected', String(b.dataset.mode === mode));
    });
    document.body.classList.toggle('mode-playground', mode === 'playground');
    el.runBtn.textContent = mode === 'playground' ? '▶ Uitvoeren' : '▶ Controleren';
    el.solutionBtn.hidden = mode === 'playground';
    if (mode === 'playground') {
        openPlayground();
    } else {
        openExercise(state.currentId);
    }
}

// ------------------------------------------------------------- gebeurtenissen

el.runBtn.addEventListener('click', () => (state.mode === 'playground' ? runPlayground() : checkCurrent()));
el.solutionBtn.addEventListener('click', showSolution);
el.nextBtn.addEventListener('click', () => {
    const next = nextExercise();
    if (next) {
        openExercise(next.id);
        $('.work').scrollIntoView({ behavior: 'smooth', block: 'start' });
        el.editor.focus({ preventScroll: true });
    }
});

el.hintBox.addEventListener('toggle', () => {
    const exercise = currentExercise();
    if (el.hintBox.open && exercise && state.mode === 'exercises' && !isDone(exercise.id) && !state.progress.hints[exercise.id]) {
        state.progress.hints[exercise.id] = true;
        saveProgress();
        renderReward(exercise);
    }
});

el.topicList.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-id]');
    if (!button) return;
    state.currentId = button.dataset.id;
    setMode('exercises');
    $('.work').scrollIntoView({ behavior: 'smooth', block: 'start' });
    el.editor.focus({ preventScroll: true });
});

el.schema.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-preview]');
    if (!button) return;
    setMode('playground');
    el.editor.value = `SELECT * FROM ${button.dataset.preview} LIMIT 10;`;
    state.progress.playground = el.editor.value;
    saveProgress();
    runPlayground();
});

document.querySelectorAll('.tabs button').forEach((button) => {
    button.addEventListener('click', () => setMode(button.dataset.mode));
});

el.editor.addEventListener('input', () => {
    if (state.mode === 'playground') {
        state.progress.playground = el.editor.value;
    } else if (state.currentId) {
        state.progress.drafts[state.currentId] = el.editor.value;
    }
    saveProgress();
});

el.editor.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        el.runBtn.click();
    } else if (event.key === 'Tab' && !event.shiftKey) {
        event.preventDefault();
        el.editor.setRangeText('    ', el.editor.selectionStart, el.editor.selectionEnd, 'end');
    }
});

function closeBadgeModal() {
    $('#badge-modal').hidden = true;
    if (!el.nextBtn.hidden) el.nextBtn.focus();
}
$('#badge-modal-close').addEventListener('click', closeBadgeModal);
$('#badge-modal').addEventListener('click', (event) => {
    if (event.target.id === 'badge-modal') closeBadgeModal();
});
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !$('#badge-modal').hidden) closeBadgeModal();
});

$('#reset-btn').addEventListener('click', async (event) => {
    if (!confirm('De database wordt teruggezet naar de oorspronkelijke staat. Alle wijzigingen gaan verloren (je XP en badges blijven bewaard). Doorgaan?')) return;
    const button = event.currentTarget;
    setBusy(button, true);
    try {
        const data = await api('reset', { method: 'POST' });
        renderSchema(data.schema);
        clearOutput();
        showFeedback('success', 'Database gereset', 'Alles staat weer zoals in het begin.');
    } catch (error) {
        showFeedback('error', 'Resetten mislukt', error.message);
    } finally {
        setBusy(button, false);
    }
});

$('#clear-progress').addEventListener('click', () => {
    if (!confirm('Weet je zeker dat je al je XP, badges en opgeslagen antwoorden wilt wissen?')) return;
    state.progress = emptyProgress();
    saveProgress();
    setMode('exercises');
    openExercise(state.exercises[0]?.id);
});

// ------------------------------------------------------------------- start

(async function init() {
    try {
        const data = await api('init');
        state.exercises = data.exercises;
        buildTopics();
        renderSchema(data.schema, data.schemaError);
        openExercise(state.progress.current);
    } catch (error) {
        el.title.textContent = 'De trainer kon niet worden geladen';
        showFeedback('error', 'Er ging iets mis', error.message);
    }
})();
