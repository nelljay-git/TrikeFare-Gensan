<?php
session_start();
if (empty($_SESSION['app_token'])) {
    $_SESSION['app_token'] = bin2hex(random_bytes(32));
}
$appToken = $_SESSION['app_token'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="description"
        content="TrikeFare Gensan - GPS-based tricycle fare calculator for General Santos City commuters">
    <meta name="theme-color" content="#0a0e1a">
    <link rel="manifest" href="manifest.json" crossorigin="use-credentials">
    <link rel="icon" type="image/png" sizes="192x192" href="icon-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="icon-512x512.png">
    <link rel="apple-touch-icon" href="icon-192x192.png">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="tutorial.css">
    <title>TrikeFare Gensan - BETA</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">


</head>

<body>
    <div id="offlineBanner" class="offline-banner">
        <div class="offline-indicator-dot"></div>
        <span>You are currently offline. Some features may be limited.</span>
    </div>
    <div id="app">
        <div class="map-wrapper">
            <div id="map"></div>
            <div id="markModeHint" class="mark-mode-hint">Tap anywhere on map to drop pin</div>
            <button class="locate-btn" id="btnMarkMode" onclick="toggleMarkMode()" title="Toggle Mark Mode"
                style="bottom: 35px; right: 80px; display: flex;">
                <span style="font-size: 1rem"><i class="fa-solid fa-thumbtack"></i></span>
            </button>
            <button class="locate-btn" id="btnLocateDest" onclick="locateDest()" title="Locate Destination"
                style="bottom: 35px; display: none; right:140px; "><i class="fa-solid fa-location-dot"></i></button>
            <button class="locate-btn" id="btnLocate" onclick="locateMe()" title="Locate Me"><i
                    class="fa-solid fa-location-crosshairs"></i></button>
        </div>
        <div class="map-search-container">
            <div class="map-search-box">
                <span style="color:var(--text-dim)"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="mapSearchInput" class="map-search-input"
                    placeholder="Search destination in GenSan..." autocomplete="off">
                <div class="search-spinner" id="searchSpinner"></div>
                <button class="btn-search-clear" id="btnSearchClear" onclick="clearSearch()"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="map-search-results" id="mapSearchResults"></div>
        </div>
        <div class="longpress-ring" id="longpressRing">
            <svg viewBox="0 0 56 56">
                <circle class="ring-bg" cx="28" cy="28" r="24" />
                <circle class="ring-fg" cx="28" cy="28" r="24" />
            </svg>
        </div>

        <!-- SIDEBAR -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">Menu</div>
                <button class="sidebar-close" onclick="closeSidebar()">×</button>
            </div>
            <div class="sidebar-content">
                <div class="sidebar-item" id="sidebarAuth" onclick="openAuthModal(); closeSidebar()"
                    style="background: rgba(108, 92, 231, 0.1); border: 1px solid rgba(108, 92, 231, 0.2); margin-bottom: 12px;">
                    <span class="sidebar-item-icon"><i class="fa-solid fa-user-astronaut"></i></span>
                    <div style="display:flex; flex-direction:column;">
                        <span id="sidebarAuthText" style="font-weight:700;">Login / Sign Up</span>
                        <span id="sidebarStreakText" style="font-size:0.65rem; color:var(--accent); display:none;">
                            <i class="fa-solid fa-fire"></i> 0 Day Streak
                        </span>
                    </div>
                </div>
                <div class="sidebar-item" id="sidebarInstall" onclick="installPwa()"
                    style="display:none; background: linear-gradient(135deg, var(--accent), #5a4bd1); color: #fff; border:none; box-shadow: 0 2px 8px rgba(108, 92, 231, .3);">
                    <span class="sidebar-item-icon"><i class="fa-solid fa-download"></i></span>
                    <span>Install App</span>
                </div>
                <div class="sidebar-item" onclick="showCabRoutes(); closeSidebar()">
                    <span class="sidebar-item-icon"><i class="fa-solid fa-bus"></i></span>
                    <span style="font-weight: 700;">Find Cab Route</span>
                </div>
                <div class="sidebar-item" onclick="openHistoryModal(); closeSidebar()">
                    <span class="sidebar-item-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                    <span>Ride History</span>
                </div>
                <div class="sidebar-item" onclick="openFareGuide(); closeSidebar()">
                    <span class="sidebar-item-icon"><i class="fa-solid fa-circle-info"></i></span>
                    <span>Fare Guide</span>
                </div>
                <div class="sidebar-item" onclick="openCommunityForum(); closeSidebar()">
                    <span class="sidebar-item-icon"><i class="fa-solid fa-users"></i></span>
                    <span>Community Forum</span>
                </div>

                <div class="sidebar-item" onclick="window.location.href='updates.php'; closeSidebar()">
                    <span class="sidebar-item-icon"><i class="fa-solid fa-bullhorn"></i></span>
                    <span>What's New</span>
                </div>
                <div class="sidebar-item" onclick="window.location.href='leaderboards.php'; closeSidebar()">
                    <span class="sidebar-item-icon"><i class="fa-solid fa-trophy"></i></span>
                    <span>Leaderboards</span>
                </div>
                <div class="sidebar-item" onclick="openSettingsModal(); closeSidebar()">
                    <span class="sidebar-item-icon"><i class="fa-solid fa-gear"></i></span>
                    <span>Settings</span>
                </div>
                <div class="sidebar-item" onclick="openDonateModal(); closeSidebar()"
                    style="background: rgba(255, 184, 0, 0.15); color: #f0a500; margin-top: auto;">
                    <span class="sidebar-item-icon"><i class="fa-solid fa-mug-hot"></i></span>
                    <span style="font-weight: 700;">Buy Me a Coffee</span>
                </div>
            </div>
        </div>

        <div id="panel">
            <div class="drag-handle" onclick="togglePanel()"><span style="opacity: 0;">---</span></div>
            <div class="app-header" onclick="togglePanel()">
                <div>
                    <span class="app-title" id="appTitle">TrikeFare Gensan</span>
                    <span class="app-badge">BETA</span>
                </div>
                <button class="hamburger-btn" onclick="event.stopPropagation(); openSidebar()"><i
                        class="fa-solid fa-bars"></i></button>
            </div>

            <div class="status-bar">
                <span class="status-dot" id="statusDot"></span>
                <span id="statusText">Checking... |</span>
                <i class="fa-solid fa-arrows-rotate status-retry-icon" id="statusRetryIcon" style="display:none;"
                    onclick="retryGPS()" title="Retry GPS"></i>
                <div class="live-clock" id="liveClock">—</div>
            </div>

            <div class="dest-fare-card" id="destFareCard">
                <div class="dest-fare-header">
                    <div class="dest-fare-name" id="destFareName">—</div>
                    <button class="dest-fare-dismiss" id="btnDismissDest" onclick="dismissDestFare()" title="Dismiss"><i
                            class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="dest-fare-grid">
                    <div class="dest-fare-item">
                        <div class="dfl"><i class="fa-solid fa-ruler"></i> Distance</div>
                        <div class="dfv" id="destFareDist">—</div>
                    </div>
                    <div class="dest-fare-item">
                        <div class="dfl"><i class="fa-solid fa-stopwatch"></i> ETA</div>
                        <div class="dfv" id="destFareETA">—</div>
                    </div>
                    <div class="dest-fare-item">
                        <div class="dfl"><i class="fa-solid fa-coins"></i> Est. Fare</div>
                        <div class="dfv fare-color" id="destFareCost">—</div>
                    </div>
                </div>
                <div id="destFareFixedRow"
                    style="display:none; justify-content:space-between; align-items:center; background:rgba(108,92,231,0.1); border-radius:8px; padding:8px 12px; margin-top:8px; border:1px solid rgba(108,92,231,0.2);">
                    <span style="font-size:0.75rem;color:var(--text-dim);font-weight:600;"><i
                            class="fa-solid fa-lock"></i> Est. Fixed Fare</span>
                    <strong style="font-size:1.1rem;color:var(--accent);" id="destFareFixedCost">—</strong>
                </div>
                <div class="dest-fare-breakdown" id="destFareBreakdown"></div>

                <!-- Community Fare Section -->
                <div class="community-fare-section" id="communityFareSection" style="display:none;">
                    <div class="community-header">
                        <div class="community-title-wrapper">
                            <span><i class="fa-solid fa-users"></i> Community Data</span>
                        </div>
                        <div class="community-filters-wrapper">
                            <div class="transport-filter-mini">
                                <i class="fa-regular fa-clock"
                                    style="font-size: 0.65rem; color: var(--text-muted);"></i>
                                <select id="communityTimeFilter" onchange="fetchCommunityFares()"
                                    style="background:none; border:none; color:var(--primary); font-size:0.75rem; font-weight:700; cursor:pointer; font-family:inherit; outline:none;">
                                    <option value="auto">Auto (Time)</option>
                                    <option value="day">Day Fares</option>
                                    <option value="night">Night Fares</option>
                                    <option value="all">All Fares</option>
                                </select>
                            </div>
                            <div class="transport-filter-mini">
                                <i class="fa-solid fa-car-side"
                                    style="font-size: 0.65rem; color: var(--text-muted);"></i>
                                <select id="communityTransportFilter" onchange="fetchCommunityFares()"
                                    style="background:none; border:none; color:var(--primary); font-size:0.75rem; font-weight:700; cursor:pointer; font-family:inherit; outline:none;">
                                    <option value="Tricycle">Tricycle</option>
                                    <option value="Motorized Pedicab">Motorized Pedicab</option>
                                    <option value="Cab">Cab</option>
                                    <option value="Jeep">Jeep</option>
                                    <option value="Taxi">Taxi</option>
                                    <option value="Grab/Private Car">Grab/Private Car</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="communityDataContent"></div>
                    <div class="community-footer">
                        <span id="communityLastUpdated"></span>
                        <span id="btnViewAllFares" onclick="openCommunityExplorer()"
                            style="color:var(--primary); cursor:pointer; font-weight:700; font-size: 0.8rem; text-decoration: underline;">View
                            All Submissions</span>
                    </div>
                </div>
            </div>

            <div class="live-stats">

                <div class="stat-card" id="distCard">
                    <div class="stat-label"><i class="fa-solid fa-ruler"></i>Est. Distance</div>
                    <div class="stat-value" id="liveDistance">0.00</div>
                    <div class="stat-unit">km</div>
                </div>
                <div class="stat-card" id="speedCard">
                    <div class="stat-label"><i class="fa-solid fa-gauge-high"></i>Est. Speed</div>
                    <div class="stat-value" id="liveSpeed">0</div>
                    <div class="stat-unit">km/h</div>
                </div>
                <div class="stat-card" id="fareCard">
                    <div class="stat-label"><i class="fa-solid fa-coins"></i> Est. Fare</div>
                    <div class="stat-value fare" id="liveFare">₱15</div>
                    <div class="stat-unit">pesos</div>
                </div>
            </div>

            <div class="live-stats-row2">
                <div class="stat-card" id="timerCard">
                    <div class="stat-label"><i class="fa-solid fa-clock"></i> Duration</div>
                    <div class="stat-value timer" id="liveTimer">00:00</div>
                    <div class="stat-unit">min:sec</div>
                </div>
                <div class="stat-card" id="avgSpeedCard">
                    <div class="stat-label"><i class="fa-solid fa-gauge"></i> Avg Speed</div>
                    <div class="stat-value" id="liveAvgSpeed">0</div>
                    <div class="stat-unit">km/h</div>
                </div>
            </div>

            <div class="options-row">
                <div class="option-toggle">
                    <span><i class="fa-solid fa-gear"></i> Base Fare (₱)</span>
                </div>
                <input type="number" id="baseFareAmount" value="20" min="15" max="100" step="1" class="surcharge-input"
                    oninput="updateBaseFare()">
            </div>

            <div class="options-row">
                <div class="option-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" id="nightToggle" onchange="toggleNightFareInput()">
                        <span class="slider"></span>
                    </label>
                    <span><i class="fa-solid fa-moon"></i> Night Surcharge (₱)</span>
                </div>
                <input type="number" id="nightAmount" value="5" min="0" max="50" step="1" class="surcharge-input"
                    oninput="updateAllFares()">
            </div>

            <div class="options-row">
                <div class="option-toggle" style="flex:1;">
                    <span><i class="fa-solid fa-lock"></i> Fixed Fare (₱)</span>
                    <span id="btnClearFixed" onclick="clearFixedFare()"
                        style="display:none; color:var(--danger, #ff4757); font-size:0.75rem; font-weight:700; cursor:pointer; padding:4px; margin-left:auto;"><i
                            class="fa-solid fa-xmark"></i>
                        Clear</span>
                </div>
                <input type="number" id="fixedFareAmount" placeholder="Opt" min="0" step="1" class="surcharge-input"
                    style="width: 60px; margin-left:10px;" oninput="updateFixedFare()">
            </div>

            <div class="btn-row">
                <button class="btn btn-start" id="btnStart" onclick="startRide()">
                    <span class="icon"><i class="fa-solid fa-play"></i></span> Start
                </button>
                <button class="btn btn-stop" id="btnStop" onclick="stopRide()" disabled>
                    <span class="icon"><i class="fa-solid fa-stop"></i></span> Stop
                </button>
                <button class="btn btn-reset" id="btnReset" onclick="resetRide()" disabled><i
                        class="fa-solid fa-arrow-rotate-right"></i></button>
            </div>

            <div class="weak-gps-warning" id="weakGpsWarning">
                <i class="fa-solid fa-triangle-exclamation"></i> Weak GPS signal — results may be inaccurate
            </div>

            <div class="gps-banner" id="gpsBanner">
                <strong><i class="fa-solid fa-location-dot"></i> GPS Unavailable</strong><br>
                <span>Location access was denied or is not supported. Use manual route selection below.</span><br>
                <button class="gps-retry-btn" onclick="retryGPS()" id="btnRetryGPS">
                    <i class="fa-solid fa-arrows-rotate"></i> Retry GPS
                </button>
            </div>

            <div class="results-card" id="resultsCard">
                <div class="results-title"><i class="fa-solid fa-flag-checkered"></i> Ride Summary</div>
                <div class="results-grid">
                    <div class="result-item">
                        <div class="result-label"><i class="fa-solid fa-ruler"></i> Total Distance</div>
                        <div class="result-value" id="resultDist">0.00 km</div>
                    </div>
                    <div class="result-item">
                        <div class="result-label"><i class="fa-solid fa-coins"></i> Suggested Fare</div>
                        <div class="result-value fare" id="resultFare">₱15</div>
                    </div>
                </div>
                <div class="results-grid-3">
                    <div class="result-item-sm">
                        <div class="result-label"><i class="fa-solid fa-clock"></i> Duration</div>
                        <div class="result-value" id="resultDuration">00:00</div>
                    </div>
                    <div class="result-item-sm">
                        <div class="result-label"><i class="fa-solid fa-gauge"></i> Avg Speed</div>
                        <div class="result-value" id="resultAvgSpeed">0 km/h</div>
                    </div>
                    <div class="result-item-sm">
                        <div class="result-label"><i class="fa-solid fa-rocket"></i> Max Speed</div>
                        <div class="result-value" id="resultMaxSpeed">0 km/h</div>
                    </div>
                </div>
                <div class="fare-breakdown" id="fareBreakdown"></div>
                <div class="passenger-row">
                    <label><i class="fa-solid fa-user-group"></i> Passengers:</label>
                    <div class="passenger-controls">
                        <button onclick="changePassengers(-1)" id="pMinus">−</button>
                        <span class="p-count" id="passengerCount">1</span>
                        <button onclick="changePassengers(1)" id="pPlus">+</button>
                    </div>
                    <span class="split-fare-text" id="splitFareText"></span>
                </div>
                <div class="btn-row" style="margin-top:8px; gap:8px;">
                    <button class="btn btn-share" onclick="shareRide()" style="flex:1;">
                        <i class="fa-solid fa-share-nodes"></i> Share Ride
                    </button>
                    <button class="btn btn-start btn-submit-ride-fare" id="btnSubmitRideFare"
                        onclick="openSubmitFareModal()"
                        style="flex:1.2; background:var(--accent); font-size:0.85rem; padding: 10px 8px; color:white;">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Submit Fare
                    </button>
                </div>
                <div class="disclaimer"><i class="fa-solid fa-triangle-exclamation"></i> This is an estimate based on
                    standard tricycle rates in General Santos City.
                    Actual
                    fares may vary.</div>
            </div>

            <div class="manual-section" id="manualSection">
                <div class="manual-title"><i class="fa-solid fa-list-check"></i> Manual Route Selector</div>
                <div class="select-wrap">
                    <select id="routeSelect" onchange="calcManualFare()">
                        <option value="">— Select a route —</option>
                    </select>
                </div>
                <div class="manual-result" id="manualResult">
                    <div class="stat-label">Distance</div>
                    <div class="stat-value" id="manualDist" style="font-size:1.1rem">—</div>
                    <div class="stat-label" style="margin-top:6px">Suggested Fare</div>
                    <div class="stat-value fare" id="manualFare" style="font-size:1.3rem">—</div>
                    <div class="disclaimer" style="margin-top:10px;text-align:left"><i
                            class="fa-solid fa-triangle-exclamation"></i> Estimate based on standard
                        tricycle rates
                        in General Santos City.</div>
                </div>
            </div>

        </div>
    </div>

    <!-- FARE GUIDE MODAL -->
    <div class="modal-overlay" id="modalOverlay" onclick="if(event.target===this) closeFareGuide()">
        <div class="fare-modal" id="fareModal">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Fare Guide</div>
                    <div class="modal-subtext">General Santos City Tricycle Rates (Reference Only)</div>
                </div>
                <button class="btn-close" onclick="closeFareGuide()">×</button>
            </div>
            <div class="modal-content">
                <div class="modal-card highlight">
                    <div class="modal-card-title">Base Fare</div>
                    <div class="fare-big-text" id="guideBaseFare">₱15 <span>first 4 km</span></div>
                    <div class="fare-visual-bar">
                        <div class="base"></div>
                        <div class="extra"></div>
                    </div>
                    <div class="fare-visual-labels">
                        <span>0 - 4 km (Base)</span>
                        <span>+₱1/km after</span>
                    </div>
                </div>

                <div class="modal-card">
                    <div class="modal-card-title">Distance Pricing</div>
                    <div style="font-size: 0.9rem;"><strong>₱1.00</strong> per kilometer beyond the first 4 km.</div>
                    <div style="font-size: 0.8rem; color: var(--text-dim); margin-top: 4px;">Night Surcharge (6PM-6AM):
                        <strong>+₱5.00</strong>
                    </div>
                </div>

                <div class="tip-card">
                    <i class="fa-solid fa-lightbulb"></i> <strong>Tip:</strong> Short trips within the city center
                    usually stay within the base fare range.
                </div>

                <div class="modal-card">
                    <div class="modal-card-title">Sample Estimates</div>
                    <div id="sampleRoutesList"></div>
                </div>

                <div class="modal-card">
                    <div class="modal-card-title">Important Notes</div>
                    <ul class="info-notes">
                        <li>Traffic, time of day, and driver discretion may affect actual fare.</li>
                        <li>This tool is for estimation only based on general consensus.</li>
                        <li><strong style="color:var(--primary)">Note:</strong> Current fare is 15% higher than the base
                            fare
                            because of
                            fuel price hikes.</li>
                        <li>Not an official government fare system.</li>
                        <li><strong style="color:var(--primary)">Rules based on Ordinance No. 8, Series of 2023 (General
                                Santos
                                City)</strong></li>
                    </ul>
                    <div
                        style="font-size: 0.65rem; color: var(--text-dim); margin-top: 12px; line-height: 1.5; border-left: 2px solid var(--primary-dark); padding-left: 10px; background: rgba(0,0,0,0.15); padding: 10px; border-radius: 0 8px 8px 0;">
                        <em>"Legal Basis: Ordinance No. 8, Series of 2023 Section 120. FARES AND FARE MATRIX- The
                            minimum fare of
                            MTH
                            in the city is at fifteen pesos (P15.00) per passenger for the first four (4) kilometers and
                            an additional
                            fare of 1.00 per kilometer, if the distance traverse exceeds 4 kilometers. Provided, that
                            the prevailing
                            transportation fare privileges granted by law to students, senior citizens, and persons with
                            disability
                            (PWD) shall continue to apply. Shortchanging the passenger, as defined under 'No Short
                            Changing Act of
                            2016' (Republic Act No. 10909) shall constitute a violation of this section."</em>
                        <br><br>
                        <em>"When the origin-destination is only CBD-CBD, minimum fare is still fifteen pesos (P15.00),
                            as the
                            shortest distance from any point never exceeds four (4) kilometers."</em>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- HISTORY MODAL -->
    <div class="modal-overlay" id="historyModalOverlay" onclick="if(event.target===this) closeHistoryModal()">
        <div class="fare-modal" id="historyModal">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Ride History</div>
                    <div class="modal-subtext">Detailed records of past trips</div>
                </div>
                <button class="btn-close" onclick="closeHistoryModal()">×</button>
            </div>
            <div class="modal-content">
                <div class="modal-card">
                    <div class="modal-card-title">Lifetime Stats</div>
                    <div class="stats-grid">
                        <div class="mini-stat">
                            <div class="ms-value" id="statTotalRides">0</div>
                            <div class="ms-label">Total Rides</div>
                        </div>
                        <div class="mini-stat">
                            <div class="ms-value" id="statTotalKm">0</div>
                            <div class="ms-label">Total km</div>
                        </div>
                        <div class="mini-stat">
                            <div class="ms-value" id="statTotalSpent">₱0</div>
                            <div class="ms-label">Total Spent</div>
                        </div>
                        <div class="mini-stat">
                            <div class="ms-value" id="statAvgDist">0</div>
                            <div class="ms-label">Avg Distance</div>
                        </div>
                        <div class="mini-stat">
                            <div class="ms-value" id="statAvgFare">₱0</div>
                            <div class="ms-label">Avg Fare</div>
                        </div>
                        <div class="mini-stat">
                            <div class="ms-value" id="statLongest">0</div>
                            <div class="ms-label">Longest Ride</div>
                        </div>
                    </div>
                </div>
                <div class="modal-card">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div class="modal-card-title" style="margin-bottom:0;">Recent Rides</div>
                        <div style="display: flex; gap: 8px;">
                            <button class="clear-history-btn"
                                style="background: var(--primary-glow); color: var(--primary); border: 1px solid var(--primary-glow);"
                                onclick="fetchHistoryFromServer()" title="Reload History"><i
                                    class="fa-solid fa-arrows-rotate"></i> Reload</button>
                            <button class="clear-history-btn"
                                style="background: var(--primary-glow); color: var(--primary); border: 1px solid var(--primary-glow);"
                                onclick="syncHistoryToServer()"><i class="fa-solid fa-cloud-arrow-up"></i> Sync</button>
                            <button class="clear-history-btn" id="clearHistBtn" onclick="clearHistory()"><i
                                    class="fa-solid fa-trash"></i> Clear</button>
                        </div>
                    </div>
                    <div class="history-list show" id="historyList" style="margin-top:12px;"></div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- DONATE MODAL -->
    <div class="modal-overlay" id="donateModalOverlay" onclick="if(event.target===this) closeDonateModal()">
        <div class="fare-modal" id="donateModal">
            <div class="modal-header">
                <div>
                    <div class="modal-title" style="color: #f0a500;"><i class="fa-solid fa-mug-hot"></i> Support the App
                    </div>
                    <div class="modal-subtext">Help keep TrikeFare Gensan running</div>
                </div>
                <button class="btn-close" onclick="closeDonateModal()">×</button>
            </div>
            <div class="modal-content"
                style="text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; padding-bottom: 20px;">
                <img id="donateQrImg" src="qr_donate.jfif" alt="Donate QR Code"
                    style="width: 300px; max-width: 100%; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15); cursor: zoom-in; transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative;"
                    onclick="this.style.transform = this.style.transform === 'scale(1.4)' ? 'scale(1)' : 'scale(1.4)'; this.style.zIndex = this.style.transform === 'scale(1.4)' ? '10' : '1'; this.style.cursor = this.style.transform === 'scale(1.4)' ? 'zoom-out' : 'zoom-in';">
                <div style="font-size: 0.95rem; color: var(--text); font-weight: 700;">
                    Scan via GCash or Maya
                </div>
                <div
                    style="font-size: 0.8rem; color: var(--text-dim); font-style: italic; max-width: 280px; line-height: 1.5;">
                    "Thank you for your support! Every coffee helps!"
                </div>
            </div>
        </div>
    </div>

    <!-- RIDE DETECTION MODAL -->
    <div class="modal-overlay" id="rideDetectionModalOverlay">
        <div class="fare-modal"
            style="max-width: 320px; border: 2px solid var(--primary); box-shadow: 0 10px 40px rgba(0, 212, 170, 0.4);">
            <div class="modal-content" style="text-align: center; padding: 20px 10px;">
                <div
                    style="font-size: 3rem; color: var(--primary); margin-bottom: 15px; animation: pulse-primary 2s infinite;">
                    <i class="fa-solid fa-person-biking"></i>
                </div>
                <h2 style="margin: 0 0 10px; font-weight: 800; color: var(--text);">Ride Detected!</h2>
                <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 25px; line-height: 1.5;">
                    It looks like you're on a ride. Would you like to start tracking your fare?
                </p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button class="btn btn-start" onclick="closeRideDetectionModal(); startRide();"
                        style="width: 100%; padding: 12px; font-weight: 700; background: var(--primary); color: white; border: none; border-radius: 12px; cursor: pointer; box-shadow: 0 4px 15px rgba(0, 212, 170, 0.3);">
                        <i class="fa-solid fa-play"></i> Start Ride Tracking
                    </button>
                    <button onclick="closeRideDetectionModal()"
                        style="width: 100%; padding: 10px; background: none; border: 1px solid var(--border); color: var(--text-dim); border-radius: 10px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                        Not now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- FARE SUBMISSION MODAL -->
    <div class="modal-overlay" id="submitFareModalOverlay">
        <div class="fare-modal">
            <div class="modal-header">
                <div>
                    <div class="modal-title"><i class="fa-solid fa-hand-holding-dollar"></i> Submit Community Fare</div>
                    <div class="modal-subtext">Contribute to local fare transparency</div>
                </div>
                <button class="btn-close" onclick="closeSubmitFareModal()">×</button>
            </div>
            <div class="modal-content">
                <div class="fare-form-group">
                    <label>Origin</label>
                    <div style="position:relative;">
                        <input type="text" id="submitOrigin" class="transport-select"
                            placeholder="Detecting your location...">
                        <button onclick="detectCurrentArea('submitOrigin')" title="Detect my location"
                            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--primary);cursor:pointer;font-size:1rem;">
                            <i class="fa-solid fa-location-crosshairs"></i>
                        </button>
                    </div>
                </div>
                <div class="fare-form-group">
                    <label>Destination</label>
                    <div style="position:relative;">
                        <input type="text" id="submitDestination" class="transport-select"
                            placeholder="Where did you go?">
                        <button onclick="detectCurrentArea('submitDestination')" title="Use current location"
                            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--primary);cursor:pointer;font-size:1rem;">
                            <i class="fa-solid fa-location-crosshairs"></i>
                        </button>
                    </div>
                </div>
                <div class="fare-form-group">
                    <label>Transport Type</label>
                    <select id="submitTransportType" class="transport-select">
                        <option value="Tricycle">Tricycle</option>
                        <option value="Motorized Pedicab">Motorized Pedicab</option>
                        <option value="Cab">Cab</option>
                        <option value="Jeep">Jeep</option>
                        <option value="Taxi">Taxi</option>
                        <option value="Grab/Private Car">Grab/Private Car</option>
                    </select>
                </div>
                <div class="fare-form-group">
                    <label>Fare Amount</label>
                    <div class="fare-input-wrapper">
                        <span class="fare-currency-symbol">₱</span>
                        <input type="number" id="submitFareAmount" placeholder="0.00" min="1" step="0.01">
                    </div>
                </div>
                <div class="fare-form-group">
                    <label>Time of Day</label>
                    <select id="submitTimeTag" class="transport-select">
                        <option value="day">Daytime</option>
                        <option value="night">Nighttime (9PM - 5AM)</option>
                        <option value="rush_hour">Rush Hour</option>
                    </select>
                </div>
                <div class="fare-form-group">
                    <label>Note <span style="font-size:0.7rem;color:var(--text-dim);font-weight:400;">(optional, max 100
                            chars)</span></label>
                    <textarea id="submitFareNote" class="transport-select" maxlength="100" rows="2"
                        placeholder="e.g. rainy day, rush hour detour..."
                        oninput="document.getElementById('submitFareNoteCount').textContent=this.value.length+'/100'"
                        style="resize:none;font-family:inherit;font-size:0.85rem;line-height:1.5;"></textarea>
                    <div id="submitFareNoteCount"
                        style="font-size:0.7rem;color:var(--text-dim);text-align:right;margin-top:2px;">0/100</div>
                </div>
                <button class="btn-submit-fare" onclick="submitCommunityFare()" id="btnSubmitFareConfirm">
                    <span class="btn-submit-fare-icon"><i class="fa-solid fa-paper-plane"></i></span>
                    <span style="padding: 20px;">Submit Fare Data</span>
                </button>
            </div>
        </div>
    </div>

    <!-- SETTINGS MODAL -->
    <div class="modal-overlay" id="settingsModalOverlay">
        <div class="fare-modal" style="max-width: 400px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title"><i class="fa-solid fa-gear"></i> Settings</div>
                    <div class="modal-subtext">Customize your app experience</div>
                </div>
                <button class="btn-close" onclick="closeSettingsModal()">×</button>
            </div>
            <div class="modal-content" style="padding: 10px 0;">
                <!-- Account Section -->
                <div
                    style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700; margin-bottom: 12px; letter-spacing: 0.5px; padding: 0 4px;">
                    Account Profile</div>
                <div class="settings-item"
                    style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--card); border: 1px solid var(--border); border-radius: 12px; margin-bottom: 24px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: 10px; background: var(--primary-glow); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fa-solid fa-user-astronaut"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 0.95rem;" id="settingsUsername">@user...</div>
                        <div style="font-size: 0.75rem; color: var(--text-dim);">Assigned automatically</div>
                    </div>
                </div>

                <!-- Appearance Section -->
                <div
                    style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700; margin-bottom: 12px; letter-spacing: 0.5px; padding: 0 4px;">
                    Appearance</div>
                <div class="settings-item" onclick="toggleTheme()"
                    style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: var(--card); border: 1px solid var(--border); border-radius: 12px; cursor: pointer; transition: all 0.2s; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div
                            style="width: 36px; height: 36px; border-radius: 10px; background: var(--primary-glow); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                            <i class="fa-solid fa-circle-half-stroke"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem;">Theme Mode</div>
                            <div id="currentThemeLabel"
                                style="font-size: 0.75rem; color: var(--text-dim); text-transform: capitalize;">Light
                                Mode</div>
                        </div>
                    </div>
                    <div style="color: var(--primary); font-size: 1.2rem;"><i class="fa-solid fa-repeat"></i></div>
                </div>

                <!-- Help Section -->
                <div
                    style="font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700; margin-bottom: 12px; letter-spacing: 0.5px; padding: 0 4px;">
                    Support & Help</div>
                <div class="settings-item" onclick="TrikeTutorial.replay(); closeSettingsModal()"
                    style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--card); border: 1px solid var(--border); border-radius: 12px; cursor: pointer; transition: all 0.2s; margin-bottom: 12px;">
                    <div
                        style="width: 36px; height: 36px; border-radius: 10px; background: rgba(0,212,170,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                        <i class="fa-solid fa-circle-question"></i>
                    </div>
                    <div>
                        <div style="font-weight: 700; font-size: 0.95rem;">Show Tutorial</div>
                        <div style="font-size: 0.75rem; color: var(--text-dim);">Learn how to use the app</div>
                    </div>
                </div>

                <!-- Account Actions -->
                <div id="settingsLogoutRow" style="display:none;">
                    <div class="settings-item" onclick="handleLogout()"
                        style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(255, 71, 87, 0.1); border: 1px solid rgba(255, 71, 87, 0.2); border-radius: 12px; cursor: pointer; transition: all 0.2s; color: #ff4757;">
                        <div
                            style="width: 36px; height: 36px; border-radius: 10px; background: rgba(255, 71, 87, 0.1); color: #ff4757; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem;">Logout</div>
                            <div style="font-size: 0.75rem; color: rgba(255, 71, 87, 0.7);">Sign out of your account
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- COMMUNITY EXPLORER MODAL -->
    <div class="modal-overlay" id="communityExplorerModalOverlay"
        onclick="if(event.target===this) closeCommunityExplorer()">
        <div class="fare-modal">
            <div class="modal-header">
                <div>
                    <div class="modal-title"><i class="fa-solid fa-users"></i> Community Submissions</div>
                    <div class="modal-subtext" id="explorerRouteName">Route Name</div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; margin-right: 10px;">
                    <div class="sort-filter-mini"
                        style="background: rgba(128,128,128,0.1); padding: 4px 8px; border-radius: 8px; border: 1px solid var(--border);">
                        <span
                            style="font-size: 0.6rem; color: var(--text-muted); margin-right: 4px; font-weight: 700;">SORT:</span>
                        <select id="communitySortFilter" onchange="renderExplorerList()"
                            style="background:none; border:none; color:var(--accent); font-size:0.7rem; font-weight:800; cursor:pointer; font-family:inherit; text-transform: uppercase; outline:none;">
                            <option value="rating">Most Upvotes</option>
                            <option value="fare_high">Highest Fare</option>
                            <option value="fare_low">Lowest Fare</option>
                            <option value="recent">Most Recent</option>
                        </select>
                    </div>
                    <button class="btn-close" onclick="closeCommunityExplorer()"
                        style="position: static; padding: 0; font-size: 1.5rem;">×</button>
                </div>
            </div>
            <div class="modal-content">
                <div style="margin-bottom:10px;position:relative;">
                    <span
                        style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-dim);font-size:0.85rem;"><i
                            class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="explorerSearchInput" placeholder="Search by note, distance, type..."
                        autocomplete="off" oninput="onExplorerSearch(this.value)"
                        style="width:100%;padding:8px 12px 8px 32px;border:1px solid var(--border);border-radius:8px;background:var(--card);color:var(--text);font-family:inherit;font-size:0.82rem;box-sizing:border-box;">
                </div>
                <div id="explorerList" style="max-height: 360px; overflow-y: auto; padding-bottom: 20px;">
                    <!-- Items injected here -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/auth_modal.php'; ?>
    <script src="components/offline_sync.js"></script>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const APP_SESSION_TOKEN = '<?php echo $appToken; ?>';
        // ============================================================
        // SIDEBAR NAVIGATION JS
        // ============================================================
        function openSidebar() {
            document.getElementById('sidebarOverlay').classList.add('show');
            document.getElementById('sidebar').classList.add('show');
        }

        function closeSidebar() {
            document.getElementById('sidebarOverlay').classList.remove('show');
            document.getElementById('sidebar').classList.remove('show');
        }

        // ============================================================
        // USER ACCOUNT & AUTH JS
        // ============================================================
        <?php include 'components/auth_js.php'; ?>

        function updateAuthUI() {
            const authText = document.getElementById('sidebarAuthText');
            const streakText = document.getElementById('sidebarStreakText');
            const logoutRow = document.getElementById('settingsLogoutRow');
            const sidebarAuth = document.getElementById('sidebarAuth');

            if (currentUser) {
                authText.textContent = '@' + currentUser.username;
                streakText.style.display = 'block';
                streakText.innerHTML = `<i class="fa-solid fa-fire"></i> ${currentUser.streak} Day Streak`;
                logoutRow.style.display = 'block';
                sidebarAuth.onclick = () => { /* Maybe open profile eventually */ };
                localStorage.setItem('trikefareVoteUsername', currentUser.username);
            } else {
                authText.textContent = 'Login / Sign Up';
                streakText.style.display = 'none';
                logoutRow.style.display = 'none';
                sidebarAuth.onclick = () => { openAuthModal(); closeSidebar(); };
            }
        }

        async function syncHistoryToServer() {
            if (!currentUser) return;
            const history = JSON.parse(localStorage.getItem('trikefareHistory') || '[]');
            if (!localStorage.getItem('trikefareHistory') && history.length === 0) return;

            const syncData = { rides: history };

            if (!navigator.onLine) {
                SyncQueue.push({ type: 'sync_history', data: syncData });
                showToast('Sync queued for offline mode', 'info');
                return;
            }

            try {
                const res = await fetch('api/sync_history.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(syncData)
                });
                const data = await res.json();
                if (data.success) {
                    console.log('Ride history synced to cloud.');
                    showToast('History synced to cloud', 'success');
                    await fetchHistoryFromServer();
                }
            } catch (e) {
                console.warn('Sync failed, queueing for later:', e);
                SyncQueue.push({ type: 'sync_history', data: syncData });
            }
        }

        async function updateStreakOnServer() {
            if (!currentUser || !navigator.onLine) return;
            try {
                const res = await fetch('api/update_streak.php');
                const data = await res.json();
                if (data.success) {
                    currentUser.streak = data.streak;
                    updateAuthUI();
                }
            } catch (e) { }
        }

        async function ensureUsername() {
            if (currentUser) return currentUser.username;
            let username = localStorage.getItem('trikefareVoteUsername');
            if (username) return username;

            try {
                const res = await fetch('api/generate_username.php');
                const data = await res.json();
                if (data.success) {
                    localStorage.setItem('trikefareVoteUsername', data.username);
                    console.log('Assigned username:', data.username);
                    return data.username;
                }
            } catch (e) {
                console.error('Failed to auto-generate username:', e);
            }
            return null;
        }

        function openSettingsModal() {
            document.getElementById('settingsModalOverlay').classList.add('show');
            // Update the theme label on open
            const label = document.getElementById('currentThemeLabel');
            if (label) label.textContent = currentTheme + ' Mode';

            // Update username
            const userEl = document.getElementById('settingsUsername');
            if (userEl) {
                const username = localStorage.getItem('trikefareVoteUsername') || 'Not assigned';
                userEl.textContent = '@' + username;
            }
        }

        function closeSettingsModal() {
            document.getElementById('settingsModalOverlay').classList.remove('show');
        }

        function showRideDetectionModal() {
            if (rideDetectionModalShown) return;
            document.getElementById('rideDetectionModalOverlay').classList.add('show');
            rideDetectionModalShown = true;
        }

        function closeRideDetectionModal() {
            document.getElementById('rideDetectionModalOverlay').classList.remove('show');
        }

        function openDonateModal() {
            document.getElementById('donateModalOverlay').classList.add('show');
        }

        function closeDonateModal() {
            document.getElementById('donateModalOverlay').classList.remove('show');
            const img = document.getElementById('donateQrImg');
            if (img) {
                img.style.transform = 'scale(1)';
                img.style.zIndex = '1';
                img.style.cursor = 'zoom-in';
            }
        }

        // ============================================================
        // CONFIG — All tunable constants in one place
        // ============================================================
        const CONFIG = {
            THROTTLE_MS: 2500, MIN_ACCURACY_M: 50, WEAK_SIGNAL_M: 30,
            MIN_MOVEMENT_KM: 0.01, MAX_JUMP_KM: 0.3, MAX_SPEED_KMH: 60,
            BASE_FARE: 15, BASE_KM: 4, PER_KM_RATE: 1,
            NIGHT_SURCHARGE: 3, NIGHT_START: 21, NIGHT_END: 5,
            CENTER: [6.1164, 125.1716], MAX_HISTORY: 20
        };

        const ROUTES = [
            // ₱15 (0–4 km range)
            { from: "SM Gensan", to: "Bulaong Terminal", distance: 2.5, group: "₱15 Base Fare (0–4 km)" },
            { from: "KCC/Gaisano/SM", to: "Bulaong Terminal", distance: 3.14, group: "₱15 Base Fare (0–4 km)" },
            { from: "CBD/Market/SM", to: "NLSA Junction (Lagao)", distance: 3.42, group: "₱15 Base Fare (0–4 km)" },
            { from: "Plaza Heneral Santos", to: "Lagao Gym", distance: 3.55, group: "₱15 Base Fare (0–4 km)" },
            { from: "SM", to: "Mindanao Medical Center", distance: 3.68, group: "₱15 Base Fare (0–4 km)" },
            { from: "SM", to: "SM Save More San Isidro", distance: 3.7, group: "₱15 Base Fare (0–4 km)" },
            { from: "KCC/Robinson", to: "Greenfield Residence Lagao", distance: 3.83, group: "₱15 Base Fare (0–4 km)" },
            { from: "KCC/SM", to: "Lagao National High School", distance: 3.91, group: "₱15 Base Fare (0–4 km)" },
            { from: "Plaza Heneral Santos", to: "Dacera Ave / Yumang Junction", distance: 3.93, group: "₱15 Base Fare (0–4 km)" },
            { from: "CBD/Public Market", to: "Salvani St", distance: 3.97, group: "₱15 Base Fare (0–4 km)" },
            { from: "Multiple CBD short routes", to: "Any point ≤ 4 km", distance: 4.0, group: "₱15 Base Fare (0–4 km)" },
            // ₱19–₱20 RANGE
            { from: "CBD", to: "Habitat Phase B", distance: 8.0, group: "₱19–₱20 Range" },
            { from: "CBD", to: "VSM Heights", distance: 8.0, group: "₱19–₱20 Range" },
            { from: "CBD", to: "Habitat Phase A", distance: 9.0, group: "₱19–₱20 Range" },
            { from: "CBD", to: "Brgy Hall Katangawan", distance: 9.0, group: "₱19–₱20 Range" },
            // ₱24–₱27 RANGE
            { from: "CBD", to: "Brgy Hall Mabuhay", distance: 13.0, group: "₱24–₱27 Range" },
            { from: "CBD", to: "Brgy Hall Conel", distance: 15.0, group: "₱24–₱27 Range" },
            { from: "CBD", to: "Brgy Hall Olympog", distance: 16.0, group: "₱24–₱27 Range" },
            // ₱36 RANGE
            { from: "CBD", to: "Upper Labay", distance: 25.0, group: "₱36 Range" }
        ];

        // ============================================================
        // DOM CACHE — Avoid repeated lookups
        // ============================================================
        const DOM = {};
        function cacheDom() {
            [
                'statusDot', 'statusText', 'liveDistance', 'liveSpeed', 'liveFare', 'liveTimer', 'liveAvgSpeed',
                'distCard', 'speedCard', 'fareCard', 'timerCard', 'avgSpeedCard',
                'btnStart', 'btnStop', 'btnReset',
                'resultsCard', 'resultDist', 'resultFare', 'resultDuration', 'resultAvgSpeed', 'resultMaxSpeed',
                'fareBreakdown', 'gpsBanner', 'weakGpsWarning', 'nightToggle', 'nightAmount',
                'manualSection', 'manualResult', 'manualDist', 'manualFare', 'routeSelect',
                'historyList', 'clearHistBtn', 'passengerCount', 'splitFareText', 'pMinus', 'pPlus',
                'statsDashboard', 'statTotalRides', 'statTotalKm', 'statTotalSpent',
                'statAvgDist', 'statAvgFare', 'statLongest',
                'modalOverlay', 'sampleRoutesList', 'baseFareAmount', 'guideBaseFare',
                'mapSearchInput', 'mapSearchResults', 'searchSpinner', 'btnSearchClear',
                'longpressRing',
                'destFareCard', 'destFareName', 'destFareDist', 'destFareETA', 'destFareCost', 'destFareBreakdown',
                'fixedFareAmount', 'btnClearFixed', 'btnLocateDest', 'btnMarkMode', 'markModeHint',
                'historyModalOverlay', 'historyModal', 'btnDismissDest', 'liveClock',
                'donateModalOverlay', 'donateModal', 'donateQrImg', 'statusRetryIcon', 'btnRetryGPS'
            ].forEach(id => { DOM[id] = document.getElementById(id); });
            updateUI();
        }

        // ============================================================
        // THEME LOGIC
        // ============================================================
        let currentTheme = localStorage.getItem('trikefareTheme') || 'light';
        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        function updateMapTiles() {
            if (!map) return;
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            // Leaflet map tile update logic would go here if needed
        }

        // ============================================================
        // REAL-TIME UI UPDATES
        // ============================================================
        function updateUI() {
            if (!DOM.liveClock) return;
            const now = new Date();
            const datePart = now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            const timePart = now.toLocaleTimeString('en-US', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
            });
            DOM.liveClock.textContent = `${datePart} – ${timePart}`;

            // Check for stale GPS (no update for > 15s)
            const timeSinceLastGps = Date.now() - lastGpsUpdateTime;
            if (timeSinceLastGps > 15000 && !gpsRetryTimer) {
                if (isTracking) {
                    setStatus('Waiting for GPS update...', 'warning');
                } else if (watchId) {
                    setStatus('GPS Signal Lost |', 'warning');
                }
            }

            // Stationary updates: refresh stats even if no move
            if (isTracking) {
                updateLiveStats();
            } else if (lastDestination && currentGPSPos) {
                // If stationary but has destination, ensure card is visible and fresh
                updatePreRidePreview();
            }
        }
        setInterval(updateUI, 1000);

        function updatePreRidePreview() {
            if (!lastDestination || !currentGPSPos || !lastPos) return;

            // Debounce route recalculation
            if (window._idleRouteTimer) return;

            const [lat, lon] = currentGPSPos;
            const distFromLastRecalc = haversine(lat, lon, lastPos[0], lastPos[1]);

            if (distFromLastRecalc > 0.015) { // 15m movement
                window._idleRouteTimer = setTimeout(async () => {
                    await selectDestination(lastDestination.destCoords[0], lastDestination.destCoords[1], lastDestination.name, true);
                    window._idleRouteTimer = null;
                }, 2000);
            }
        }

        function toggleTheme() {
            currentTheme = currentTheme === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', currentTheme);
            localStorage.setItem('trikefareTheme', currentTheme);
            updateMapTiles();

            // Update settings label if it exists
            const label = document.getElementById('currentThemeLabel');
            if (label) label.textContent = currentTheme + ' Mode';
        }

        function updateMapTiles() {
            if (!map) return;

            if (tileLayer) map.removeLayer(tileLayer);
            if (labelLayer) map.removeLayer(labelLayer);

            // Always use standard OSM tiles — map stays light regardless of app theme
            tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(map);
            labelLayer = null;
        }

        // ============================================================
        // STATE
        // ============================================================
        let map, userMarker, routeLine, accuracyCircle, tileLayer, labelLayer;
        let watchId = null;
        let isTracking = false;
        let destinationMarker = null;
        let destinationRouteLine = null; // Road-route line to destination
        let positions = [];
        let totalDistance = 0;
        let lastPos = null;
        let currentGPSPos = null; // Always holds the latest known GPS fix
        let lastTimestamp = 0;
        let currentSpeed = 0;
        let maxSpeed = 0;
        let weakSignalCount = 0;
        let rideStartTime = 0;
        let timerInterval = null;
        let passengerCount = 1;
        let isNightFare = false;
        let isMarkModeActive = false; // Flag for tap-to-mark mode
        // Stores the last destination selected via search so the popup can be refreshed on fare changes
        let lastDestination = null; // { distKm, name, originLabel, routeLabel }
        let rideJustCompleted = false; // True after stopRide(), cleared on reset or submit
        let awaitingFareSubmission = false; // Prevents reset and history save until fare is submitted
        let lastCompletedRideData = null; // Temporarily holds ride data until fare submission
        let gpsRetryCount = 0;
        let gpsRetryTimer = null;
        let lastGpsUpdateTime = Date.now();
        let untrackedDistance = 0;
        let rideDetectionModalShown = false;

        function persistRideState() {
            if (!isTracking && !awaitingFareSubmission) {
                localStorage.removeItem('trikefare_active_route');
                return;
            }
            const state = {
                isTracking,
                positions,
                totalDistance,
                lastPos,
                rideStartTime,
                currentSpeed,
                maxSpeed,
                passengerCount,
                isNightFare,
                awaitingFareSubmission,
                lastCompletedRideData
            };
            localStorage.setItem('trikefare_active_route', JSON.stringify(state));
        }

        function restoreRideState() {
            try {
                const saved = localStorage.getItem('trikefare_active_route');
                if (!saved) return;
                const state = JSON.parse(saved);

                if (!state.isTracking && !state.awaitingFareSubmission) return;

                isTracking = state.isTracking;
                positions = state.positions || [];
                totalDistance = state.totalDistance || 0;
                lastPos = state.lastPos;
                rideStartTime = state.rideStartTime || Date.now();
                currentSpeed = state.currentSpeed || 0;
                maxSpeed = state.maxSpeed || 0;
                passengerCount = state.passengerCount || 1;
                isNightFare = state.isNightFare || false;
                awaitingFareSubmission = state.awaitingFareSubmission || false;
                lastCompletedRideData = state.lastCompletedRideData || null;

                if (positions.length > 0 && routeLine) {
                    routeLine.setLatLngs(positions);
                    if (isTracking || awaitingFareSubmission) {
                        try { map.fitBounds(routeLine.getBounds(), { padding: [40, 40] }); } catch (e) { }
                    }
                }

                if (isTracking) {
                    DOM.resultsCard.classList.remove('show');
                    setButtonStates(false, true, false);
                    setOptionsState(true);
                    setCardActive(true);
                    setStatus(`Resumed Tracking...`, 'tracking');

                    if (timerInterval) clearInterval(timerInterval);
                    timerInterval = setInterval(tickTimer, 1000);
                    updateLiveStats();

                    if (!watchId && navigator.geolocation) {
                        watchId = navigator.geolocation.watchPosition(onPosition, onGPSError, {
                            enableHighAccuracy: true, maximumAge: 0, timeout: 5000
                        });
                    }
                } else if (awaitingFareSubmission) {
                    setButtonStates(false, false, true);
                    setCardActive(false);

                    DOM.resultDist.textContent = totalDistance.toFixed(2) + ' km';
                    DOM.resultFare.textContent = '₱' + (state.lastCompletedRideData ? state.lastCompletedRideData.fare : calculateFare(totalDistance, true));
                    DOM.resultDuration.textContent = formatTime(state.lastCompletedRideData ? state.lastCompletedRideData.duration : 0);
                    DOM.resultMaxSpeed.textContent = Math.round(maxSpeed) + ' km/h';

                    DOM.resultsCard.classList.add('show');
                    setStatus('Awaiting fare submission', 'warning');
                    rideJustCompleted = true;
                }
            } catch (e) {
                console.error("Failed to restore ride state:", e);
                localStorage.removeItem('trikefare_active_route');
            }
        }

        // ============================================================
        // CAB ROUTES & ADMIN STATE
        // ============================================================
        let cabRouteLayers = [];
        let activeCabRouteId = null;
        let isAdminActive = false;
        let adminRecording = false;
        let adminRoutePath = [];
        let adminRecordingLayer = null;
        let adminStops = [];
        let adminStopMarkers = [];
        let adminLongPressTimer = null;

        const CAB_ROUTES = JSON.parse(localStorage.getItem('trikefare_cab_routess')) || [
            {
                id: 'yellow-a',
                name: 'Yellow Multicab - Route A',
                color: '#c5bd00ff',
                path: [
                    [6.1130, 125.1717],
                    [6.1157, 125.1765],
                    [6.1195, 125.1845],
                    [6.1245, 125.1905]
                ],
                stops: [
                    { name: 'Gaisano Mall', lat: 6.1157, lng: 125.1765 },
                    { name: 'Public Market', lat: 6.1245, lng: 125.1905 }
                ]
            },
            {
                id: 'green-a',
                name: 'Green Multicab - Route A',
                color: '#059c00ff',
                path: [
                    [6.1130, 125.1717],
                    [6.1157, 125.1765],
                    [6.1195, 125.1845],
                    [6.1245, 125.1905]
                ],
                stops: [
                    { name: 'Gaisano Mall', lat: 6.1157, lng: 125.1765 },
                    { name: 'Public Market', lat: 6.1245, lng: 125.1905 }
                ]
            },
            {
                id: 'orange-a',
                name: 'Orange Multicab - Route A',
                color: '#df7100ff',
                path: [
                    [6.1130, 125.1717],
                    [6.1157, 125.1765],
                    [6.1195, 125.1845],
                    [6.1245, 125.1905]
                ],
                stops: [
                    { name: 'Gaisano Mall', lat: 6.1157, lng: 125.1765 },
                    { name: 'Public Market', lat: 6.1245, lng: 125.1905 }
                ]
            },
            {
                id: 'white-a',
                name: 'White Multicab - Route A',
                color: '#e8e8e8ff',
                path: [
                    [6.1130, 125.1717],
                    [6.1157, 125.1765],
                    [6.1195, 125.1845],
                    [6.1245, 125.1905]
                ],
                stops: [
                    { name: 'Gaisano Mall', lat: 6.1157, lng: 125.1765 },
                    { name: 'Public Market', lat: 6.1245, lng: 125.1905 }
                ]
            },
            {
                id: 'blue-a',
                name: 'Blue Multicab - Route A',
                color: '#008fc7ff',
                path: [
                    [6.1130, 125.1717],
                    [6.1157, 125.1765],
                    [6.1195, 125.1845],
                    [6.1245, 125.1905]
                ],
                stops: [
                    { name: 'Gaisano Mall', lat: 6.1157, lng: 125.1765 },
                    { name: 'Public Market', lat: 6.1245, lng: 125.1905 }
                ]
            },
            {
                id: 'pink-a',
                name: 'Pink Multicab - Route A',
                color: '#f105c9ff',
                path: [
                    [6.1130, 125.1717],
                    [7.1157, 125.1765],
                    [6.1195, 125.1845],
                    [6.1245, 125.1905]
                ],
                stops: [
                    { name: 'Gaisano Mall', lat: 6.1157, lng: 125.1765 },
                    { name: 'Public Market', lat: 6.1245, lng: 125.1905 }
                ]
            }
        ];

        // ============================================================
        // MATH UTILITIES
        // ============================================================
        function togglePanel() {
            const panel = document.getElementById('panel');
            panel.classList.toggle('collapsed');
            if (!panel.classList.contains('collapsed')) {
                panel.scrollTop = 0;
            }

            // Smooth map animation to maintain current view while resizing
            let start = null;
            const duration = 400; // Match CSS transition

            if (!map) return;
            const currentCenter = map.getCenter();

            function step(timestamp) {
                if (!start) start = timestamp;
                const progress = timestamp - start;

                map.invalidateSize({ pan: false });
                map.setView(currentCenter, map.getZoom(), { animate: false });

                if (progress < duration) {
                    window.requestAnimationFrame(step);
                } else {
                    map.invalidateSize();
                    map.setView(currentCenter, map.getZoom(), { animate: false });
                }
            }
            window.requestAnimationFrame(step);
        }

        // ============================================================
        // MAP SEARCH — Real-time as-you-type with debounce
        // ============================================================
        let searchDebounceTimer = null;
        let searchAbortController = null;

        function setupRealtimeSearch() {
            DOM.mapSearchInput.addEventListener('input', function () {
                const q = this.value.trim();
                DOM.btnSearchClear.classList.toggle('show', q.length > 0);
                if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                if (q.length < 2) {
                    DOM.mapSearchResults.classList.remove('show');
                    DOM.searchSpinner.classList.remove('show');
                    return;
                }
                DOM.searchSpinner.classList.add('show');
                searchDebounceTimer = setTimeout(() => performSearch(q), 400);
            });
            // Also handle Enter key — keep results visible
            DOM.mapSearchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
                    const q = this.value.trim();
                    if (q.length >= 2) {
                        performSearch(q);
                    }
                    // Keep input focused so results stay visible
                    this.focus();
                }
            });
            // Prevent blur from hiding results when clicking results
            DOM.mapSearchResults.addEventListener('mousedown', function (e) {
                e.preventDefault(); // Prevents input blur
            });
        }

        function clearSearch() {
            DOM.mapSearchInput.value = '';
            DOM.btnSearchClear.classList.remove('show');
            DOM.mapSearchResults.classList.remove('show');
            DOM.searchSpinner.classList.remove('show');
            if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
            DOM.mapSearchInput.focus();
        }

        async function performSearch(query) {
            if (!query) return;
            if (searchAbortController) searchAbortController.abort();
            searchAbortController = new AbortController();

            try {
                // Use Photon API for fast, POI-rich autocomplete. 
                // Biased to GenSan, strictly bounded to SOCCSKSARGEN Region (Region XII) using bbox
                const url = `https://photon.komoot.io/api/?q=${encodeURIComponent(query)}&lat=6.1164&lon=125.1716&limit=15&bbox=123.8,5.5,125.6,7.8`;
                const res = await fetch(url, { signal: searchAbortController.signal });
                const data = await res.json();

                if (!data.features || data.features.length === 0) {
                    DOM.mapSearchResults.innerHTML = `<div class="search-result-item" style="text-align:center; color:var(--danger)">No results found</div>`;
                } else {
                    DOM.mapSearchResults.innerHTML = data.features.map((feature) => {
                        const p = feature.properties;
                        const lat = feature.geometry.coordinates[1];
                        const lon = feature.geometry.coordinates[0];

                        const name = p.name || p.street || p.city || "Unknown Location";
                        const addressParts = [];
                        if (p.name && p.street) addressParts.push(p.street);
                        if (p.district) addressParts.push(p.district);
                        if (p.city) addressParts.push(p.city);
                        if (p.state) addressParts.push(p.state);

                        const address = addressParts.length > 0 ? addressParts.join(', ') : (p.country || 'Philippines');

                        return `<div class="search-result-item" onclick="selectDestination(${lat}, ${lon}, '${name.replace(/'/g, "\\\'")}')">
                      <div class="search-result-name">${name}</div>
                      <div class="search-result-address">${address}</div>
                    </div>`;
                    }).join('');
                }
                DOM.mapSearchResults.classList.add('show');
            } catch (err) {
                if (err.name === 'AbortError') return;
                DOM.mapSearchResults.innerHTML = `<div class="search-result-item" style="text-align:center; color:var(--danger)">Search error</div>`;
                DOM.mapSearchResults.classList.add('show');
            } finally {
                DOM.searchSpinner.classList.remove('show');
            }
        }

        async function selectDestination(lat, lon, name, silent = false) {
            if (isTracking && lastDestination) {
                if (!silent) alert("Destination is locked while a ride is active. Please stop the ride to change it.");
                return;
            }
            DOM.mapSearchResults.classList.remove('show');
            const destLatLng = [lat, lon];

            // Remove previous destination marker and route line
            if (destinationMarker) map.removeLayer(destinationMarker);
            if (destinationRouteLine) { map.removeLayer(destinationRouteLine); destinationRouteLine = null; }

            const destIcon = L.divIcon({
                className: 'user-marker',
                html: '<div style="background:var(--danger);width:100%;height:100%;border-radius:50%;border:2px solid #fff;box-shadow:0 0 10px rgba(255,107,107,0.5);"></div>',
                iconSize: [20, 20], iconAnchor: [10, 10]
            });

            destinationMarker = L.marker(destLatLng, { icon: destIcon }).addTo(map);
            DOM.btnLocateDest.style.display = 'flex';

            // Determine the best available origin — prefer live GPS fix, then last ride pos, then city center
            const originLatLng = currentGPSPos || lastPos || null;
            const hasRealGPS = !!currentGPSPos;

            if (!silent) {
                // Show a loading popup immediately
                destinationMarker.bindPopup(
                    `<div style="text-align:center;font-family:'Inter',sans-serif;color:#333;padding:4px 8px;">
              <div style="font-weight:800;font-size:1rem;color:#e55;">${name}</div>
              <div style="font-size:0.78rem;color:#888;margin-top:4px;"><i class="fa-solid fa-satellite-dish"></i> Calculating route...</div>
            </div>`,
                    { closeButton: false }
                ).openPopup();

                // Fit map to show both origin and destination immediately
                const originForBounds = originLatLng || CONFIG.CENTER;
                map.fitBounds(L.latLngBounds([originForBounds, destLatLng]), { padding: [60, 60] });
            }

            let durationSec = null;
            let calculatedRoutes = [];
            const origin = originLatLng || CONFIG.CENTER;
            const fallbackDist = haversine(origin[0], origin[1], lat, lon);

            // Try OSRM road routing if we have a real origin
            // Request up to 3 alternatives
            if (originLatLng) {
                try {
                    const [oLat, oLon] = originLatLng;
                    const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${oLon},${oLat};${lon},${lat}?overview=full&geometries=geojson&alternatives=3`;
                    const res = await fetch(osrmUrl, { signal: AbortSignal.timeout(8000) });
                    if (res.ok) {
                        const data = await res.json();
                        if (data.routes && data.routes.length > 0) {
                            data.routes.forEach((rt) => {
                                calculatedRoutes.push({
                                    distKm: rt.distance / 1000,
                                    durationSec: rt.duration,
                                    routeCoords: rt.geometry.coordinates.map(([lng, lat]) => [lat, lng])
                                });
                            });

                            // Classify routes
                            let fastest = [...calculatedRoutes].sort((a, b) => a.durationSec - b.durationSec)[0];
                            fastest.label = 'Fastest route';

                            let shortest = [...calculatedRoutes].sort((a, b) => a.distKm - b.distKm)[0];
                            if (shortest === fastest && calculatedRoutes.length > 1) {
                                shortest = [...calculatedRoutes].sort((a, b) => a.distKm - b.distKm)[1];
                            }

                            if (shortest === fastest) {
                                // Add a straight line fallback if OSRM only returned 1 route
                                calculatedRoutes.push({
                                    distKm: fallbackDist,
                                    durationSec: fastest.durationSec * 1.1, // Ensure it sorts AFTER the real fastest route
                                    routeCoords: [[oLat, oLon], [lat, lon]],
                                    label: 'Short route'
                                });
                            } else {
                                if (!shortest.label) shortest.label = 'Short route';
                            }

                            calculatedRoutes.forEach((rt, idx) => {
                                if (!rt.label) rt.label = 'Alternate route';
                            });

                            calculatedRoutes.sort((a, b) => a.durationSec - b.durationSec);
                        }
                    }
                } catch (e) {
                    console.warn('OSRM routing failed:', e);
                }
            }

            // Fall back to haversine if OSRM failed completely or no real GPS origin
            if (calculatedRoutes.length === 0) {
                calculatedRoutes.push({
                    distKm: fallbackDist * 1.3, // approximate road distance
                    durationSec: (fallbackDist * 1.3 / 20) * 3600, // assuming 20km/h
                    routeCoords: [[origin[0], origin[1]], [lat, lon]],
                    label: 'Fastest route'
                });
                calculatedRoutes.push({
                    distKm: fallbackDist,
                    durationSec: (fallbackDist / 20) * 3600, // straight line
                    routeCoords: [[origin[0], origin[1]], [lat, lon]],
                    label: 'Straight route'
                });
            }

            let distKm = calculatedRoutes[0].distKm;
            durationSec = calculatedRoutes[0].durationSec;
            let usedRoad = calculatedRoutes[0].routeCoords.length > 2;

            // Persist context so fare changes can re-render the popup without re-routing
            const originLabel = hasRealGPS
                ? 'From your location'
                : (lastPos ? 'From last known position' : '<i class="fa-solid fa-triangle-exclamation"></i> GPS unavailable — city center used');
            const routeLabel = usedRoad ? '<i class="fa-solid fa-route"></i> ' + calculatedRoutes[0].label : '<i class="fa-solid fa-ruler-combined"></i> Straight-line (est.)';

            lastDestination = { distKm, name, originLabel, routeLabel, durationSec, calculatedRoutes, selectedRouteIndex: 0, destCoords: [lat, lon] };

            renderDestinationRoute(silent);
            updateDestinationPopup();
            updatePanelDestFare();
        }

        function renderDestinationRoute(silent = false) {
            if (!lastDestination) return;
            if (destinationRouteLine) { map.removeLayer(destinationRouteLine); destinationRouteLine = null; }

            const { calculatedRoutes, selectedRouteIndex } = lastDestination;
            if (calculatedRoutes && calculatedRoutes.length > 0) {
                const route = calculatedRoutes[selectedRouteIndex];
                if (route && route.routeCoords && route.routeCoords.length > 1) {
                    destinationRouteLine = L.polyline(route.routeCoords, {
                        color: '#ff6b6b', weight: 4, opacity: 0.75, dashArray: '8 6'
                    }).addTo(map);
                    if (!silent) map.fitBounds(destinationRouteLine.getBounds(), { padding: [60, 60] });
                }
            }
        }

        window.switchRoute = function (index) {
            if (!lastDestination || !lastDestination.calculatedRoutes) return;
            const route = lastDestination.calculatedRoutes[index];
            if (!route) return;

            lastDestination.selectedRouteIndex = index;
            lastDestination.distKm = route.distKm;
            lastDestination.durationSec = route.durationSec;
            lastDestination.routeLabel = '🛣️ ' + route.label;

            renderDestinationRoute();
            updateDestinationPopup();
            updatePanelDestFare();
        };

        function haversine(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const toRad = v => v * Math.PI / 180;
            const dLat = toRad(lat2 - lat1);
            const dLon = toRad(lon2 - lon1);
            const a = Math.sin(dLat / 2) ** 2 +
                Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function getFixedFare() {
            const val = parseInt(DOM.fixedFareAmount.value);
            return isNaN(val) ? null : val;
        }

        function calculateFare(distanceKm, includeNight) {
            const fixed = getFixedFare();
            if (fixed !== null) return fixed;

            let fare = CONFIG.BASE_FARE;
            if (distanceKm > CONFIG.BASE_KM) fare += Math.ceil((distanceKm - CONFIG.BASE_KM) * CONFIG.PER_KM_RATE);
            if (includeNight && DOM.nightToggle.checked) {
                fare += parseInt(DOM.nightAmount.value) || 0;
            }
            return fare;
        }

        function getFareBreakdown(distanceKm) {
            const fixed = getFixedFare();
            if (fixed !== null) {
                return { base: fixed, extra: 0, night: 0, total: fixed, isFixed: true };
            }
            const base = CONFIG.BASE_FARE;
            const extra = distanceKm > CONFIG.BASE_KM ? Math.ceil((distanceKm - CONFIG.BASE_KM) * CONFIG.PER_KM_RATE) : 0;
            const night = DOM.nightToggle.checked ? (parseInt(DOM.nightAmount.value) || 0) : 0;
            return { base, extra, night, total: base + extra + night, isFixed: false };
        }

        function toggleNightFareInput() {
            DOM.nightAmount.disabled = !DOM.nightToggle.checked;
            updateAllFares();
        }

        function updateBaseFare() {
            let parsedVal = parseInt(DOM.baseFareAmount.value);
            CONFIG.BASE_FARE = !isNaN(parsedVal) && parsedVal >= 15 ? parsedVal : 15;
            DOM.guideBaseFare.innerHTML = `₱${CONFIG.BASE_FARE} <span>first ${CONFIG.BASE_KM} km</span>`;
            DOM.sampleRoutesList.innerHTML = ''; // Force re-render of groups on next open
            if (DOM.modalOverlay.classList.contains('show')) {
                openFareGuide(); // refresh immediately if it's open
            }
            updateAllFares();
        }

        function formatETA(seconds) {
            if (seconds === null || seconds === undefined) return null;
            const totalMin = Math.round(seconds / 60);
            if (totalMin < 60) return `~${totalMin} min`;
            const h = Math.floor(totalMin / 60);
            const m = totalMin % 60;
            return m > 0 ? `~${h}h ${m}min` : `~${h}h`;
        }

        function updateDestinationPopup() {
            if (!lastDestination || !destinationMarker) return;

            const { distKm, name, originLabel, routeLabel, durationSec } = lastDestination;
            const estFare = calculateFare(distKm, true);
            const fixed = getFixedFare();
            const etaLabel = formatETA(durationSec);
            const etaRow = etaLabel
                ? `<div style="display:flex;justify-content:space-between;align-items:center;background:#f0f4ff;border-radius:6px;padding:6px 10px;margin-bottom:4px;">
            <span style="font-size:0.72rem;color:#555;"><i class="fa-solid fa-stopwatch"></i> Est. travel time</span>
            <strong style="font-size:0.92rem;color:#3a3abd;">${etaLabel}</strong>
           </div>`
                : '';

            const fareLabel = fixed !== null ? 'Fixed Fare' : 'Est. Fare';
            const computedFixed = fixed !== null ? fixed : Math.ceil(estFare / 5) * 5;
            const showFixed = fixed === null && (estFare % 5 !== 0);

            const fixedRow = showFixed ? `
          <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(108,92,231,0.1);border-radius:6px;padding:6px 10px;margin-top:4px;border:1px solid rgba(108,92,231,0.2);">
            <span style="font-size:0.72rem;color:#555;font-weight:600;"><i class="fa-solid fa-lock"></i> Est. Fixed Fare</span>
            <strong style="font-size:1.1rem;color:#6c5ce7;">₱${computedFixed}</strong>
          </div>` : '';

            let routeButtonsHtml = '';
            if (lastDestination.calculatedRoutes && lastDestination.calculatedRoutes.length > 0) {
                routeButtonsHtml = `<div style="display:flex;gap:4px;margin-bottom:8px;flex-wrap:wrap;">`;
                lastDestination.calculatedRoutes.forEach((rt, idx) => {
                    const isSelected = idx === lastDestination.selectedRouteIndex;
                    const bg = isSelected ? 'var(--primary)' : '#e0e0e0';
                    const color = isSelected ? '#fff' : '#333';
                    routeButtonsHtml += `<button onclick="switchRoute(${idx})" style="flex:1;font-size:0.65rem;padding:6px 2px;border:none;border-radius:4px;background:${bg};color:${color};font-weight:700;cursor:pointer;">${rt.label}</button>`;
                });
                routeButtonsHtml += `</div>`;
            }

            const popupContent = `
        <div style="font-family:'Inter',sans-serif;color:#1a1a2e;min-width:170px;">
          <div style="font-weight:800;font-size:1rem;color:#c0392b;margin-bottom:6px;">${name}</div>
          <div style="font-size:0.7rem;color:#666;margin-bottom:8px;"><i class="fa-solid fa-location-crosshairs"></i> ${originLabel}</div>
          ${routeButtonsHtml}
          <div style="display:flex;justify-content:space-between;align-items:center;background:#f5f5f5;border-radius:6px;padding:6px 10px;margin-bottom:4px;">
            <span style="font-size:0.72rem;color:#555;">${routeLabel}</span>
            <strong style="font-size:1rem;color:#1a1a2e;">${distKm.toFixed(2)} km</strong>
          </div>
          ${etaRow}
          <div style="display:flex;justify-content:space-between;align-items:center;background:#e8faf5;border-radius:6px;padding:8px 10px;">
            <span style="font-size:0.72rem;color:#555;">${fareLabel}</span>
            <strong style="font-size:1.3rem;color:#00a885;">₱${estFare}</strong>
          </div>
          ${fixedRow}
        </div>
      `;
            destinationMarker.bindPopup(popupContent, { closeButton: false });
            if (!destinationMarker.isPopupOpen()) {
                destinationMarker.openPopup();
            } else {
                destinationMarker.getPopup().setContent(popupContent);
            }
        }

        // ============================================================
        // PANEL DESTINATION FARE CARD
        // ============================================================
        function updatePanelDestFare() {
            if (!lastDestination) {
                DOM.destFareCard.classList.remove('show');
                return;
            }
            const { distKm, name, durationSec } = lastDestination;
            const estFare = calculateFare(distKm, true);
            const fareBreak = getFareBreakdown(distKm);
            const etaLabel = formatETA(durationSec) || '—';

            DOM.destFareName.textContent = name;
            DOM.destFareDist.textContent = distKm.toFixed(2) + ' km';
            DOM.destFareETA.textContent = etaLabel;
            DOM.destFareCost.textContent = '₱' + estFare;

            const fixedFare = Math.ceil(estFare / 5) * 5;
            const showFixed = (estFare % 5 !== 0) && !fareBreak.isFixed;
            const fixedRowEl = document.getElementById('destFareFixedRow');
            const fixedCostEl = document.getElementById('destFareFixedCost');

            if (showFixed) {
                fixedCostEl.textContent = '₱' + fixedFare;
                fixedRowEl.style.display = 'flex';
            } else {
                fixedRowEl.style.display = 'none';
            }

            if (fareBreak.isFixed) {
                DOM.destFareBreakdown.textContent = "Manual Fixed Fare";
            } else {
                let bd = `₱${fareBreak.base} base`;
                if (fareBreak.extra > 0) bd += ` + ₱${fareBreak.extra} dist`;
                if (fareBreak.night > 0) bd += ` + ₱${fareBreak.night} 🌙`;
                bd += ` = ₱${fareBreak.total}`;
                DOM.destFareBreakdown.textContent = bd;
            }

            DOM.destFareCard.classList.add('show');
            fetchCommunityFares();
        }

        function dismissDestFare() {
            if (isTracking && lastDestination) {
                alert("Destination is locked while a ride is active. Please stop the ride to dismiss it.");
                return;
            }
            lastDestination = null;
            DOM.destFareCard.classList.remove('show');
            DOM.btnLocateDest.style.display = 'none';
            if (destinationMarker) { map.removeLayer(destinationMarker); destinationMarker = null; }
            if (destinationRouteLine) { map.removeLayer(destinationRouteLine); destinationRouteLine = null; }

            // Revert live fare to actual tracked distance or base
            if (isTracking) {
                DOM.liveFare.textContent = '₱' + calculateFare(totalDistance, true);
            } else {
                DOM.liveFare.textContent = '₱' + calculateFare(0, true);
            }
        }

        async function fetchPlaceName(lat, lon) {
            let placeName = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
            try {
                const rgUrl = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`;
                const res = await fetch(rgUrl, { signal: AbortSignal.timeout(5000) });
                const data = await res.json();
                if (data && data.display_name) {
                    const addr = data.address || {};

                    const priority = [
                        // Core POIs (most important)
                        'name', 'amenity', 'tourism', 'shop', 'leisure',

                        // Accommodation & travel
                        'hotel', 'hostel', 'guest_house', 'motel',

                        // Transport & routing
                        'highway', 'road', 'junction', 'route', 'public_transport', 'bus_stop', 'terminal',

                        // Buildings & places
                        'building', 'address', 'place',

                        // Commercial & services
                        'office', 'commercial', 'industrial', 'retail',

                        // Land use & areas
                        'landuse', 'area',

                        // Points of interest extras
                        'historic', 'man_made', 'natural',

                        // Streets (for fallback geocoding)
                        'street', 'residential', 'path'
                    ];

                    let foundPoi = null;
                    for (const key of priority) {
                        if (addr[key]) {
                            foundPoi = addr[key];
                            break;
                        }
                    }

                    let area = addr.neighbourhood || addr.suburb || addr.village || addr.city_district || '';

                    if (foundPoi) {
                        placeName = area ? `${foundPoi}, ${area}` : foundPoi;
                    } else if (area) {
                        placeName = area;
                    } else {
                        placeName = data.display_name.split(',').slice(0, 2).join(', ');
                    }
                }
            } catch (err) {
                console.warn('Reverse geocode failed:', err);
            }
            return placeName;
        }

        // ============================================================
        // LONG-PRESS ON MAP (3 seconds) — drop destination pin
        // ============================================================
        function setupLongPress() {
            let lpTimer = null;
            let lpLatLng = null;
            const LONG_PRESS_MS = 3000;
            const ring = DOM.longpressRing;
            const mapContainer = map.getContainer();

            function startLP(e) {
                lpLatLng = e.latlng;
                const pt = map.latLngToContainerPosition ? map.latLngToContainerPosition(lpLatLng) : map.latLngToLayerPoint(lpLatLng);
                // Position the ring at the point on the map
                const rect = mapContainer.getBoundingClientRect();
                const containerPt = map.latLngToContainerPoint(lpLatLng);
                ring.style.left = containerPt.x + 'px';
                ring.style.top = containerPt.y + 'px';
                ring.classList.add('active');
                // Force reflow before adding filling class for CSS transition
                void ring.offsetWidth;
                ring.classList.add('filling');

                lpTimer = setTimeout(async () => {
                    ring.classList.remove('active', 'filling');
                    if (navigator.vibrate) navigator.vibrate(80);
                    const placeName = await fetchPlaceName(lpLatLng.lat, lpLatLng.lng);
                    selectDestination(lpLatLng.lat, lpLatLng.lng, placeName);
                }, LONG_PRESS_MS);
            }

            function cancelLP() {
                if (lpTimer) { clearTimeout(lpTimer); lpTimer = null; }
                ring.classList.remove('active', 'filling');
            }

            // Mouse events
            map.on('mousedown', startLP);
            map.on('mouseup', cancelLP);
            map.on('mousemove', (e) => {
                if (lpTimer && lpLatLng) {
                    const d = map.latLngToContainerPoint(e.latlng).distanceTo(map.latLngToContainerPoint(lpLatLng));
                    if (d > 10) cancelLP();
                }
            });

            // Touch events
            map.on('touchstart', (e) => {
                if (e.originalEvent && e.originalEvent.touches && e.originalEvent.touches.length === 1) {
                    startLP(e);
                }
            });
            map.on('touchend', cancelLP);
            map.on('touchmove', (e) => {
                if (lpTimer && lpLatLng) {
                    const d = map.latLngToContainerPoint(e.latlng).distanceTo(map.latLngToContainerPoint(lpLatLng));
                    if (d > 15) cancelLP(); // slightly larger tolerance for touch to avoid accidental cancellation
                } else if (lpTimer) {
                    cancelLP();
                }
            });

            // Cancel on drag/zoom
            map.on('dragstart', cancelLP);
            map.on('zoomstart', cancelLP);

            // Tap-to-mark handling (single click)
            map.on('click', async (e) => {
                if (!isMarkModeActive) return; // Only process if Mark Mode is enabled

                // Turn off mode after successful tap
                toggleMarkMode(false);

                if (navigator.vibrate) navigator.vibrate(50);

                const placeName = await fetchPlaceName(e.latlng.lat, e.latlng.lng);
                selectDestination(e.latlng.lat, e.latlng.lng, placeName);
            });
        }

        function toggleMarkMode(forceState) {
            if (typeof forceState === 'boolean') {
                isMarkModeActive = forceState;
            } else {
                isMarkModeActive = !isMarkModeActive;
            }

            if (isMarkModeActive) {
                DOM.btnMarkMode.classList.add('active-mode');
                DOM.markModeHint.classList.add('show');
                document.getElementById('map').style.cursor = 'crosshair';
            } else {
                DOM.btnMarkMode.classList.remove('active-mode');
                DOM.markModeHint.classList.remove('show');
                document.getElementById('map').style.cursor = '';
            }
        }

        function locateDest() {
            if (destinationMarker) {
                map.flyTo(destinationMarker.getLatLng(), 16, { animate: true, duration: 1.5 });
            }
        }

        function locateMe() {
            if (currentGPSPos) {
                map.flyTo(currentGPSPos, 16, { animate: true, duration: 1.5 });
            } else {
                map.flyTo(CONFIG.CENTER, 14, { animate: true, duration: 1.5 });
            }
        }

        function updateFixedFare() {
            const val = DOM.fixedFareAmount.value;
            if (val) {
                DOM.btnClearFixed.style.display = 'block';
            } else {
                DOM.btnClearFixed.style.display = 'none';
            }
            updateAllFares();
        }

        function clearFixedFare() {
            if (typeof isTracking !== 'undefined' && isTracking) return;
            DOM.fixedFareAmount.value = '';
            DOM.btnClearFixed.style.display = 'none';
            updateAllFares();
        }

        function smoothMoveMarker(marker, newLatLngArray, duration = 1000) {
            const startLatLng = marker.getLatLng();
            if (!startLatLng || !marker._map) {
                marker.setLatLng(newLatLngArray);
                return;
            }
            if (marker._animId) cancelAnimationFrame(marker._animId);

            const startTime = performance.now();
            const targetLat = newLatLngArray[0] !== undefined ? newLatLngArray[0] : newLatLngArray.lat;
            const targetLng = newLatLngArray[1] !== undefined ? newLatLngArray[1] : newLatLngArray.lng;

            function step(currentTime) {
                const elapsed = currentTime - startTime;
                let progress = elapsed / duration;
                if (progress > 1) progress = 1;

                const ease = 1 - Math.pow(1 - progress, 3);
                const lat = startLatLng.lat + (targetLat - startLatLng.lat) * ease;
                const lng = startLatLng.lng + (targetLng - startLatLng.lng) * ease;

                marker.setLatLng([lat, lng]);

                if (progress < 1) {
                    marker._animId = requestAnimationFrame(step);
                } else {
                    marker._animId = null;
                }
            }
            marker._animId = requestAnimationFrame(step);
        }

        function updateAllFares() {
            updateLiveStats(); // Always update top cards immediately for instant feedback

            // Update Results Card if visible
            if (DOM.resultsCard.classList.contains('show')) {
                const fare = calculateFare(totalDistance, true);
                DOM.resultFare.textContent = '₱' + fare;
                renderFareBreakdown(totalDistance);
                if (passengerCount > 1) {
                    const split = Math.ceil(fare / passengerCount);
                    DOM.splitFareText.textContent = `(₱${split} each)`;
                }
            }

            // Update Manual Route Result if visible
            if (DOM.manualResult.classList.contains('show')) {
                calcManualFare();
            }

            // Update destination popup fare in real-time
            updateDestinationPopup();
            updatePanelDestFare();
        }

        function formatTime(seconds) {
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }

        function getElapsedSeconds() {
            if (!rideStartTime) return 0;
            return (Date.now() - rideStartTime) / 1000;
        }

        // ============================================================
        // MAP INIT
        // ============================================================
        function initMap() {
            map = L.map('map', {
                center: CONFIG.CENTER, zoom: 14,
                zoomControl: true, attributionControl: false
            });
            updateMapTiles();
            L.control.attribution({ prefix: false, position: 'bottomright' })
                .addAttribution('&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>')
                .addTo(map);

            const markerIcon = L.divIcon({
                className: 'user-marker', iconSize: [20, 20], iconAnchor: [10, 10]
            });
            userMarker = L.marker(CONFIG.CENTER, { icon: markerIcon }).addTo(map);
            routeLine = L.polyline([], {
                color: '#00d4aa', weight: 4, opacity: 0.8, smoothFactor: 1
            }).addTo(map);
            accuracyCircle = L.circle(CONFIG.CENTER, {
                radius: 0, className: 'accuracy-circle', interactive: false
            }).addTo(map);

        }

        // ============================================================
        // UI UPDATERS
        // ============================================================
        let lastRenderedDist = -1;
        let lastRenderedSpeed = -1;

        function updateLiveStats() {
            const distStr = totalDistance.toFixed(2);
            const speedInt = Math.round(currentSpeed);
            if (distStr !== lastRenderedDist) {
                DOM.liveDistance.textContent = distStr;
                lastRenderedDist = distStr;
            }
            // Always update fare because base rates or toggles may have changed
            DOM.liveFare.textContent = '₱' + calculateFare(totalDistance, true);
            if (speedInt !== lastRenderedSpeed) {
                DOM.liveSpeed.textContent = speedInt;
                lastRenderedSpeed = speedInt;
            }
            // Avg speed
            const elapsed = getElapsedSeconds();
            if (elapsed > 0) {
                DOM.liveAvgSpeed.textContent = Math.round((totalDistance / (elapsed / 3600)) || 0);
            }
        }

        function tickTimer() {
            const s = getElapsedSeconds();
            DOM.liveTimer.textContent = formatTime(s);

            if (isTracking) {
                // Decay speed to 0 if no significant movement recently
                if (Date.now() - lastTimestamp > CONFIG.THROTTLE_MS + 1500) {
                    currentSpeed = 0;
                }
                // Decouple stats refresh from movement to keep UI alive
                updateLiveStats();
                persistRideState();
            }
        }

        function setStatus(text, type) {
            DOM.statusText.textContent = text;
            DOM.statusDot.className = 'status-dot' +
                (type === 'tracking' ? ' tracking' : type === 'error' ? ' error' : '');
        }

        function setButtonStates(start, stop, reset) {
            DOM.btnStart.disabled = !start;
            DOM.btnStop.disabled = !stop;
            DOM.btnReset.disabled = !reset;
        }

        function setOptionsState(disabled) {
            DOM.baseFareAmount.disabled = disabled;
            DOM.nightToggle.disabled = disabled;
            DOM.fixedFareAmount.disabled = disabled;
            if (disabled) {
                DOM.nightAmount.disabled = true;
                DOM.btnClearFixed.style.display = 'none';
            } else {
                DOM.nightAmount.disabled = !DOM.nightToggle.checked;
                if (DOM.fixedFareAmount.value) {
                    DOM.btnClearFixed.style.display = 'block';
                }
            }
        }

        function setCardActive(active) {
            const m = active ? 'add' : 'remove';
            DOM.distCard.classList[m]('active');
            DOM.speedCard.classList[m]('active');
            DOM.fareCard.classList[m]('active');
            DOM.timerCard.classList[m]('active');
            DOM.avgSpeedCard.classList[m]('active');
        }

        function showWeakSignal(show) {
            DOM.weakGpsWarning.classList.toggle('show', show);
        }

        function renderFareBreakdown(distanceKm) {
            const { base, extra, night, total, isFixed } = getFareBreakdown(distanceKm);
            let html = '';
            if (isFixed) {
                html = `<span>Fixed Fare: <strong>₱${total}</strong></span>`;
            } else {
                html = `<span>₱${base} base</span>`;
                if (extra > 0) html += `<span class="fare-op">+</span><span>₱${extra} distance</span>`;
                if (night > 0) html += `<span class="fare-op">+</span><span>₱${night} night</span>`;
                html += `<span class="fare-op">=</span><span><strong>₱${total}</strong></span>`;
            }
            DOM.fareBreakdown.innerHTML = html;
        }

        // ============================================================
        // GPS POSITION HANDLER — With throttling + filtering
        // ============================================================
        function onPosition(pos) {
            const now = pos.timestamp || Date.now();
            const { latitude: lat, longitude: lng, accuracy } = pos.coords;

            // 1) THROTTLE — Skip if too soon since last processed update
            if (now - lastTimestamp < CONFIG.THROTTLE_MS) return;

            // 2) ACCURACY GATE — Reject poor readings entirely
            if (accuracy > CONFIG.MIN_ACCURACY_M) return;

            // 2b) ADMIN RECORDING
            if (isAdminActive && adminRecording) {
                updateAdminRecording(lat, lng);
            }

            // 3) WEAK SIGNAL DETECTION — Warn but still process
            if (accuracy > CONFIG.WEAK_SIGNAL_M) {
                weakSignalCount++;
                if (weakSignalCount >= 3) showWeakSignal(true);
            } else {
                weakSignalCount = Math.max(0, weakSignalCount - 1);
                if (weakSignalCount === 0) showWeakSignal(false);
            }

            const latlng = [lat, lng];
            let distKm = 0;

            if (lastPos) {
                distKm = haversine(lastPos[0], lastPos[1], lat, lng);
                const timeDeltaH = (now - lastTimestamp) / 3600000; // ms -> hours

                // 4) NOISE FILTER — Ignore tiny movements (GPS jitter)
                if (distKm < CONFIG.MIN_MOVEMENT_KM) {
                    // Still update marker position for visual smoothness
                    smoothMoveMarker(userMarker, latlng, 1000);
                    return;
                }

                // 5) SPIKE PROTECTION — Check both distance AND speed
                if (distKm > CONFIG.MAX_JUMP_KM) return;

                const speedKmh = timeDeltaH > 0 ? distKm / timeDeltaH : 0;
                if (speedKmh > CONFIG.MAX_SPEED_KMH) return;

                if (isTracking) {
                    totalDistance += distKm;
                    currentSpeed = speedKmh;
                    if (speedKmh > maxSpeed) maxSpeed = speedKmh;
                } else {
                    // RIDE DETECTION LOGIC
                    untrackedDistance += (distKm * 1000); // convert to meters
                    if (untrackedDistance >= 150 && speedKmh >= 3) {
                        showRideDetectionModal();
                    }
                }
            }

            // Update state
            lastPos = latlng;
            currentGPSPos = latlng; // Always keep a fresh GPS fix available
            lastTimestamp = now;
            lastGpsUpdateTime = Date.now();
            gpsRetryCount = 0; // Reset retry counter on success
            if (gpsRetryTimer) { clearTimeout(gpsRetryTimer); gpsRetryTimer = null; }
            DOM.statusRetryIcon.style.display = 'none';
            DOM.btnRetryGPS.classList.remove('loading');
            DOM.gpsBanner.classList.remove('show');

            if (isTracking) {
                positions.push(latlng);
                routeLine.setLatLngs(positions);
            }

            // Update map marker
            smoothMoveMarker(userMarker, latlng, 1000);
            accuracyCircle.setLatLng(latlng);
            accuracyCircle.setRadius(accuracy);

            const markerEl = userMarker.getElement();

            if (isTracking) {
                if (markerEl) markerEl.classList.add('tracking');
                map.panTo(latlng, { animate: true, duration: 0.5 });

                // Update UI
                updateLiveStats();
                setCardActive(true);
                setStatus(`Tracking · ${positions.length} pts · ${totalDistance.toFixed(2)} km`, 'tracking');
            } else {
                if (markerEl) markerEl.classList.remove('tracking');
                // Dynamic Route Updates handled by updateUI interval
            }
        }

        function onGPSError(err) {
            console.warn('GPS Error:', err.message);
            const msg = err.code === 1 ? 'Permission denied' : (err.code === 3 ? 'Timeout' : 'Unavailable');
            setStatus(`GPS ${msg} — retrying...`, 'error');
            DOM.statusDot.classList.add('error');
            DOM.statusRetryIcon.style.display = 'inline-block';
            DOM.gpsBanner.classList.add('show');
            DOM.manualSection.classList.add('show');

            // Auto-retry logic (exponential backoff)
            if (gpsRetryCount < 5) {
                gpsRetryCount++;
                const delay = Math.min(Math.pow(2, gpsRetryCount) * 2000, 30000);
                console.log(`Auto-retry GPS in ${delay / 1000}s (Attempt ${gpsRetryCount})...`);
                if (gpsRetryTimer) clearTimeout(gpsRetryTimer);
                gpsRetryTimer = setTimeout(retryGPS, delay);
            } else {
                setStatus('GPS failed after multiple attempts', 'error');
                if (isTracking) stopRide();
            }
        }

        function retryGPS() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }

            DOM.btnRetryGPS.classList.add('loading');
            setStatus('Re-acquiring GPS...', 'warning');

            if (navigator.geolocation) {
                watchId = navigator.geolocation.watchPosition(onPosition, onGPSError, {
                    enableHighAccuracy: true, maximumAge: 0, timeout: 15000
                });
            } else {
                onGPSError({ message: 'GPS not supported' });
            }
        }

        // ============================================================
        // RIDE CONTROLS
        // ============================================================
        function startRide() {
            if (isTracking) return;

            // Reset detection state
            untrackedDistance = 0;
            closeRideDetectionModal();

            if (!navigator.geolocation) { onGPSError({ message: 'Not supported' }); return; }

            isTracking = true;
            totalDistance = 0; positions = [];
            currentSpeed = 0; maxSpeed = 0;
            weakSignalCount = 0; lastRenderedDist = -1; lastRenderedSpeed = -1;
            passengerCount = 1;
            rideStartTime = Date.now();
            routeLine.setLatLngs([]);
            if (currentGPSPos) {
                positions.push(currentGPSPos);
                routeLine.setLatLngs(positions);
            }

            setButtonStates(false, true, false);
            setOptionsState(true);
            DOM.resultsCard.classList.remove('show');
            if (lastDestination && DOM.btnDismissDest) {
                DOM.btnDismissDest.style.display = 'none';
            }
            showWeakSignal(false);
            DOM.liveSpeed.textContent = '0';
            DOM.liveTimer.textContent = '00:00';
            DOM.liveAvgSpeed.textContent = '0';
            setStatus('Ride started...', 'tracking');
            updateLiveStats();
            persistRideState();

            timerInterval = setInterval(tickTimer, 1000);
        }

        function stopRide() {
            isTracking = false; currentSpeed = 0;
            if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }

            const markerEl = userMarker.getElement();
            if (markerEl) markerEl.classList.remove('tracking');
            accuracyCircle.setRadius(0);

            setButtonStates(false, false, true);
            setCardActive(false);
            showWeakSignal(false);
            if (DOM.btnDismissDest) {
                DOM.btnDismissDest.style.display = '';
            }
            DOM.liveSpeed.textContent = '0';
            setStatus('Ride complete', '');

            const elapsed = getElapsedSeconds();
            const fare = calculateFare(totalDistance, true);
            const avgSpd = elapsed > 0 ? Math.round(totalDistance / (elapsed / 3600)) : 0;

            DOM.resultDist.textContent = totalDistance.toFixed(2) + ' km';
            DOM.resultFare.textContent = '₱' + fare;
            DOM.resultDuration.textContent = formatTime(elapsed);
            DOM.resultAvgSpeed.textContent = avgSpd + ' km/h';
            DOM.resultMaxSpeed.textContent = Math.round(maxSpeed) + ' km/h';
            renderFareBreakdown(totalDistance);
            DOM.passengerCount.textContent = '1';
            DOM.splitFareText.textContent = '';

            // Smart Fare Fallback for rides without destination
            if (!lastDestination && totalDistance > 0.1) {
                fetchSmartFareFallback();
            }

            DOM.resultsCard.classList.add('show');

            if (navigator.vibrate) navigator.vibrate([100, 50, 100]);

            // Do NOT save ride yet. Wait for mandatory fare submission.
            awaitingFareSubmission = true;
            lastCompletedRideData = { dist: totalDistance, fare: fare, duration: elapsed };
            setStatus('Awaiting fare submission', 'warning');

            rideJustCompleted = true;

            if (positions.length > 1) {
                map.fitBounds(routeLine.getBounds(), { padding: [40, 40] });
            }

            persistRideState();

            // Scroll to results card and highlight the submit button
            setTimeout(() => {
                const resultsEl = DOM.resultsCard;
                if (resultsEl) {
                    resultsEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                const submitBtn = document.getElementById('btnSubmitRideFare');
                if (submitBtn) {
                    submitBtn.classList.add('pulse-highlight');
                    setTimeout(() => submitBtn.classList.remove('pulse-highlight'), 4000);
                }
            }, 600);
        }

        function resetRide() {
            if (awaitingFareSubmission) {
                showToast("Please submit your fare to finalize the ride before resetting.", "warning");
                const submitBtn = document.getElementById('btnSubmitRideFare');
                if (submitBtn) {
                    submitBtn.classList.add('pulse-highlight');
                    setTimeout(() => submitBtn.classList.remove('pulse-highlight'), 2000);
                }
                return;
            }

            // Reset detection state
            untrackedDistance = 0;
            rideDetectionModalShown = false;
            closeRideDetectionModal();

            totalDistance = 0; positions = [];
            currentSpeed = 0; maxSpeed = 0;
            lastRenderedDist = -1; lastRenderedSpeed = -1;
            rideStartTime = 0; passengerCount = 1;
            routeLine.setLatLngs([]);
            if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }

            const oldArea = document.getElementById('areaFallbackTitle');
            if (oldArea) oldArea.remove();
            const oldFallback = document.getElementById('smartFareFallbackResults');
            if (oldFallback) oldFallback.remove();
            const oldTopFare = document.getElementById('topFare');
            if (oldTopFare) oldTopFare.remove();

            setButtonStates(true, false, false);
            setOptionsState(false);
            DOM.resultsCard.classList.remove('show');
            DOM.liveSpeed.textContent = '0';
            DOM.liveTimer.textContent = '00:00';
            DOM.liveAvgSpeed.textContent = '0';
            rideJustCompleted = false;
            updateLiveStats();
            setStatus('GPS Active |', '');
            if (currentGPSPos) {
                map.setView(currentGPSPos, 15, { animate: true });
            } else {
                map.setView(CONFIG.CENTER, 14, { animate: true });
            }
            persistRideState();
        }

        // ============================================================
        // MANUAL MODE FALLBACK
        // ============================================================
        function populateRoutes() {
            ROUTES.forEach((r, i) => {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = `${r.from} → ${r.to} (${r.distance} km)`;
                DOM.routeSelect.appendChild(opt);
            });
        }

        function calcManualFare() {
            const idx = DOM.routeSelect.value;
            if (idx === '') { DOM.manualResult.classList.remove('show'); return; }
            const route = ROUTES[idx];
            const fare = calculateFare(route.distance);
            DOM.manualDist.textContent = route.distance.toFixed(1) + ' km';
            DOM.manualFare.textContent = '₱' + fare;
            DOM.manualResult.classList.add('show');
        }

        // ============================================================
        // RIDE HISTORY (localStorage)
        // ============================================================
        async function saveRide(dist, fare, duration) {
            const history = JSON.parse(localStorage.getItem('trikefareHistory') || '[]');

            const endDate = new Date();
            // Start time is estimated or tracked
            const startDate = rideStartTime ? new Date(rideStartTime) : new Date(endDate.getTime() - ((duration || 0) * 1000));

            let origin = "Current Location";
            let destination = "Unknown Area";
            let routeSummary = "GPS Tracked Route";

            if (lastDestination) {
                origin = lastDestination.originLabel.replace('📍 From ', '');
                destination = lastDestination.name;
                routeSummary = lastDestination.routeLabel;
            } else {
                // Resolve readable names from the tracked path endpoints
                if (!window._geocodeCache) window._geocodeCache = {};
                const endPt = positions.length > 0 ? positions[positions.length - 1] : (currentGPSPos || null);
                const startPt = positions.length > 1 ? positions[0] : null;
                if (endPt) {
                    const eKey = `${endPt[0].toFixed(4)},${endPt[1].toFixed(4)}`;
                    if (window._geocodeCache[eKey]) {
                        destination = window._geocodeCache[eKey];
                    } else {
                        try {
                            const name = await fetchPlaceName(endPt[0], endPt[1]);
                            window._geocodeCache[eKey] = name;
                            destination = name;
                        } catch (e) { destination = "Unknown Area"; }
                    }
                }
                if (startPt) {
                    const sKey = `${startPt[0].toFixed(4)},${startPt[1].toFixed(4)}`;
                    if (window._geocodeCache[sKey]) {
                        origin = window._geocodeCache[sKey];
                    } else {
                        try {
                            const name = await fetchPlaceName(startPt[0], startPt[1]);
                            window._geocodeCache[sKey] = name;
                            origin = name;
                        } catch (e) { origin = "Current Location"; }
                    }
                }
            }

            const breakdown = getFareBreakdown(dist);
            const fixedFare = Math.ceil(fare / 5) * 5;

            history.unshift({
                distance: dist.toFixed(2),
                fare,
                fixedFare: (fare % 5 !== 0 && !breakdown.isFixed) ? fixedFare : null,
                duration: Math.round(duration || 0),
                date: endDate.toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }),
                startTime: startDate.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }),
                endTime: endDate.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' }),
                origin,
                destination,
                routeSummary,
                breakdown: { base: breakdown.base, extra: breakdown.extra, night: breakdown.night, isFixed: breakdown.isFixed },
                paymentStatus: 'Paid (Cash)',
                path: positions.length > 0 ? JSON.parse(JSON.stringify(positions)) : null, // Deep copy to prevent mutation
                timestamp: endDate.toISOString(),
                ride_uuid: crypto.randomUUID(), // Unique immutable identifier
                destCoords: lastDestination ? lastDestination.destCoords : null
            });

            if (history.length > CONFIG.MAX_HISTORY) history.pop();
            localStorage.setItem('trikefareHistory', JSON.stringify(history));
            renderHistory();
            updateStatsDashboard();
            syncHistoryToServer(); // Trigger sync after saving new ride
        }

        function renderHistory() {
            const history = JSON.parse(localStorage.getItem('trikefareHistory') || '[]');
            if (!history.length) {
                DOM.historyList.innerHTML =
                    '<div style="font-size:.75rem;color:var(--text-muted);padding:8px">No rides yet</div>';
                DOM.clearHistBtn.style.display = 'none';
                return;
            }
            DOM.clearHistBtn.style.display = '';

            DOM.historyList.innerHTML = history.map((h, index) => {
                const durStr = h.duration ? formatTime(h.duration) : '--:--';

                // Use timestamp for consistent date/time parsing if available
                let dateStr = h.date;
                let sTime = h.startTime;
                let eTime = h.endTime;

                if (h.timestamp) {
                    const d = new Date(h.timestamp);
                    dateStr = d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
                    eTime = d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
                    if (h.duration) {
                        const sd = new Date(d.getTime() - (h.duration * 1000));
                        sTime = sd.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
                    }
                } else {
                    // Fallback for older entries
                    dateStr = h.date.split(',')[0] || h.date;
                    sTime = h.startTime || h.date.split(',')[1] || '--:--';
                    eTime = h.endTime || '--:--';
                }

                const orig = h.origin || "Current Location";
                const dest = h.destination || "Custom Drop-off";
                const rSum = h.routeSummary || "GPS Tracked Route";
                const pmt = h.paymentStatus || "Cash";

                let bdStr = '';
                if (h.breakdown) {
                    if (h.breakdown.isFixed) {
                        bdStr = 'Fixed Fare';
                    } else {
                        bdStr = `Base: ₱${h.breakdown.base} ${h.breakdown.extra ? '+ Dist: ₱' + h.breakdown.extra : ''} ${h.breakdown.night ? '+ Night: ₱' + h.breakdown.night : ''}`;
                        if (h.fixedFare) {
                            bdStr += ` | Est. Fixed: ₱${h.fixedFare}`;
                        }
                    }
                }

                return `
                <div class="history-item">
                    <div class="history-header">
                        <div class="history-date">${dateStr}</div>
                        <div style="display:flex;gap:6px;">
                            <div class="history-status">Completed</div>
                            <button onclick="deleteHistoryItem(${index})" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:1rem;" title="Delete Ride"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                    
                    <div class="history-route">
                        <div class="history-point">
                            <div class="history-point-icon" style="color: var(--primary);"><i class="fa-solid fa-circle-dot"></i></div>
                            <div class="history-point-details">
                                <div class="history-point-name">${orig}</div>
                                <div class="history-point-time">${sTime}</div>
                            </div>
                        </div>
                        <div class="history-connector"></div>
                        <div class="history-point">
                            <div class="history-point-icon" style="color: var(--danger);"><i class="fa-solid fa-location-dot"></i></div>
                            <div class="history-point-details">
                                <div class="history-point-name">${dest}</div>
                                <div class="history-point-time">${eTime}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="font-size: 0.7rem; color: var(--text-dim); margin-top: 4px;">
                         ${rSum}
                    </div>

                    <div class="history-stats">
                        <div class="h-stat">
                            <div class="h-stat-label"><i class="fa-solid fa-ruler"></i> Distance</div>
                            <div class="h-stat-val">${h.distance} km</div>
                        </div>
                        <div class="h-stat">
                            <div class="h-stat-label"><i class="fa-solid fa-clock"></i> Duration</div>
                            <div class="h-stat-val">${durStr}</div>
                        </div>
                        <div class="h-stat">
                            <div class="h-stat-label"><i class="fa-solid fa-coins"></i> Fare</div>
                            <div class="h-stat-val fare" style="font-size: 1.1rem;">₱${h.fare}</div>
                            ${h.fixedFare ? `<div style="font-size:0.7rem;color:var(--accent);font-weight:700;margin-top:2px;">Est. Fixed: ₱${h.fixedFare}</div>` : ''}
                        </div>
                    </div>
                    
                    <div class="history-payment">
                        ${bdStr ? `<span>${bdStr}</span> &bull; ` : ''}<i class="fa-solid fa-wallet"></i> ${pmt}
                    </div>
                    ${h.path && h.path.length > 1 ? `
                    <button class="btn-view-route" onclick="viewHistoryRoute(${index})">
                        <i class="fa-solid fa-map-location-dot"></i> View Route on Map
                    </button>` : ''}
                </div>`;
            }).join('');
        }

        function openHistoryModal() {
            DOM.historyModalOverlay.classList.add('show');
            renderHistory();
            updateStatsDashboard();
        }

        function closeHistoryModal() {
            DOM.historyModalOverlay.classList.remove('show');
        }

        async function clearHistory() {
            if (!confirm('Clear all ride history? This will also remove it from your cloud backup.')) return;

            localStorage.removeItem('trikefareHistory');
            renderHistory();
            updateStatsDashboard();

            // If logged in, clear from database too
            if (currentUser && navigator.onLine) {
                try {
                    const res = await fetch('api/delete_history.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ clear_all: true })
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast('Cloud history cleared', 'info');
                    }
                } catch (e) {
                    console.error('Failed to clear cloud history:', e);
                }
            }
        }

        async function deleteHistoryItem(index) {
            if (!confirm('Delete this ride record?')) return;
            const history = JSON.parse(localStorage.getItem('trikefareHistory') || '[]');
            if (index >= 0 && index < history.length) {
                const rideToDelete = history[index];
                const rideUuid = rideToDelete.ride_uuid;

                // Remove from local array
                history.splice(index, 1);
                localStorage.setItem('trikefareHistory', JSON.stringify(history));
                renderHistory();
                updateStatsDashboard();

                // If logged in, delete from cloud too
                if (currentUser && navigator.onLine && rideUuid) {
                    try {
                        await fetch('api/delete_history.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ ride_uuid: rideUuid })
                        });
                    } catch (e) {
                        console.error('Failed to delete ride from cloud:', e);
                    }
                }
            }
        }

        // ============================================================
        // PATH HISTORY — View saved route on map
        // ============================================================
        let historyRouteLayer = null;
        let historyOriginMarker = null;
        let historyDestMarker = null;

        function clearHistoryRoute() {
            if (historyRouteLayer) { map.removeLayer(historyRouteLayer); historyRouteLayer = null; }
            if (historyOriginMarker) { map.removeLayer(historyOriginMarker); historyOriginMarker = null; }
            if (historyDestMarker) { map.removeLayer(historyDestMarker); historyDestMarker = null; }
        }

        function viewHistoryRoute(index) {
            const history = JSON.parse(localStorage.getItem('trikefareHistory') || '[]');
            const ride = history[index];
            if (!ride || !ride.path || ride.path.length < 2) return;

            clearHistoryRoute();

            // Close the history modal
            closeHistoryModal();

            // Draw the saved route
            historyRouteLayer = L.polyline(ride.path, {
                color: '#6c5ce7', weight: 5, opacity: 0.8, dashArray: '10 6',
                lineCap: 'round', lineJoin: 'round'
            }).addTo(map);

            // Origin marker (green)
            const originIcon = L.divIcon({
                className: 'user-marker',
                html: '<div style="background:#00d4aa;width:100%;height:100%;border-radius:50%;border:3px solid #fff;box-shadow:0 0 12px rgba(0,212,170,0.6);"></div>',
                iconSize: [18, 18], iconAnchor: [9, 9]
            });
            historyOriginMarker = L.marker(ride.path[0], { icon: originIcon }).addTo(map)
                .bindPopup(`<div style="font-family:'Inter',sans-serif;text-align:center;">
                    <div style="font-size:0.7rem;color:#00d4aa;font-weight:800;text-transform:uppercase;">Start</div>
                    <div style="font-weight:700;">${ride.origin || 'Origin'}</div>
                    <div style="font-size:0.75rem;color:#888;">${ride.startTime || ''}</div>
                </div>`, { closeButton: false });

            // Destination marker (red)
            const destIcon = L.divIcon({
                className: 'user-marker',
                html: '<div style="background:#ff6b6b;width:100%;height:100%;border-radius:50%;border:3px solid #fff;box-shadow:0 0 12px rgba(255,107,107,0.6);"></div>',
                iconSize: [18, 18], iconAnchor: [9, 9]
            });
            historyDestMarker = L.marker(ride.path[ride.path.length - 1], { icon: destIcon }).addTo(map)
                .bindPopup(`<div style="font-family:'Inter',sans-serif;text-align:center;">
                    <div style="font-size:0.7rem;color:#ff6b6b;font-weight:800;text-transform:uppercase;">End</div>
                    <div style="font-weight:700;">${ride.destination || 'Destination'}</div>
                    <div style="font-size:0.75rem;color:#888;">${ride.endTime || ''}</div>
                    <div style="font-size:0.85rem;font-weight:700;color:#00a885;margin-top:4px;">₱${ride.fare} · ${ride.distance} km</div>
                </div>`, { closeButton: false });

            // Fit map to the route
            map.fitBounds(historyRouteLayer.getBounds(), { padding: [50, 50] });

            // Show a dismissal toast
            showToast(`Viewing: ${ride.origin || 'Start'} → ${ride.destination || 'End'} (${ride.date})`, 'info', 5000);

            // Auto-clear after 30 seconds
            setTimeout(() => clearHistoryRoute(), 30000);
        }

        // ============================================================
        // TOAST NOTIFICATION SYSTEM
        // ============================================================
        function showToast(message, type = 'info', duration = 3000) {
            const existing = document.querySelector('.toast-notification');
            if (existing) existing.remove();

            const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
            const colors = { success: '#00d4aa', error: '#ff6b6b', info: '#6c5ce7' };

            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.style.cssText = `
                position: fixed; bottom: 90px; left: 50%; transform: translateX(-50%) translateY(20px);
                background: var(--card, #1a1a2e); color: var(--text); padding: 12px 20px;
                border-radius: 12px; font-size: 0.85rem; font-weight: 600; font-family: 'Inter', sans-serif;
                z-index: 99999; display: flex; align-items: center; gap: 10px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.3); border: 1px solid ${colors[type]}33;
                opacity: 0; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                max-width: 90vw; pointer-events: none;
            `;
            toast.innerHTML = `<i class="fa-solid ${icons[type]}" style="color:${colors[type]};font-size:1.1rem;"></i> ${message}`;
            document.body.appendChild(toast);

            requestAnimationFrame(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(-50%) translateY(0)';
            });

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-50%) translateY(20px)';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        // ============================================================
        // PASSENGER FARE SPLIT
        // ============================================================
        function changePassengers(delta) {
            passengerCount = Math.max(1, Math.min(6, passengerCount + delta));
            DOM.passengerCount.textContent = passengerCount;
            DOM.pMinus.disabled = passengerCount <= 1;
            DOM.pPlus.disabled = passengerCount >= 6;
            const fare = calculateFare(totalDistance, true);
            if (passengerCount > 1) {
                DOM.splitFareText.textContent = '₱' + Math.ceil(fare / passengerCount) + ' each';
            } else {
                DOM.splitFareText.textContent = '';
            }
        }

        // ============================================================
        // SHARE RIDE
        // ============================================================
        function shareRide() {
            const elapsed = getElapsedSeconds();
            const fare = calculateFare(totalDistance, true);
            const text = `🛺 TrikeFare Gensan\n` +
                `📏 Distance: ${totalDistance.toFixed(2)} km\n` +
                `💰 Fare: ₱${fare}\n` +
                `⏱ Duration: ${formatTime(elapsed)}\n` +
                `🚀 Max Speed: ${Math.round(maxSpeed)} km/h\n` +
                (DOM.nightToggle.checked ? `🌙 Night surcharge: +₱${DOM.nightAmount.value}\n` : '') +
                `\n⚠️ Estimate based on GenSan tricycle rates.`;

            if (navigator.share) {
                navigator.share({ title: 'TrikeFare Gensan', text }).catch(() => { });
            } else {
                navigator.clipboard.writeText(text).then(() => {
                    const btn = document.querySelector('.btn-share');
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                    setTimeout(() => { btn.innerHTML = '<i class="fa-solid fa-share-nodes"></i> Share Ride'; }, 2000);
                });
            }
        }

        // ============================================================
        // STATS DASHBOARD
        // ============================================================
        function updateStatsDashboard() {
            const history = JSON.parse(localStorage.getItem('trikefareHistory') || '[]');
            const count = history.length;
            if (!count) {
                DOM.statTotalRides.textContent = '0';
                DOM.statTotalKm.textContent = '0';
                DOM.statTotalSpent.textContent = '₱0';
                DOM.statAvgDist.textContent = '0';
                DOM.statAvgFare.textContent = '₱0';
                DOM.statLongest.textContent = '0';
                return;
            }
            const totalKm = history.reduce((s, h) => s + parseFloat(h.distance), 0);
            const totalSpent = history.reduce((s, h) => s + h.fare, 0);
            const longest = Math.max(...history.map(h => parseFloat(h.distance)));

            DOM.statTotalRides.textContent = count;
            DOM.statTotalKm.textContent = totalKm.toFixed(1);
            DOM.statTotalSpent.textContent = '₱' + totalSpent;
            DOM.statAvgDist.textContent = (totalKm / count).toFixed(1);
            DOM.statAvgFare.textContent = '₱' + Math.round(totalSpent / count);
            DOM.statLongest.textContent = longest.toFixed(1);
        }

        // ============================================================
        // FARE GUIDE MODAL
        // ============================================================
        function openFareGuide() {
            DOM.modalOverlay.classList.add('show');

            // Populate sample routes if empty
            if (!DOM.sampleRoutesList.innerHTML) {
                const groups = {};
                ROUTES.forEach(r => {
                    const fare = calculateFare(r.distance, false);
                    let g = '';
                    if (r.distance <= CONFIG.BASE_KM) g = `₱${CONFIG.BASE_FARE} Base Fare (0–${CONFIG.BASE_KM} km)`;
                    else g = `₱${fare} Range`;

                    if (!groups[g]) groups[g] = [];
                    groups[g].push(r);
                });

                let html = '';
                for (const [groupName, routes] of Object.entries(groups)) {
                    html += `<div style="margin-top:16px; margin-bottom:8px; font-size:0.75rem; color:var(--primary); font-weight:800; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--border); padding-bottom:4px;">${groupName}</div>`;
                    html += routes.map(r => {
                        const fare = calculateFare(r.distance, false);
                        const fixedFare = Math.ceil(fare / 5) * 5;
                        const fixedHtml = (fare % 5 !== 0)
                            ? `<div style="font-size:0.75rem;font-weight:700;color:var(--accent);margin-top:2px;background:rgba(108,92,231,0.1);padding:2px 4px;border-radius:4px;display:inline-block;border:1px solid rgba(108,92,231,0.2);">Est. Fixed Fare: ₱${fixedFare}</div>`
                            : '';
                        return `<div class="route-item" style="align-items:center;">
              <div>
                <div class="r-name">${r.from} → ${r.to}</div>
                <div class="r-dist">${r.distance.toFixed(1)} km</div>
              </div>
              <div style="text-align:right;">
                <div class="r-fare">₱${fare}</div>
                ${fixedHtml}
              </div>
            </div>`;
                    }).join('');
                }
                DOM.sampleRoutesList.innerHTML = html;
            }

            DOM.guideBaseFare.innerHTML = `₱${CONFIG.BASE_FARE} <span>first ${CONFIG.BASE_KM} km</span>`;
        }

        function closeFareGuide() {
            DOM.modalOverlay.classList.remove('show');
        }

        // ============================================================
        // INIT
        // ============================================================
        initMap();
        cacheDom();
        setupLongPress();
        populateRoutes();
        renderHistory();
        updateStatsDashboard();
        setupRealtimeSearch();

        // Init night fare toggle based on current time
        const currentHour = new Date().getHours();
        DOM.nightToggle.checked = (currentHour >= CONFIG.NIGHT_START || currentHour < CONFIG.NIGHT_END);

        // Sync UI with the default input values
        updateBaseFare();
        toggleNightFareInput();

        // Initialize global watch position for idle tracking
        if (navigator.geolocation) {
            watchId = navigator.geolocation.watchPosition(onPosition, onGPSError, {
                enableHighAccuracy: true, maximumAge: 0, timeout: 15000
            });
        } else {
            onGPSError({ message: 'GPS not supported' });
        }

        // Keep initial get location for faster startup
        navigator.geolocation?.getCurrentPosition(
            pos => {
                const latlng = [pos.coords.latitude, pos.coords.longitude];
                currentGPSPos = latlng; // Store the initial fix so selectDestination can use it immediately
                smoothMoveMarker(userMarker, latlng, 1000);
                accuracyCircle.setLatLng(latlng);
                accuracyCircle.setRadius(pos.coords.accuracy || 0);
                map.setView(latlng, 15, { animate: true });
                setStatus('GPS Active |', '');
            },
            () => onGPSError({ message: 'denied' }),
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );

        // ============================================================
        // COMMUNITY FARE LOGIC (PHP/MYSQL)
        // ============================================================
        let currentCommunityData = null;
        let explorerSearchFilter = '';

        // Deduplicate fares: group by distance bucket (±bucketSize km) + same fare,
        // keep shortest distance; tie-break on highest rating.
        function deduplicateFares(subs, bucketSize = 0.5) {
            const buckets = {};
            subs.forEach(s => {
                const dist = s.distance_km !== null && s.distance_km !== undefined
                    ? parseFloat(s.distance_km) : null;
                const fareRounded = parseFloat(s.fare).toFixed(2);
                const bucketKey = dist !== null
                    ? `${Math.round(dist / bucketSize)}_${fareRounded}`
                    : `nodist_${fareRounded}`;
                if (!buckets[bucketKey]) {
                    buckets[bucketKey] = s;
                } else {
                    const existing = buckets[bucketKey];
                    const eDist = existing.distance_km !== null ? parseFloat(existing.distance_km) : Infinity;
                    const sDist = dist !== null ? dist : Infinity;
                    if (sDist < eDist || (sDist === eDist && (s.rating || 0) > (existing.rating || 0))) {
                        buckets[bucketKey] = s;
                    }
                }
            });
            return Object.values(buckets);
        }

        function onExplorerSearch(val) {
            explorerSearchFilter = val.trim();
            renderExplorerList();
        }


        async function fetchCommunityFares() {
            if (!lastDestination) {
                document.getElementById('communityFareSection').style.display = 'none';
                return;
            }

            const origin = lastDestination.originLabel || "Current Location";
            const destination = lastDestination.name;
            const transportType = document.getElementById('communityTransportFilter').value;
            const distKm = lastDestination.distKm || null;
            let timePeriod = document.getElementById('communityTimeFilter') ? document.getElementById('communityTimeFilter').value : 'auto';

            if (timePeriod === 'auto') {
                const hour = new Date().getHours();
                timePeriod = (hour >= 21 || hour < 5) ? 'night' : 'day';
            }

            // Build query with distance & coordinate params for distance-based filtering
            let queryParams = `origin=${encodeURIComponent(origin)}&destination=${encodeURIComponent(destination)}&transport_type=${transportType}&time_period=${timePeriod}`;
            if (distKm) queryParams += `&distance_km=${distKm.toFixed(3)}`;
            if (currentGPSPos) {
                queryParams += `&origin_lat=${currentGPSPos[0]}&origin_lng=${currentGPSPos[1]}`;
            }
            if (lastDestination.destCoords) {
                queryParams += `&dest_lat=${lastDestination.destCoords[0]}&dest_lng=${lastDestination.destCoords[1]}`;
            }

            document.getElementById('communityFareSection').style.display = 'block';
            document.getElementById('communityDataContent').innerHTML = '<div style="font-size:0.75rem; color:var(--text-dim); padding:10px;">Loading community data...</div>';

            try {
                const res = await fetch(`api/get_fares.php?${queryParams}`, {
                    headers: { 'x-session-token': APP_SESSION_TOKEN }
                });

                if (!res.ok) {
                    let errText = await res.text();
                    let errMsg = 'Access Denied or Server Error';
                    try {
                        let errData = JSON.parse(errText);
                        errMsg = errData.error || errMsg;
                    } catch (e) { }
                    document.getElementById('communityDataContent').innerHTML = `<div style="color:var(--danger); padding:10px;"><i class="fa-solid fa-circle-exclamation"></i> ${errMsg}</div>`;
                    return;
                }

                let dataText = await res.text();
                let data;
                try {
                    data = JSON.parse(dataText);
                } catch (e) {
                    console.error('Invalid JSON from server:', dataText.substring(0, 200));
                    throw new Error('Server returned invalid data format');
                }
                currentCommunityData = data;

                if (!data.count || data.count === 0) {
                    const estFare = calculateFare(distKm || 0, true);
                    document.getElementById('communityDataContent').innerHTML = `
                        <div class="community-data-card">
                            <div class="community-estimate-row" style="display: none;">
                                <span class="ce-label"><i class="fa-solid fa-calculator"></i> Estimated Fare</span>
                                <span class="ce-value">₱${estFare}</span>
                            </div>
                            <div style="font-size:0.78rem; color:var(--text-dim); padding:8px 0; font-style:italic; text-align:center;">
                                <i class="fa-solid fa-circle-info"></i> No nearby distance data yet
                            </div>
                        </div>`;
                    document.getElementById('communityLastUpdated').textContent = '';
                    const oldBadge = document.querySelector('.contributor-badge');
                    if (oldBadge) oldBadge.remove();
                } else {
                    const estFare = calculateFare(distKm || 0, true);
                    const rangeLabel = data.distance_range
                        ? `${data.distance_range.min}–${data.distance_range.max} km`
                        : (data.cluster_range ? `${data.cluster_range.min}–${data.cluster_range.max} km` : 'All distances');

                    // ---- Top Fare: filter by user's actual route distance ±4km ----
                    // Only consider submissions whose stored distance_km is within ±4km
                    // of the user's current route. Fares without a stored distance are skipped.
                    let distFilteredSubs = data.raw_submissions;
                    if (distKm && distKm > 0) {
                        const topFareTolerance = 4; // fixed ±4km as per requirement
                        const tfMin = distKm - topFareTolerance;
                        const tfMax = distKm + topFareTolerance;
                        const withDist = data.raw_submissions.filter(s =>
                            s.distance_km !== null && s.distance_km !== undefined &&
                            parseFloat(s.distance_km) >= tfMin &&
                            parseFloat(s.distance_km) <= tfMax
                        );
                        // Only use the filtered set if it has at least one entry; otherwise keep all
                        if (withDist.length > 0) {
                            distFilteredSubs = withDist;
                        }
                    }

                    // Sort candidates: highest rating (likes) first, then closest to user distance
                    distFilteredSubs = [...distFilteredSubs].sort((a, b) => {
                        const aRating = a.rating || 0;
                        const bRating = b.rating || 0;
                        if (aRating !== bRating) return bRating - aRating;     // higher rating wins

                        const aDiff = a.distance_km ? Math.abs(parseFloat(a.distance_km) - (distKm || 0)) : 999;
                        const bDiff = b.distance_km ? Math.abs(parseFloat(b.distance_km) - (distKm || 0)) : 999;
                        return aDiff - bDiff;                                  // closer distance as tie-break
                    });

                    const topFare = distFilteredSubs[0]; // Always show the best match
                    let topFareHtml = '';

                    if (topFare) {
                        const tfVotes = JSON.parse(localStorage.getItem('trikefareVotes') || '{}');
                        const tfExisting = tfVotes[topFare.id] || null;
                        const tfUpClass = tfExisting === 'upvote' ? 'active voted' : '';
                        const tfDownClass = tfExisting === 'downvote' ? 'active voted' : '';
                        const tfNoteHtml = (topFare.note && topFare.note.trim())
                            ? `<div style="font-size:0.7rem;color: whitesmoke; padding: 5px 8px;border-radius: 5px;background-color: rgba(0, 0, 0, 0.1);margin-top:3px; margin-top: 8px"><span style="color: var(--primary);">Commuter's Note: </span><br> ${topFare.note.trim()}</div>`
                            : '';
                        const votes = topFare.rating || 0;
                        const ratingColor = votes > 0 ? 'var(--primary)' : (votes < 0 ? 'var(--danger)' : 'var(--text-dim)');

                        topFareHtml = `
                            <div id="topFare" class="premium-suggestion-card" style="margin-top: 8px; border: 1px solid var(--warning-glow); background: rgba(240, 165, 0, 0.03);">
                                <div class="suggestion-main-wrapper">
                                    <div class="suggestion-badge" style="background: var(--warning-glow); color: var(--warning); border: 1px solid rgba(240, 165, 0, 0.2);"><i class="fa-solid fa-trophy"></i> Top Recommendation</div>
                                    <div class="suggestion-main">
                                        <div class="suggestion-fare" style="color: var(--warning);">₱${parseFloat(topFare.fare).toFixed(2)}</div>
                                        <div class="suggestion-meta">
                                            <span>${topFare.distance_km ? parseFloat(topFare.distance_km).toFixed(1) : '0.0'} km trip</span>
                                            <span>${topFare.time_tag === 'night' ? '<i class="fa-solid fa-moon"></i> Night' : '<i class="fa-solid fa-sun"></i> Day'}</span>
                                        </div>
                                    </div>
                                    <div style="font-size:0.7rem;color: rgba(140, 224, 112, 1);margin-top:3px;"><i class="fa-solid fa-location-dot"></i> ${topFare.origin ? topFare.origin : 'No Origin'} <i class="fa-solid fa-angles-down"></i></div>
                                    <div style="font-size:0.7rem;color: rgba(140, 224, 112, 1);margin-top:3px;"><i class="fa-solid fa-car"></i> ${topFare.destination ? topFare.destination : 'No Destination'}</div>
                                    
                                    ${tfNoteHtml}
                                    <div class="suggestion-stats" style="margin-top: 6px; padding-top: 6px;">
                                        <div class="trust-score" style="color: ${ratingColor}" id="score-${topFare.id}">
                                            <i class="fa-solid fa-thumbs-up"></i> ${(topFare.upvote || 0)} commuters like this
                                        </div>
                                        <div class="rating-controls" style="gap:4px;">
                                            <button class="rate-btn up ${tfUpClass}" onclick="rateFare(${topFare.id}, 'upvote', this)" title="Upvote"><i class="fa-solid fa-thumbs-up"></i></button>
                                            <button class="rate-btn down ${tfDownClass}" onclick="rateFare(${topFare.id}, 'downvote', this)" title="Downvote"><i class="fa-solid fa-thumbs-down"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    document.getElementById('communityDataContent').innerHTML = `
                        <div class="community-data-card">
                            <div class="community-estimate-row" style="display: none;">
                                <span class="ce-label"><i class="fa-solid fa-calculator"></i> Estimated Fare</span>
                                <span class="ce-value">₱${estFare}</span>
                            </div>
                            <div class="community-crowd-header">
                                <i class="fa-solid fa-users"></i> Community Fare
                                <span class="community-range-badge">${rangeLabel}, ${data.count} users</span>
                            </div>
                            <div class="community-stats">
                                <div class="stat-box">
                                    <div class="stat-label">Median</div>
                                    <div class="stat-value">₱${data.median}</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Average</div>
                                    <div class="stat-value">₱${data.average}</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Range</div>
                                    <div class="stat-value" style="font-size:0.9rem;">₱${data.min} - ₱${data.max}</div>
                                </div>
                            </div>
                            ${topFareHtml}
                            ${data.nearby_count > 0 ? `<div class="community-nearby-tag"><i class="fa-solid fa-map-pin"></i> ${data.nearby_count} nearby fare(s) within 1km</div>` : ''}
                        </div>`;

                    const updateTime = new Date(data.last_updated).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    document.getElementById('communityLastUpdated').textContent = `Updated ${updateTime}`;

                    const oldBadge = document.querySelector('.contributor-badge');
                    if (oldBadge) oldBadge.remove();

                    const badge = document.createElement('span');
                    badge.className = 'contributor-badge';
                    badge.innerHTML = `<i class="fa-solid fa-users"></i> ${data.count}`;

                    const titleWrapper = document.querySelector('.community-title-wrapper');
                    if (titleWrapper) {
                        titleWrapper.appendChild(badge);
                    } else {
                        document.querySelector('.community-header').appendChild(badge);
                    }
                }
            } catch (err) {
                console.error('Community Fares Fetch Error:', err);
                document.getElementById('communityDataContent').innerHTML = `<div style="color:var(--danger); padding:10px;"><i class="fa-solid fa-triangle-exclamation"></i> Network or server error loading community data.</div>`;
            }
        }

        async function fetchSmartFareFallback() {
            console.log('Smart Fare Fallback triggered. Distance:', totalDistance);
            if (!currentGPSPos || totalDistance < 0.1 || positions.length < 2) {
                console.warn('Smart Fare Fallback: Insufficient data.');
                return;
            }

            const startPt = positions[0];
            const endPt = positions[positions.length - 1];

            const resultsCard = document.getElementById('resultsCard');
            if (!resultsCard) {
                console.error('resultsCard not found in DOM.');
                return;
            }

            // Remove existing fallback if any
            const oldFallback = document.getElementById('smartFareFallbackResults');
            if (oldFallback) oldFallback.remove();

            const statusArea = document.createElement('div');
            statusArea.className = 'area-fallback-wrapper';
            statusArea.id = 'smartFareFallbackResults';
            statusArea.innerHTML = `
                <div class="area-fallback-title" style="margin-bottom: 8px;">
                    <i class="fa-solid fa-spinner fa-spin"></i> Analyzing your route...
                </div>
            `;

            const title = resultsCard.querySelector('.results-title');
            if (title) title.after(statusArea);
            else resultsCard.prepend(statusArea);

            // Also show community section as users expect it there
            const commSection = document.getElementById('communityFareSection');
            const commContent = document.getElementById('communityDataContent');
            if (commSection) commSection.style.display = 'block';
            if (commContent) commContent.innerHTML = '<div style="font-size:0.75rem; color:var(--text-dim); padding:10px;"><i class="fa-solid fa-spinner fa-spin"></i> Loading community data...</div>';

            try {
                // 1) Detect Start and End Areas
                console.log('Detecting area names for:', startPt, endPt);
                const [startName, endName] = await Promise.all([
                    fetchPlaceName(startPt[0], startPt[1]),
                    fetchPlaceName(endPt[0], endPt[1])
                ]);

                const startArea = startName.split(',')[0].trim();
                const endArea = endName.split(',')[0].trim();
                console.log(`Detected areas: ${startArea} -> ${endArea}`);

                // 2) Fetch matching fares
                const transportType = document.getElementById('communityTransportFilter')?.value || 'Tricycle';
                const hour = new Date().getHours();
                const timePeriod = (hour >= 21 || hour < 5) ? 'night' : 'day';

                const queryParams = new URLSearchParams({
                    origin: startArea,
                    destination: endArea,
                    transport_type: transportType,
                    time_period: timePeriod,
                    distance_km: totalDistance.toFixed(3),
                    origin_lat: startPt[0],
                    origin_lng: startPt[1],
                    dest_lat: endPt[0],
                    dest_lng: endPt[1]
                }).toString();

                console.log('Fetching community fares with:', queryParams);
                const res = await fetch(`api/get_fares.php?${queryParams}`, {
                    headers: { 'x-session-token': APP_SESSION_TOKEN }
                });

                if (res.ok) {
                    const data = await res.json();
                    console.log('API Response:', data);

                    if (data.count > 0 && data.raw_submissions) {
                        // Sort by rating first, then distance proximity
                        const sortedSubs = [...data.raw_submissions].sort((a, b) => {
                            const aRating = a.rating || 0;
                            const bRating = b.rating || 0;
                            if (aRating !== bRating) return bRating - aRating;

                            const aDiff = a.distance_km ? Math.abs(parseFloat(a.distance_km) - totalDistance) : 999;
                            const bDiff = b.distance_km ? Math.abs(parseFloat(b.distance_km) - totalDistance) : 999;
                            return aDiff - bDiff;
                        });
                        const topFare = sortedSubs[0];
                        const votes = topFare.rating || 0;
                        const ratingColor = votes > 0 ? 'var(--primary)' : (votes < 0 ? 'var(--danger)' : 'var(--text-dim)');

                        const tfVotes = JSON.parse(localStorage.getItem('trikefareVotes') || '{}');
                        const tfExisting = tfVotes[topFare.id] || null;
                        const tfUpClass = tfExisting === 'upvote' ? 'active voted' : '';
                        const tfDownClass = tfExisting === 'downvote' ? 'active voted' : '';
                        const tfNoteHtml = (topFare.note && topFare.note.trim())
                            ? `<div style="font-size:0.7rem;color:var(--text-dim);font-style:italic;margin-top:3px;"><i class="fa-solid fa-note-sticky"></i> ${topFare.note.trim()}</div>`
                            : '';

                        const html = `
                            <div class="area-fallback-title" style="margin-bottom: 6px; justify-content: center; font-size: 0.75rem; color: var(--text);">
                                <span>${startArea}</span> <i class="fa-solid fa-arrow-right" style="font-size: 0.6rem; opacity: 0.5;"></i> <span>${endArea}</span>
                            </div>
                            <div id="topFare" class="premium-suggestion-card" style="margin-top: 8px; border: 1px solid var(--warning-glow); background: rgba(240, 165, 0, 0.03);">
                                <div class="suggestion-main-wrapper">
                                    <div class="suggestion-badge" style="background: var(--warning-glow); color: var(--warning); border: 1px solid rgba(240, 165, 0, 0.2);"><i class="fa-solid fa-users"></i> Community Suggested</div>
                                    <div class="suggestion-main">
                                        <div class="suggestion-fare" style="color: var(--warning);">₱${topFare.fare ? parseFloat(topFare.fare).toFixed(2) : '0.00'}</div>
                                        <div class="suggestion-meta">
                                            <span>${topFare.distance_km ? parseFloat(topFare.distance_km).toFixed(1) : '0.0'} km trip</span>
                                            <span>${topFare.time_tag === 'night' ? '<i class="fa-solid fa-moon"></i> Night' : '<i class="fa-solid fa-sun"></i> Day'}</span>
                                        </div>
                                    </div>
                                    ${tfNoteHtml}
                                    <div class="suggestion-stats" style="margin-top: 6px; padding-top: 6px;">
                                        <div class="trust-score" style="color: ${ratingColor}" id="score-${topFare.id}">
                                            <i class="fa-solid fa-thumbs-up"></i> ${votes} commuters like this
                                        </div>
                                        <div class="rating-controls" style="gap:4px;">
                                            <button class="rate-btn up ${tfUpClass}" onclick="rateFare(${topFare.id}, 'upvote', this)" title="Upvote"><i class="fa-solid fa-thumbs-up"></i></button>
                                            <button class="rate-btn down ${tfDownClass}" onclick="rateFare(${topFare.id}, 'downvote', this)" title="Downvote"><i class="fa-solid fa-thumbs-down"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        statusArea.innerHTML = html;
                        if (commContent) commContent.innerHTML = html;
                    } else {
                        console.log('No matching fares found.');
                        const fallbackHtml = `
                            <div class="fallback-no-data">
                                <div class="fallback-message">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>No community fare found for <strong>${startArea} → ${endArea}</strong> yet.</span>
                                </div>
                                <button class="btn-submit-new-fare" onclick="openSubmitFareModal()">
                                    <i class="fa-solid fa-plus"></i> Contribute this fare
                                </button>
                            </div>
                        `;
                        statusArea.innerHTML = fallbackHtml;
                        if (commContent) commContent.innerHTML = fallbackHtml;
                    }
                } else {
                    console.error('API Error:', res.status);
                    statusArea.innerHTML = '<div class="area-fallback-title"><i class="fa-solid fa-circle-exclamation"></i> API error loading fares.</div>';
                }
            } catch (e) {
                console.error('Smart Fare Fallback Exception:', e);
                statusArea.innerHTML = '<div class="area-fallback-title"><i class="fa-solid fa-circle-exclamation"></i> Error analyzing route.</div>';
            }
        }

        async function openSubmitFareModal() {
            // Block submission during active tracking
            if (isTracking) {
                showToast('Please stop your ride first before submitting a fare.', 'error');
                return;
            }

            // Require a completed ride
            if (!rideJustCompleted) {
                showToast('Complete a ride first to submit fare data.', 'error');
                return;
            }

            const originInput = document.getElementById('submitOrigin');
            const destInput = document.getElementById('submitDestination');

            // Show modal immediately so the user sees progress
            document.getElementById('submitFareModalOverlay').classList.add('show');

            // If a destination is already selected, pre-fill from context
            if (lastDestination) {
                originInput.value = lastDestination.originLabel || 'Current Location';
                destInput.value = lastDestination.name;
            } else if (positions.length >= 2) {
                // Use tracked path start/end points for geocoding
                originInput.value = 'Detecting location...';
                destInput.value = 'Detecting location...';
                originInput.disabled = true;
                destInput.disabled = true;
                try {
                    if (!window._geocodeCache) window._geocodeCache = {};
                    const startPt = positions[0];
                    const endPt = positions[positions.length - 1];
                    const sKey = `${startPt[0].toFixed(4)},${startPt[1].toFixed(4)}`;
                    const eKey = `${endPt[0].toFixed(4)},${endPt[1].toFixed(4)}`;
                    const [oName, dName] = await Promise.all([
                        window._geocodeCache[sKey] ? Promise.resolve(window._geocodeCache[sKey]) : fetchPlaceName(startPt[0], startPt[1]),
                        window._geocodeCache[eKey] ? Promise.resolve(window._geocodeCache[eKey]) : fetchPlaceName(endPt[0], endPt[1])
                    ]);
                    window._geocodeCache[sKey] = oName;
                    window._geocodeCache[eKey] = dName;
                    originInput.value = oName;
                    destInput.value = dName;
                } catch (e) {
                    originInput.value = 'Current Location';
                    destInput.value = 'GPS Tracked End';
                } finally {
                    originInput.disabled = false;
                    destInput.disabled = false;
                }
            } else if (currentGPSPos) {
                // Auto-detect from current GPS only
                originInput.value = 'Detecting location...';
                destInput.value = 'Detecting location...';
                originInput.disabled = true;
                destInput.disabled = true;
                try {
                    const placeName = await fetchPlaceName(currentGPSPos[0], currentGPSPos[1]);
                    originInput.value = placeName;
                    destInput.value = placeName;
                } catch (e) {
                    originInput.value = 'Current Location';
                    destInput.value = 'Current Location';
                } finally {
                    originInput.disabled = false;
                    destInput.disabled = false;
                }
            } else {
                originInput.value = '';
                destInput.value = '';
            }

            // Try to pre-fill fare from ride summary if available
            const resultFareEl = document.getElementById('resultFare');
            const suggested = resultFareEl ? resultFareEl.textContent.replace('₱', '') : '';
            document.getElementById('submitFareAmount').value = (suggested && suggested !== '0' && suggested !== '₱15') ? suggested : '';

            // Auto-detect time tag
            const hour = new Date().getHours();
            if (hour >= 21 || hour < 5) document.getElementById('submitTimeTag').value = 'night';
            else if ((hour >= 7 && hour <= 9) || (hour >= 16 && hour <= 18)) document.getElementById('submitTimeTag').value = 'rush_hour';
            else document.getElementById('submitTimeTag').value = 'day';
        }

        // Detect area name from GPS and fill into a target input
        async function detectCurrentArea(targetInputId) {
            const input = document.getElementById(targetInputId);
            if (!currentGPSPos) {
                showToast('GPS not available. Please enable location access.', 'error');
                return;
            }
            input.value = 'Detecting...';
            input.disabled = true;
            try {
                const placeName = await fetchPlaceName(currentGPSPos[0], currentGPSPos[1]);
                input.value = placeName;
            } catch (e) {
                input.value = 'Current Location';
            } finally {
                input.disabled = false;
            }
        }

        function sidebarSubmitFare() {
            closeSidebar();
            openSubmitFareModal();
        }

        function closeSubmitFareModal() {
            document.getElementById('submitFareModalOverlay').classList.remove('show');
        }

        let isSubmittingFare = false; // Debounce lock

        async function submitCommunityFare() {
            if (isSubmittingFare) return; // Prevent double-submit
            const btn = document.getElementById('btnSubmitFareConfirm');
            const fareVal = parseFloat(document.getElementById('submitFareAmount').value);

            if (!fareVal || fareVal <= 0) {
                showToast('Please enter a valid fare amount.', 'error');
                return;
            }

            const originVal = document.getElementById('submitOrigin').value.trim();
            const destVal = document.getElementById('submitDestination').value.trim();

            if (!originVal || originVal === 'Detecting...' || originVal === 'Detecting location...') {
                showToast('Please wait for location detection or enter an origin.', 'error');
                return;
            }
            if (!destVal) {
                showToast('Please enter a destination.', 'error');
                return;
            }

            isSubmittingFare = true;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';

            // Compute distance from tracked path if no destination was pinned
            let submissionDistKm = lastDestination ? lastDestination.distKm : null;
            if (!submissionDistKm && positions.length >= 2) {
                submissionDistKm = 0;
                for (let i = 1; i < positions.length; i++) {
                    submissionDistKm += haversine(positions[i - 1][0], positions[i - 1][1], positions[i][0], positions[i][1]);
                }
                submissionDistKm = parseFloat(submissionDistKm.toFixed(3));
            }

            const noteVal = (document.getElementById('submitFareNote')?.value || '').trim().slice(0, 100);

            // Get username for voting/deduplication
            let username = await ensureUsername();
            if (!username) {
                showToast('Unable to assign a username. Please check your connection.', 'error');
                isSubmittingFare = false;
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                return;
            }

            const payload = {
                origin: originVal,
                destination: destVal,
                transport_type: document.getElementById('submitTransportType').value,
                fare: fareVal,
                time_tag: document.getElementById('submitTimeTag').value,
                note: noteVal || null,
                distance_km: submissionDistKm,
                origin_lat: currentGPSPos ? currentGPSPos[0] : null,
                origin_lng: currentGPSPos ? currentGPSPos[1] : null,
                dest_lat: lastDestination && lastDestination.destCoords ? lastDestination.destCoords[0] : null,
                dest_lng: lastDestination && lastDestination.destCoords ? lastDestination.destCoords[1] : null,
                username: username
            };

            // OFFLINE HANDLING
            if (!navigator.onLine) {
                saveOfflineFare(payload);
                finalizeFareSubmission(btn, originalHtml);
                showToast('Device is offline. Fare saved locally and will sync once connected!', 'warning');
                isSubmittingFare = false;
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                return;
            }

            try {
                const res = await fetch('api/submit_fare.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Session-Token': APP_SESSION_TOKEN
                    },
                    body: JSON.stringify(payload)
                });

                if (res.ok) {
                    showToast('Thank you for contributing fare data!', 'success');
                    finalizeFareSubmission(btn, originalHtml);
                } else {
                    // Fallback to offline storage if server returns error but we have connection issues
                    const errData = await res.json().catch(() => ({}));
                    if (res.status >= 500) {
                        saveOfflineFare(payload);
                        showToast('Server error. Fare saved locally for later sync.', 'warning');
                        finalizeFareSubmission(btn, originalHtml);
                    } else {
                        showToast(errData.message || 'Submission failed.', 'error');
                    }
                }
            } catch (e) {
                // Network error (e.g. DNS failure) - save offline
                saveOfflineFare(payload);
                showToast('Connection lost. Fare saved locally for later sync.', 'warning');
                finalizeFareSubmission(btn, originalHtml);
            } finally {
                isSubmittingFare = false;
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

        function finalizeFareSubmission(btn, originalHtml) {
            closeSubmitFareModal();
            rideJustCompleted = false; // Prevent re-submission of same ride
            if (awaitingFareSubmission && lastCompletedRideData) {
                // If it was a forced prompt after stopping, save to history now
                saveRide(lastCompletedRideData.dist, lastCompletedRideData.fare, lastCompletedRideData.duration);
                awaitingFareSubmission = false;
                lastCompletedRideData = null;
                setStatus('Ride complete & saved', '');
                resetRide(); // Reset tracking and UI after successful submission
                updateStreakOnServer(); // Update streak if logged in
            }
        }

        function saveOfflineFare(payload) {
            SyncQueue.push({ type: 'submit_fare', data: payload });
        }

        // Note: Old syncOfflineFares is replaced by SyncQueue.processQueue() in offline_sync.js

        function renderExplorerList() {
            if (!currentCommunityData || !currentCommunityData.raw_submissions) return;

            const list = document.getElementById('explorerList');
            if (!list) return;

            const sortBy = document.getElementById('communitySortFilter').value;
            let subs = [...currentCommunityData.raw_submissions];

            if (sortBy === 'rating') {
                subs.sort((a, b) => (b.rating || 0) - (a.rating || 0));
            } else if (sortBy === 'fare_high') {
                subs.sort((a, b) => b.fare - a.fare);
            } else if (sortBy === 'fare_low') {
                subs.sort((a, b) => a.fare - b.fare);
            } else if (sortBy === 'recent') {
                subs.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            }

            // Deduplicate: keep best entry per ±0.5km bucket
            subs = deduplicateFares(subs);

            // Apply search filter (case-insensitive, multi-field)
            if (explorerSearchFilter) {
                const q = explorerSearchFilter.toLowerCase();
                subs = subs.filter(s => {
                    const distLabel = s.distance_km ? parseFloat(s.distance_km).toFixed(1) + ' km' : '';
                    const note = (s.note || '').toLowerCase();
                    const fareStr = parseFloat(s.fare).toFixed(2);
                    return distLabel.includes(q) || note.includes(q) || fareStr.includes(q) ||
                        (s.time_tag || '').toLowerCase().includes(q) ||
                        (s.transport_type || '').toLowerCase().includes(q);
                });
            }

            if (subs.length === 0) {
                list.innerHTML = `<div style="text-align:center;font-size:0.78rem;color:var(--text-dim);padding:20px;"><i class="fa-solid fa-circle-info"></i> No matching fares found.</div>`;
                return;
            }

            const voteHistory = JSON.parse(localStorage.getItem('trikefareVotes') || '{}');
            const topRating = Math.max(...subs.map(s => s.rating || 0));

            list.innerHTML = subs.map(item => {
                const date = new Date(item.created_at).toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
                const vehicleType = item.transport_type || 'Unknown Vehicle';
                const distLabel = item.distance_km ? `${parseFloat(item.distance_km).toFixed(1)} km` : '';
                const isTopFare = topRating > 0 && (item.rating || 0) === topRating;
                const topBadge = isTopFare ? '<span class="top-fare-badge"><i class="fa-solid fa-trophy"></i> Top Fare</span>' : '';

                let timeBadge = '';
                if (item.time_tag === 'night') {
                    timeBadge = '<span class="time-badge night-badge"><i class="fa-solid fa-moon"></i> Night Fare</span>';
                } else {
                    timeBadge = '<span class="time-badge day-badge"><i class="fa-solid fa-sun"></i> Day Fare</span>';
                }

                const existingVote = voteHistory[item.id] || null;
                const upClass = existingVote === 'upvote' ? 'active voted' : '';
                const downClass = existingVote === 'downvote' ? 'active voted' : '';
                const votedLabel = existingVote ? `<div class="vote-status">${existingVote === 'upvote' ? '👍' : '👎'} Voted</div>` : '';
                const noteHtml = (item.note && item.note.trim())
                    ? `<div style="font-size:0.72rem;color:var(--text-dim);font-style:italic;margin-top:3px;"><i class="fa-solid fa-note-sticky"></i> ${item.note.trim()}</div>`
                    : '';

                return `
                    <div class="submission-item${isTopFare ? ' top-fare-item' : ''}">
                        <div class="submission-info">
                            <div class="submission-fare">₱${parseFloat(item.fare).toFixed(2)} ${topBadge}</div>
                            <div class="submission-meta">Type: ${vehicleType}${distLabel ? ' | ' + distLabel : ''} | ${date} ${timeBadge}</div>
                            ${noteHtml}
                            ${votedLabel}
                        </div>
                        <div class="rating-controls" style="gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <div class="rating-score" style="font-size: 0.95rem; font-weight: 800; color: ${(item.upvote || 0) > 0 ? 'var(--primary)' : 'var(--text-dim)'}" id="upvote-score-${item.id}">${item.upvote || 0}</div>
                                <button class="rate-btn up ${upClass}" onclick="rateFare(${item.id}, 'upvote', this)" title="Upvote"><i class="fa-solid fa-thumbs-up"></i></button>
                            </div>
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <div class="rating-score" style="font-size: 0.95rem; font-weight: 800; color: ${(item.downvote || 0) > 0 ? 'var(--danger)' : 'var(--text-dim)'}" id="downvote-score-${item.id}">${item.downvote || 0}</div>
                                <button class="rate-btn down ${downClass}" onclick="rateFare(${item.id}, 'downvote', this)" title="Downvote"><i class="fa-solid fa-thumbs-down"></i></button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function openCommunityExplorer() {
            if (!currentCommunityData || !currentCommunityData.raw_submissions) return;

            const routeName = document.getElementById('explorerRouteName');
            routeName.textContent = `${lastDestination.originLabel || "Current Location"} → ${lastDestination.name}`;

            renderExplorerList();

            document.getElementById('communityExplorerModalOverlay').classList.add('show');
        }

        function closeCommunityExplorer() {
            document.getElementById('communityExplorerModalOverlay').classList.remove('show');
            explorerSearchFilter = '';
            const searchEl = document.getElementById('explorerSearchInput');
            if (searchEl) searchEl.value = '';
        }

        // Get or generate username
        async function getVoteUsername() {
            return await ensureUsername();
        }

        async function rateFare(id, action, btn) {
            // Check localStorage first for existing vote
            const voteHistory = JSON.parse(localStorage.getItem('trikefareVotes') || '{}');
            if (voteHistory[id] === action) {
                showToast(`You already ${voteHistory[id]}d this fare.`, 'info');
                return;
            }

            // Get username
            const username = await getVoteUsername();
            if (!username) {
                showToast('Unable to assign a username.', 'error');
                return;
            }

            btn.disabled = true;

            try {
                const res = await fetch('api/rate_fare.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'x-session-token': APP_SESSION_TOKEN
                    },
                    body: JSON.stringify({ id, action, username })
                });

                if (res.status === 401 || res.status === 429 || res.status >= 500) {
                    let errData = {};
                    try { errData = await res.json(); } catch (e) { }
                    const errMsg = errData.error || (res.status >= 500 ? 'Server Error: Unable to record vote.' : 'Access Denied');
                    showToast(errMsg, 'error');
                    btn.disabled = false;
                    return;
                }

                const result = await res.json();

                if (result.success) {
                    // Save vote to localStorage
                    voteHistory[id] = action;
                    localStorage.setItem('trikefareVotes', JSON.stringify(voteHistory));

                    // Update UI internally
                    const upScoreEl = document.getElementById(`upvote-score-${id}`);
                    const downScoreEl = document.getElementById(`downvote-score-${id}`);
                    if (upScoreEl) {
                        upScoreEl.textContent = result.new_upvotes || 0;
                        upScoreEl.style.color = (result.new_upvotes || 0) > 0 ? 'var(--primary)' : 'var(--text-dim)';
                    }
                    if (downScoreEl) {
                        downScoreEl.textContent = result.new_downvotes || 0;
                        downScoreEl.style.color = (result.new_downvotes || 0) > 0 ? 'var(--danger)' : 'var(--text-dim)';
                    }

                    // For the single score fallback (like in Top Fare card)
                    const scoreEl = document.getElementById(`score-${id}`);
                    if (scoreEl) {
                        scoreEl.textContent = result.new_rating;
                        scoreEl.style.color = result.new_rating > 0 ? 'var(--primary)' : (result.new_rating < 0 ? 'var(--danger)' : 'var(--text)');
                    }

                    // Manage UI active states
                    const container = btn.parentElement;
                    container.querySelectorAll('.rate-btn').forEach(b => b.classList.remove('active', 'voted'));
                    btn.classList.add('active', 'voted');

                    // Update the cache so re-opening or rendering top fare gets the new score
                    if (currentCommunityData && currentCommunityData.raw_submissions) {
                        const fareItem = currentCommunityData.raw_submissions.find(s => s.id === id);
                        if (fareItem) {
                            fareItem.rating = result.new_rating;
                        }
                    }

                    showToast(`Vote recorded! Thank you, ${username}.`, 'success');

                    // Refresh the summary stats in the background to reflect changes
                    fetchCommunityFares();
                } else if (result.error === 'already_voted') {
                    // Server says already voted — sync localStorage
                    voteHistory[id] = result.existing_vote || action;
                    localStorage.setItem('trikefareVotes', JSON.stringify(voteHistory));
                    showToast(result.message || 'You have already voted on this fare.', 'info');

                    // Sync UI classes to match real server state
                    const container = btn.parentElement;
                    container.querySelectorAll('.rate-btn').forEach(b => b.classList.remove('active', 'voted'));
                    if (voteHistory[id] === 'upvote') container.querySelector('.up').classList.add('active', 'voted');
                    if (voteHistory[id] === 'downvote') container.querySelector('.down').classList.add('active', 'voted');
                } else {
                    showToast(result.error || 'Failed to submit vote.', 'error');
                    btn.disabled = false;
                }
            } catch (err) {
                console.error("Rating error:", err);
                showToast('Network error. Please try again.', 'error');
                btn.disabled = false;
            }
        }

        // ============================================================
        // ============================================================
        // CAB ROUTES & COMMUTE ASSISTANCE
        // ============================================================
        function showCabRoutes() {
            const modal = document.getElementById('cabRouteModalOverlay');
            if (!modal) return;
            modal.classList.add('show');
            renderCabRouteList();
            autoSuggestBestRoute();
        }

        function openCommunityForum() {
            window.location.href = "community.php";
        }

        function closeCabRoutes() {
            const modal = document.getElementById('cabRouteModalOverlay');
            if (modal) modal.classList.remove('show');
        }

        function renderCabRouteList() {
            const list = document.getElementById('cabRouteList');
            list.innerHTML = CAB_ROUTES.map(route => `
                <div class="route-item ${activeCabRouteId === route.id ? 'active' : ''}" onclick="selectCabRoute('${route.id}')">
                    <div class="route-icon" style="background: ${route.color || 'var(--accent)'}">
                        <i class="fa-solid fa-bus"></i>
                    </div>
                    <div class="route-details">
                        <div class="route-name">${route.name}</div>
                        <div class="route-meta">${route.stops.length} stops · Approx. ${((route.path.length * 0.1) || 1).toFixed(1)} km</div>
                    </div>
                    ${activeCabRouteId === route.id ? '<i class="fa-solid fa-circle-check" style="color:var(--accent)"></i>' : ''}
                </div>
            `).join('');
        }

        function selectCabRoute(id) {
            const route = CAB_ROUTES.find(r => r.id === id);
            if (!route) return;

            // Clear previous layers
            cabRouteLayers.forEach(l => map.removeLayer(l));
            cabRouteLayers = [];

            activeCabRouteId = id;
            renderCabRouteList();

            // Draw Path
            const poly = L.polyline(route.path, {
                color: route.color || '#6c5ce7',
                weight: 6,
                opacity: 0.8,
                lineJoin: 'round'
            }).addTo(map);
            cabRouteLayers.push(poly);

            // Draw Stops
            route.stops.forEach(stop => {
                const marker = L.marker([stop.lat, stop.lng], {
                    icon: L.divIcon({
                        className: 'stop-marker',
                        html: `<div style="background:white; border:3px solid ${route.color}; width:12px; height:12px; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.3);"></div>`,
                        iconSize: [12, 12],
                        iconAnchor: [6, 6]
                    })
                }).addTo(map);
                marker.bindPopup(`<strong>${stop.name}</strong><br>Cab Stop`);
                cabRouteLayers.push(marker);
            });

            map.fitBounds(poly.getBounds(), { padding: [50, 50] });
            closeCabRoutes();
            showToast(`Showing ${route.name}`, 'info');

            // Assistance logic
            suggestCabCommute(route);
        }

        function autoSuggestBestRoute() {
            if (!currentGPSPos || !lastDestination || !lastDestination.destCoords) return;

            const userPt = L.latLng(currentGPSPos[0], currentGPSPos[1]);
            const destPt = L.latLng(lastDestination.destCoords[0], lastDestination.destCoords[1]);

            let bestRoute = null;
            let bestScore = Infinity;

            CAB_ROUTES.forEach(route => {
                let dUser = Infinity;
                route.path.forEach(p => {
                    const d = userPt.distanceTo(L.latLng(p[0], p[1]));
                    if (d < dUser) dUser = d;
                });

                let dDest = Infinity;
                route.path.forEach(p => {
                    const d = destPt.distanceTo(L.latLng(p[0], p[1]));
                    if (d < dDest) dDest = d;
                });

                const totalDist = dUser + dDest;
                if (totalDist < bestScore) {
                    bestScore = totalDist;
                    bestRoute = route;
                }
            });

            if (bestRoute && bestScore < 1500) {
                const infoArea = document.getElementById('routeAssistanceInfo');
                const suggestionBox = document.getElementById('commuteSuggestion');
                infoArea.style.display = 'block';
                suggestionBox.innerHTML = `
                    <div class="commute-suggestion-card" style="margin-top:0;">
                        <div class="suggestion-title"><i class="fa-solid fa-star"></i> Recommended Route</div>
                        <div class="suggestion-text">
                            The <strong>${bestRoute.name}</strong> is your best option! It passes near you and goes close to your destination.
                        </div>
                        <button class="admin-btn primary" style="margin-top:10px; width:100%; border-radius:8px;" onclick="selectCabRoute('${bestRoute.id}')">View Route Path</button>
                    </div>
                `;
            }
        }

        function suggestCabCommute(route) {
            if (!currentGPSPos) return;

            // Find nearest point on route from user
            const userPt = L.latLng(currentGPSPos[0], currentGPSPos[1]);
            let minDist = Infinity;
            let nearestStop = null;

            route.stops.forEach(stop => {
                const d = userPt.distanceTo(L.latLng(stop.lat, stop.lng));
                if (d < minDist) {
                    minDist = d;
                    nearestStop = stop;
                }
            });

            if (!nearestStop) return; // Safety check

            const suggestionBox = document.getElementById('commuteSuggestion');
            const infoArea = document.getElementById('routeAssistanceInfo');

            if (minDist < 500) { // Within 500m
                infoArea.style.display = 'block';
                suggestionBox.innerHTML = `
                    <div class="suggestion-title"><i class="fa-solid fa-thumbs-up"></i> Commute Tip</div>
                    <div class="suggestion-text">
                        This route passes near you! Walk to <strong>${nearestStop.name}</strong> (${Math.round(minDist)}m) to catch the <strong>${route.name}</strong>.
                    </div>
                `;
            } else {
                infoArea.style.display = 'block';
                suggestionBox.innerHTML = `
                    <div class="suggestion-title"><i class="fa-solid fa-person-walking"></i> Commute Tip</div>
                    <div class="suggestion-text">
                        The nearest stop for this route is <strong>${nearestStop.name}</strong>, about ${(minDist / 1000).toFixed(1)}km away.
                    </div>
                `;
            }
        }

        // ============================================================
        // ADMIN ROUTE PANEL LOGIC
        // ============================================================
        function initAdminTrigger() {
            const title = document.getElementById('appTitle');
            if (!title) return;

            title.addEventListener('touchstart', e => {
                adminLongPressTimer = setTimeout(openAdminPanel, 3000);
            });
            title.addEventListener('touchend', e => {
                clearTimeout(adminLongPressTimer);
            });
            title.addEventListener('mousedown', e => {
                adminLongPressTimer = setTimeout(openAdminPanel, 3000);
            });
            title.addEventListener('mouseup', e => {
                clearTimeout(adminLongPressTimer);
            });
        }

        function openAdminPanel() {
            if (navigator.vibrate) navigator.vibrate(200);
            document.getElementById('adminRoutePanel').style.display = 'block';
            isAdminActive = true;
            showToast('Admin Mode Active', 'warning');
        }

        function closeAdminPanel() {
            document.getElementById('adminRoutePanel').style.display = 'none';
            if (adminRecording) stopAdminRecording();
        }

        function toggleAdminRecording() {
            const btn = document.getElementById('btnAdminRecord');
            if (!adminRecording) {
                // Start
                const name = document.getElementById('adminRouteName').value.trim();
                if (!name) {
                    showToast('Please enter a route name first', 'error');
                    return;
                }
                adminRecording = true;
                adminRoutePath = [];
                adminStops = [];
                btn.innerHTML = '<i class="fa-solid fa-stop"></i> Stop Recording';
                btn.classList.add('recording');
                document.getElementById('adminRecordStatus').textContent = 'Status: Recording...';

                if (adminRecordingLayer) map.removeLayer(adminRecordingLayer);
                adminRecordingLayer = L.polyline([], { color: '#e74c3c', weight: 4, dashArray: '5, 10' }).addTo(map);
            } else {
                // Stop
                stopAdminRecording();
            }
        }

        function stopAdminRecording() {
            adminRecording = false;
            const btn = document.getElementById('btnAdminRecord');
            btn.innerHTML = '<i class="fa-solid fa-circle"></i> Start Recording';
            btn.classList.remove('recording');
            document.getElementById('adminRecordStatus').textContent = 'Status: Idle (Paused)';
            showToast('Recording paused', 'info');
        }

        function adminAddStop() {
            if (!currentGPSPos) {
                showToast('GPS position needed to add stop', 'error');
                return;
            }
            const name = prompt("Enter stop name (e.g. Robinson's Mall):");
            if (!name) return;

            const stop = { name, lat: currentGPSPos[0], lng: currentGPSPos[1] };
            adminStops.push(stop);

            const m = L.marker([stop.lat, stop.lng], {
                icon: L.divIcon({
                    className: 'admin-stop-marker',
                    html: '<i class="fa-solid fa-map-pin" style="color:#e67e22; font-size:1.2rem;"></i>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 20]
                })
            }).addTo(map).bindTooltip(name, { permanent: true });
            adminStopMarkers.push(m);
            showToast(`Stop added: ${name}`, 'success');
        }

        function adminUndoPoint() {
            if (adminRoutePath.length > 0) {
                adminRoutePath.pop();
                if (adminRecordingLayer) adminRecordingLayer.setLatLngs(adminRoutePath);
            }
        }

        function adminClearRoute() {
            if (!confirm('Clear current recording?')) return;
            adminRoutePath = [];
            adminStops = [];
            if (adminRecordingLayer) adminRecordingLayer.setLatLngs([]);
            adminStopMarkers.forEach(m => map.removeLayer(m));
            adminStopMarkers = [];
            showToast('Route cleared', 'info');
        }

        function adminExportJson() {
            const name = document.getElementById('adminRouteName').value.trim() || 'New Route';
            const routeObj = {
                id: 'route-' + Date.now(),
                name: name,
                color: '#' + Math.floor(Math.random() * 16777215).toString(16),
                path: adminRoutePath,
                stops: adminStops
            };

            const jsonStr = JSON.stringify(routeObj, null, 2);
            console.log("EXPORTED ROUTE:", jsonStr);

            // Trigger download
            const blob = new Blob([jsonStr], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${name.replace(/\s+/g, '_')}.json`;
            a.click();

            // Also save to local storage for immediate testing
            const currentRoutes = JSON.parse(localStorage.getItem('trikefare_cab_routes')) || CAB_ROUTES;
            currentRoutes.push(routeObj);
            localStorage.setItem('trikefare_cab_routes', JSON.stringify(currentRoutes));

            showToast('Route exported and saved locally!', 'success');
        }

        // Call this during onPosition if adminRecording is true
        function updateAdminRecording(lat, lng) {
            if (!adminRecording) return;

            // Avoid duplicates
            const last = adminRoutePath[adminRoutePath.length - 1];
            if (last && last[0] === lat && last[1] === lng) return;

            adminRoutePath.push([lat, lng]);
            if (adminRecordingLayer) {
                adminRecordingLayer.setLatLngs(adminRoutePath);
            }
        }

        // Initialize Admin trigger
        initAdminTrigger();

        // PWA & SERVICE WORKER
        // ============================================================
        let deferredPrompt;
        const btnInstallPwa = document.getElementById('sidebarInstall');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            if (btnInstallPwa) {
                btnInstallPwa.style.display = 'flex';
            }
        });

        async function installPwa() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                }
                deferredPrompt = null;
                if (btnInstallPwa) {
                    btnInstallPwa.style.display = 'none';
                }
            }
        }

        window.addEventListener('appinstalled', () => {
            if (btnInstallPwa) btnInstallPwa.style.display = 'none';
            deferredPrompt = null;
        });

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                checkAuthStatus(); // Check if user is logged in
                ensureUsername(); // Auto-generate on load if missing (only if not logged in)

                // Restore tracking state if the app was accidentally closed during a ride
                restoreRideState();

                navigator.serviceWorker.register('sw.js', { updateViaCache: 'none' })
                    .then(registration => {
                        // Force check for updates on every page load
                        registration.update();
                    })
                    .catch(err => {
                        console.warn('SW registration failed:', err);
                    });
            });

            // Reload page if a new service worker takes over
            let refreshing = false;
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                if (!refreshing) {
                    refreshing = true;
                    window.location.reload();
                }
            });
        }

        // Handle online restoration for ride history
        window.addEventListener('online', () => {
            console.log('Connection restored. Syncing ride history...');
            if (currentUser) {
                syncHistoryToServer();
            }
        });
    </script>
    <!-- CAB ROUTE MODAL -->
    <div class="modal-overlay" id="cabRouteModalOverlay" onclick="if(event.target===this) closeCabRoutes()">
        <div class="fare-modal">
            <div class="modal-header">
                <div>
                    <div class="modal-title"><i class="fa-solid fa-bus"></i> Find Cab Route</div>
                    <div class="modal-subtext">Predefined Multicab & Jeepney Routes</div>
                </div>
                <button class="sidebar-close" onclick="closeCabRoutes()">×</button>
            </div>
            <div class="modal-body" style="padding: 15px;">
                <div class="route-list" id="cabRouteList">
                    <!-- Dynamic List -->
                </div>
                <div id="routeAssistanceInfo" style="margin-top: 15px; display: none;">
                    <div id="commuteSuggestion"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ADMIN ROUTE PANEL -->
    <div class="admin-panel" id="adminRoutePanel" style="display: none;">
        <div class="admin-header">
            <div class="admin-title">Route Admin Panel</div>
            <button class="sidebar-close" onclick="closeAdminPanel()" style="color: #fff;">×</button>
        </div>
        <div class="admin-body">
            <div class="admin-group">
                <label>Route Name</label>
                <input type="text" id="adminRouteName" placeholder="e.g. Yellow Multicab - Route A">
            </div>
            <div class="admin-controls"
                style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px;">
                <button class="admin-btn primary" id="btnAdminRecord" onclick="toggleAdminRecording()">
                    <i class="fa-solid fa-circle"></i> Start Recording
                </button>
                <button class="admin-btn" onclick="adminAddStop()">
                    <i class="fa-solid fa-map-pin"></i> Add Stop
                </button>
                <button class="admin-btn" onclick="adminUndoPoint()">
                    <i class="fa-solid fa-undo"></i> Undo
                </button>
                <button class="admin-btn danger" onclick="adminClearRoute()">
                    <i class="fa-solid fa-trash"></i> Clear
                </button>
            </div>
            <div class="admin-status" id="adminRecordStatus" style="margin-top: 10px; font-size: 0.8rem; opacity: 0.8;">
                Status: Idle</div>
            <div style="margin-top: 15px;">
                <button class="admin-btn success" style="width: 100%;" onclick="adminExportJson()">
                    <i class="fa-solid fa-file-export"></i> Export Route JSON
                </button>
            </div>
        </div>
    </div>
    <!-- Tutorial / Onboarding System (deferred — never blocks page load) -->
    <script src="tutorial.js" defer></script>
</body>

</html>