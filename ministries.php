<?php
session_start();
require_once 'db_connect.php';

if (!is_logged_in() || !has_role(['Administrator', 'Pastor/Clergy', 'Ministry Leader'])) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $id = $_POST['id'] ?? null;
            $name = sanitize_input($_POST['name']);
            $description = sanitize_input($_POST['description']);
            
            if ($_POST['action'] == 'add') {
                $query = "INSERT INTO ministries (name, description) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ss", $name, $description);
                $success_msg = 'Ministry added successfully!';
            } else {
                $query = "UPDATE ministries SET name=?, description=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $id);
                $success_msg = 'Ministry updated successfully!';
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $message = $success_msg;
                $message_type = 'success';
            } else {
                $message = 'Error: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = sanitize_input($_POST['id']);
            $query = "DELETE FROM ministries WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Ministry deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting ministry: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'assign_member') {
            $member_id = sanitize_input($_POST['member_id']);
            $ministry_id = sanitize_input($_POST['ministry_id']);
            $role = sanitize_input($_POST['role']);
            $date_joined = sanitize_input($_POST['date_joined']);
            
            $query = "INSERT INTO ministry_members (member_id, ministry_id, role, date_joined) 
                      VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iiss", $member_id, $ministry_id, $role, $date_joined);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Member assigned to ministry!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'remove_member') {
            $id = sanitize_input($_POST['id']);
            $query = "DELETE FROM ministry_members WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Member removed from ministry!';
                $message_type = 'success';
            }
        }
    }
}

// Get all ministries with member count
$ministries = mysqli_query($conn, 
    "SELECT m.*, COUNT(mm.id) as member_count 
     FROM ministries m 
     LEFT JOIN ministry_members mm ON m.id = mm.ministry_id 
     GROUP BY m.id 
     ORDER BY m.name");

// Get all members for assignment dropdown
$all_members = mysqli_query($conn, 
    "SELECT mem_id, first_name, last_name FROM members WHERE passing_date IS NULL ORDER BY last_name, first_name");

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-grid-3x3-gap me-2"></i>Ministry Management</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ministryModal" onclick="resetForm()">
                    <i class="bi bi-plus-circle me-2"></i>Add New Ministry
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Ministries Grid -->
            <div class="row">
                <?php while ($ministry = mysqli_fetch_assoc($ministries)): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-star me-2"></i><?php echo htmlspecialchars($ministry['name']); ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text text-muted">
                                    <?php echo htmlspecialchars($ministry['description'] ?: 'No description'); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-info rounded-pill">
                                        <?php echo $ministry['member_count']; ?> members
                                    </span>
                                </div>
                                <div class="btn-group w-100" role="group">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewMembers(<?php echo $ministry['id']; ?>, '<?php echo addslashes($ministry['name']); ?>')">
                                        <i class="bi bi-people"></i> Members
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="editMinistry(<?php echo htmlspecialchars(json_encode($ministry)); ?>)">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $ministry['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>
</div>

<!-- Ministry Modal -->
<div class="modal fade" id="ministryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Ministry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="ministry_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Ministry Name *</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Ministry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Members Modal -->
<div class="modal fade" id="membersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="membersModalTitle">Ministry Members</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <button type="button" class="btn btn-sm btn-success mb-3" data-bs-toggle="modal" data-bs-target="#assignModal">
                    <i class="bi bi-plus-circle me-1"></i>Assign Member
                </button>
                <div id="membersList"></div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Member Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Assign Member to Ministry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_member">
                    <input type="hidden" name="ministry_id" id="assign_ministry_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Member *</label>
                        <select class="form-select" name="member_id" required>
                            <option value="">Choose...</option>
                            <?php while ($member = mysqli_fetch_assoc($all_members)): ?>
                                <option value="<?php echo $member['mem_id']; ?>">
                                    <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Role in Ministry</label>
                        <input type="text" class="form-control" name="role" placeholder="e.g., Leader, Member, Secretary">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Date Joined</label>
                        <input type="date" class="form-control" name="date_joined" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentMinistryId = null;

function resetForm() {
    document.getElementById('action').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add New Ministry';
    document.querySelector('#ministryModal form').reset();
}

function editMinistry(ministry) {
    document.getElementById('action').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Edit Ministry';
    document.getElementById('ministry_id').value = ministry.id;
    document.getElementById('name').value = ministry.name;
    document.getElementById('description').value = ministry.description || '';
    
    new bootstrap.Modal(document.getElementById('ministryModal')).show();
}

function viewMembers(ministryId, ministryName) {
    currentMinistryId = ministryId;
    document.getElementById('membersModalTitle').textContent = ministryName + ' - Members';
    document.getElementById('assign_ministry_id').value = ministryId;
    
    // Fetch members via AJAX
    fetch('get_ministry_members.php?ministry_id=' + ministryId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('membersList').innerHTML = html;
            new bootstrap.Modal(document.getElementById('membersModal')).show();
        });
}
</script>

<?php include 'includes/footer.php'; ?>