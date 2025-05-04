// DOM Elements
const chatbotToggle = document.getElementById('chatbot-toggle');
const chatbotWindow = document.getElementById('chatbot-window');
const closeChatbot = document.querySelector('.close-chatbot');
const chatbotMessages = document.getElementById('chatbot-messages');
const chatbotInput = document.querySelector('.chatbot-input input');
const chatbotSendButton = document.querySelector('.chatbot-input button');
const userName = document.getElementById('user-name');

// Chatbot Toggle
chatbotToggle.addEventListener('click', () => {
    chatbotWindow.classList.add('active');
});

closeChatbot.addEventListener('click', () => {
    chatbotWindow.classList.remove('active');
});

// Chatbot Functionality
function addMessage(message, isUser = false) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${isUser ? 'user-message' : 'bot-message'}`;
    messageDiv.innerHTML = `
        <div class="message-content">
            <p>${message}</p>
            <span class="message-time">${new Date().toLocaleTimeString()}</span>
        </div>
    `;
    chatbotMessages.appendChild(messageDiv);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

// Initial bot message
addMessage('Hello! I\'m your EVibe assistant. How can I help you today?');

// Handle user input
chatbotSendButton.addEventListener('click', sendMessage);
chatbotInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        sendMessage();
    }
});

function sendMessage() {
    const message = chatbotInput.value.trim();
    if (message) {
        addMessage(message, true);
        chatbotInput.value = '';
        
        // Simulate bot response
        setTimeout(() => {
            const response = getBotResponse(message);
            addMessage(response);
        }, 1000);
    }
}

function getBotResponse(message) {
    const lowerMessage = message.toLowerCase();
    
    if (lowerMessage.includes('hello') || lowerMessage.includes('hi')) {
        return 'Hello! How can I assist you today?';
    }
    else if (lowerMessage.includes('event') || lowerMessage.includes('booking')) {
        return 'I can help you find and book events. Would you like to see upcoming events or search for something specific?';
    }
    else if (lowerMessage.includes('ticket') || lowerMessage.includes('price')) {
        return 'Ticket prices vary by event. You can find specific pricing on each event\'s details page. Would you like me to show you some upcoming events?';
    }
    else if (lowerMessage.includes('help')) {
        return 'I can help you with:\n- Finding events\n- Booking tickets\n- Managing your bookings\n- Payment information\n- General inquiries\nWhat would you like to know more about?';
    }
    else {
        return 'I\'m not sure I understand. Could you please rephrase your question? Or type "help" to see what I can assist you with.';
    }
}

// Load Events
async function loadEvents() {
    try {
        // Load upcoming events
        const upcomingResponse = await fetch('../php/get_events.php?filter=upcoming&limit=4');
        const upcomingData = await upcomingResponse.json();
        if (upcomingData.success) {
            renderEvents('upcoming-events', upcomingData.events);
        }

        // Load trending events
        const trendingResponse = await fetch('../php/get_events.php?filter=trending&limit=4');
        const trendingData = await trendingResponse.json();
        if (trendingData.success) {
            renderEvents('trending-events', trendingData.events);
        }

        // Load nearby events
        const nearbyResponse = await fetch('../php/get_events.php?filter=nearby&limit=4');
        const nearbyData = await nearbyResponse.json();
        if (nearbyData.success) {
            renderEvents('nearby-events', nearbyData.events);
        }
    } catch (error) {
        console.error('Error loading events:', error);
    }
}

// Render Events
function renderEvents(containerId, events) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = events.map(event => `
        <div class="event-card">
            <img src="/evibe_database-update_with_php/templates/${event.image_url}" alt="${event.title}" class="event-image">
            <div class="event-details">
                <span class="event-date">
                    <i class="far fa-calendar-alt"></i> 
                    ${new Date(event.event_date).toLocaleDateString()}
                </span>
                <h3 class="event-title">${event.title}</h3>
                <div class="event-location">
                    <i class="fas fa-map-marker-alt"></i> 
                    ${event.venue}
                </div>
                <div class="event-price">PKR ${event.ticket_price}</div>
                <a href="event-details.html?id=${event.event_id}" class="event-button">
                    View Details
                </a>
            </div>
        </div>
    `).join('');
}

// Load Recent Bookings
async function loadRecentBookings() {
    try {
        const response = await fetch('../php/get_bookings.php?limit=4');
        const data = await response.json();
        if (data.success) {
            renderBookings('recent-bookings', data.bookings);
        }
    } catch (error) {
        console.error('Error loading bookings:', error);
    }
}

// Render Bookings
function renderBookings(containerId, bookings) {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = bookings.map(booking => `
        <div class="booking-card">
            <div class="booking-header">
                <span class="booking-status ${booking.status.toLowerCase()}">${booking.status}</span>
                <span class="booking-date">${new Date(booking.booking_date).toLocaleDateString()}</span>
            </div>
            <div class="booking-details">
                <h3>${booking.event_title}</h3>
                <p><i class="far fa-calendar-alt"></i> ${new Date(booking.event_date).toLocaleDateString()}</p>
                <p><i class="fas fa-ticket-alt"></i> ${booking.ticket_quantity} tickets</p>
                <p><i class="fas fa-money-bill-wave"></i> PKR ${booking.total_amount}</p>
            </div>
            <a href="booking-details.html?id=${booking.booking_id}" class="booking-button">
                View Details
            </a>
        </div>
    `).join('');
}

// Load User Stats
async function loadUserStats() {
    try {
        const response = await fetch('../php/get_user_stats.php');
        const data = await response.json();
        if (data.success) {
            updateStats(data.stats);
        }
    } catch (error) {
        console.error('Error loading user stats:', error);
    }
}

// Update Stats
function updateStats(stats) {
    const statElements = {
        'upcoming-events': stats.upcoming_events,
        'total-bookings': stats.total_bookings,
        'favorites': stats.favorites,
        'new-messages': stats.new_messages
    };

    Object.entries(statElements).forEach(([key, value]) => {
        const element = document.querySelector(`.stat-card[data-stat="${key}"] p`);
        if (element) {
            element.textContent = value;
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    // Load user data
    await loadUserData();
    
    // Load dashboard data
    await loadDashboardData();
});

// Load User Data
async function loadUserData() {
    try {
        const response = await fetch('/evibe_database-update_with_php/php/get_user_data.php');
        
        if (!response.ok) {
            throw new Error('Failed to fetch user data');
        }

        const data = await response.json();
        
        // Update user name
        userName.textContent = data.name;
    } catch (error) {
        console.error('Error loading user data:', error);
    }
}

// Load Dashboard Data
async function loadDashboardData() {
    try {
        const response = await fetch('/evibe_database-update_with_php/php/get_dashboard_data.php');
        
        if (!response.ok) {
            throw new Error('Failed to fetch dashboard data');
        }

        const data = await response.json();
        
        // Update quick stats
        updateQuickStats(data.stats);
        
        // Update trending events
        updateTrendingEvents(data.trending_events);
        
        // Update nearby events
        updateNearbyEvents(data.nearby_events);
        
        // Update upcoming bookings
        updateUpcomingBookings(data.upcoming_bookings);
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

// Update Quick Stats
function updateQuickStats(stats) {
    // Update upcoming events count
    document.querySelector('.stat-card:nth-child(1) .stat-value').textContent = stats.upcoming_events;
    
    // Update saved events count
    document.querySelector('.stat-card:nth-child(2) .stat-value').textContent = stats.saved_events;
    
    // Update past events count
    document.querySelector('.stat-card:nth-child(3) .stat-value').textContent = stats.past_events;
}

// Update Trending Events
function updateTrendingEvents(events) {
    const eventsGrid = document.querySelector('.trending-events .events-grid');
    
    eventsGrid.innerHTML = events.map(event => `
        <div class="event-card">
            <img src="/evibe_database-update_with_php/templates/${event.image_url}" alt="${event.title}">
            <div class="event-content">
                <div class="event-date">${formatDate(event.date)}</div>
                <h3>${event.title}</h3>
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

// Update Nearby Events
function updateNearbyEvents(events) {
    const eventsGrid = document.querySelector('.events-near-you .events-grid');
    
    eventsGrid.innerHTML = events.map(event => `
        <div class="event-card">
            <img src="/evibe_database-update_with_php/templates/${event.image_url}" alt="${event.title}">
            <div class="event-content">
                <div class="event-date">${formatDate(event.date)}</div>
                <h3>${event.title}</h3>
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

// Update Upcoming Bookings
function updateUpcomingBookings(bookings) {
    const bookingsList = document.querySelector('.upcoming-bookings .bookings-list');
    
    bookingsList.innerHTML = bookings.map(booking => `
        <div class="booking-item">
            <div class="booking-image">
                <img src="/evibe_database-update_with_php/templates/${booking.event.image_url}" alt="${booking.event.title}">
            </div>
            <div class="booking-info">
                <h3>${booking.event.title}</h3>
                <div class="booking-meta">
                    <span><i class="far fa-calendar-alt"></i> ${formatDate(booking.event.date)}</span>
                    <span><i class="fas fa-ticket-alt"></i> ${booking.ticket_count} Tickets</span>
                </div>
                <div class="booking-status ${booking.status.toLowerCase()}">${capitalizeFirst(booking.status)}</div>
            </div>
            <div class="booking-actions">
                <button class="view-booking" onclick="viewBooking(${booking.id})">
                    <i class="fas fa-eye"></i> View
                </button>
                <button class="download-tickets" onclick="downloadTickets(${booking.id})">
                    <i class="fas fa-download"></i> Tickets
                </button>
            </div>
        </div>
    `).join('');
}

// Event Handlers
function viewEventDetails(eventId) {
    window.location.href = `/evibe_database-update_with_php/templates/html/event_details.html?id=${eventId}`;
}

function bookEvent(eventId) {
    window.location.href = `/evibe_database-update_with_php/templates/html/event_booking.html?id=${eventId}`;
}

function viewBooking(bookingId) {
    window.location.href = `/evibe_database-update_with_php/templates/html/booking_confirmation.html?id=${bookingId}`;
}

async function downloadTickets(bookingId) {
    try {
        const response = await fetch(`/evibe_database-update_with_php/php/generate_tickets.php?id=${bookingId}`);
        
        if (!response.ok) {
            throw new Error('Failed to generate tickets');
        }
        
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `tickets_${bookingId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    } catch (error) {
        showError('Failed to download tickets');
        console.error('Error:', error);
    }
}

// Utility Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function showError(message) {
    // Create error element
    const errorElement = document.createElement('div');
    errorElement.className = 'error-message';
    errorElement.textContent = message;
    
    // Add to page
    document.body.appendChild(errorElement);
    
    // Remove after 3 seconds
    setTimeout(() => {
        errorElement.remove();
    }, 3000);
} 