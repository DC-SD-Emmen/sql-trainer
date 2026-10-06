<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PixelForge SQL-trainer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
    <div class="brand">
        <span class="logo" aria-hidden="true"><span></span><span></span><span></span><span></span></span>
        <div>
            <h1>PixelForge</h1>
            <p>SQL-trainer</p>
        </div>
    </div>

    <nav class="tabs" role="tablist">
        <button type="button" role="tab" data-mode="exercises" aria-selected="true">🎯 Missies</button>
        <button type="button" role="tab" data-mode="playground" aria-selected="false">🧪 Vrij oefenen</button>
    </nav>

    <div class="player" title="Verdien XP door missies te voltooien">
        <div class="player-rank">
            <span class="rank-label">Rang</span>
            <strong id="rank-name">Stagiair</strong>
        </div>
        <div class="xp">
            <div class="xp-bar"><div id="xp-fill"></div></div>
            <div class="xp-text"><span id="xp-total">0 XP</span><span id="xp-next"></span></div>
        </div>
    </div>

    <button type="button" class="btn btn-ghost btn-danger" id="reset-btn" title="Zet de database terug naar de oorspronkelijke staat">⟲ Database resetten</button>
</header>

<div class="layout">
    <aside class="panel sidebar" id="exercise-nav">
        <section class="badges">
            <h3>Badges <span class="muted" id="badge-count">0/10</span></h3>
            <div id="badge-list" class="badge-list"></div>
        </section>
        <h3 class="levels-title">Levels</h3>
        <div id="topic-list"></div>
        <button type="button" class="link-btn" id="clear-progress">Voortgang wissen</button>
    </aside>

    <main class="panel work">
        <div class="mission-head">
            <p class="eyebrow" id="ex-meta"></p>
            <span class="reward" id="ex-reward" hidden></span>
        </div>
        <h2 id="ex-title">Laden…</h2>

        <div class="chat" id="ex-chat" hidden>
            <div class="avatar" id="ex-avatar"></div>
            <div class="bubble">
                <div class="who" id="ex-who"></div>
                <p id="ex-story"></p>
            </div>
        </div>

        <div class="task">
            <div class="task-label" id="task-label">Jouw opdracht</div>
            <div class="ex-text" id="ex-text"></div>
        </div>

        <details class="hint" id="ex-hint-box">
            <summary id="ex-hint-summary">💡 Hint bekijken</summary>
            <div id="ex-hint"></div>
        </details>

        <label class="editor-label" for="editor"><span>Jouw SQL</span><span class="kbd-hint"><kbd>Ctrl</kbd> + <kbd>Enter</kbd> om te controleren</span></label>
        <textarea id="editor" spellcheck="false" autocapitalize="off" autocomplete="off"
                  placeholder="SELECT ..."></textarea>

        <div class="actions">
            <button type="button" class="btn btn-primary" id="run-btn">▶ Controleren</button>
            <span class="spacer"></span>
            <button type="button" class="btn btn-ghost" id="solution-btn">👀 Antwoord bekijken</button>
            <button type="button" class="btn btn-success" id="next-btn" hidden>Volgende missie →</button>
        </div>

        <div id="feedback" class="feedback" hidden></div>
        <div id="output"></div>
    </main>

    <aside class="panel schema">
        <h3>🗄️ Database</h3>
        <p class="muted small">Klik op een tabel om te zien welke kolommen erin zitten.</p>
        <div id="schema-list"></div>
    </aside>
</div>

<div class="toast-area" id="toasts" aria-live="polite"></div>

<div class="modal" id="badge-modal" hidden>
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="badge-modal-title">
        <p class="eyebrow">Badge ontgrendeld!</p>
        <div class="badge-big" id="badge-modal-icon"></div>
        <h2 id="badge-modal-title"></h2>
        <p id="badge-modal-text" class="muted"></p>
        <button type="button" class="btn btn-primary" id="badge-modal-close">Gaaf, door!</button>
    </div>
</div>

<canvas id="confetti" aria-hidden="true"></canvas>

<script src="assets/app.js"></script>
</body>
</html>
