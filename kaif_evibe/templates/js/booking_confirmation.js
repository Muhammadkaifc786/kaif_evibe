// DOM Elements
const bookingId = document.getElementById('booking-id');
const bookingDate = document.getElementById('booking-date');
const bookingStatus = document.getElementById('booking-status');
const paymentMethod = document.getElementById('payment-method');
const eventImage = document.getElementById('event-image');
const eventTitle = document.getElementById('event-title');
const eventDate = document.getElementById('event-date');
const eventLocation = document.getElementById('event-location');
const eventTime = document.getElementById('event-time');
const ticketsList = document.getElementById('tickets-list');
const subtotal = document.getElementById('subtotal');
const serviceFee = document.getElementById('service-fee');
const total = document.getElementById('total');

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    // Get booking ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const bookingId = urlParams.get('id');

    if (!bookingId) {
        showError('Booking ID not found');
        return;
    }

    // Load booking data
    await loadBookingData(bookingId);
});

// Load Booking Data
async function loadBookingData(bookingId) {
    try {
        const response = await fetch(`/evibe_database-update_with_php/php/get_booking_details.php?id=${bookingId}`);
        
        if (!response.ok) {
            throw new Error('Failed to fetch booking data');
        }

        const data = await response.json();
        
        // Update UI with booking data
        updateBookingUI(data);
        
        // Update UI with event data
        updateEventUI(data.event);
        
        // Update UI with ticket data
        updateTicketUI(data.tickets);
        
        // Calculate totals
        calculateTotals(data.tickets);
    } catch (error) {
        showError('Failed to load booking data');
        console.error('Error:', error);
    }
}

// Update Booking UI
function updateBookingUI(data) {
    bookingId.textContent = `#${data.id}`;
    bookingDate.textContent = formatDate(data.created_at);
    bookingStatus.textContent = capitalizeFirst(data.status);
    paymentMethod.textContent = capitalizeFirst(data.payment_method.replace('-', ' '));
}

// Update Event UI
function updateEventUI(event) {
    eventImage.src = event.image_url;
    eventTitle.textContent = event.title;
    eventDate.textContent = formatDate(event.date);
    eventLocation.textContent = event.location;
    eventTime.textContent = formatTime(event.time);
}

// Update Ticket UI
function updateTicketUI(tickets) {
    ticketsList.innerHTML = tickets.map(ticket => `
        <div class="ticket-item">
            <div class="ticket-info">
                <div class="ticket-name">${ticket.name}</div>
                <div class="ticket-price">PKR ${ticket.price}</div>
            </div>
            <div class="ticket-quantity">
                ${ticket.quantity} ${ticket.quantity === 1 ? 'ticket' : 'tickets'}
            </div>
        </div>
    `).join('');
}

// Calculate Totals
function calculateTotals(tickets) {
    let subtotalAmount = 0;
    
    // Calculate subtotal
    tickets.forEach(ticket => {
        subtotalAmount += ticket.price * ticket.quantity;
    });
    
    // Calculate service fee (5% of subtotal)
    const serviceFeeAmount = subtotalAmount * 0.05;
    
    // Calculate total
    const totalAmount = subtotalAmount + serviceFeeAmount;
    
    // Update UI
    subtotal.textContent = `PKR ${subtotalAmount.toFixed(2)}`;
    serviceFee.textContent = `PKR ${serviceFeeAmount.toFixed(2)}`;
    total.textContent = `PKR ${totalAmount.toFixed(2)}`;
}

// Download Tickets
async function downloadTickets() {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const bookingId = urlParams.get('id');
        
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

// Share Booking
function shareBooking() {
    const urlParams = new URLSearchParams(window.location.search);
    const bookingId = urlParams.get('id');
    const shareUrl = `${window.location.origin}/evibe_database-update_with_php/templates/html/booking_confirmation.html?id=${bookingId}`;
    
    if (navigator.share) {
        navigator.share({
            title: 'My Event Booking',
            text: 'Check out my event booking on EVibe!',
            url: shareUrl
        }).catch(error => {
            console.error('Error sharing:', error);
            copyToClipboard(shareUrl);
        });
    } else {
        copyToClipboard(shareUrl);
    }
}

// Copy to Clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showSuccess('Booking link copied to clipboard!');
    }).catch(error => {
        console.error('Error copying to clipboard:', error);
        showError('Failed to copy booking link');
    });
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

function formatTime(timeString) {
    return new Date(`1970-01-01T${timeString}`).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
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

function showSuccess(message) {
    // Create success element
    const successElement = document.createElement('div');
    successElement.className = 'success-message';
    successElement.textContent = message;
    
    // Add to page
    document.body.appendChild(successElement);
    
    // Remove after 3 seconds
    setTimeout(() => {
        successElement.remove();
    }, 3000);
} 