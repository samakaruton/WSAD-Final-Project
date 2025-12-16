<?php
session_start();
require_once 'db_connect.php';

if (!is_logged_in() || !has_role(['Administrator', 'Pastor/Clergy', 'Clerk/Secretary'])) {
    redirect('dashboard.php');
}

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $id = $_POST['id'] ?? null;
            $event_type = sanitize_input($_POST['event_type']);
            $event_date = sanitize_input($_POST['event_date']);
            $member_id = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
            $notes = sanitize_input($_POST['notes']);
            
            if ($_POST['action'] == 'add') {
                $query = "INSERT INTO events (event_type, event_date, member_id, notes) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssis", $event_type, $event_date, $member_id, $notes);
                $success_msg = 'Event added successfully!';
            } else {
                $query = "UPDATE events SET event_type=?, event_date=?, member_id=?, notes=? WHERE id=?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ssisi", $event_type, $event_date, $member_id, $notes, $id);
                $success_msg = 'Event updated successfully!';
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
            $query = "DELETE FROM events WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Event deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        }
    }
}

// Get all events
$type_filter = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$month_filter = isset($_GET['month']) ? sanitize_input($_GET['month']) : '';

$query = "SELECT e.*, m.first_name, m.last_name FROM events e 
          LEFT JOIN members m ON e.member_id = m.mem_id 
          WHERE 1=1";

if ($type_filter) {
    $query .= " AND e.event_type = '$type_filter'";
}
if ($month_filter) {
    $query .= " AND DATE_FORMAT(e.event_date, '%Y-%m') = '$month_filter'";
}
$query .= " ORDER BY e.event_date DESC";

$events = mysqli_query($conn, $query);

// Get all members for dropdown
$members = mysqli_query($conn, "SELECT mem_id, first_name, last_name FROM members WHERE passing_date IS NULL ORDER BY last_name, first_name");

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-calendar-event me-2"></i>Events Management</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal" onclick="resetForm()">
                    <i class="bi bi-plus-circle me-2"></i>Add New Event
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Event Type</label>
                            <select class="form-select" name="type">
                                <option value="">All Types</option>
                                <option value="wedding" <?php echo $type_filter == 'wedding' ? 'selected' : ''; ?>>Wedding</option>
                                <option value="birthday" <?php echo $type_filter == 'birthday' ? 'selected' : ''; ?>>Birthday</option>
                                <option value="anniversary" <?php echo $type_filter == 'anniversary' ? 'selected' : ''; ?>>Anniversary</option>
                                <option value="baptism" <?php echo $type_filter == 'baptism' ? 'selected' : ''; ?>>Baptism</option>
                                <option value="death" <?php echo $type_filter == 'death' ? 'selected' : ''; ?>>Death</option>
                                <option value="other" <?php echo $type_filter == 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Month</label>
                            <input type="month" class="form-control" name="month" value="<?php echo htmlspecialchars($month_filter); ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-funnel me-1"></i>Filter
                            </button>
                            <a href="events.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Events Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Events List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Event Type</th>
                                    <th>Date</th>
                                    <th>Member</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($events) > 0): ?>
                                    <?php while ($event = mysqli_fetch_assoc($events)): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $event['event_type'] == 'wedding' ? 'danger' : 
                                                        ($event['event_type'] == 'birthday' ? 'success' : 
                                                        ($event['event_type'] == 'baptism' ? 'info' : 
                                                        ($event['event_type'] == 'death' ? 'dark' : 'secondary'))); 
                                                ?>">
                                                    <i class="bi bi-<?php 
                                                        echo $event['event_type'] == 'wedding' ? 'heart' : 
                                                            ($event['event_type'] == 'birthday' ? 'cake2' : 
                                                            ($event['event_type'] == 'baptism' ? 'droplet' : 
                                                            ($event['event_type'] == 'death' ? 'cross' : 'calendar-event'))); 
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($event['event_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                            <td>
                                                <?php 
                                                if ($event['first_name']) {
                                                    echo htmlspecialchars($event['first_name'] . ' ' . $event['last_name']);
                                                } else {
                                                    echo '<em class="text-muted">N/A</em>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($event['notes'] ?? '', 0, 50)); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info" onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No events found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="id" id="event_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Event Type *</label>
                        <select class="form-select" name="event_type" id="event_type" required>
                            <option value="">Select...</option>
                            <option value="wedding">Wedding</option>
                            <option value="birthday">Birthday</option>
                            <option value="anniversary">Anniversary</option>
                            <option value="baptism">Baptism</option>
                            <option value="death">Death</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Event Date *</label>
                        <input type="date" class="form-control" name="event_date" id="event_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Member (Optional)</label>
                        <select class="form-select" name="member_id" id="member_id">
                            <option value="">Select member...</option>
                            <?php while ($member = mysqli_fetch_assoc($members)): ?>
                                <option value="<?php echo $member['mem_id']; ?>">
                                    <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('action').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add New Event';
    document.querySelector('#eventModal form').reset();
    document.querySelector('#eventModal form').classList.remove('was-validated');
}

function editEvent(event) {
    document.getElementById('action').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Edit Event';
    document.getElementById('event_id').value = event.id;
    document.getElementById('event_type').value = event.event_type;
    document.getElementById('event_date').value = event.event_date;
    document.getElementById('member_id').value = event.member_id || '';
    document.getElementById('notes').value = event.notes || '';
    
    new bootstrap.Modal(document.getElementById('eventModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>