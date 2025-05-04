// DOM Elements
const bookingsList = document.getElementById('bookings-list');
const statusFilter = document.getElementById('status-filter');
const sortFilter = document.getElementById('sort-filter');
const prevPageBtn = document.querySelector('.prev-page');
const nextPageBtn = document.querySelector('.next-page');
const pageNumbers = document.querySelector('.page-numbers');

// State
let currentPage = 1;
let totalPages = 1;
let bookings = [];
let filteredBookings = [];

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    loadBookings();
    setupEventListeners();
});

// Event Listeners
function setupEventListeners() {
    statusFilter.addEventListener('change', () => {
        currentPage = 1;
        filterBookings();
    });

    sortFilter.addEventListener('change', () => {
        currentPage = 1;
        sortBookings();
    });

    prevPageBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            updateBookingsDisplay();
        }
    });

    nextPageBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            updateBookingsDisplay();
        }
    });
}

// Load Bookings
async function loadBookings() {
    try {
        const response = await fetch('/evibe_database-update_with_php/php/get_user_bookings.php');
        if (!response.ok) {
            throw new Error('Failed to fetch bookings');
        }
        const data = await response.json();
        bookings = data.bookings;
        filteredBookings = [...bookings];
        updateBookingsDisplay();
    } catch (error) {
        showError('Failed to load bookings. Please try again later.');
        console.error('Error loading bookings:', error);
    }
}

// Filter Bookings
function filterBookings() {
    const status = statusFilter.value;
    filteredBookings = bookings.filter(booking => {
        if (status === 'all') return true;
        if (status === 'upcoming') return booking.status === 'upcoming';
        if (status === 'past') return booking.status === 'past';
        if (status === 'cancelled') return booking.status === 'cancelled';
        return true;
    });
    sortBookings();
}

// Sort Bookings
function sortBookings() {
    const sortBy = sortFilter.value;
    filteredBookings.sort((a, b) => {
        switch (sortBy) {
            case 'date-asc':
                return new Date(a.booking_date) - new Date(b.booking_date);
            case 'date-desc':
                return new Date(b.booking_date) - new Date(a.booking_date);
            case 'price-asc':
                return a.total_amount - b.total_amount;
            case 'price-desc':
                return b.total_amount - a.total_amount;
            default:
                return 0;
        }
    });
    updateBookingsDisplay();
}

// Update Bookings Display
function updateBookingsDisplay() {
    const itemsPerPage = 10;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageBookings = filteredBookings.slice(startIndex, endIndex);
    totalPages = Math.ceil(filteredBookings.length / itemsPerPage);

    // Update bookings list
    bookingsList.innerHTML = pageBookings.map(booking => `
        <div class="booking-item">
            <img src="${booking.event_image}" alt="${booking.event_title}" class="booking-image">
            <div class="booking-details">
                <h3 class="booking-title">${booking.event_title}</h3>
                <div class="booking-info">
                    <span><i class="fas fa-calendar"></i> ${formatDate(booking.booking_date)}</span>
                    <span><i class="fas fa-map-marker-alt"></i> ${booking.event_location}</span>
                    <span><i class="fas fa-ticket-alt"></i> ${booking.ticket_count} tickets</span>
                    <span><i class="fas fa-dollar-sign"></i> ${formatPrice(booking.total_amount)}</span>
                </div>
                <span class="booking-status status-${booking.status.toLowerCase()}">
                    ${capitalizeFirst(booking.status)}
                </span>
            </div>
            <div class="booking-actions">
                <a href="booking_details.html?id=${booking.id}" class="btn btn-secondary">
                    <i class="fas fa-eye"></i> View Details
                </a>
                ${booking.status === 'upcoming' ? `
                    <button onclick="downloadTickets(${booking.id})" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download Tickets
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');

    // Update pagination
    updatePagination();
}

// Update Pagination
function updatePagination() {
    prevPageBtn.disabled = currentPage === 1;
    nextPageBtn.disabled = currentPage === totalPages;

    let paginationHTML = '';
    for (let i = 1; i <= totalPages; i++) {
        if (
            i === 1 ||
            i === totalPages ||
            (i >= currentPage - 2 && i <= currentPage + 2)
        ) {
            paginationHTML += `
                <span class="page-number ${i === currentPage ? 'active' : ''}" 
                      onclick="goToPage(${i})">
                    ${i}
                </span>
            `;
        } else if (
            i === currentPage - 3 ||
            i === currentPage + 3
        ) {
            paginationHTML += '<span class="page-number">...</span>';
        }
    }
    pageNumbers.innerHTML = paginationHTML;
}

// Go to Page
function goToPage(page) {
    currentPage = page;
    updateBookingsDisplay();
}

// Download Tickets
async function downloadTickets(bookingId) {
    try {
        const response = await fetch(`/evibe_database-update_with_php/php/generate_tickets.php?booking_id=${bookingId}`);
        if (!response.ok) {
            throw new Error('Failed to generate tickets');
        }
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `tickets_booking_${bookingId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        showSuccess('Tickets downloaded successfully!');
    } catch (error) {
        showError('Failed to download tickets. Please try again later.');
        console.error('Error downloading tickets:', error);
    }
}

// Utility Functions
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

function formatPrice(price) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}

function showError(message) {
    // Implement error notification
    alert(message);
}

function showSuccess(message) {
    // Implement success notification
    alert(message);
} 