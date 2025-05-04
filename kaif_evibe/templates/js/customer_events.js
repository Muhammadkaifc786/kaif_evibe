// DOM Elements
const eventsGrid = document.getElementById('events-grid');
const eventsList = document.getElementById('events-list');
const eventsMap = document.getElementById('events-map');
const viewGridBtn = document.querySelector('.view-grid');
const viewListBtn = document.querySelector('.view-list');
const viewMapBtn = document.querySelector('.view-map');
const categoryFilter = document.getElementById('category-filter');
const dateFilter = document.getElementById('date-filter');
const priceFilter = document.getElementById('price-filter');
const locationFilter = document.getElementById('location-filter');
const applyFiltersBtn = document.querySelector('.apply-filters');
const chatbotToggle = document.getElementById('chatbot-toggle');
const chatbotWindow = document.getElementById('chatbot-window');
const closeChatbotBtn = document.querySelector('.close-chatbot');
const chatbotMessages = document.getElementById('chatbot-messages');
const chatbotInput = document.querySelector('.chatbot-input input');
const chatbotSendBtn = document.querySelector('.chatbot-input button');

// State
let currentView = 'grid';
let currentPage = 1;
let filters = {
    category: '',
    date: '',
    price: '',
    location: ''
};

// View Switching
viewGridBtn.addEventListener('click', () => switchView('grid'));
viewListBtn.addEventListener('click', () => switchView('list'));
viewMapBtn.addEventListener('click', () => switchView('map'));

function switchView(view) {
    currentView = view;
    eventsGrid.style.display = view === 'grid' ? 'grid' : 'none';
    eventsList.style.display = view === 'list' ? 'flex' : 'none';
    eventsMap.style.display = view === 'map' ? 'block' : 'none';
    
    viewGridBtn.classList.toggle('active', view === 'grid');
    viewListBtn.classList.toggle('active', view === 'list');
    viewMapBtn.classList.toggle('active', view === 'map');
    
    loadEvents();
}

// Filter Handling
applyFiltersBtn.addEventListener('click', () => {
    filters = {
        category: categoryFilter.value,
        date: dateFilter.value,
        price: priceFilter.value,
        location: locationFilter.value
    };
    currentPage = 1;
    loadEvents();
});

// Event Loading
async function loadEvents() {
    try {
        const response = await fetch(`/evibe_database-update_with_php/php/get_events.php?${new URLSearchParams({
            page: currentPage,
            view: currentView,
            ...filters
        })}`);
        
        const data = await response.json();
        
        if (currentView === 'grid') {
            renderEventsGrid(data.events);
        } else if (currentView === 'list') {
            renderEventsList(data.events);
        } else {
            renderEventsMap(data.events);
        }
        
        updatePagination(data.totalPages);
    } catch (error) {
        console.error('Error loading events:', error);
        showError('Failed to load events. Please try again later.');
    }
}

function renderEventsGrid(events) {
    eventsGrid.innerHTML = events.map(event => `
        <div class="event-card">
            <img src="../uploads/${event.image.split('/').pop()}" alt="${event.title}" class="event-image">
            <div class="event-content">
                <div class="event-date">${formatDate(event.date)}</div>
                <h3 class="event-title">${event.title}</h3>
                <div class="event-location">
                    <i class="fas fa-map-marker-alt"></i>
                    ${event.location}
                </div>
                <div class="event-price">PKR ${event.price}</div>
                <div class="event-actions">
                    <button class="view-details" onclick="viewEventDetails(${event.id})">
                        <i class="fas fa-info-circle"></i> View Details
                    </button>
                    <button class="book-now" onclick="bookEvent(${event.id})">
                        <i class="fas fa-ticket-alt"></i> Book Now
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function renderEventsList(events) {
    eventsList.innerHTML = events.map(event => `
        <div class="event-list-item">
            <img src="../uploads/${event.image.split('/').pop()}" alt="${event.title}" class="event-list-image">
            <div class="event-list-content">
                <div class="event-date">${formatDate(event.date)}</div>
                <h3 class="event-title">${event.title}</h3>
                <div class="event-location">
                    <i class="fas fa-map-marker-alt"></i>
                    ${event.location}
                </div>
                <div class="event-price">PKR ${event.price}</div>
                <div class="event-actions">
                    <button class="view-details" onclick="viewEventDetails(${event.id})">
                        <i class="fas fa-info-circle"></i> View Details
                    </button>
                    <button class="book-now" onclick="bookEvent(${event.id})">
                        <i class="fas fa-ticket-alt"></i> Book Now
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function renderEventsMap(events) {
    // Initialize map if not already done
    if (!window.map) {
        window.map = new google.maps.Map(eventsMap, {
            center: { lat: 24.8607, lng: 67.0011 }, // Default to Karachi
            zoom: 12
        });
    }
    
    // Clear existing markers
    if (window.markers) {
        window.markers.forEach(marker => marker.setMap(null));
    }
    window.markers = [];
    
    // Add markers for each event
    events.forEach(event => {
        const marker = new google.maps.Marker({
            position: { lat: event.latitude, lng: event.longitude },
            map: window.map,
            title: event.title
        });
        
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div class="map-info-window">
                    <h3>${event.title}</h3>
                    <p>${event.location}</p>
                    <p>PKR ${event.price}</p>
                    <button onclick="viewEventDetails(${event.id})">View Details</button>
                </div>
            `
        });
        
        marker.addListener('click', () => {
            infoWindow.open(window.map, marker);
        });
        
        window.markers.push(marker);
    });
}

// Pagination
function updatePagination(totalPages) {
    const pagination = document.querySelector('.pagination');
    const pageNumbers = pagination.querySelector('.page-numbers');
    const prevBtn = pagination.querySelector('.prev-page');
    const nextBtn = pagination.querySelector('.next-page');
    
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages;
    
    let pagesHtml = '';
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            pagesHtml += `
                <button class="${i === currentPage ? 'active' : ''}" 
                        onclick="changePage(${i})">${i}</button>
            `;
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            pagesHtml += '<span>...</span>';
        }
    }
    
    pageNumbers.innerHTML = pagesHtml;
}

function changePage(page) {
    currentPage = page;
    loadEvents();
}

// Chatbot
chatbotToggle.addEventListener('click', () => {
    chatbotWindow.classList.add('active');
});

closeChatbotBtn.addEventListener('click', () => {
    chatbotWindow.classList.remove('active');
});

chatbotSendBtn.addEventListener('click', sendMessage);
chatbotInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

async function sendMessage() {
    const message = chatbotInput.value.trim();
    if (!message) return;
    
    // Add user message
    addMessage('user', message);
    chatbotInput.value = '';
    
    try {
        const response = await fetch('/evibe_database-update_with_php/php/chatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message })
        });
        
        const data = await response.json();
        addMessage('bot', data.response);
    } catch (error) {
        console.error('Error sending message:', error);
        addMessage('bot', 'Sorry, I encountered an error. Please try again later.');
    }
}

function addMessage(type, content) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `chatbot-message ${type}-message`;
    messageDiv.innerHTML = `
        <div class="message-content">
            <p>${content}</p>
        </div>
    `;
    
    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

// Utility Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function showError(message) {
    // Implement error notification
    console.error(message);
}

function viewEventDetails(eventId) {
    window.location.href = `/evibe_database-update_with_php/templates/html/event_details.html?id=${eventId}`;
}

async function bookEvent(eventId) {
    try {
        // First check if user is logged in
        const response = await fetch('/evibe_database-update_with_php/php/check_auth.php');
        const data = await response.json();
        
        if (!data.authenticated) {
            // Redirect to login page
            window.location.href = '/evibe_database-update_with_php/templates/html/login.html';
            return;
        }
        
        // Get event details
        const eventResponse = await fetch(`/evibe_database-update_with_php/php/get_event_details.php?id=${eventId}`);
        const eventData = await eventResponse.json();
        
        if (eventData.success) {
            // Store event details in session storage
            sessionStorage.setItem('bookingEvent', JSON.stringify(eventData.event));
            
            // Redirect to booking page
            window.location.href = `/evibe_database-update_with_php/templates/html/event_booking.html?id=${eventId}`;
        } else {
            showError('Failed to load event details. Please try again.');
        }
    } catch (error) {
        console.error('Error booking event:', error);
        showError('An error occurred. Please try again later.');
    }
}

// Initial Load
document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
}); 