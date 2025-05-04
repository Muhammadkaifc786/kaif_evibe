<?php
// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php/admin_users_errors.log');

require_once __DIR__ . '/../../php/session_config.php';

// Log access attempt
error_log("Access attempt to admin_users.php");

// Check if user is logged in as admin
if (!isAdmin()) {
    error_log("User is not admin. Redirecting to login page.");
    header("Location: /kaif_evibe/templates/html/login.html");
    exit();
}

// Regenerate session ID for security
regenerateSession();

error_log("User is admin. Proceeding to display page.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="/kaif_evibe/templates/css/admin_user.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: url("/kaif_evibe/templates/images/bg.jpg") no-repeat center center/cover;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        .Nav-1 {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(13px);
            -webkit-backdrop-filter: blur(13px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 32px 0 rgba(0, 0, 0, 0.37);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }

        .Nav-1 h1 {
            font-size: 2rem;
            color: #fff;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
            font-weight: 600;
            letter-spacing: 2px;
            white-space: nowrap;
        }

        .Nav-Element {
            flex: 0 0 auto;
        }

        .Nav-Element ul {
            display: flex;
            list-style: none;
            gap: 1.5rem;
            margin: 0;
            padding: 0;
        }

        .Nav-Element a {
            color: #fff;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .Nav-Element a:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .Nav-Element a i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .Nav-Element a:hover i {
            transform: scale(1.1);
            color: #FF4B2B;
        }

        .logout-btn {
            background: rgba(255, 75, 43, 0.2);
            border: 1px solid rgba(255, 75, 43, 0.3);
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            color: #fff;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255, 75, 43, 0.3);
        }
    </style>
</head>
<body>
    <!-- Glassmorphic Navigation -->
    <div class="Nav-1">
        <h1>EVibe</h1>
        
        <div class="Nav-Element">
            <ul>
                <a href="/kaif_evibe/templates/html/admin_panel.html"><i class="fas fa-home"></i> Dashboard</a>
                <a href="/kaif_evibe/templates/html/admin_register.html"><i class="fas fa-user-plus"></i> Register</a>
                <a href="/kaif_evibe/templates/html/admin_add_category.html"><i class="fas fa-folder-plus"></i> Add Category</a>
                <a href="/kaif_evibe/templates/html/admin_categories.html"><i class="fas fa-folder"></i> Categories</a>
                <a href="/kaif_evibe/templates/html/admin_events.html"><i class="fas fa-calendar"></i> Events</a>
                <a href="/kaif_evibe/templates/html/admin_users.php"><i class="fas fa-users"></i> Users</a>
                <button id="logoutBtn" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </ul>
        </div>
    </div>

    <main class="main-content">
        <div class="content-wrapper">
            <div class="users-container">
                <div class="users-header">
                    <h2><i class="fas fa-users"></i> User Management</h2>
                    <div class="filters">
                        <button class="filter-btn active" data-filter="all">All Users</button>
                        <button class="filter-btn" data-filter="admin">Admins</button>
                        <button class="filter-btn" data-filter="organizer">Organizers</button>
                        <button class="filter-btn" data-filter="customer">Customers</button>
                    </div>
                </div>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User Info</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Function to check admin session
        function checkAdminSession() {
            return fetch('/kaif_evibe/php/check_admin.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Session check response:', data);
                    if (!data.success || data.role !== 'admin') {
                        console.log('Session check failed, redirecting to login');
                        window.location.href = '/kaif_evibe/templates/html/login.html';
                        return false;
                    }
                    return true;
                })
                .catch(error => {
                    console.error('Error checking session:', error);
                    window.location.href = '/kaif_evibe/templates/html/login.html';
                    return false;
                });
        }

        // Load users from database
        function loadUsers() {
            fetch('/kaif_evibe/php/admin/get_users.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('usersTableBody');
                    tbody.innerHTML = '';

                    if (!data.success) {
                        tbody.innerHTML = `<tr><td colspan="5" class="no-data">Error: ${data.message}</td></tr>`;
                        return;
                    }

                    if (!data.users || data.users.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="no-data">No users found</td></tr>';
                        return;
                    }

                    data.users.forEach(user => {
                        const role_class = user.role.toLowerCase();
                        const row = document.createElement('tr');
                        row.className = 'user-row';
                        row.setAttribute('data-role', role_class);
                        
                        row.innerHTML = `
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">${user.fullname.charAt(0).toUpperCase()}</div>
                                    <div class="user-details">
                                        <span class="user-name">${user.fullname}</span>
                                        <span class="user-address">${user.email}</span>
                                    </div>
                                </div>
                            </td>
                            <td>${user.contact_number || 'N/A'}</td>
                            <td><span class="role-badge ${role_class}">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span></td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td class="action-cell">
                                <div class="action-buttons">
                                    <a href="/kaif_evibe/templates/html/admin_edit_user.html?id=${user.id}" class="action-btn edit-btn" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="action-btn delete-btn" onclick="deleteUser(${user.id})" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('usersTableBody').innerHTML = '<tr><td colspan="5" class="no-data">Error loading users</td></tr>';
                });
        }

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                const filter = button.getAttribute('data-filter');
                const rows = document.querySelectorAll('.user-row');

                rows.forEach(row => {
                    if (filter === 'all' || row.getAttribute('data-role') === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Delete user function
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                fetch('/kaif_evibe/php/admin/delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ user_id: userId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadUsers(); // Reload the users list
                    } else {
                        alert('Error deleting user: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the user');
                });
            }
        }

        // Handle logout
        document.getElementById('logoutBtn').addEventListener('click', function() {
            fetch('/kaif_evibe/php/logout.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/kaif_evibe/templates/html/login.html';
                } else {
                    alert('Logout failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred during logout');
            });
        });

        // Check session and load users when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded, checking session...');
            checkAdminSession().then(isAdmin => {
                if (isAdmin) {
                    console.log('Session valid, loading users...');
                    loadUsers();
                }
            });
        });

        // Check session periodically
        setInterval(checkAdminSession, 300000); // Check every 5 minutes
    </script>
</body>
</html>