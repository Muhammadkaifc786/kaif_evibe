// Map functionality
let map;
let markers = [];
let userLocation;
let searchRadius = 10; // Default radius in kilometers

function initMap() {
    // Initialize the map
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
                
                // Load nearby events
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

function toggleMapView() {
    const mapContainer = document.getElementById('mapContainer');
    if (!mapContainer) {
        // Create map container if it doesn't exist
        const container = document.createElement('div');
        container.id = 'mapContainer';
        container.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 1000px;
            height: 80vh;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 20px;
            display: flex;
            flex-direction: column;
        `;
        document.body.appendChild(container);
        
        // Add map div
        const mapDiv = document.createElement('div');
        mapDiv.id = 'map';
        mapDiv.style.cssText = `
            width: 100%;
            height: 50%;
            border-radius: 4px;
            margin-bottom: 10px;
        `;
        container.appendChild(mapDiv);
        
        // Add controls
        const controls = document.createElement('div');
        controls.className = 'map-controls';
        controls.innerHTML = `
            <input type="text" id="locationSearch" placeholder="Search location...">
            <input type="range" id="searchRadius" min="1" max="50" value="10">
            <span id="radiusValue">10 km</span>
            <button onclick="searchLocation()">Search</button>
            <button class="close-map" onclick="toggleMapView()">&times;</button>
        `;
        container.appendChild(controls);
        
        // Add event list container
        const eventList = document.createElement('div');
        eventList.id = 'eventList';
        eventList.className = 'event-list';
        eventList.style.cssText = `
            width: 100%;
            height: 40%;
            overflow-y: auto;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-top: 10px;
        `;
        container.appendChild(eventList);
        
        // Initialize map
        initMap();
    } else {
        if (mapContainer.style.display === 'none') {
            mapContainer.style.display = 'flex';
            if (!map) {
                initMap();
            } else {
                // Refresh the map view
                map.invalidateSize();
                loadNearbyEvents();
            }
        } else {
            mapContainer.style.display = 'none';
        }
    }
}

function searchLocation() {
    const searchInput = document.getElementById('locationSearch').value;
    if (!searchInput) return;

    // Use Nominatim for geocoding
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchInput)}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                const location = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                map.setView(location, 13);
                userLocation = location;
                loadNearbyEvents();
            } else {
                showNotification('Location not found', 'error');
            }
        })
        .catch(error => {
            console.error('Error searching location:', error);
            showNotification('Error searching location', 'error');
        });
}

function updateToPeshawarLocation() {
    console.log('Updating location to Peshawar...');
    $.ajax({
        url: '/kaif_evibe/templates/php/update_user_location.php',
        method: 'POST',
        success: function(response) {
            console.log('Update location response:', response);
            if (response.success) {
                // Update the userLocation variable
                userLocation = [response.latitude, response.longitude];
                console.log('Updated user location:', userLocation);
                
                // Update the map view
                map.setView(userLocation, 13);
                
                // Add marker for user location
                L.marker(userLocation)
                    .addTo(map)
                    .bindPopup('Your Location (Peshawar)')
                    .openPopup();
                
                // Load nearby events
                loadNearbyEvents();
                showNotification('Location updated to Peshawar. Loading nearby events...', 'success');
            } else {
                console.error('Failed to update location:', response.message);
                showNotification('Failed to update location: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error updating location:', error);
            console.error('Response:', xhr.responseText);
            showNotification('Error updating location', 'error');
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
                <button class="view-btn" onclick="viewEvent(${event.event_id})">View</button>
                <button class="book-btn" onclick="bookEvent(${event.event_id})">Book</button>
            </div>
        </div>
    `;
}

function loadNearbyEvents() {
    if (!userLocation) {
        console.error('User location not set');
        return;
    }

    console.log('Loading nearby events with location:', userLocation);
    console.log('Search radius:', searchRadius);

    // Clear existing markers
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];

    // Clear existing event list
    const eventList = document.getElementById('eventList');
    if (eventList) {
        eventList.innerHTML = '';
    }

    // Get search radius
    searchRadius = document.getElementById('searchRadius').value;
    document.getElementById('radiusValue').textContent = `${searchRadius} km`;

    // Check if we're in Peshawar area first
    const peshawarLat = 34.012385;
    const peshawarLng = 71.578746;
    const distanceToPeshawar = calculateDistance(
        userLocation[0], 
        userLocation[1], 
        peshawarLat, 
        peshawarLng
    );
    
    console.log('Distance to Peshawar:', distanceToPeshawar, 'km');
    
    if (distanceToPeshawar > 50) {
        console.log('User is far from Peshawar, showing confirmation dialog');
        if (confirm('You are currently too far from Peshawar. Would you like to update your location to Peshawar to see the events?')) {
            console.log('User confirmed location update');
            updateToPeshawarLocation();
            return;
        } else {
            console.log('User declined location update');
            showNotification('You are currently too far from Peshawar. Most events are in the Peshawar area.', 'info');
            return;
        }
    }

    // If we're in Peshawar area or user declined update, proceed with normal event loading
    $.ajax({
        url: '/kaif_evibe/templates/php/get_nearby_events.php',
        method: 'POST',
        data: {
            lat: userLocation[0],
            lng: userLocation[1],
            radius: searchRadius
        },
        success: function(response) {
            console.log('Nearby events response:', response);
            if (response.success && response.data && response.data.events && response.data.events.length > 0) {
                // Navigate to nearby_events_display.php
                window.location.href = '/kaif_evibe/templates/php/nearby_events_display.php';
            } else {
                console.log('No events found in response');
                showNotification('No events found nearby. Try increasing the search radius.', 'info');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading nearby events:', error);
            console.error('Response:', xhr.responseText);
            showNotification('Error loading nearby events', 'error');
        }
    });
}

// Add Haversine formula function to calculate distance
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Radius of the earth in km
    const dLat = deg2rad(lat2 - lat1);
    const dLon = deg2rad(lon2 - lon1);
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
        Math.sin(dLon/2) * Math.sin(dLon/2); 
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
    const distance = R * c; // Distance in km
    return distance;
}

function deg2rad(deg) {
    return deg * (Math.PI/180);
}

// Add event listeners when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for radius change
    const radiusInput = document.getElementById('searchRadius');
    if (radiusInput) {
        radiusInput.addEventListener('input', function() {
            document.getElementById('radiusValue').textContent = `${this.value} km`;
        });
        radiusInput.addEventListener('change', loadNearbyEvents);
    }

    // Add event listener for location search
    const locationSearch = document.getElementById('locationSearch');
    if (locationSearch) {
        locationSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchLocation();
            }
        });
    }
}); 