<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /kaif_evibe/templates/html/login.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nearby Events - E-Vibe</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/kaif_evibe/templates/css/style.css">
    <style>
        .events-container {
            max-width: 1100px;
            margin: 60px auto 30px auto;
            padding: 32px 24px 32px 24px;
            background: rgba(255,255,255,0.97);
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        }
        .events-header {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 18px;
            color: #222;
        }
        .controls {
            display: flex;
            gap: 12px;
            margin-bottom: 22px;
            align-items: center;
        }
        .controls input[type="text"] {
            flex: 1;
            padding: 12px 16px;
            border: 1.5px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }
        .controls input[type="range"] {
            width: 140px;
        }
        .controls button {
            padding: 10px 22px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .controls button:hover {
            background: #0056b3;
        }
        #map {
            width: 100%;
            height: 340px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 32px;
        }
        .events-scroll-area {
            background: #f7f8fa;
            border-radius: 14px;
            padding: 24px 0 24px 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
            overflow-x: auto;
            overflow-y: visible;
            display: flex;
            flex-direction: row;
            gap: 28px;
            margin-bottom: 8px;
        }
        .event-card {
            min-width: 270px;
            max-width: 270px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.10);
            padding: 22px 18px 18px 18px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .event-card:hover {
            box-shadow: 0 8px 32px rgba(0,0,0,0.16);
            transform: translateY(-4px) scale(1.03);
        }
        .event-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #222;
        }
        .event-card p {
            color: #444;
            margin: 7px 0;
            font-size: 0.98rem;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .event-card i {
            color: #007bff;
            font-size: 1.05rem;
        }
        .event-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            width: 100%;
        }
        .event-actions button {
            flex: 1;
            padding: 9px 0;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .event-actions button:first-child {
            background: #007bff;
            color: #fff;
        }
        .event-actions button:last-child {
            background: #28a745;
            color: #fff;
        }
        .event-actions button:hover {
            opacity: 0.93;
        }
        @media (max-width: 900px) {
            .events-container {
                padding: 12px 2vw 24px 2vw;
            }
            .events-scroll-area {
                gap: 16px;
                padding: 18px 0 18px 6px;
            }
            .event-card {
                min-width: 220px;
                max-width: 220px;
                padding: 14px 8px 14px 12px;
            }
        }
        @media (max-width: 600px) {
            .events-header {
                font-size: 1.2rem;
            }
            #map {
                height: 200px;
            }
            .event-card {
                min-width: 170px;
                max-width: 170px;
                padding: 10px 4px 10px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="events-container">
        <div class="events-header">Nearby Events</div>
        <div class="controls">
            <input type="text" id="locationSearch" placeholder="Search location...">
            <input type="range" id="searchRadius" min="1" max="50" value="10">
            <span id="radiusValue">10 km</span>
            <button onclick="searchLocation()">Search</button>
        </div>
        <div id="map"></div>
        <div class="events-scroll-area" id="eventList"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let map;
        let markers = [];
        let userLocation;
        let searchRadius = 10;

        function initMap() {
            map = L.map('map').setView([34.012385, 71.578746], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Get user's location from database
            $.ajax({
                url: '/kaif_evibe/templates/php/get_user_location.php',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.latitude && response.longitude) {
                        userLocation = [parseFloat(response.latitude), parseFloat(response.longitude)];
                        map.setView(userLocation, 13);
                        L.marker(userLocation)
                            .addTo(map)
                            .bindPopup('Your Location')
                            .openPopup();
                        
                        loadNearbyEvents();
                    } else {
                        console.error('Error getting user location:', response.message);
                        showNotification('Unable to get your location. Please try again.', 'error');
                    }
                },
                error: function(error) {
                    console.error('Error getting location:', error);
                    showNotification('Error getting your location. Please try again.', 'error');
                }
            });
        }

        function createPopupContent(event) {
            return `
                <div class="event-popup">
                    <h3>${event.title}</h3>
                    <p><i class="fas fa-calendar"></i> ${event.formatted_date}</p>
                    <p><i class="fas fa-map-marker-alt"></i> ${event.venue}</p>
                    <p><i class="fas fa-tag"></i> ${event.price}</p>
                    <p><i class="fas fa-road"></i> ${event.distance} km away</p>
                    <div class="popup-actions">
                        <button onclick="viewEvent(${event.event_id})">View</button>
                        <button onclick="bookEvent(${event.event_id})">Book</button>
                    </div>
                </div>
            `;
        }

        function loadNearbyEvents() {
            if (!userLocation) {
                console.error('User location not set');
                return;
            }

            // Clear existing markers
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];

            // Clear existing event list
            const eventList = document.getElementById('eventList');
            eventList.innerHTML = '';

            // Get search radius
            searchRadius = document.getElementById('searchRadius').value;
            document.getElementById('radiusValue').textContent = `${searchRadius} km`;

            $.ajax({
                url: '/kaif_evibe/templates/php/get_nearby_events.php',
                method: 'POST',
                data: {
                    lat: userLocation[0],
                    lng: userLocation[1],
                    radius: searchRadius
                },
                success: function(response) {
                    if (response.success && response.data && response.data.events && response.data.events.length > 0) {
                        const bounds = L.latLngBounds();
                        
                        response.data.events.forEach(event => {
                            // Add marker to map
                            const marker = L.marker([event.latitude, event.longitude])
                                .addTo(map)
                                .bindPopup(createPopupContent(event));
                            markers.push(marker);
                            bounds.extend([event.latitude, event.longitude]);
                            
                            // Add event to list
                            const eventElement = document.createElement('div');
                            eventElement.className = 'event-card';
                            eventElement.innerHTML = `
                                <h3>${event.title}</h3>
                                <p><i class="fas fa-calendar"></i> ${event.formatted_date}</p>
                                <p><i class="fas fa-map-marker-alt"></i> ${event.venue}</p>
                                <p><i class="fas fa-tag"></i> ${event.price}</p>
                                <p><i class="fas fa-road"></i> ${event.distance} km away</p>
                                <div class="event-actions">
                                    <button onclick="viewEvent(${event.event_id})">View</button>
                                    <button onclick="bookEvent(${event.event_id})">Book</button>
                                </div>
                            `;
                            eventList.appendChild(eventElement);
                        });
                        
                        map.fitBounds(bounds, { padding: [50, 50] });
                        if (map.getZoom() > 13) {
                            map.setZoom(13);
                        }
                    } else {
                        eventList.innerHTML = '<p>No events found nearby. Try increasing the search radius.</p>';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading nearby events:', error);
                    eventList.innerHTML = '<p>Error loading nearby events. Please try again.</p>';
                }
            });
        }

        function searchLocation() {
            const searchInput = document.getElementById('locationSearch').value;
            if (!searchInput) return;

            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchInput)}`)
                .then(response => response.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const location = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                        map.setView(location, 13);
                        userLocation = location;
                        loadNearbyEvents();
                    } else {
                        alert('Location not found');
                    }
                })
                .catch(error => {
                    console.error('Error searching location:', error);
                    alert('Error searching location');
                });
        }

        function viewEvent(eventId) {
            window.location.href = `/kaif_evibe/templates/html/event_details.html?id=${eventId}`;
        }

        function bookEvent(eventId) {
            window.location.href = `/kaif_evibe/templates/html/booking.html?event_id=${eventId}`;
        }

        // Initialize map when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
            
            // Add event listener for radius change
            const radiusInput = document.getElementById('searchRadius');
            radiusInput.addEventListener('input', function() {
                document.getElementById('radiusValue').textContent = `${this.value} km`;
            });
            radiusInput.addEventListener('change', loadNearbyEvents);

            // Add event listener for location search
            const locationSearch = document.getElementById('locationSearch');
            locationSearch.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchLocation();
                }
            });
        });
    </script>
</body>
</html> 