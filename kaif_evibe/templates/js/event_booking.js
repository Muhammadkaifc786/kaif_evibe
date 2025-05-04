// DOM Elements
const eventImage = document.getElementById('event-image');
const eventTitle = document.getElementById('event-title');
const eventDate = document.getElementById('event-date');
const eventLocation = document.getElementById('event-location');
const eventTime = document.getElementById('event-time');
const eventDescription = document.getElementById('event-description');
const ticketTypes = document.getElementById('ticket-types');
const subtotal = document.getElementById('subtotal');
const serviceFee = document.getElementById('service-fee');
const total = document.getElementById('total');
const bookingForm = document.getElementById('booking-form');
const creditCardDetails = document.getElementById('credit-card-details');
const bankTransferDetails = document.getElementById('bank-transfer-details');

// State
let eventData = null;
let selectedTickets = new Map();

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    // Get event ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const eventId = urlParams.get('id');

    if (!eventId) {
        showError('Event ID not found');
        return;
    }

    // Load event data
    await loadEventData(eventId);
    
    // Setup event listeners
    setupEventListeners();
});

// Load Event Data
async function loadEventData(eventId) {
    try {
        const response = await fetch(`/evibe_database-update_with_php/php/get_event_details.php?id=${eventId}`);
        
        if (!response.ok) {
            throw new Error('Failed to fetch event data');
        }

        eventData = await response.json();
        
        // Update UI with event data
        updateEventUI();
        
        // Load ticket types
        loadTicketTypes();
        
        // Calculate initial totals
        calculateTotals();
    } catch (error) {
        showError('Failed to load event data');
        console.error('Error:', error);
    }
}

// Update Event UI
function updateEventUI() {
    eventImage.src = eventData.image_url;
    eventTitle.textContent = eventData.title;
    eventDate.textContent = formatDate(eventData.date);
    eventLocation.textContent = eventData.location;
    eventTime.textContent = formatTime(eventData.time);
    eventDescription.textContent = eventData.description;
}

// Load Ticket Types
function loadTicketTypes() {
    ticketTypes.innerHTML = eventData.ticket_types.map(ticket => `
        <div class="ticket-type" data-id="${ticket.id}">
            <div class="ticket-info">
                <div class="ticket-name">${ticket.name}</div>
                <div class="ticket-price">PKR ${ticket.price}</div>
            </div>
            <div class="ticket-quantity">
                <button class="quantity-btn minus" onclick="updateQuantity(${ticket.id}, -1)">-</button>
                <span class="quantity">0</span>
                <button class="quantity-btn plus" onclick="updateQuantity(${ticket.id}, 1)">+</button>
            </div>
        </div>
    `).join('');
}

// Update Quantity
function updateQuantity(ticketId, change) {
    const ticketType = document.querySelector(`.ticket-type[data-id="${ticketId}"]`);
    const quantityElement = ticketType.querySelector('.quantity');
    let currentQuantity = parseInt(quantityElement.textContent) || 0;
    
    // Get ticket data
    const ticket = eventData.ticket_types.find(t => t.id === ticketId);
    
    // Update quantity
    if (change > 0) {
        if (currentQuantity < ticket.available) {
            currentQuantity++;
        } else {
            showError('Maximum available tickets reached');
            return;
        }
    } else {
        if (currentQuantity > 0) {
            currentQuantity--;
        }
    }
    
    // Update UI
    quantityElement.textContent = currentQuantity;
    
    // Update selected tickets
    if (currentQuantity > 0) {
        selectedTickets.set(ticketId, currentQuantity);
    } else {
        selectedTickets.delete(ticketId);
    }
    
    // Calculate totals
    calculateTotals();
}

// Calculate Totals
function calculateTotals() {
    let subtotalAmount = 0;
    
    // Calculate subtotal
    selectedTickets.forEach((quantity, ticketId) => {
        const ticket = eventData.ticket_types.find(t => t.id === ticketId);
        subtotalAmount += ticket.price * quantity;
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

// Setup Event Listeners
function setupEventListeners() {
    // Payment method change
    document.querySelectorAll('input[name="payment"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (e.target.value === 'bank-transfer') {
                creditCardDetails.style.display = 'none';
                bankTransferDetails.style.display = 'block';
            } else {
                creditCardDetails.style.display = 'block';
                bankTransferDetails.style.display = 'none';
            }
        });
    });
    
    // Form submission
    bookingForm.addEventListener('submit', handleBooking);
}

// Handle Booking
async function handleBooking(e) {
    e.preventDefault();
    
    // Validate form
    if (!validateForm()) {
        return;
    }
    
    // Prepare booking data
    const bookingData = {
        event_id: eventData.id,
        tickets: Array.from(selectedTickets.entries()).map(([ticketId, quantity]) => ({
            ticket_id: ticketId,
            quantity: quantity
        })),
        personal_info: {
            full_name: document.getElementById('full-name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value
        },
        payment_method: document.querySelector('input[name="payment"]:checked').value,
        payment_details: getPaymentDetails(),
        total_amount: parseFloat(total.textContent.replace('PKR ', ''))
    };
    
    try {
        const response = await fetch('/evibe_database-update_with_php/php/create_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingData)
        });
        
        if (!response.ok) {
            throw new Error('Failed to create booking');
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Redirect to booking confirmation page
            window.location.href = `/evibe_database-update_with_php/templates/html/booking_confirmation.html?id=${result.booking_id}`;
        } else {
            showError(result.message || 'Failed to create booking');
        }
    } catch (error) {
        showError('Failed to process booking');
        console.error('Error:', error);
    }
}

// Validate Form
function validateForm() {
    // Check if tickets are selected
    if (selectedTickets.size === 0) {
        showError('Please select at least one ticket');
        return false;
    }
    
    // Validate personal information
    const fullName = document.getElementById('full-name').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    
    if (!fullName || !email || !phone) {
        showError('Please fill in all personal information fields');
        return false;
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showError('Please enter a valid email address');
        return false;
    }
    
    // Validate payment details based on selected method
    const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
    if (paymentMethod === 'credit-card' || paymentMethod === 'debit-card') {
        const cardNumber = document.getElementById('card-number').value.trim();
        const expiry = document.getElementById('expiry').value.trim();
        const cvv = document.getElementById('cvv').value.trim();
        
        if (!cardNumber || !expiry || !cvv) {
            showError('Please fill in all card details');
            return false;
        }
        
        // Basic card validation
        if (!/^\d{16}$/.test(cardNumber.replace(/\s/g, ''))) {
            showError('Please enter a valid card number');
            return false;
        }
        
        if (!/^\d{2}\/\d{2}$/.test(expiry)) {
            showError('Please enter a valid expiry date (MM/YY)');
            return false;
        }
        
        if (!/^\d{3,4}$/.test(cvv)) {
            showError('Please enter a valid CVV');
            return false;
        }
    }
    
    return true;
}

// Get Payment Details
function getPaymentDetails() {
    const paymentMethod = document.querySelector('input[name="payment"]:checked').value;
    
    if (paymentMethod === 'credit-card' || paymentMethod === 'debit-card') {
        return {
            card_number: document.getElementById('card-number').value.trim(),
            expiry: document.getElementById('expiry').value.trim(),
            cvv: document.getElementById('cvv').value.trim()
        };
    } else {
        return {
            bank_name: 'HBL Bank',
            account_title: 'EVibe Events',
            account_number: '1234-5678-9012-3456',
            iban: 'PK36HABB0000123456789012'
        };
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

function formatTime(timeString) {
    return new Date(`1970-01-01T${timeString}`).toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    });
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