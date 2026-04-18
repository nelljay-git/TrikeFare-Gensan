
// ============================================================
// CONFIG — All tunable constants in one place
// ============================================================
const CONFIG = {
  THROTTLE_MS: 2500, MIN_ACCURACY_M: 50, WEAK_SIGNAL_M: 30,
  MIN_MOVEMENT_KM: 0.01, MAX_JUMP_KM: 0.3, MAX_SPEED_KMH: 60,
  BASE_FARE: 15, BASE_KM: 4, PER_KM_RATE: 1,
  NIGHT_SURCHARGE: 3, NIGHT_START: 18, NIGHT_END: 6,
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
    'mapSearchInput', 'btnSearch', 'mapSearchResults'
  ].forEach(id => { DOM[id] = document.getElementById(id); });
}

// ============================================================
// STATE
// ============================================================
let map, userMarker, routeLine, accuracyCircle;
let watchId = null;
let isTracking = false;
let destinationMarker = null;
let positions = [];
let totalDistance = 0;
let lastPos = null;
let lastTimestamp = 0;
let currentSpeed = 0;
let maxSpeed = 0;
let weakSignalCount = 0;
let rideStartTime = 0;
let timerInterval = null;
let passengerCount = 1;
let isNightFare = false;

// ============================================================
// MATH UTILITIES
// ============================================================
function togglePanel() {
  const panel = document.getElementById('panel');
  panel.classList.toggle('collapsed');
  if (!panel.classList.contains('collapsed')) {
    // If expanding, scroll to top of panel to ensure controls are visible
    panel.scrollTop = 0;
  }
}

// ============================================================
// MAP SEARCH
// ============================================================
async function performSearch() {
  const query = DOM.mapSearchInput.value.trim();
  if (!query) return;

  DOM.btnSearch.textContent = '...';
  DOM.btnSearch.disabled = true;

  try {
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&viewbox=124.9,6.3,125.4,5.9&bounded=1`;
    const res = await fetch(url);
    const data = await res.json();

    if (data.length === 0) {
      DOM.mapSearchResults.innerHTML = `<div class="search-result-item" style="text-align:center; color:var(--danger)">No results found in GenSan</div>`;
    } else {
      DOM.mapSearchResults.innerHTML = data.map((item) => {
        const name = item.name || item.display_name.split(',')[0];
        const address = item.display_name;
        return `<div class="search-result-item" onclick="selectDestination(${item.lat}, ${item.lon}, '${name.replace(/'/g, "\\'")}')">
                      <div class="search-result-name">${name}</div>
                      <div class="search-result-address">${address}</div>
                    </div>`;
      }).join('');
    }
    DOM.mapSearchResults.classList.add('show');
  } catch (err) {
    DOM.mapSearchResults.innerHTML = `<div class="search-result-item" style="text-align:center; color:var(--danger)">Search error</div>`;
    DOM.mapSearchResults.classList.add('show');
  } finally {
    DOM.btnSearch.textContent = 'Search';
    DOM.btnSearch.disabled = false;
  }
}

function selectDestination(lat, lon, name) {
  DOM.mapSearchResults.classList.remove('show');
  const destLatLng = [lat, lon];

  if (destinationMarker) {
    map.removeLayer(destinationMarker);
  }

  const destIcon = L.divIcon({
    className: 'user-marker',
    html: '<div style="background:var(--danger);width:100%;height:100%;border-radius:50%;border:2px solid #fff;box-shadow:0 0 10px rgba(255,107,107,0.5);"></div>',
    iconSize: [20, 20], iconAnchor: [10, 10]
  });

  destinationMarker = L.marker(destLatLng, { icon: destIcon }).addTo(map);

  const startLatLgn = lastPos || CONFIG.CENTER;
  const distKm = haversine(startLatLgn[0], startLatLgn[1], lat, lon);
  const estFare = calculateFare(distKm, true);

  const fixedFare = Math.ceil(estFare / 5) * 5;
  const fixedRow = (estFare % 5 !== 0) ? `
          <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(108,92,231,0.1);border-radius:6px;padding:4px 8px;margin-top:6px;border:1px solid rgba(108,92,231,0.2);">
            <span style="font-size:0.75rem;color:#555;font-weight:600;">Est. Fixed Fare</span>
            <strong style="font-size:1rem;color:#6c5ce7;">₱${fixedFare}</strong>
          </div>` : '';

  const popupContent = `
        <div style="text-align:center;font-family:'Inter',sans-serif;color:#333;">
          <div style="font-weight:800;font-size:1.1rem;color:#e55;">${name}</div>
          <div style="font-size:0.8rem;margin-top:4px;">Distance: <strong>${distKm.toFixed(2)} km</strong></div>
          <div style="font-size:1.1rem;font-weight:800;color:#00a885;margin-top:4px;">Est. Fare: ₱${estFare}</div>
          ${fixedRow}
        </div>
      `;
  destinationMarker.bindPopup(popupContent, { closeButton: false }).openPopup();

  const bounds = L.latLngBounds([startLatLgn, destLatLng]);
  map.fitBounds(bounds, { padding: [50, 50] });
}

function haversine(lat1, lon1, lat2, lon2) {
  const R = 6371;
  const toRad = v => v * Math.PI / 180;
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a = Math.sin(dLat / 2) ** 2 +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function calculateFare(distanceKm, includeNight) {
  let fare = CONFIG.BASE_FARE;
  if (distanceKm > CONFIG.BASE_KM) fare += Math.ceil((distanceKm - CONFIG.BASE_KM) * CONFIG.PER_KM_RATE);
  if (includeNight && DOM.nightToggle.checked) {
    fare += parseInt(DOM.nightAmount.value) || 0;
  }
  return fare;
}

function getFareBreakdown(distanceKm) {
  const base = CONFIG.BASE_FARE;
  const extra = distanceKm > CONFIG.BASE_KM ? Math.ceil((distanceKm - CONFIG.BASE_KM) * CONFIG.PER_KM_RATE) : 0;
  const night = DOM.nightToggle.checked ? (parseInt(DOM.nightAmount.value) || 0) : 0;
  return { base, extra, night, total: base + extra + night };
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
  L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 19
  }).addTo(map);
  L.control.attribution({ prefix: false, position: 'bottomright' })
    .addAttribution('© OpenStreetMap · CartoDB')
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
    DOM.liveFare.textContent = '₱' + calculateFare(totalDistance, true);
    lastRenderedDist = distStr;
  }
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
  const { base, extra, night, total } = getFareBreakdown(distanceKm);
  let html = `<span>₱${base} base</span>`;
  if (extra > 0) html += `<span class="fare-op">+</span><span>₱${extra} distance</span>`;
  if (night > 0) html += `<span class="fare-op">+</span><span>₱${night} night</span>`;
  html += `<span class="fare-op">=</span><span><strong>₱${total}</strong></span>`;
  DOM.fareBreakdown.innerHTML = html;
}

// ============================================================
// GPS POSITION HANDLER — With throttling + filtering
// ============================================================
function onPosition(pos) {
  if (!isTracking) return;

  const now = pos.timestamp || Date.now();
  const { latitude: lat, longitude: lng, accuracy } = pos.coords;

  // 1) THROTTLE — Skip if too soon since last processed update
  if (now - lastTimestamp < CONFIG.THROTTLE_MS) return;

  // 2) ACCURACY GATE — Reject poor readings entirely
  if (accuracy > CONFIG.MIN_ACCURACY_M) return;

  // 3) WEAK SIGNAL DETECTION — Warn but still process
  if (accuracy > CONFIG.WEAK_SIGNAL_M) {
    weakSignalCount++;
    if (weakSignalCount >= 3) showWeakSignal(true);
  } else {
    weakSignalCount = Math.max(0, weakSignalCount - 1);
    if (weakSignalCount === 0) showWeakSignal(false);
  }

  const latlng = [lat, lng];

  if (lastPos) {
    const distKm = haversine(lastPos[0], lastPos[1], lat, lng);
    const timeDeltaH = (now - lastTimestamp) / 3600000; // ms -> hours

    // 4) NOISE FILTER — Ignore tiny movements (GPS jitter)
    if (distKm < CONFIG.MIN_MOVEMENT_KM) {
      // Still update marker position for visual smoothness
      userMarker.setLatLng(latlng);
      return;
    }

    // 5) SPIKE PROTECTION — Check both distance AND speed
    if (distKm > CONFIG.MAX_JUMP_KM) return;

    const speedKmh = timeDeltaH > 0 ? distKm / timeDeltaH : 0;
    if (speedKmh > CONFIG.MAX_SPEED_KMH) return;

    totalDistance += distKm;
    currentSpeed = speedKmh;
    if (speedKmh > maxSpeed) maxSpeed = speedKmh;
  }

  // Update state
  lastPos = latlng;
  lastTimestamp = now;
  positions.push(latlng);

  // Update map
  userMarker.setLatLng(latlng);
  accuracyCircle.setLatLng(latlng);
  accuracyCircle.setRadius(accuracy);
  const markerEl = userMarker.getElement();
  if (markerEl) markerEl.classList.add('tracking');
  routeLine.setLatLngs(positions);
  map.panTo(latlng, { animate: true, duration: 0.5 });

  // Update UI
  updateLiveStats();
  setCardActive(true);
  setStatus(`Tracking · ${positions.length} pts · ${totalDistance.toFixed(2)} km`, 'tracking');
}

function onGPSError(err) {
  console.warn('GPS Error:', err.message);
  setStatus('GPS unavailable — use manual mode', 'error');
  DOM.gpsBanner.classList.add('show');
  DOM.manualSection.classList.add('show');
  if (isTracking) stopRide();
}

// ============================================================
// RIDE CONTROLS
// ============================================================
function startRide() {
  if (!navigator.geolocation) { onGPSError({ message: 'Not supported' }); return; }

  isTracking = true;
  totalDistance = 0; positions = []; lastPos = null;
  lastTimestamp = 0; currentSpeed = 0; maxSpeed = 0;
  weakSignalCount = 0; lastRenderedDist = -1; lastRenderedSpeed = -1;
  passengerCount = 1;
  rideStartTime = Date.now();
  routeLine.setLatLngs([]);
  accuracyCircle.setRadius(0);

  setButtonStates(false, true, false);
  DOM.resultsCard.classList.remove('show');
  showWeakSignal(false);
  DOM.liveSpeed.textContent = '0';
  DOM.liveTimer.textContent = '00:00';
  DOM.liveAvgSpeed.textContent = '0';
  setStatus('Acquiring GPS signal...', 'tracking');
  updateLiveStats();

  timerInterval = setInterval(tickTimer, 1000);

  watchId = navigator.geolocation.watchPosition(onPosition, onGPSError, {
    enableHighAccuracy: true, maximumAge: 0, timeout: 15000
  });
}

function stopRide() {
  isTracking = false; currentSpeed = 0;
  if (watchId !== null) { navigator.geolocation.clearWatch(watchId); watchId = null; }
  if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }

  const markerEl = userMarker.getElement();
  if (markerEl) markerEl.classList.remove('tracking');
  accuracyCircle.setRadius(0);

  setButtonStates(false, false, true);
  setCardActive(false);
  showWeakSignal(false);
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
  DOM.resultsCard.classList.add('show');

  if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
  saveRide(totalDistance, fare, elapsed);

  if (positions.length > 1) {
    map.fitBounds(routeLine.getBounds(), { padding: [40, 40] });
  }
}

function resetRide() {
  totalDistance = 0; positions = []; lastPos = null;
  lastTimestamp = 0; currentSpeed = 0; maxSpeed = 0;
  lastRenderedDist = -1; lastRenderedSpeed = -1;
  rideStartTime = 0; passengerCount = 1;
  routeLine.setLatLngs([]);
  accuracyCircle.setRadius(0);
  if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }

  setButtonStates(true, false, false);
  DOM.resultsCard.classList.remove('show');
  DOM.liveSpeed.textContent = '0';
  DOM.liveTimer.textContent = '00:00';
  DOM.liveAvgSpeed.textContent = '0';
  updateLiveStats();
  setStatus('Ready — tap Start Ride to begin', '');
  map.setView(CONFIG.CENTER, 14, { animate: true });
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
function saveRide(dist, fare, duration) {
  const history = JSON.parse(localStorage.getItem('trikefareHistory') || '[]');
  history.unshift({
    distance: dist.toFixed(2), fare,
    duration: Math.round(duration || 0),
    date: new Date().toLocaleString('en-PH', {
      month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    })
  });
  if (history.length > CONFIG.MAX_HISTORY) history.pop();
  localStorage.setItem('trikefareHistory', JSON.stringify(history));
  renderHistory();
  updateStatsDashboard();
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
  DOM.historyList.innerHTML = history.map(h =>
    `<div class="history-item">` +
    `<span class="h-date">${h.date}</span>` +
    `<span class="h-dist">${h.distance} km${h.duration ? ' · ' + formatTime(h.duration) : ''}</span>` +
    `<span class="h-fare">₱${h.fare}</span>` +
    `</div>`
  ).join('');
}

function toggleHistory() {
  DOM.historyList.classList.toggle('show');
  DOM.statsDashboard.classList.toggle('show');
}

function clearHistory() {
  if (!confirm('Clear all ride history?')) return;
  localStorage.removeItem('trikefareHistory');
  renderHistory();
  updateStatsDashboard();
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
      btn.textContent = '✅ Copied!';
      setTimeout(() => { btn.innerHTML = '📤 Share Ride'; }, 2000);
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
populateRoutes();
renderHistory();
updateStatsDashboard();

// Init night fare toggle based on current time
const currentHour = new Date().getHours();
DOM.nightToggle.checked = (currentHour >= CONFIG.NIGHT_START || currentHour < CONFIG.NIGHT_END);

// Sync UI with the default input values
updateBaseFare();
toggleNightFareInput();

navigator.geolocation?.getCurrentPosition(
  pos => {
    const latlng = [pos.coords.latitude, pos.coords.longitude];
    userMarker.setLatLng(latlng);
    map.setView(latlng, 15, { animate: true });
    setStatus('GPS ready — tap Start Ride', '');
  },
  () => onGPSError({ message: 'denied' }),
  { enableHighAccuracy: true, timeout: 8000 }
);
