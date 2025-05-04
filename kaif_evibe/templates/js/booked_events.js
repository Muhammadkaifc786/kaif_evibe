let allBookedEvents = [];
let currentCategory = 'all';

$(document).ready(function() {
    loadBookedEvents();

    // Filter button click
    $('#filterBtn').on('click', function() {
        filterAndRender();
    });

    // Search and date filter
    $('#searchBooked, #filterDate').on('input change', function() {
        filterAndRender();
    });

    // Category selection
    $(document).on('click', '.category-link', function(e) {
        e.preventDefault();
        currentCategory = $(this).data('category');
        $('.category-link').removeClass('active');
        $(this).addClass('active');
        $('#categoryDropdown').hide();
        loadBookedEvents();
    });
});

function loadBookedEvents() {
    $.ajax({
        url: '/kaif_evibe/templates/php/get_booked_events_by_category.php',
        method: 'GET',
        data: { category_id: currentCategory },
        success: function(response) {
            console.log('Booked events response:', response);
            if (response.success) {
                allBookedEvents = response.events;
                filterAndRender();
            } else {
                $('#bookedEventsContainer').html('<p class="error-message">No booked events found.</p>');
            }
        },
        error: function() {
            $('#bookedEventsContainer').html('<p class="error-message">Error loading events.</p>');
        }
    });
}

function filterAndRender() {
    let search = $('#searchBooked').val().toLowerCase();
    let date = $('#filterDate').val();
    
    let filtered = allBookedEvents.filter(ev => {
        let match = true;
        if (search && !(ev.title.toLowerCase().includes(search) || ev.venue.toLowerCase().includes(search))) {
            match = false;
        }
        if (date) {
            let eventDate = new Date(ev.event_date || ev.booking_date).toISOString().split('T')[0];
            if (eventDate !== date) match = false;
        }
        return match;
    });
    
    renderBookedEvents(filtered);
}

function renderBookedEvents(events) {
    const container = $('#bookedEventsContainer');
    container.empty();

    if (events.length === 0) {
        container.html('<p class="error-message">No booked events found.</p>');
        return;
    }

    events.forEach(event => {
        let imageUrl = event.image_url;
        if (imageUrl && !imageUrl.startsWith('/kaif_evibe/')) {
            if (!imageUrl.startsWith('/')) {
                imageUrl = '/kaif_evibe/templates/' + imageUrl;
            } else {
                imageUrl = '/kaif_evibe' + imageUrl;
            }
        }
        if (!imageUrl) imageUrl = '/kaif_evibe/templates/images/default-event.jpg';

        const eventDate = new Date(event.event_date || event.booking_date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        const card = $(`
            <div class="event-card">
                <img src="${imageUrl}" alt="${event.title}" class="event-image">
                <div class="event-details">
                    <h3 class="event-title">${event.title}</h3>
                    <span class="event-date">
                        <i class="far fa-calendar-alt"></i> ${eventDate}
                    </span>
                    
                    <div class="event-location">
                        <i class="fas fa-map-marker-alt"></i> ${event.venue}
                    </div>
                    <div class="event-price">Price: $${event.total_price}</div>
                    <a href="View_Event.html?id=${event.event_id}" class="event-button">
                        View 
                    </a>
                    <button class="cancel-btn" data-booking-id="${event.booking_id}">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        `);

        card.find('.cancel-btn').on('click', function() {
            if (confirm('Are you sure you want to cancel this booking?')) {
                cancelBooking(event.booking_id);
            }
        });

        container.append(card);
    });
}

function cancelBooking(bookingId) {
    console.log('Attempting to cancel booking:', bookingId);
    
    $.ajax({
        url: '/kaif_evibe/templates/php/cancel_booking.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ bookingId: bookingId }),
        success: function(response) {
            console.log('Cancel booking response:', response);
            if (response.success) {
                alert('Booking cancelled successfully');
                loadBookedEvents();
            } else {
                alert(response.message || 'Failed to cancel booking');
            }
        },
        error: function(xhr, status, error) {
            console.error('Cancel booking error:', {
                status: status,
                error: error,
                response: xhr.responseText
            });
            alert('Failed to cancel booking. Please try again later.');
        }
    });
} 