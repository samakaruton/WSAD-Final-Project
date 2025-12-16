<?php
session_start();
require_once 'db_connect.php';

if (!is_logged_in() || !has_role(['Administrator', 'Pastor/Clergy', 'Clerk/Secretary'])) {
    redirect('dashboard.php');
}

$report_data = [];
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : '';

// Generate report based on type
if ($report_type) {
    switch ($report_type) {
        case 'monthly_attendance':
            $month = isset($_GET['month']) ? sanitize_input($_GET['month']) : date('Y-m');
            $query = "SELECT DATE(date) as attendance_date, SUM(count) as total, 
                      GROUP_CONCAT(DISTINCT service_type SEPARATOR ', ') as services
                      FROM attendance 
                      WHERE DATE_FORMAT(date, '%Y-%m') = '$month'
                      GROUP BY DATE(date)
                      ORDER BY date";
            $report_data = mysqli_query($conn, $query);
            break;
            
        case 'birthday_list':
            $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
            $query = "SELECT mem_id, first_name, last_name, dob, contact_home, email 
                      FROM members 
                      WHERE MONTH(dob) = $month AND passing_date IS NULL
                      ORDER BY DAY(dob), last_name";
            $report_data = mysqli_query($conn, $query);
            break;
            
        case 'membership_growth':
            $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
            $query = "SELECT 
                      MONTH(date_joined) as month,
                      MONTHNAME(date_joined) as month_name,
                      COUNT(*) as new_members
                      FROM members 
                      WHERE YEAR(date_joined) = $year
                      GROUP BY MONTH(date_joined), MONTHNAME(date_joined)
                      ORDER BY MONTH(date_joined)";
            $report_data = mysqli_query($conn, $query);
            break;
            
        case 'ministry_participation':
            $ministry_id = isset($_GET['ministry_id']) ? (int)$_GET['ministry_id'] : 0;
            $start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
            $end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';
            
            $query = "SELECT a.date, a.count, a.service_type, m.name as ministry_name
                      FROM attendance a
                      JOIN ministries m ON a.ministry_id = m.id
                      WHERE a.ministry_id = $ministry_id";
            
            if ($start_date) $query .= " AND a.date >= '$start_date'";
            if ($end_date) $query .= " AND a.date <= '$end_date'";
            
            $query .= " ORDER BY a.date DESC";
            $report_data = mysqli_query($conn, $query);
            break;
            
        case 'member_demographics':
            $query = "SELECT 
                      gender,
                      status,
                      COUNT(*) as count
                      FROM members 
                      WHERE passing_date IS NULL
                      GROUP BY gender, status
                      ORDER BY status, gender";
            $report_data = mysqli_query($conn, $query);
            break;
            
        case 'upcoming_anniversaries':
            $weeks = isset($_GET['weeks']) ? (int)$_GET['weeks'] : 4;
            $query = "SELECT e.event_date, e.notes, m.first_name, m.last_name, m.contact_home
                      FROM events e
                      JOIN members m ON e.member_id = m.mem_id
                      WHERE e.event_type = 'anniversary'
                      AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $weeks WEEK)
                      ORDER BY e.event_date";
            $report_data = mysqli_query($conn, $query);
            break;
    }
}

// Get ministries for dropdown
$ministries = mysqli_query($conn, "SELECT * FROM ministries ORDER BY name");

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-file-earmark-bar-graph me-2"></i>Reports</h1>
            </div>

            <div class="row">
                <!-- Report Selection -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Select Report Type</h6>
                        </div>
                        <div class="list-group list-group-flush">
                            <a href="#monthlyAttendance" class="list-group-item list-group-item-action" 
                               data-bs-toggle="collapse">
                                <i class="bi bi-calendar3 me-2"></i>Monthly Attendance Summary
                            </a>
                            <div class="collapse p-3 bg-light" id="monthlyAttendance">
                                <form method="GET">
                                    <input type="hidden" name="report_type" value="monthly_attendance">
                                    <label class="form-label small">Select Month</label>
                                    <input type="month" class="form-control form-control-sm mb-2" 
                                           name="month" value="<?php echo date('Y-m'); ?>">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        Generate
                                    </button>
                                </form>
                            </div>
                            
                            <a href="#birthdayList" class="list-group-item list-group-item-action" 
                               data-bs-toggle="collapse">
                                <i class="bi bi-cake2 me-2"></i>Birthday List
                            </a>
                            <div class="collapse p-3 bg-light" id="birthdayList">
                                <form method="GET">
                                    <input type="hidden" name="report_type" value="birthday_list">
                                    <label class="form-label small">Select Month</label>
                                    <select class="form-select form-select-sm mb-2" name="month">
                                        <?php for ($m = 1; $m <= 12; $m++): ?>
                                            <option value="<?php echo $m; ?>" <?php echo $m == date('m') ? 'selected' : ''; ?>>
                                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        Generate
                                    </button>
                                </form>
                            </div>
                            
                            <a href="#membershipGrowth" class="list-group-item list-group-item-action" 
                               data-bs-toggle="collapse">
                                <i class="bi bi-graph-up me-2"></i>Membership Growth
                            </a>
                            <div class="collapse p-3 bg-light" id="membershipGrowth">
                                <form method="GET">
                                    <input type="hidden" name="report_type" value="membership_growth">
                                    <label class="form-label small">Select Year</label>
                                    <select class="form-select form-select-sm mb-2" name="year">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 10; $y--): ?>
                                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        Generate
                                    </button>
                                </form>
                            </div>
                            
                            <a href="#ministryParticipation" class="list-group-item list-group-item-action" 
                               data-bs-toggle="collapse">
                                <i class="bi bi-people me-2"></i>Ministry Participation
                            </a>
                            <div class="collapse p-3 bg-light" id="ministryParticipation">
                                <form method="GET">
                                    <input type="hidden" name="report_type" value="ministry_participation">
                                    <label class="form-label small">Ministry</label>
                                    <select class="form-select form-select-sm mb-2" name="ministry_id" required>
                                        <option value="">Select...</option>
                                        <?php 
                                        mysqli_data_seek($ministries, 0);
                                        while ($m = mysqli_fetch_assoc($ministries)): 
                                        ?>
                                            <option value="<?php echo $m['id']; ?>">
                                                <?php echo htmlspecialchars($m['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <label class="form-label small">Start Date</label>
                                    <input type="date" class="form-control form-control-sm mb-2" name="start_date">
                                    <label class="form-label small">End Date</label>
                                    <input type="date" class="form-control form-control-sm mb-2" name="end_date">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        Generate
                                    </button>
                                </form>
                            </div>
                            
                            <a href="?report_type=member_demographics" class="list-group-item list-group-item-action">
                                <i class="bi bi-bar-chart me-2"></i>Member Demographics
                            </a>
                            
                            <a href="#anniversaries" class="list-group-item list-group-item-action" 
                               data-bs-toggle="collapse">
                                <i class="bi bi-heart me-2"></i>Upcoming Anniversaries
                            </a>
                            <div class="collapse p-3 bg-light" id="anniversaries">
                                <form method="GET">
                                    <input type="hidden" name="report_type" value="upcoming_anniversaries">
                                    <label class="form-label small">Next X Weeks</label>
                                    <input type="number" class="form-control form-control-sm mb-2" 
                                           name="weeks" value="4" min="1" max="52">
                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                        Generate
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Display -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <?php 
                                $report_titles = [
                                    'monthly_attendance' => 'Monthly Attendance Summary',
                                    'birthday_list' => 'Birthday List',
                                    'membership_growth' => 'Membership Growth Report',
                                    'ministry_participation' => 'Ministry Participation Report',
                                    'member_demographics' => 'Member Demographics',
                                    'upcoming_anniversaries' => 'Upcoming Anniversaries'
                                ];
                                echo $report_type ? $report_titles[$report_type] : 'Select a report to view';
                                ?>
                            </h6>
                            <?php if ($report_type): ?>
                                <button onclick="window.print()" class="btn btn-sm btn-primary">
                                    <i class="bi bi-printer me-1"></i>Print
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (!$report_type): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-file-earmark-text display-1"></i>
                                    <p class="mt-3">Please select a report type from the left panel</p>
                                </div>
                            <?php elseif ($report_data && mysqli_num_rows($report_data) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-light">
                                            <?php if ($report_type == 'monthly_attendance'): ?>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Total Attendance</th>
                                                    <th>Services</th>
                                                </tr>
                                            <?php elseif ($report_type == 'birthday_list'): ?>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Date of Birth</th>
                                                    <th>Contact</th>
                                                    <th>Email</th>
                                                </tr>
                                            <?php elseif ($report_type == 'membership_growth'): ?>
                                                <tr>
                                                    <th>Month</th>
                                                    <th>New Members</th>
                                                </tr>
                                            <?php elseif ($report_type == 'ministry_participation'): ?>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Ministry</th>
                                                    <th>Service Type</th>
                                                    <th>Attendance</th>
                                                </tr>
                                            <?php elseif ($report_type == 'member_demographics'): ?>
                                                <tr>
                                                    <th>Status</th>
                                                    <th>Gender</th>
                                                    <th>Count</th>
                                                </tr>
                                            <?php elseif ($report_type == 'upcoming_anniversaries'): ?>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Member</th>
                                                    <th>Contact</th>
                                                    <th>Notes</th>
                                                </tr>
                                            <?php endif; ?>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($report_data)): ?>
                                                <tr>
                                                    <?php if ($report_type == 'monthly_attendance'): ?>
                                                        <td><?php echo date('M d, Y', strtotime($row['attendance_date'])); ?></td>
                                                        <td><strong><?php echo number_format($row['total']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($row['services']); ?></td>
                                                    <?php elseif ($report_type == 'birthday_list'): ?>
                                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                                        <td><?php echo date('F d', strtotime($row['dob'])); ?></td>
                                                        <td><?php echo htmlspecialchars($row['contact_home']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                    <?php elseif ($report_type == 'membership_growth'): ?>
                                                        <td><?php echo htmlspecialchars($row['month_name']); ?></td>
                                                        <td><strong><?php echo $row['new_members']; ?></strong></td>
                                                    <?php elseif ($report_type == 'ministry_participation'): ?>
                                                        <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($row['ministry_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['service_type']); ?></td>
                                                        <td><strong><?php echo $row['count']; ?></strong></td>
                                                    <?php elseif ($report_type == 'member_demographics'): ?>
                                                        <td><span class="badge bg-secondary"><?php echo ucfirst($row['status']); ?></span></td>
                                                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                                        <td><strong><?php echo $row['count']; ?></strong></td>
                                                    <?php elseif ($report_type == 'upcoming_anniversaries'): ?>
                                                        <td><?php echo date('M d, Y', strtotime($row['event_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['contact_home']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['notes']); ?></td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>No data available for this report.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>