<?php
session_start();
require_once 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Unauthorized access!'); window.location.href='login.html';</script>";
    exit();
}

// Fetch all users from database
$sql = "SELECT * FROM users ORDER BY role, fullname";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Evibe</title>
    <link rel="stylesheet" href="admin_user.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Glassmorphic Navigation -->
    <nav class="glass-nav">
        <div class="nav-container">
            <!-- Logo Section -->
            <div class="logo-section">
                <div class="logo">
                    <h1><i class="fas fa-calendar-star"></i> Evibe</h1>
                </div>
            </div>

            <!-- Navigation Section -->
            <div class="navigation-section">
                <ul class="nav-links">
                    <li><a href="admin_panel.html"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                    <li><a href="admin_categories.html"><i class="fas fa-folder"></i> Categories</a></li>
                    <li><a href="admin_register.html"><i class="fas fa-user-plus"></i> Register Admin</a></li>
                    <li><a href="login.html"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <div class="content-wrapper">
            <div class="users-container">
                <h2><i class="fas fa-users"></i> User Management</h2>
                
                <!-- User Type Filters -->
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">All Users</button>
                    <button class="filter-btn" data-filter="admin">Admins</button>
                    <button class="filter-btn" data-filter="organizer">Organizers</button>
                    <button class="filter-btn" data-filter="customer">Customers</button>
                </div>

                <!-- Users Table -->
                <div class="table-container">
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
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $role_class = strtolower($row['role']);
                                    echo "<tr class='user-row' data-role='" . $role_class . "'>";
                                    echo "<td>";
                                    echo "<div class='user-info'>";
                                    echo "<div class='user-avatar'>" . strtoupper(substr($row['fullname'], 0, 1)) . "</div>";
                                    echo "<div class='user-details'>";
                                    echo "<div class='user-name'>" . $row['fullname'] . "</div>";
                                    echo "<div class='user-address'>" . $row['email'] . "</div>";
                                    echo "</div>";
                                    echo "</div>";
                                    echo "</td>";
                                    echo "<td>" . $row['contact_number'] . "</td>";
                                    echo "<td><span class='role-badge " . $role_class . "'>" . ucfirst($row['role']) . "</span></td>";
                                    echo "<td><span class='status-badge status-active'>Active</span></td>";
                                    echo "<td class='action-cell'>";
                                    echo "<div class='action-buttons'>";
                                    echo "<button class='action-btn edit-btn' onclick='editUser(" . $row['id'] . ", \"" . $row['fullname'] . "\", \"" . $row['email'] . "\", \"" . $row['contact_number'] . "\", \"" . $row['address'] . "\", \"" . $row['role'] . "\")'><i class='fas fa-edit'></i></button>";
                                    echo "<button class='action-btn delete-btn' onclick='deleteUser(" . $row['id'] . ")'><i class='fas fa-trash'></i></button>";
                                    echo "</div>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='no-data'>No users found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit User</h3>
                <button class="close-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <form id="editForm" onsubmit="updateUser(event)">
                <input type="hidden" id="edit_user_id" name="user_id">
                <div class="form-group">
                    <label for="edit_fullname">Full Name</label>
                    <input type="text" id="edit_fullname" name="fullname" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_contact">Contact Number</label>
                    <input type="tel" id="edit_contact" name="contact_number" required>
                </div>
                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <textarea id="edit_address" name="address" required></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_role">Role</label>
                    <select id="edit_role" name="role" required>
                        <option value="admin">Admin</option>
                        <option value="organizer">Organizer</option>
                        <option value="customer">Customer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_password">New Password (leave blank to keep current)</label>
                    <input type="password" id="edit_password" name="password">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="submit-btn">Update User</button>
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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

        // Modal functionality
        function editUser(id, fullname, email, contact, address, role) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_fullname').value = fullname;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_contact').value = contact;
            document.getElementById('edit_address').value = address;
            document.getElementById('edit_role').value = role;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Update user function
        function updateUser(event) {
            event.preventDefault();
            const formData = new FormData(event.target);

            fetch('edit_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the user');
            });
        }

        // Delete user function
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                const formData = new FormData();
                formData.append('user_id', userId);

                fetch('delete_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the user');
                });
            }
        }
    </script>
</body>
</html> 