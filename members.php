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
            $mem_id = $_POST['mem_id'] ?? null;
            $first_name = sanitize_input($_POST['first_name']);
            $middle_initials = sanitize_input($_POST['middle_initials']);
            $last_name = sanitize_input($_POST['last_name']);
            $dob = sanitize_input($_POST['dob']);
            $gender = sanitize_input($_POST['gender']);
            $home_address_1 = sanitize_input($_POST['home_address_1']);
            $home_address_2 = sanitize_input($_POST['home_address_2']);
            $town = sanitize_input($_POST['town']);
            $parish = sanitize_input($_POST['parish']);
            $contact_home = sanitize_input($_POST['contact_home']);
            $contact_work = sanitize_input($_POST['contact_work']);
            $email = sanitize_input($_POST['email']);
            $status = sanitize_input($_POST['status']);
            $date_joined = sanitize_input($_POST['date_joined']);
            
            // Next of kin details
            $nok_name = sanitize_input($_POST['nok_name']);
            $nok_address = sanitize_input($_POST['nok_address']);
            $nok_relation = sanitize_input($_POST['nok_relation']);
            $nok_contact = sanitize_input($_POST['nok_contact']);
            $nok_email = sanitize_input($_POST['nok_email']);
            
            if ($_POST['action'] == 'add') {
                $query = "INSERT INTO members (first_name, middle_initials, last_name, dob, gender, 
                          home_address_1, home_address_2, town, parish, contact_home, contact_work, 
                          email, status, date_joined, next_of_kin_name, next_of_kin_address, 
                          next_of_kin_relation, next_of_kin_contact, next_of_kin_email) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssssssssssssssssss", $first_name, $middle_initials, 
                    $last_name, $dob, $gender, $home_address_1, $home_address_2, $town, $parish, 
                    $contact_home, $contact_work, $email, $status, $date_joined, $nok_name, 
                    $nok_address, $nok_relation, $nok_contact, $nok_email);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Member added successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error adding member: ' . mysqli_error($conn);
                    $message_type = 'danger';
                }
            } else {
                $query = "UPDATE members SET first_name=?, middle_initials=?, last_name=?, dob=?, 
                          gender=?, home_address_1=?, home_address_2=?, town=?, parish=?, 
                          contact_home=?, contact_work=?, email=?, status=?, date_joined=?, 
                          next_of_kin_name=?, next_of_kin_address=?, next_of_kin_relation=?, 
                          next_of_kin_contact=?, next_of_kin_email=? WHERE mem_id=?";
                
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "sssssssssssssssssssi", $first_name, $middle_initials, 
                    $last_name, $dob, $gender, $home_address_1, $home_address_2, $town, $parish, 
                    $contact_home, $contact_work, $email, $status, $date_joined, $nok_name, 
                    $nok_address, $nok_relation, $nok_contact, $nok_email, $mem_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $message = 'Member updated successfully!';
                    $message_type = 'success';
                } else {
                    $message = 'Error updating member: ' . mysqli_error($conn);
                    $message_type = 'danger';
                }
            }
        } elseif ($_POST['action'] == 'delete') {
            $mem_id = sanitize_input($_POST['mem_id']);
            $query = "DELETE FROM members WHERE mem_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $mem_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Member deleted successfully!';
                $message_type = 'success';
            } else {
                $message = 'Error deleting member: ' . mysqli_error($conn);
                $message_type = 'danger';
            }
        }
    }
}

// Get all members
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';

$query = "SELECT * FROM members WHERE 1=1";
if ($search) {
    $query .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($status_filter) {
    $query .= " AND status = '$status_filter'";
}
$query .= " ORDER BY last_name, first_name";

$members = mysqli_query($conn, $query);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-people me-2"></i>Members Management</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#memberModal" onclick="resetForm()">
                    <i class="bi bi-plus-circle me-2"></i>Add New Member
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Search and Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search by name or email..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="member" <?php echo $status_filter == 'member' ? 'selected' : ''; ?>>Member</option>
                                <option value="adherent" <?php echo $status_filter == 'adherent' ? 'selected' : ''; ?>>Adherent</option>
                                <option value="visitor" <?php echo $status_filter == 'visitor' ? 'selected' : ''; ?>>Visitor</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bi bi-search me-1"></i>Search
                            </button>
                            <a href="members.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Members Table -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Members List (<?php echo mysqli_num_rows($members); ?> total)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Gender</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Date Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($member = mysqli_fetch_assoc($members)): ?>
                                    <tr>
                                        <td><?php echo $member['mem_id']; ?></td>
                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($member['first_name'] . ' ' . 
                                                    ($member['middle_initials'] ? $member['middle_initials'] . ' ' : '') . 
                                                    $member['last_name']); ?>
                                            </strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($member['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($member['contact_home']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $member['status'] == 'member' ? 'success' : 
                                                    ($member['status'] == 'adherent' ? 'info' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($member['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $member['date_joined'] ? date('M d, Y', strtotime($member['date_joined'])) : 'N/A'; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="editMember(<?php echo htmlspecialchars(json_encode($member)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirmDelete()">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="mem_id" value="<?php echo $member['mem_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Member Modal -->
<div class="modal fade" id="memberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="mem_id" id="mem_id">
                    
                    <h6 class="border-bottom pb-2 mb-3">Personal Information</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="first_name" id="first_name" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">M.I.</label>
                            <input type="text" class="form-control" name="middle_initials" id="middle_initials">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="last_name" id="last_name" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" id="dob">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender *</label>
                            <select class="form-select" name="gender" id="gender" required>
                                <option value="">Select...</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status *</label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="visitor">Visitor</option>
                                <option value="adherent">Adherent</option>
                                <option value="member">Member</option>
                            </select>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Contact Information</h6>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Home Address 1</label>
                            <input type="text" class="form-control" name="home_address_1" id="home_address_1">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Home Address 2</label>
                            <input type="text" class="form-control" name="home_address_2" id="home_address_2">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Town</label>
                            <input type="text" class="form-control" name="town" id="town">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Parish</label>
                            <input type="text" class="form-control" name="parish" id="parish">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Home Phone</label>
                            <input type="tel" class="form-control" name="contact_home" id="contact_home">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Work Phone</label>
                            <input type="tel" class="form-control" name="contact_work" id="contact_work">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Next of Kin</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="nok_name" id="nok_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Relation</label>
                            <input type="text" class="form-control" name="nok_relation" id="nok_relation">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="nok_address" id="nok_address">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="nok_contact" id="nok_contact">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="nok_email" id="nok_email">
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Church Information</h6>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Date Joined</label>
                            <input type="date" class="form-control" name="date_joined" id="date_joined">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Save Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('action').value = 'add';
    document.getElementById('modalTitle').textContent = 'Add New Member';
    document.querySelector('#memberModal form').reset();
    document.querySelector('#memberModal form').classList.remove('was-validated');
}

function editMember(member) {
    document.getElementById('action').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Edit Member';
    document.getElementById('mem_id').value = member.mem_id;
    document.getElementById('first_name').value = member.first_name;
    document.getElementById('middle_initials').value = member.middle_initials || '';
    document.getElementById('last_name').value = member.last_name;
    document.getElementById('dob').value = member.dob || '';
    document.getElementById('gender').value = member.gender || '';
    document.getElementById('status').value = member.status;
    document.getElementById('home_address_1').value = member.home_address_1 || '';
    document.getElementById('home_address_2').value = member.home_address_2 || '';
    document.getElementById('town').value = member.town || '';
    document.getElementById('parish').value = member.parish || '';
    document.getElementById('contact_home').value = member.contact_home || '';
    document.getElementById('contact_work').value = member.contact_work || '';
    document.getElementById('email').value = member.email || '';
    document.getElementById('nok_name').value = member.next_of_kin_name || '';
    document.getElementById('nok_relation').value = member.next_of_kin_relation || '';
    document.getElementById('nok_address').value = member.next_of_kin_address || '';
    document.getElementById('nok_contact').value = member.next_of_kin_contact || '';
    document.getElementById('nok_email').value = member.next_of_kin_email || '';
    document.getElementById('date_joined').value = member.date_joined || '';
    
    new bootstrap.Modal(document.getElementById('memberModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>