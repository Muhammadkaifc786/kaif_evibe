// Function to check admin session
async function checkAdminSession() {
    try {
        const response = await fetch('/kaif_evibe/templates/php/check_session.php');
        const data = await response.json();
        
        if (!data.logged_in || data.role !== 'admin') {
            // Store the current URL to redirect back after login
            sessionStorage.setItem('redirectAfterLogin', window.location.href);
            window.location.href = '/kaif_evibe/templates/html/login.html';
            return false;
        }
        return true;
    } catch (error) {
        console.error('Error checking session:', error);
        window.location.href = '/kaif_evibe/templates/html/login.html';
        return false;
    }
}

// Function to handle logout
async function handleLogout() {
    try {
        const response = await fetch('/kaif_evibe/templates/php/logout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        if (data.success) {
            window.location.href = '/kaif_evibe/templates/html/login.html';
        } else {
            console.error('Logout failed:', data.message);
        }
    } catch (error) {
        console.error('Error during logout:', error);
    }
}

// Add event listener for logout button
document.addEventListener('DOMContentLoaded', function() {
    // Check session on page load
    checkAdminSession();
    
    // Add logout button event listener if it exists
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            handleLogout();
        });
    }
    
    // Check session periodically (every 5 minutes)
    setInterval(checkAdminSession, 300000);
}); 