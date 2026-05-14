/**
 * TrikeFare Onboarding Tutorial System
 * ─────────────────────────────────────
 * Modular, adaptive, SaaS-quality step-by-step guided tour.
 * Automatically detects key UI elements, shows spotlight with
 * tooltip, and persists completion state via localStorage.
 *
 * Public API (window.TrikeTutorial):
 *   .start()   — Launch from step 1
 *   .replay()  — Alias for start()
 *   .skip()    — Dismiss and mark as seen
 *   .next()    — Advance one step
 *   .back()    — Go back one step
 */

(function (window, document) {
    'use strict';

    /* ============================================================
       ① CONFIGURATION — All tunables in one place
       ============================================================ */
    const CFG = {
        LS_KEY: 'trikefare_tutorialSeen',      // localStorage flag
        PADDING: 14,                            // spotlight padding around element (px)
        RETRY_MS: 600,                          // retry interval if element not found
        RETRY_MAX: 10,                          // max retries per step
        TRANSITION_MS: 380,                     // ms between steps
        MOBILE_BREAKPOINT: 600,                 // px — tooltip placement changes
    };

    /* ============================================================
       ② STEP DEFINITIONS
          Each step describes what to highlight and what to say.
          `selector`  — CSS selector for the target element
          `fallback`  — optional secondary selector
          `icon`      — FontAwesome class for the badge
          `title`     — headline text
          `desc`      — body explanation
          `tip`       — optional extra tip (green callout)
          `position`  — 'auto'|'top'|'bottom'|'left'|'right'
          `scrollIntoView` — boolean
          `noHighlight`    — true for generic steps with no target
       ============================================================ */
    const STEPS = [
        {
            selector: '#mapSearchInput',
            icon: 'fa-solid fa-magnifying-glass',
            title: 'Search Your Destination',
            desc: 'Type any landmark, street, or barangay in General Santos City here. As you type, live results will appear below the search box.',
            tip: 'Try searching "SM Gensan", "Lagao", or "KCC Mall" to get started!',
            position: 'bottom',
            scrollIntoView: false,
        },
        {
            selector: '#btnLocate',
            icon: 'fa-solid fa-location-crosshairs',
            title: 'Locate Your Position',
            desc: 'Tap this button to find your current GPS location on the map. This is the starting point for fare calculation.',
            tip: 'Make sure location permission is granted in your browser for accurate readings.',
            position: 'left',
            scrollIntoView: false,
        },
        {
            selector: '#btnMarkMode',
            icon: 'fa-solid fa-thumbtack',
            title: 'Pin Mode — Drop a Manual Point',
            desc: 'Activate this to tap anywhere on the map and drop a custom pin. Useful when GPS has issues or you want to plan a route manually.',
            position: 'left',
            scrollIntoView: false,
        },
        {
            selector: '.live-stats',
            icon: 'fa-solid fa-gauge-high',
            title: 'Live Ride Statistics',
            desc: 'These cards update in real-time while you ride: Distance traveled, current Speed, and the running Fare estimate based on official GenSan rates.',
            position: 'top',
            scrollIntoView: true,
        },
        {
            selector: '#fareCard',
            icon: 'fa-solid fa-coins',
            title: 'Estimated Fare',
            desc: 'Your fare is calculated using the official ordinance: ₱15 base for the first 4 km, then ₱1 per additional km. Night surcharge applies between 9 PM–5 AM.',
            tip: 'The fare updates every second as you move!',
            position: 'top',
            scrollIntoView: true,
        },
        {
            selector: '#baseFareAmount',
            icon: 'fa-solid fa-gear',
            title: 'Adjust Base Fare',
            desc: 'Change the base fare amount if the driver quotes a different starting price. This directly affects the final estimate shown.',
            position: 'top',
            scrollIntoView: true,
        },
        {
            selector: '#nightToggle',
            fallback: '.options-row:nth-child(2)',
            useParent: 2,
            icon: 'fa-solid fa-moon',
            title: 'Night Surcharge Toggle',
            desc: 'Enable this to add the night surcharge (₱5 by default) for late-night rides. The amount is adjustable next to the toggle.',
            position: 'top',
            scrollIntoView: true,
        },
        {
            selector: '#btnStart',
            icon: 'fa-solid fa-play',
            title: 'Start Tracking Your Ride',
            desc: 'Tap START to begin GPS tracking. The app will record your route, distance, speed, and calculate the fare in real-time as you travel.',
            tip: 'Once started, a Stop button appears. Tap it when you arrive to see your full Ride Summary.',
            position: 'top',
            scrollIntoView: true,
        },
        {
            selector: null,
            noHighlight: true,
            icon: 'fa-solid fa-list-check',
            title: 'Manual Route Selector',
            desc: 'No GPS? Open the panel and use the Manual Route Selector dropdown to pick a common GenSan route and instantly get the estimated fare based on known distances.',
            tip: 'Great for planning ahead or checking fares for routes you frequently travel!',
            position: 'auto',
            scrollIntoView: false,
        },
        {
            selector: '.hamburger-btn',
            fallback: '#panel .app-header button',
            icon: 'fa-solid fa-bars',
            title: 'Menu — More Features',
            desc: 'Tap the hamburger menu to access: Ride History, the official Fare Guide, Community Forum, and app settings like Dark Mode.',
            position: 'bottom',
            scrollIntoView: true,
        },
    ];

    /* ============================================================
       ③ STATE
       ============================================================ */
    let currentStep = 0;
    let isActive = false;
    let retryCount = 0;
    let retryTimer = null;
    let lastHighlighted = null;
    let arrowEl = null;

    /* ============================================================
       ④ DOM ELEMENT REFERENCES (built lazily)
       ============================================================ */
    let UI = {};

    function buildDOM() {
        if (document.getElementById('tut-overlay')) return; // already built

        /* ── SVG Overlay ── */
        const overlayHTML = `
<div id="tut-overlay">
    <svg id="tut-svg-mask" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <mask id="tut-hole-mask">
                <rect id="tut-dim-rect" width="100%" height="100%" fill="white"/>
                <rect id="tut-spotlight-hole" x="0" y="0" width="0" height="0" rx="14" ry="14" fill="black"/>
            </mask>
        </defs>
        <rect id="tut-dim-bg" width="100%" height="100%" fill="rgba(0,0,0,0.50)" mask="url(#tut-hole-mask)"/>
        <rect id="tut-spotlight-ring" x="0" y="0" width="0" height="0" rx="14" ry="14"/>
    </svg>
</div>`;

        /* ── Tooltip ── */
        const tooltipHTML = `
<div id="tut-tooltip" role="dialog" aria-label="Tutorial step">
    <div id="tut-step-counter"><i class="fa-solid fa-circle-dot"></i> <span id="tut-step-label">Step 1 of ${STEPS.length}</span></div>
    <div id="tut-icon"><i class="fa-solid fa-star"></i></div>
    <div id="tut-title">Welcome</div>
    <div id="tut-desc"></div>
    <div id="tut-tip-block" class="tut-tip" style="display:none;">
        <i class="fa-solid fa-lightbulb"></i>
        <span id="tut-tip-text"></span>
    </div>
    <div id="tut-progress-dots"></div>
    <div id="tut-controls">
        <button id="tut-btn-back" aria-label="Previous step"><i class="fa-solid fa-chevron-left"></i> Back</button>
        <button id="tut-btn-next" aria-label="Next step">Next <i class="fa-solid fa-chevron-right"></i></button>
    </div>
</div>`;

        /* ── Skip button ── */
        const skipHTML = `<button id="tut-btn-skip" aria-label="Skip tutorial"><i class="fa-solid fa-xmark"></i> Skip Tour</button>`;

        /* ── Welcome screen ── */
        const welcomeHTML = `
<div id="tut-welcome" role="dialog" aria-label="Welcome to TrikeFare">
    <div id="tut-welcome-card">
        <div id="tut-welcome-logo">🛺</div>
        <h2>Welcome to TrikeFare!</h2>
        <p id="tut-welcome-sub">Your smart tricycle fare calculator for General Santos City. Let us show you around in under 2 minutes.</p>
        <div class="tut-feature-list">
            <div class="tut-feature-item">
                <div class="fi-icon"><i class="fa-solid fa-location-dot"></i></div>
                <span>GPS-based real-time fare tracking</span>
            </div>
            <div class="tut-feature-item">
                <div class="fi-icon"><i class="fa-solid fa-users"></i></div>
                <span>Community-reported fares from locals</span>
            </div>
            <div class="tut-feature-item">
                <div class="fi-icon"><i class="fa-solid fa-list-check"></i></div>
                <span>Manual route lookup for common trips</span>
            </div>
        </div>
        <div class="tut-welcome-btn-row">
            <button id="tut-btn-skip-welcome">Skip</button>
            <button id="tut-btn-start-tour"><i class="fa-solid fa-play"></i> Start Tour</button>
        </div>
    </div>
</div>`;

        /* ── Done screen ── */
        const doneHTML = `
<div id="tut-done" role="dialog" aria-label="Tutorial complete">
    <div id="tut-done-card">
        <div id="tut-done-icon">🎉</div>
        <h2>You're All Set!</h2>
        <p>You now know how to use TrikeFare Gensan. Start by searching your destination or tapping the GPS button to begin tracking!</p>
        <button id="tut-btn-done"><i class="fa-solid fa-flag-checkered"></i> Let's Ride!</button>
    </div>
</div>`;

        /* ── Help FAB ── */
        const fabHTML = `<button id="tut-help-fab" data-label="Show Tutorial" aria-label="Show tutorial"><i class="fa-solid fa-circle-question"></i></button>`;

        /* Inject all into body */
        [overlayHTML, tooltipHTML, skipHTML, welcomeHTML, doneHTML, fabHTML].forEach(html => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            document.body.appendChild(wrapper.firstElementChild);
        });

        /* Cache references */
        UI = {
            overlay: document.getElementById('tut-overlay'),
            svgMask: document.getElementById('tut-svg-mask'),
            dimRect: document.getElementById('tut-dim-bg'),
            holeRect: document.getElementById('tut-spotlight-hole'),
            ringRect: document.getElementById('tut-spotlight-ring'),
            tooltip: document.getElementById('tut-tooltip'),
            stepLabel: document.getElementById('tut-step-label'),
            icon: document.getElementById('tut-icon'),
            title: document.getElementById('tut-title'),
            desc: document.getElementById('tut-desc'),
            tipBlock: document.getElementById('tut-tip-block'),
            tipText: document.getElementById('tut-tip-text'),
            progressDots: document.getElementById('tut-progress-dots'),
            btnBack: document.getElementById('tut-btn-back'),
            btnNext: document.getElementById('tut-btn-next'),
            btnSkip: document.getElementById('tut-btn-skip'),
            welcome: document.getElementById('tut-welcome'),
            btnStartTour: document.getElementById('tut-btn-start-tour'),
            btnSkipWelcome: document.getElementById('tut-btn-skip-welcome'),
            done: document.getElementById('tut-done'),
            btnDone: document.getElementById('tut-btn-done'),
            helpFab: document.getElementById('tut-help-fab'),
        };

        bindEvents();
    }

    /* ============================================================
       ⑤ EVENT BINDINGS
       ============================================================ */
    function bindEvents() {
        UI.btnStartTour.addEventListener('click', () => {
            hideWelcome();
            runStep(0);
        });

        UI.btnSkipWelcome.addEventListener('click', () => {
            hideWelcome();
            markSeen();
        });

        UI.btnSkip.addEventListener('click', skipTutorial);
        UI.btnBack.addEventListener('click', () => runStep(currentStep - 1));
        UI.btnNext.addEventListener('click', onNext);
        UI.btnDone.addEventListener('click', finishTutorial);
        UI.helpFab.addEventListener('click', () => TrikeTutorial.replay());

        /* Close overlay by clicking dim area (outside spotlight + tooltip) */
        UI.overlay.addEventListener('click', function (e) {
            // Only skip if they click the dim region, not inside tooltip
            if (e.target === UI.overlay) skipTutorial();
        });

        /* Keyboard navigation */
        document.addEventListener('keydown', onKeyDown);
    }

    function onKeyDown(e) {
        if (!isActive) return;
        if (e.key === 'ArrowRight' || e.key === 'Enter') onNext();
        if (e.key === 'ArrowLeft') { if (currentStep > 0) runStep(currentStep - 1); }
        if (e.key === 'Escape') skipTutorial();
    }

    function onNext() {
        if (currentStep < STEPS.length - 1) {
            runStep(currentStep + 1);
        } else {
            showDone();
        }
    }

    /* ============================================================
       ⑥ ELEMENT DETECTION (adaptive, with retry)
       ============================================================ */
    function detectElement(step) {
        let el = null;

        // Try primary selector
        if (step.selector) {
            el = document.querySelector(step.selector);
        }

        // Try fallback
        if (!el && step.fallback) {
            el = document.querySelector(step.fallback);
        }

        // Walk up to a parent if step requests it (e.g. checkbox -> options-row)
        if (el && step.useParent) {
            let levels = step.useParent;
            let parent = el;
            while (levels-- > 0 && parent.parentElement) {
                parent = parent.parentElement;
            }
            el = parent;
        }

        // Verify it's visible (allow zero-size checkboxes if parent is used)
        if (el && !isElementVisible(el)) el = null;

        return el;
    }

    function isElementVisible(el) {
        if (!el) return false;
        const rect = el.getBoundingClientRect();
        const style = getComputedStyle(el);
        return (
            rect.width > 0 &&
            rect.height > 0 &&
            rect.top < window.innerHeight &&
            rect.bottom > 0 &&
            style.visibility !== 'hidden' &&
            style.display !== 'none'
        );
    }

    /* ============================================================
       ⑦ STEP RUNNER
       ============================================================ */
    function runStep(index, retries) {
        if (index < 0 || index >= STEPS.length) return;
        retries = retries || 0;

        currentStep = index;
        const step = STEPS[index];

        // Clear any existing retry timer
        clearRetry();

        // Animate tooltip out briefly for transition feel
        UI.tooltip.classList.remove('tut-tt-visible');

        // Remove previous highlight
        clearHighlight();

        // Ensure the panel is expanded so elements inside are visible
        const panel = document.getElementById('panel');
        if (panel && panel.classList.contains('collapsed')) {
            panel.classList.remove('collapsed');
        }

        // noHighlight steps skip element detection entirely
        if (step.noHighlight) {
            renderInfoStep(step, index);
            return;
        }

        // Try to find the target element
        let el = detectElement(step);

        if (!el && retries < CFG.RETRY_MAX) {
            // Element not found yet — retry
            retryTimer = setTimeout(() => runStep(index, retries + 1), CFG.RETRY_MS);
            return;
        }

        // Force-scroll element into view and wait until it's actually visible
        if (el && step.scrollIntoView) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
        }

        // Render after scroll settles (or immediately if no scroll needed)
        const delay = (el && step.scrollIntoView) ? 300 : 0;
        setTimeout(() => {
            // Re-read position after scroll
            positionSpotlight(el);
            updateTooltipContent(step, index);
            positionTooltip(el, step.position || 'auto');
            updateProgressDots(index);
            applyHighlight(el);

            requestAnimationFrame(() => {
                UI.tooltip.classList.add('tut-tt-visible');
            });
        }, delay);
    }

    /** Render a pure informational step — no spotlight, no highlight */
    function renderInfoStep(step, index) {
        // Hide the spotlight hole (no cutout)
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        UI.svgMask.setAttribute('viewBox', `0 0 ${vw} ${vh}`);
        setSpotlightRect(-100, -100, 0, 0); // move off-screen

        updateTooltipContent(step, index);
        positionTooltip(null, 'auto'); // center on screen
        updateProgressDots(index);

        requestAnimationFrame(() => {
            UI.tooltip.classList.add('tut-tt-visible');
        });
    }

    /* ============================================================
       ⑧ SPOTLIGHT POSITIONING
       ============================================================ */
    function positionSpotlight(el) {
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        // Update SVG dimensions to full viewport
        UI.svgMask.setAttribute('viewBox', `0 0 ${vw} ${vh}`);

        if (!el) {
            // No element — full dim, no hole
            setSpotlightRect(vw / 2 - 50, vh / 2 - 30, 100, 60);
            return;
        }

        const rect = el.getBoundingClientRect();
        const pad = CFG.PADDING;

        const x = Math.max(0, rect.left - pad);
        const y = Math.max(0, rect.top - pad);
        const w = Math.min(vw - x, rect.width + pad * 2);
        const h = Math.min(vh - y, rect.height + pad * 2);

        setSpotlightRect(x, y, w, h);
    }

    function setSpotlightRect(x, y, w, h) {
        UI.holeRect.setAttribute('x', x);
        UI.holeRect.setAttribute('y', y);
        UI.holeRect.setAttribute('width', w);
        UI.holeRect.setAttribute('height', h);

        // Ring slightly larger
        UI.ringRect.setAttribute('x', x - 2);
        UI.ringRect.setAttribute('y', y - 2);
        UI.ringRect.setAttribute('width', w + 4);
        UI.ringRect.setAttribute('height', h + 4);
    }

    /* ============================================================
       ⑨ TOOLTIP CONTENT
       ============================================================ */
    function updateTooltipContent(step, index) {
        UI.stepLabel.textContent = `Step ${index + 1} of ${STEPS.length}`;

        // Update icon
        UI.icon.innerHTML = `<i class="${step.icon || 'fa-solid fa-circle-info'}"></i>`;

        // Title & description
        UI.title.textContent = step.title;
        UI.desc.textContent = step.desc;

        // Tip block
        if (step.tip) {
            UI.tipText.textContent = step.tip;
            UI.tipBlock.style.display = 'flex';
        } else {
            UI.tipBlock.style.display = 'none';
        }

        // Button states
        UI.btnBack.disabled = (index === 0);

        if (index === STEPS.length - 1) {
            UI.btnNext.innerHTML = `<i class="fa-solid fa-flag-checkered"></i> Finish`;
            UI.btnNext.classList.add('tut-finish');
        } else {
            UI.btnNext.innerHTML = `Next <i class="fa-solid fa-chevron-right"></i>`;
            UI.btnNext.classList.remove('tut-finish');
        }
    }

    /* ============================================================
       ⑩ TOOLTIP POSITIONING — Responsive, never off-screen
       ============================================================ */
    function positionTooltip(el, preferredPos) {
        // Remove old arrow
        if (arrowEl) { arrowEl.remove(); arrowEl = null; }

        const vw = window.innerWidth;
        const vh = window.innerHeight;
        const ttW = Math.min(340, vw * 0.92);
        const ttH = UI.tooltip.offsetHeight || 220;

        let left, top, arrowDir;

        if (!el) {
            // Center on screen
            left = (vw - ttW) / 2;
            top = (vh - ttH) / 2;
            applyTooltipPos(left, top, ttW);
            return;
        }

        const rect = el.getBoundingClientRect();
        const pad = CFG.PADDING + 12;
        const isMobile = vw <= CFG.MOBILE_BREAKPOINT;

        // Auto-detect best position
        let pos = preferredPos;
        if (pos === 'auto') {
            pos = rect.top > vh / 2 ? 'top' : 'bottom';
        }

        // On mobile, always prefer bottom or top
        if (isMobile) {
            pos = rect.top > vh * 0.55 ? 'top' : 'bottom';
        }

        // Calculate
        if (pos === 'bottom') {
            left = clamp(rect.left + rect.width / 2 - ttW / 2, 8, vw - ttW - 8);
            top = rect.bottom + pad + CFG.PADDING;
            arrowDir = 'up';
            if (top + ttH > vh - 16) { pos = 'top'; }
        }

        if (pos === 'top') {
            left = clamp(rect.left + rect.width / 2 - ttW / 2, 8, vw - ttW - 8);
            top = rect.top - ttH - pad - CFG.PADDING;
            arrowDir = 'down';
            if (top < 8) { pos = 'bottom'; top = rect.bottom + pad; arrowDir = 'up'; }
        }

        if (pos === 'left') {
            left = rect.left - ttW - pad;
            top = clamp(rect.top + rect.height / 2 - ttH / 2, 8, vh - ttH - 8);
            arrowDir = 'right';
            if (left < 8) { pos = 'right'; left = rect.right + pad; arrowDir = 'left'; }
        }

        if (pos === 'right') {
            left = rect.right + pad;
            top = clamp(rect.top + rect.height / 2 - ttH / 2, 8, vh - ttH - 8);
            arrowDir = 'left';
            if (left + ttW > vw - 8) { pos = 'bottom'; left = clamp(rect.left + rect.width / 2 - ttW / 2, 8, vw - ttW - 8); top = rect.bottom + pad; arrowDir = 'up'; }
        }

        // Final clamp
        left = clamp(left, 8, vw - ttW - 8);
        top = clamp(top, 8, vh - ttH - 8);

        applyTooltipPos(left, top, ttW);
        drawArrow(arrowDir, rect, left, top, ttW, ttH);
    }

    function applyTooltipPos(left, top, width) {
        UI.tooltip.style.left = left + 'px';
        UI.tooltip.style.top = top + 'px';
        UI.tooltip.style.width = width + 'px';
    }

    function drawArrow(dir, elRect, ttLeft, ttTop, ttW, ttH) {
        if (!dir) return;
        arrowEl = document.createElement('div');
        arrowEl.className = `tut-arrow tut-arrow-${dir === 'up' ? 'up' : dir === 'down' ? 'down' : ''}`;

        let ax, ay;
        if (dir === 'up') {
            ax = clamp(elRect.left + elRect.width / 2 - 10, ttLeft + 10, ttLeft + ttW - 30);
            ay = ttTop - 12;
        } else if (dir === 'down') {
            ax = clamp(elRect.left + elRect.width / 2 - 10, ttLeft + 10, ttLeft + ttW - 30);
            ay = ttTop + ttH;
        } else {
            return; // skip side arrows for simplicity
        }

        arrowEl.style.left = ax + 'px';
        arrowEl.style.top = ay + 'px';
        document.body.appendChild(arrowEl);
    }

    /* ============================================================
       ⑪ HIGHLIGHT (element pulse ring)
       ============================================================ */
    function applyHighlight(el) {
        if (!el) return;
        el.classList.add('tut-highlight-pulse');
        lastHighlighted = el;
    }

    function clearHighlight() {
        if (lastHighlighted) {
            lastHighlighted.classList.remove('tut-highlight-pulse');
            lastHighlighted = null;
        }
    }

    /* ============================================================
       ⑫ PROGRESS DOTS
       ============================================================ */
    function updateProgressDots(current) {
        UI.progressDots.innerHTML = '';
        STEPS.forEach((_, i) => {
            const dot = document.createElement('div');
            dot.className = 'tut-dot';
            if (i < current) dot.classList.add('done');
            if (i === current) dot.classList.add('active');
            dot.setAttribute('title', `Step ${i + 1}`);
            dot.addEventListener('click', () => runStep(i));
            UI.progressDots.appendChild(dot);
        });
    }

    /* ============================================================
       ⑬ WELCOME / DONE / SKIP SCREENS
       ============================================================ */
    function showWelcome() {
        buildDOM();
        UI.welcome.classList.add('tut-visible');
        UI.helpFab.style.display = 'none';
    }

    function hideWelcome() {
        UI.welcome.classList.remove('tut-visible');
    }

    function showDone() {
        clearHighlight();
        UI.overlay.classList.remove('tut-visible');
        UI.tooltip.classList.remove('tut-tt-visible');
        UI.btnSkip.classList.remove('tut-visible');
        if (arrowEl) { arrowEl.remove(); arrowEl = null; }
        UI.done.classList.add('tut-visible');
        markSeen();
    }

    function finishTutorial() {
        UI.done.classList.remove('tut-visible');
        destroyAllDOM();
        isActive = false;
    }

    function skipTutorial() {
        clearHighlight();
        clearRetry();
        if (arrowEl) { arrowEl.remove(); arrowEl = null; }
        destroyAllDOM();
        markSeen();
        isActive = false;
    }

    /**
     * Fully remove every tutorial-injected DOM element.
     * After this, only buildDOM() can recreate them (on replay).
     */
    function destroyAllDOM() {
        const ids = [
            'tut-overlay', 'tut-tooltip', 'tut-btn-skip',
            'tut-welcome', 'tut-done', 'tut-help-fab'
        ];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.remove();
        });
        // Remove any stray arrows
        document.querySelectorAll('.tut-arrow').forEach(a => a.remove());
        // Clear UI cache so buildDOM() will re-create everything
        UI = {};
        restoreScroll();
    }

    function markSeen() {
        try { localStorage.setItem(CFG.LS_KEY, 'true'); } catch (_) { }
    }

    function hasSeen() {
        try { return localStorage.getItem(CFG.LS_KEY) === 'true'; } catch (_) { return false; }
    }

    /* ============================================================
       ⑭ START / REPLAY
       ============================================================ */
    function startTutorial() {
        // Destroy any leftover DOM so buildDOM() creates fresh elements
        destroyAllDOM();
        buildDOM();
        isActive = true;
        currentStep = 0;

        UI.overlay.classList.add('tut-visible');
        UI.btnSkip.classList.add('tut-visible');
        if (UI.helpFab) UI.helpFab.style.display = 'none';
        UI.done.classList.remove('tut-visible');
        UI.welcome.classList.remove('tut-visible');

        runStep(0);
    }

    /* ============================================================
       ⑮ UTILITIES
       ============================================================ */
    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function clearRetry() {
        if (retryTimer) { clearTimeout(retryTimer); retryTimer = null; }
        retryCount = 0;
    }

    /** Restore scroll on body, html, panel, and app after tutorial exits */
    function restoreScroll() {
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';
        document.documentElement.style.overflow = '';
        const panel = document.getElementById('panel');
        if (panel) { panel.style.overflow = ''; panel.style.overflowY = 'auto'; }
        const app = document.getElementById('app');
        if (app) { app.style.overflow = ''; }
    }

    /* ============================================================
       ⑯ AUTO-INIT — Show welcome on first visit
       ============================================================ */
    function init() {
        // Only show tutorial on first visit; otherwise don't inject DOM at all
        if (!hasSeen()) {
            buildDOM();
            if (UI.helpFab) UI.helpFab.style.display = 'none';
            // Small delay so the app finishes rendering
            setTimeout(showWelcome, 900);
        }
        // If already seen, no DOM is injected — replay via sidebar calls startTutorial()
    }

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM already ready — defer slightly to not block app init
        setTimeout(init, 300);
    }

    /* ============================================================
       ⑰ PUBLIC API
       ============================================================ */
    window.TrikeTutorial = {
        start: startTutorial,
        replay: startTutorial,
        skip: skipTutorial,
        next: onNext,
        back: () => { if (currentStep > 0) runStep(currentStep - 1); },
        reset: () => { try { localStorage.removeItem(CFG.LS_KEY); } catch (_) { } },
    };

}(window, document));
