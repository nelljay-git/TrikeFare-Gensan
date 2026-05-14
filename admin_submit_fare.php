<?php
session_start();

$admin_pass = 'admin123'; // Hardcoded password

if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_pass) {
        $_SESSION['admin_auth'] = true;
        if (!isset($_SESSION['app_token'])) {
            $_SESSION['app_token'] = bin2hex(random_bytes(32));
        }
    } else {
        $error = "Invalid password";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_submit_fare.php");
    exit;
}

if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - TrikeFare GenSan</title>
        <style>
            body {
                font-family: 'Inter', sans-serif;
                background: #f8fafc;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
            }

            .login-box {
                background: white;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
                width: 100%;
                max-width: 350px;
                text-align: center;
                border: 1px solid #e2e8f0;
            }

            .login-box h2 {
                margin-top: 0;
                color: #1e293b;
            }

            input[type="password"] {
                width: 100%;
                padding: 14px;
                margin: 15px 0;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                box-sizing: border-box;
                font-size: 1rem;
            }

            input[type="password"]:focus {
                outline: none;
                border-color: #00b894;
                box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.2);
            }

            button {
                background: #00b894;
                color: white;
                border: none;
                padding: 14px;
                border-radius: 8px;
                width: 100%;
                cursor: pointer;
                font-weight: bold;
                font-size: 1rem;
                transition: background 0.2s;
            }

            button:hover {
                background: #00a082;
            }

            .error {
                color: #ef4444;
                font-size: 0.9rem;
                margin-bottom: 10px;
            }

            .back-link {
                margin-top: 20px;
                display: block;
                font-size: 0.85rem;
                color: #64748b;
                text-decoration: none;
            }

            .back-link:hover {
                color: #00b894;
            }
        </style>
    </head>

    <body>
        <div class="login-box">
            <h2>Admin Portal</h2>
            <?php if (isset($error))
                echo "<div class='error'>$error</div>"; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter Admin Password" required autofocus>
                <button type="submit" name="login">Access Dashboard</button>
            </form>
            <a href="index.php" class="back-link">&larr; Return to Public App</a>
        </div>
    </body>

    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Fare - Admin Dashboard</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #00b894;
            --primary-dark: #008f73;
            --accent: #6c5ce7;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            background: var(--bg);
            color: var(--text);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        /* Navbar */
        .navbar {
            background: var(--card);
            border-bottom: 1px solid var(--border);
            padding: 15px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            z-index: 10;
        }

        .nav-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-title .badge {
            background: var(--accent);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        .nav-actions a {
            color: #ef4444;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 8px 16px;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-actions a:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* Main Layout */
        .main-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* Left Panel - Form */
        .form-panel {
            width: 450px;
            background: var(--card);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            padding: 24px;
            box-sizing: border-box;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--text);
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: inherit;
            box-sizing: border-box;
            background: #fff;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.1);
        }

        .form-control:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .row {
            display: flex;
            gap: 12px;
        }

        .col {
            flex: 1;
        }

        .btn {
            width: 100%;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: inherit;
            box-sizing: border-box;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
        }

        .btn-outline:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        .btn-secondary {
            background: var(--accent);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a4bd1;
        }

        .coords-box {
            font-family: monospace;
            font-size: 0.75rem;
            color: var(--text-muted);
            background: #f1f5f9;
            padding: 6px 10px;
            border-radius: 6px;
            margin-top: 4px;
            display: none;
        }

        /* Map Panel */
        .map-panel {
            flex: 1;
            position: relative;
        }

        #map {
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .map-overlay {
            position: absolute;
            top: 16px;
            left: 16px;
            z-index: 10;
            background: white;
            padding: 12px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border);
            pointer-events: auto;
        }

        .map-instructions {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text);
        }

        .map-instructions span {
            color: var(--primary);
        }

        /* Toast */
        #toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #333;
            color: #fff;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 9999;
            pointer-events: none;
        }

        #toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        #toast.success {
            background: var(--primary);
        }

        #toast.error {
            background: #ef4444;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .input-icon-wrapper input {
            padding-left: 36px;
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }

            .form-panel {
                width: 100%;
                max-height: 50vh;
                order: 2;
            }

            .map-panel {
                order: 1;
                flex: none;
                height: 50vh;
            }
        }
    </style>
</head>

<body>

    <div class="navbar">
        <div class="nav-title">
            <i class="fa-solid fa-user-shield"></i> TrikeFare GenSan
            <span class="badge">Admin Panel</span>
        </div>
        <div class="nav-actions">
            <a href="?logout=1"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>

    <div class="main-container">
        <!-- Form Panel -->
        <div class="form-panel">
            <div class="section-title">
                1. Route Setup
                <button class="btn btn-outline" style="width:auto; padding: 4px 8px; font-size: 0.7rem;"
                    onclick="useMyLocation()">
                    <i class="fa-solid fa-location-crosshairs"></i> Use GPS
                </button>
            </div>

            <div class="form-group" style="position: relative;">
                <label>Origin</label>
                <div class="input-icon-wrapper">
                    <i class="fa-regular fa-circle-dot" style="color: var(--primary);"></i>
                    <input type="text" id="originName" class="form-control" placeholder="Search origin..."
                        autocomplete="off" oninput="searchLocation(this.value, 'origin')">
                </div>
                <div id="originSearchResults"
                    style="display:none; position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #cbd5e1; border-radius:8px; max-height:200px; overflow-y:auto; z-index:100; box-shadow:0 4px 15px rgba(0,0,0,0.1); margin-top:4px;">
                </div>
                <div class="coords-box" id="originCoords">Lat: -, Lng: -</div>
            </div>

            <div class="form-group" style="position: relative;">
                <label>Destination</label>
                <div class="input-icon-wrapper">
                    <i class="fa-solid fa-location-dot" style="color: #ef4444;"></i>
                    <input type="text" id="destName" class="form-control" placeholder="Search destination..."
                        autocomplete="off" oninput="searchLocation(this.value, 'dest')">
                </div>
                <div id="destSearchResults"
                    style="display:none; position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #cbd5e1; border-radius:8px; max-height:200px; overflow-y:auto; z-index:100; box-shadow:0 4px 15px rgba(0,0,0,0.1); margin-top:4px;">
                </div>
                <div class="coords-box" id="destCoords">Lat: -, Lng: -</div>
            </div>

            <div class="form-group">
                <button class="btn btn-secondary" onclick="calculateRoute()">
                    <i class="fa-solid fa-route"></i> Calculate Route & Distance
                </button>
            </div>

            <div class="section-title" style="margin-top: 10px;">2. Fare Details</div>

            <div class="row">
                <div class="col form-group">
                    <label>Distance (km)</label>
                    <input type="number" id="distanceKm" class="form-control" step="0.01" placeholder="0.00" required>
                </div>
                <div class="col form-group">
                    <label>Fare (₱)</label>
                    <input type="number" id="fareAmount" class="form-control" step="0.5" placeholder="0.00" required>
                </div>
            </div>

            <div class="row">
                <div class="col form-group">
                    <label>Vehicle Type</label>
                    <select id="transportType" class="form-control">
                        <option value="Tricycle">Tricycle</option>
                        <option value="Motorized Pedicab">Motorized Pedicab</option>
                        <option value="Cab">Cab</option>
                        <option value="Jeep">Jeep</option>
                        <option value="Taxi">Taxi</option>
                        <option value="Grab/Private Car">Grab/Private Car</option>
                    </select>
                </div>
                <div class="col form-group">
                    <label>Time Tag</label>
                    <select id="timeTag" class="form-control">
                        <option value="auto">Auto (Current Time)</option>
                        <option value="day">Day Fare</option>
                        <option value="night">Night Fare</option>
                        <option value="rush_hour">Rush Hour</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <button class="btn btn-primary" id="btnSubmit" onclick="submitAdminFare()">
                    <i class="fa-solid fa-paper-plane"></i> Submit Fare Entry
                </button>
            </div>
            <div class="form-group">
                <button class="btn btn-outline" onclick="resetMapAndForm()">
                    <i class="fa-solid fa-trash-can"></i> Clear Form & Map
                </button>
            </div>
        </div>

        <!-- Map Panel -->
        <div class="map-panel">
            <div class="map-overlay">
                <div class="map-instructions" id="mapInstructions">
                    <i class="fa-solid fa-hand-pointer"></i> <span>Step 1:</span> Click on map to set <b>Origin</b>.
                </div>
            </div>
            <div id="map"></div>
        </div>
    </div>

    <div id="toast"></div>

    <!-- Scripts -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const APP_SESSION_TOKEN = '<?php echo isset($_SESSION['app_token']) ? $_SESSION['app_token'] : ''; ?>';

        // State
        let map;
        let originMarker = null;
        let destMarker = null;
        let routePolyline = null;

        let originData = { lat: null, lng: null };
        let destData = { lat: null, lng: null };

        // Constants
        const GENSAN_COORDS = [6.115, 125.172];

        // Initialize Map
        document.addEventListener('DOMContentLoaded', () => {
            map = L.map('map', { zoomControl: false }).setView(GENSAN_COORDS, 13);
            L.control.zoom({ position: 'bottomright' }).addTo(map);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            // Map Click Event
            map.on('click', async (e) => {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                if (!originMarker) {
                    setOrigin(lat, lng);
                    document.getElementById('mapInstructions').innerHTML = `<i class="fa-solid fa-hand-pointer"></i> <span>Step 2:</span> Click on map to set <b>Destination</b>.`;
                } else if (!destMarker) {
                    setDestination(lat, lng);
                    document.getElementById('mapInstructions').innerHTML = `<i class="fa-solid fa-check-circle" style="color:var(--primary);"></i> Locations set. You can now calculate the route.`;
                } else {
                    // Both set, reset and start over
                    resetMapAndForm();
                    setOrigin(lat, lng);
                    document.getElementById('mapInstructions').innerHTML = `<i class="fa-solid fa-hand-pointer"></i> <span>Step 2:</span> Click on map to set <b>Destination</b>.`;
                }
            });
        });

        // Set Origin
        async function setOrigin(lat, lng, prefilledName = null) {
            if (originMarker) map.removeLayer(originMarker);

            const customIcon = L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color: var(--primary); width: 14px; height: 14px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 8px rgba(0,0,0,0.4);"></div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });

            originMarker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
            originData = { lat, lng };

            document.getElementById('originCoords').textContent = `Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}`;
            document.getElementById('originCoords').style.display = 'block';

            if (prefilledName) {
                document.getElementById('originName').value = prefilledName;
            } else {
                document.getElementById('originName').value = 'Fetching location name...';
                try {
                    const name = await reverseGeocode(lat, lng);
                    document.getElementById('originName').value = name;
                } catch (e) {
                    document.getElementById('originName').value = 'Selected Origin';
                }
            }
        }

        // Set Destination
        async function setDestination(lat, lng, prefilledName = null) {
            if (destMarker) map.removeLayer(destMarker);

            const customIcon = L.divIcon({
                className: 'custom-div-icon',
                html: `<div style="background-color: #ef4444; width: 14px; height: 14px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 8px rgba(0,0,0,0.4);"></div>`,
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });

            destMarker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
            destData = { lat, lng };

            document.getElementById('destCoords').textContent = `Lat: ${lat.toFixed(5)}, Lng: ${lng.toFixed(5)}`;
            document.getElementById('destCoords').style.display = 'block';

            if (prefilledName) {
                document.getElementById('destName').value = prefilledName;
            } else {
                // Reverse Geocode
                document.getElementById('destName').value = 'Fetching location name...';
                try {
                    const name = await reverseGeocode(lat, lng);
                    document.getElementById('destName').value = name;
                } catch (e) {
                    document.getElementById('destName').value = 'Selected Destination';
                }
            }
        }

        // Reverse Geocode using Nominatim
        async function reverseGeocode(lat, lng) {
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
                if (!res.ok) throw new Error('Geocoding failed');
                const data = await res.json();
                return data.name || data.display_name.split(',')[0] || "Unknown Location";
            } catch (e) {
                console.error(e);
                throw e;
            }
        }

        // Use GPS
        function useMyLocation() {
            if (!navigator.geolocation) {
                showToast("Geolocation is not supported by your browser", "error");
                return;
            }
            showToast("Acquiring GPS...", "");
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    map.setView([lat, lng], 15);
                    setOrigin(lat, lng, "Your Location");
                    document.getElementById('mapInstructions').innerHTML = `<i class="fa-solid fa-hand-pointer"></i> <span>Step 2:</span> Click on map to set <b>Destination</b>.`;
                    showToast("Origin set to your location", "success");
                },
                (err) => {
                    showToast("Failed to acquire GPS", "error");
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }

        // Calculate Route
        async function calculateRoute() {
            if (!originData.lat || !destData.lat) {
                showToast("Please set both Origin and Destination on the map first.", "error");
                return;
            }

            if (routePolyline) {
                map.removeLayer(routePolyline);
            }

            const osrmUrl = `http://router.project-osrm.org/route/v1/driving/${originData.lng},${originData.lat};${destData.lng},${destData.lat}?overview=full&geometries=geojson`;

            try {
                const res = await fetch(osrmUrl);
                const data = await res.json();

                if (data.code !== 'Ok' || !data.routes.length) {
                    throw new Error('No route found');
                }

                const route = data.routes[0];
                const coords = route.geometry.coordinates.map(c => [c[1], c[0]]); // GeoJSON is [lng,lat], Leaflet needs [lat,lng]

                const distanceKm = (route.distance / 1000).toFixed(2);
                document.getElementById('distanceKm').value = distanceKm;

                // Draw route
                routePolyline = L.polyline(coords, { color: '#6c5ce7', weight: 5, opacity: 0.8 }).addTo(map);
                map.fitBounds(routePolyline.getBounds(), { padding: [30, 30] });

                showToast(`Route calculated: ${distanceKm} km`, "success");

                // Auto-suggest fare based on GenSan Ordinance No.8 (15 base + 2 per km after 3km)
                let base = 15;
                if (distanceKm > 3) {
                    base += (Math.ceil(distanceKm - 3) * 2);
                }
                if (document.getElementById('fareAmount').value === '') {
                    document.getElementById('fareAmount').value = base.toFixed(2);
                }

            } catch (err) {
                console.error(err);
                showToast("Failed to route using OSRM. Falling back to straight-line distance.", "error");

                // Fallback to Haversine
                const start = L.latLng(originData.lat, originData.lng);
                const end = L.latLng(destData.lat, destData.lng);
                const distanceKm = (start.distanceTo(end) / 1000).toFixed(2);
                document.getElementById('distanceKm').value = distanceKm;

                routePolyline = L.polyline([start, end], { color: '#94a3b8', weight: 4, dashArray: '5, 10' }).addTo(map);
                map.fitBounds(routePolyline.getBounds(), { padding: [30, 30] });
            }
        }

        // Submit Form
        async function submitAdminFare() {
            const originName = document.getElementById('originName').value.trim();
            const destName = document.getElementById('destName').value.trim();
            const distance = parseFloat(document.getElementById('distanceKm').value);
            const fare = parseFloat(document.getElementById('fareAmount').value);
            const transportType = document.getElementById('transportType').value;
            let timeTag = document.getElementById('timeTag').value;

            if (!originName || !destName) {
                showToast("Origin and Destination names are required.", "error"); return;
            }
            if (isNaN(fare) || fare <= 0) {
                showToast("Valid Fare amount is required.", "error"); return;
            }

            // Auto time tag if needed
            if (timeTag === 'auto') {
                const hour = new Date().getHours();
                timeTag = (hour >= 18 || hour < 5) ? 'night' : 'day';
            }

            const payload = {
                origin: originName,
                destination: destName,
                fare: fare,
                transport_type: transportType,
                time_tag: timeTag,
                distance_km: isNaN(distance) ? null : distance,
                origin_lat: originData.lat,
                origin_lng: originData.lng,
                dest_lat: destData.lat,
                dest_lng: destData.lng,
                username: 'Admin'
            };

            const btn = document.getElementById('btnSubmit');
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
            btn.disabled = true;

            try {
                const res = await fetch('api/submit_fare.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'x-session-token': APP_SESSION_TOKEN
                    },
                    body: JSON.stringify(payload)
                });

                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.error || 'Server error');
                }

                const result = await res.json();
                if (result.success) {
                    showToast("Fare successfully added to database!", "success");
                    // We DO NOT clear the map here so admin can add reverse route easily
                    document.getElementById('fareAmount').value = '';
                } else {
                    throw new Error(result.error || 'Failed to submit');
                }

            } catch (err) {
                console.error(err);
                showToast("Submission failed: " + err.message, "error");
            } finally {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        }

        // Reset
        function resetMapAndForm() {
            if (originMarker) map.removeLayer(originMarker);
            if (destMarker) map.removeLayer(destMarker);
            if (routePolyline) map.removeLayer(routePolyline);

            originMarker = null; destMarker = null; routePolyline = null;
            originData = { lat: null, lng: null }; destData = { lat: null, lng: null };

            document.getElementById('originName').value = '';
            document.getElementById('destName').value = '';
            document.getElementById('distanceKm').value = '';
            document.getElementById('fareAmount').value = '';
            document.getElementById('originCoords').style.display = 'none';
            document.getElementById('destCoords').style.display = 'none';

            document.getElementById('mapInstructions').innerHTML = `<i class="fa-solid fa-hand-pointer"></i> <span>Step 1:</span> Click on map to set <b>Origin</b>.`;
            map.setView(GENSAN_COORDS, 13);
        }

        // Toast functionality
        function showToast(msg, type) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = type === 'error' ? 'error show' : 'success show';
            setTimeout(() => { t.classList.remove('show'); }, 3000);
        }

        // Location Search functionality
        let searchTimeout = null;
        async function searchLocation(query, type) {
            const resultsBox = type === 'origin' ? document.getElementById('originSearchResults') : document.getElementById('destSearchResults');
            if (!query || query.length < 3) {
                resultsBox.style.display = 'none';
                return;
            }
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(async () => {
                resultsBox.innerHTML = '<div style="padding:10px; color:#64748b; font-size:0.85rem; text-align:center;">Searching...</div>';
                resultsBox.style.display = 'block';
                try {
                    // Dynamically fetch current map bounds and pad them significantly 
                    // to ensure large buildings/polygons with off-screen centroids are not excluded.
                    const bounds = map.getBounds().pad(1.0);
                    const viewbox = `${bounds.getWest()},${bounds.getNorth()},${bounds.getEast()},${bounds.getSouth()}`;

                    // Limit 15, soft-bias to viewbox (no bounded=1), restrict to PH
                    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=15&viewbox=${viewbox}&addressdetails=1&extratags=1`;
                    const res = await fetch(url);
                    const data = await res.json();
                    if (data.length === 0) {
                        resultsBox.innerHTML = '<div style="padding:10px; color:#ef4444; font-size:0.85rem; text-align:center;">No results found in GenSan</div>';
                    } else {
                        resultsBox.innerHTML = data.map((item) => {
                            const name = item.name || item.display_name.split(',')[0];
                            const address = item.display_name;
                            return `<div style="padding:10px; border-bottom:1px solid #e2e8f0; cursor:pointer;" onclick="selectSearchResult(${item.lat}, ${item.lon}, '${name.replace(/'/g, "\\'")}', '${type}')">
                                        <div style="font-weight:600; font-size:0.9rem; color:#1e293b;">${name}</div>
                                        <div style="font-size:0.75rem; color:#64748b;">${address}</div>
                                    </div>`;
                        }).join('');
                    }
                } catch (err) {
                    resultsBox.innerHTML = '<div style="padding:10px; color:#ef4444; font-size:0.85rem; text-align:center;">Search error</div>';
                }
            }, 500);
        }

        function selectSearchResult(lat, lon, name, type) {
            if (type === 'origin') {
                document.getElementById('originSearchResults').style.display = 'none';
                setOrigin(lat, lon, name);
                map.setView([lat, lon], 15);
            } else {
                document.getElementById('destSearchResults').style.display = 'none';
                setDestination(lat, lon, name);
                map.setView([lat, lon], 15);

                // Automatically get GPS for Origin if not set
                if (!originData.lat) {
                    useMyLocation();
                }
            }
        }

        // Hide search results if clicked outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#destName') && !e.target.closest('#destSearchResults')) {
                const destRes = document.getElementById('destSearchResults');
                if (destRes) destRes.style.display = 'none';
            }
            if (!e.target.closest('#originName') && !e.target.closest('#originSearchResults')) {
                const originRes = document.getElementById('originSearchResults');
                if (originRes) originRes.style.display = 'none';
            }
        });
    </script>
</body>

</html>