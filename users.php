<?php
session_start();
require_once 'db_connect.php';

if (!is_logged_in() || !has_role(['Administrator'])) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $id = $_POST['id'] ?? null;
            $username = sanitize_input($_POST['username']);
            $role_id = (int)$_POST['role_id'];
            $status = sanitize_input($_POST['status']);
            $password = $_POST['password'];
            
            if ($_POST['action'] == 'add') {
                if (empty($password)) {
                    $message = 'Password is required for new users';
                    $message_type = 'danger';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "INSERT INTO users (username, password, role_id, status) VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "ssis", $username, $hashed_password, $role_id, $status);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $message = 'User added successfully!';
                        $message_type = 'success';
                    } else {
                        $message = 'Error: ' . mysqli_error($conn);
                        $message_type = 'danger';
                    }
                }
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET username=?, password=?, role_id=?, status=? WHERE id=?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "ssisi", $username, $hashed_password, $role_id, $status, $id);
                } else {
                    $query = "UPDATE users SET username=?, role_id=?, status=? WHERE id=?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "sisi", $username, $role_id, $status, $id);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'User updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error: ' . mysqli_error($conn);
                    $message_type = 'danger';
                }
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = sanitize_input($_POST['id']);
            
            // Prevent deleting yourself
            if ($id == $_SESSION['user_id']) {
                $message = 'You cannot delete your own account!';
                $message_type = 'warning';
            } else {
                $query = "DELETE FROM users WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'User deleted successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error: ' . mysqli_error($conn);
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Get all users
$users = mysqli_query($conn, 
    "SELECT u.*, r.role_name FROM users u 
     JOIN roles r ON u.role_id = r.id 
     ORDER BY u.username");

// Get all roles
$roles = mysqli_query($conn, "SELECT * FROM roles ORDER BY role_name");

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-person-gear me-2"></i>User Management</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
                    <i class="bi bi-plus-circle me-2"></i>Add New User
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">System Users</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-primary ms-2">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($user['role_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirmDelete('Are you sure you want to delete this user?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Role Descriptions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Role Descriptions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php 
                                mysqli_data_seek($roles, 0);
                                while ($role = mysqli_fetch_assoc($roles)): 
                                ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="border-start border-primary border-4 ps-3">
                                            <h6 class="text-primary"><?php echo htmlspecialchars($role['role_name']); ?></h6>
                                            <p class="text-muted mb-0 small">
                                                <?php echo htmlspecialchars($role['description']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" id="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password <span id="password-note">(required)</span></label>
                        <input type="password" class="form-control" name="password" id="password">
                        <small class="text-muted">Leave blank to keep current password when editing</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select class="form-select" name="role_id" id="role_id" required>
                            <option value="">Select role...</option>
                            <?php 
                            mysqli_data_seek($roles, 0);
                            while ($role = mysqli_fetch_assoc($roles)): 
                            ?>
                                <option value="<?php echo $role['id']; ?>">
                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select class="form-select" name="status" id="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('action').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('password-note').textContent = '(required)';
    document.getElementById('password').required = true;
    document.querySelector('#userModal form').reset();
    document.querySelector('#userModal form').classList.remove('was-validated');
}

function editUser(user) {
    document.getElementById('action').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('password-note').textContent = '(leave blank to keep current)';
    document.getElementById('password').required = false;
    document.getElementById('user_id').value = user.id;
    document.getElementById('username').value = user.username;
    document.getElementById('role_id').value = user.role_id;
    document.getElementById('status').value = user.status;
    document.getElementById('password').value = '';
    
    new bootstrap.Modal(document.getElementById('userModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>