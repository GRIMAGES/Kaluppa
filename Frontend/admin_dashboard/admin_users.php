<?php
require_once '../../Backend/connection.php';
session_start();
// Set session timeout duration (in seconds)
$timeout_duration = 1000; // 30 minutes

// Redirect to login page if not logged in
if (!isset($_SESSION['email'])) {
    header("Location: /Frontend/index.php");
    exit();
}

// Update last activity time stamp
$_SESSION['LAST_ACTIVITY'] = time();
// Logout logic
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: /Frontend/index.php");
    exit();
}

// Function to add user to alumni table if role is 'alumni'
function addToAlumniTable($conn, $userId, $firstName, $middleName, $lastName, $category) {
    // Fetch course name or volunteer work title based on category
    if ($category == 'Course') {
        $stmt = $conn->prepare("SELECT name FROM courses WHERE user_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = $result->num_rows > 0 ? $result->fetch_assoc()['name'] : 'N/A';
    } else {
        $stmt = $conn->prepare("SELECT title FROM works WHERE user_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = $result->num_rows > 0 ? $result->fetch_assoc()['title'] : 'N/A';
    }

    $stmt = $conn->prepare("INSERT INTO alumni (user_id, first_name, middle_name, last_name, category, details, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $status = 'completed'; // Example status, adjust as needed
    $stmt->bind_param("issssss", $userId, $firstName, $middleName, $lastName, $category, $details, $status);
    $stmt->execute();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/admin_css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel" style="color:black;">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to log out?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="/Kaluppa/Frontend/logout.php" class="btn btn-theme">Logout</a>

            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel" style="color:black;">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color:black;">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label for="firstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstName" name="firstName" required>
                    </div>
                    <div class="mb-3">
                        <label for="middleName" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="middleName" name="middleName">
                    </div>
                    <div class="mb-3">
                        <label for="lastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastName" name="lastName" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                            <option value="alumni">Alumni</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel" style="color:black;">Delete User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="color:black;">
                Are you sure you want to delete this user?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
            </div>
        </div>
    </div>
</div>

    <!-- Main Content -->
<div class="content" style="margin-left: 270px; padding: 20px;">
    <div class="container mt-5">
        <h2 class="mb-4 text-center">User Management</h2>
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
            Add User
        </button>

        <!-- Dark Container Card -->
        <div class="card shadow-lg" style="background-color: #2c2f33; border-radius: 15px;">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="userTable" class="table table-hover align-middle table-striped bg-white rounded">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Gender</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        require_once '../../Backend/connection.php';
                        $sql = "SELECT id, CONCAT(first_name, ' ', middle_name, ' ', last_name) AS full_name, role, email, gender FROM user";
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr>
                                        <td>' . htmlspecialchars($row['id']) . '</td>
                                        <td>' . htmlspecialchars($row['full_name']) . '</td>
                                        <td>
                                            <select class="form-select form-select-sm" onchange="changeRole(' . htmlspecialchars($row['id']) . ', this.value)">
                                                <option value="admin"' . ($row['role'] == 'admin' ? ' selected' : '') . '>Admin</option>
                                                <option value="superadmin"' . ($row['role'] == 'superadmin' ? ' selected' : '') . '>Superadmin</option>
                                                <option value="alumni"' . ($row['role'] == 'alumni' ? ' selected' : '') . '>Alumni</option>
                                                <option value="user"' . ($row['role'] == 'user' ? ' selected' : '') . '>User</option>
                                            </select>
                                        </td>
                                        <td>' . htmlspecialchars($row['email']) . '</td>
                                        <td>' . htmlspecialchars($row['gender']) . '</td>
                                        <td>
                                            <div class="d-inline-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="updateRole(' . htmlspecialchars($row['id']) . ')">
                                                    <i class="fas fa-sync-alt"></i> Update
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(' . htmlspecialchars($row['id']) . ')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                      </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6" class="text-center">No users found.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- End of Dark Container Card -->

    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#userTable').DataTable({
                "pagingType": "full_numbers",
                "lengthMenu": [5, 10, 25, 50],
                "pageLength": 10,
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search users..."
                }
            });

            $('#addUserForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: '/Kaluppa/Backend/add_users.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        // Extract only the last line which contains our success/error message
                        const lines = response.split('\n');
                        const lastLine = lines[lines.length - 1].trim();
                        
                        if (lastLine.includes('successfully')) {
                            alert('User added successfully and email sent');
                            location.reload();
                        } else {
                            alert('Error: ' + lastLine);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error adding user: ' + error);
                    }
                });
            });

            // Make table rows draggable
            $("#userTable tbody").sortable({
                helper: function(e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                start: function(event, ui) {
                    ui.item.addClass('dragging');
                },
                stop: function(event, ui) {
                    ui.item.removeClass('dragging');
                }
            }).disableSelection();
        });

        function changeRole(userId, newRole) {
            $.ajax({
                url: '/Kaluppa/Backend/change_role.php',
                type: 'POST',
                data: { id: userId, role: newRole },
                success: function(response) {
                    alert('Role updated successfully');
                    if (newRole === 'alumni') {
                        // Fetch user details and add to alumni table
                        $.ajax({
                            url: '/Kaluppa/Backend/get_user_details.php',
                            type: 'POST',
                            data: { id: userId },
                            success: function(userDetails) {
                                const user = JSON.parse(userDetails);
                                // Determine category
                                const category = 'Course'; // or 'Volunteer', adjust as needed
                                addToAlumniTable(user.id, user.first_name, user.middle_name, user.last_name, category);
                            },
                            error: function() {
                                alert('Error fetching user details');
                            }
                        });
                    }
                },
                error: function() {
                    alert('Error updating role');
                }
            });
        }

        function updateRole(userId) {
            const selectElement = document.querySelector(`select[onchange="changeRole(${userId}, this.value)"]`);
            const newRole = selectElement.value;
            changeRole(userId, newRole);
        }

        function confirmDelete(userId) {
            $('#deleteUserModal').modal('show');
            $('#confirmDeleteButton').off('click').on('click', function() {
                deleteUser(userId);
            });
        }

        function deleteUser(userId) {
            $.ajax({
                url: '/Kaluppa/Backend/delete_user.php',
                type: 'POST',
                data: { id: userId },
                success: function(response) {
                    alert('User deleted successfully');
                    location.reload();
                },
                error: function() {
                    alert('Error deleting user');
                }
            });
        }

        function addToAlumniTable(userId, firstName, middleName, lastName, category) {
            $.ajax({
                url: '/Kaluppa/Backend/add_to_alumni.php',
                type: 'POST',
                data: { user_id: userId, first_name: firstName, middle_name: middleName, last_name: lastName, category: category },
                success: function(response) {
                    alert('User added to alumni table successfully');
                },
                error: function() {
                    alert('Error adding user to alumni table');
                }
            });
        }
    </script>
</body>
</html>
