<?php
session_start();
require_once 'db_connect.php';

if (!is_logged_in() || !has_role(['Administrator', 'Pastor/Clergy', 'Ministry Leader', 'Clerk/Secretary'])) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_attendance') {
            $date = sanitize_input($_POST['date']);
            $ministry_id = !empty($_POST['ministry_id']) ? (int)$_POST['ministry_id'] : null;
            $service_type = sanitize_input($_POST['service_type']);
            $count = (int)$_POST['count'];
            $notes = sanitize_input($_POST['notes']);
            $recorded_by = $_SESSION['user_id'];
            
            // If ministry_id is provided, check if it exists
            if ($ministry_id !== null) {
                $check_query = "SELECT id FROM ministries WHERE id = ?";
                $check_stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($check_stmt, "i", $ministry_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_result) == 0) {
                    $ministry_id = null;
                }
            }
            
            $query = "INSERT INTO attendance (date, ministry_id, service_type, count, recorded_by, notes) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sisiss", $date, $ministry_id, $service_type, $count, $recorded_by, $notes);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Attendance recorded successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error recording attendance: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'delete_attendance') {
            $id = sanitize_input($_POST['id']);
            $query = "DELETE FROM attendance WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Attendance record deleted!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting record: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'add_vestry' || $_POST['action'] == 'edit_vestry') {
            $id = $_POST['vestry_id'] ?? null;
            $date = sanitize_input($_POST['vestry_date']);
            $minister_name = sanitize_input($_POST['minister_name']);
            $hours_detail = sanitize_input($_POST['hours_detail']);
            
            if ($_POST['action'] == 'add_vestry') {
                $query = "INSERT INTO vestry_hours (date, minister_name, hours_detail) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sss", $date, $minister_name, $hours_detail);
                $success_msg = 'Vestry hours recorded successfully!';
            } else {
                $query = "UPDATE vestry_hours SET date=?, minister_name=?, hours_detail=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssi", $date, $minister_name, $hours_detail, $id);
                $success_msg = 'Vestry hours updated successfully!';
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $message = $success_msg;
                $message_type = 'success';
            } else {
                $message = 'Error: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] == 'delete_vestry') {
            $id = sanitize_input($_POST['id']);
            $query = "DELETE FROM vestry_hours WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Vestry hours deleted!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        }
    }
}

// Get ministries for dropdown
$ministries = mysqli_query($conn, "SELECT * FROM ministries ORDER BY name");

// Get attendance records
$date_filter = isset($_GET['date']) ? sanitize_input($_GET['date']) : '';
$ministry_filter = isset($_GET['ministry']) ? sanitize_input($_GET['ministry']) : '';

$query = "SELECT a.*, m.name as ministry_name, u.username 
          FROM attendance a 
          LEFT JOIN ministries m ON a.ministry_id = m.id 
          LEFT JOIN users u ON a.recorded_by = u.id 
          WHERE 1=1";

if ($date_filter) {
    $query .= " AND a.date = '$date_filter'";
}
if ($ministry_filter) {
    $query .= " AND a.ministry_id = '$ministry_filter'";
}
$query .= " ORDER BY a.date DESC, a.created_at DESC";

$attendance_records = mysqli_query($conn, $query);

// Get vestry hours records
$vestry_date_filter = isset($_GET['vestry_date']) ? sanitize_input($_GET['vestry_date']) : '';
$vestry_query = "SELECT * FROM vestry_hours WHERE 1=1";
if ($vestry_date_filter) {
    $vestry_query .= " AND date = '$vestry_date_filter'";
}
$vestry_query .= " ORDER BY date DESC";
$vestry_records = mysqli_query($conn, $vestry_query);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-person-check me-2"></i>Attendance Management</h1>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-4" id="attendanceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                        <i class="bi bi-people me-1"></i>General Attendance
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="vestry-tab" data-bs-toggle="tab" data-bs-target="#vestry" type="button">
                        <i class="bi bi-file-text me-1"></i>Ministers Vestry Hours
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="attendanceTabContent">
                <!-- General Attendance Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <div class="d-flex justify-content-between mb-3">
                        <h5>Church & Ministry Attendance</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#attendanceModal">
                            <i class="bi bi-plus-circle me-2"></i>Record Attendance
                        </button>
                    </div>

                    <!-- Filter Form -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Date</label>
                                    <input type="date" class="form-control" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ministry</label>
                                    <select class="form-select" name="ministry">
                                        <option value="">All Ministries</option>
                                        <?php 
                                        mysqli_data_seek($ministries, 0);
                                        while ($ministry = mysqli_fetch_assoc($ministries)): 
                                        ?>
                                            <option value="<?php echo $ministry['id']; ?>" 
                                                    <?php echo $ministry_filter == $ministry['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($ministry['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-funnel me-1"></i>Filter
                                    </button>
                                    <a href="attendance.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <?php
                        $today_query = "SELECT SUM(count) as total FROM attendance WHERE date = CURDATE()";
                        $today_result = mysqli_query($conn, $today_query);
                        $today_count = mysqli_fetch_assoc($today_result)['total'] ?? 0;
                        
                        $week_query = "SELECT SUM(count) as total FROM attendance WHERE YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)";
                        $week_result = mysqli_query($conn, $week_query);
                        $week_count = mysqli_fetch_assoc($week_result)['total'] ?? 0;
                        
                        $month_query = "SELECT SUM(count) as total FROM attendance WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())";
                        $month_result = mysqli_query($conn, $month_query);
                        $month_count = mysqli_fetch_assoc($month_result)['total'] ?? 0;
                        ?>
                        
                        <div class="col-md-4 mb-3">
                            <div class="card text-white bg-primary">
                                <div class="card-body">
                                    <h5 class="card-title">Today's Attendance</h5>
                                    <p class="card-text display-6"><?php echo number_format($today_count); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card text-white bg-success">
                                <div class="card-body">
                                    <h5 class="card-title">This Week</h5>
                                    <p class="card-text display-6"><?php echo number_format($week_count); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card text-white bg-info">
                                <div class="card-body">
                                    <h5 class="card-title">This Month</h5>
                                    <p class="card-text display-6"><?php echo number_format($month_count); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Table -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Attendance Records</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Ministry</th>
                                            <th>Service Type</th>
                                            <th>Count</th>
                                            <th>Recorded By</th>
                                            <th>Notes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($attendance_records) > 0): ?>
                                            <?php while ($record = mysqli_fetch_assoc($attendance_records)): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?php echo htmlspecialchars($record['ministry_name'] ?? 'General'); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($record['service_type']); ?></td>
                                                    <td><strong><?php echo $record['count']; ?></strong></td>
                                                    <td><?php echo htmlspecialchars($record['username']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($record['notes'] ?? '', 0, 50)); ?></td>
                                                    <td>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                                            <input type="hidden" name="action" value="delete_attendance">
                                                            <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No attendance records found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vestry Hours Tab -->
                <div class="tab-pane fade" id="vestry" role="tabpanel">
                    <div class="d-flex justify-content-between mb-3">
                        <h5>Ministers Vestry Hours</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#vestryModal" onclick="resetVestryForm()">
                            <i class="bi bi-plus-circle me-2"></i>Record Vestry Hours
                        </button>
                    </div>

                    <!-- Vestry Filter -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="tab" value="vestry">
                                <div class="col-md-6">
                                    <label class="form-label">Date</label>
                                    <input type="date" class="form-control" name="vestry_date" value="<?php echo htmlspecialchars($vestry_date_filter); ?>">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-funnel me-1"></i>Filter
                                    </button>
                                    <a href="attendance.php#vestry" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Vestry Hours Table -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Vestry Hours Records</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Minister Name</th>
                                            <th>Hours Detail</th>
                                            <th>Recorded</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($vestry_records) > 0): ?>
                                            <?php while ($vestry = mysqli_fetch_assoc($vestry_records)): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($vestry['date'])); ?></td>
                                                    <td><strong><?php echo htmlspecialchars($vestry['minister_name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars(substr($vestry['hours_detail'], 0, 100)); ?><?php echo strlen($vestry['hours_detail']) > 100 ? '...' : ''; ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($vestry['created_at'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="editVestry(<?php echo htmlspecialchars(json_encode($vestry)); ?>)">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                                            <input type="hidden" name="action" value="delete_vestry">
                                                            <input type="hidden" name="id" value="<?php echo $vestry['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No vestry hours recorded</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Record Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_attendance">
                    
                    <div class="mb-3">
                        <label class="form-label">Date *</label>
                        <input type="date" class="form-control" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ministry</label>
                        <select class="form-select" name="ministry_id">
                            <option value="">General Church Service</option>
                            <?php 
                            mysqli_data_seek($ministries, 0);
                            while ($ministry = mysqli_fetch_assoc($ministries)): 
                            ?>
                                <option value="<?php echo $ministry['id']; ?>">
                                    <?php echo htmlspecialchars($ministry['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Service Type *</label>
                        <select class="form-select" name="service_type" required>
                            <option value="">Select...</option>
                            <option value="Sunday Morning Service">Sunday Morning Service</option>
                            <option value="Sunday Evening Service">Sunday Evening Service</option>
                            <option value="Wednesday Prayer Meeting">Wednesday Prayer Meeting</option>
                            <option value="Friday Night Service">Friday Night Service</option>
                            <option value="Ministry Meeting">Ministry Meeting</option>
                            <option value="Sunday School">Sunday School</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Attendance Count *</label>
                        <input type="number" class="form-control" name="count" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Vestry Hours Modal -->
<div class="modal fade" id="vestryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="vestryModalTitle">Record Vestry Hours</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="vestry_action" value="add_vestry">
                    <input type="hidden" name="vestry_id" id="vestry_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Date *</label>
                        <input type="date" class="form-control" name="vestry_date" id="vestry_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Minister Name *</label>
                        <input type="text" class="form-control" name="minister_name" id="minister_name" required placeholder="e.g., Rev. John Smith">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Hours Detail *</label>
                        <textarea class="form-control" name="hours_detail" id="hours_detail" rows="4" required placeholder="Describe the vestry meeting details, topics discussed, decisions made, etc."></textarea>
                        <small class="text-muted">Include meeting topics, decisions made, and any important notes</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Vestry Hours
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle tab switching from URL hash or query parameter
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    const hash = window.location.hash;
    
    if (tabParam === 'vestry' || hash === '#vestry') {
        const vestryTab = new bootstrap.Tab(document.getElementById('vestry-tab'));
        vestryTab.show();
    }
});

function resetVestryForm() {
    document.getElementById('vestry_action').value = 'add_vestry';
    document.getElementById('vestryModalTitle').textContent = 'Record Vestry Hours';
    document.querySelector('#vestryModal form').reset();
    document.getElementById('vestry_date').value = '<?php echo date('Y-m-d'); ?>';
    document.querySelector('#vestryModal form').classList.remove('was-validated');
}

function editVestry(vestry) {
    document.getElementById('vestry_action').value = 'edit_vestry';
    document.getElementById('vestryModalTitle').textContent = 'Edit Vestry Hours';
    document.getElementById('vestry_id').value = vestry.id;
    document.getElementById('vestry_date').value = vestry.date;
    document.getElementById('minister_name').value = vestry.minister_name;
    document.getElementById('hours_detail').value = vestry.hours_detail;
    
    new bootstrap.Modal(document.getElementById('vestryModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>