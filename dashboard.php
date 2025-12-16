<?php
session_start();
require_once 'db_connect.php';

if (!is_logged_in()) {
    redirect('login.php');
}

// Get dashboard statistics
$stats = [];

// Total members
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM members WHERE passing_date IS NULL");
$stats['total_members'] = mysqli_fetch_assoc($result)['total'];

// Active ministries
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM ministries");
$stats['active_ministries'] = mysqli_fetch_assoc($result)['total'];

// This week's birthdays
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM members 
                               WHERE WEEK(dob, 1) = WEEK(CURDATE(), 1) 
                               AND passing_date IS NULL");
$stats['birthdays_this_week'] = mysqli_fetch_assoc($result)['total'];

// Recent attendance (last Sunday)
$result = mysqli_query($conn, "SELECT SUM(count) as total FROM attendance 
                               WHERE YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)");
$attendance_row = mysqli_fetch_assoc($result);
$stats['recent_attendance'] = $attendance_row['total'] ?? 0;

// Get upcoming birthdays
$upcoming_birthdays = mysqli_query($conn, 
    "SELECT first_name, last_name, dob FROM members 
     WHERE WEEK(dob, 1) = WEEK(CURDATE(), 1) AND passing_date IS NULL 
     ORDER BY DAY(dob) LIMIT 5");

// Get recent events
$recent_events = mysqli_query($conn,
    "SELECT e.*, m.first_name, m.last_name FROM events e
     LEFT JOIN members m ON e.member_id = m.mem_id
     ORDER BY e.event_date DESC LIMIT 5");

// Get attendance trend (last 6 weeks)
$attendance_trend = mysqli_query($conn,
    "SELECT WEEK(date, 1) as week, YEAR(date) as year, SUM(count) as total
     FROM attendance
     WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 WEEK)
     GROUP BY YEAR(date), WEEK(date, 1)
     ORDER BY year, week");

$trend_data = [];
while ($row = mysqli_fetch_assoc($attendance_trend)) {
    $trend_data[] = $row;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-calendar3 me-1"></i><?php echo date('F d, Y'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Members
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($stats['total_members']); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people-fill fs-2 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Active Ministries
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['active_ministries']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-grid-3x3-gap-fill fs-2 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Birthdays This Week
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['birthdays_this_week']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-cake2-fill fs-2 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        This Week's Attendance
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($stats['recent_attendance']); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-check-fill fs-2 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables Row -->
            <div class="row">
                <!-- Attendance Trend Chart -->
                <div class="col-xl-8 col-lg-7 mb-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-graph-up me-2"></i>Attendance Trend (Last 6 Weeks)
                            </h6>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Birthdays -->
                <div class="col-xl-4 col-lg-5 mb-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-cake2 me-2"></i>Upcoming Birthdays
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($upcoming_birthdays) > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php while ($birthday = mysqli_fetch_assoc($upcoming_birthdays)): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="bi bi-gift text-danger me-2"></i>
                                                <?php echo htmlspecialchars($birthday['first_name'] . ' ' . $birthday['last_name']); ?>
                                            </span>
                                            <span class="badge bg-primary rounded-pill">
                                                <?php echo date('M d', strtotime($birthday['dob'])); ?>
                                            </span>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted text-center">No upcoming birthdays this week</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Events -->
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-calendar-event me-2"></i>Recent Events
                            </h6>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($recent_events) > 0): ?>
                                            <?php while ($event = mysqli_fetch_assoc($recent_events)): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-info">
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
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No recent events</td>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Attendance Trend Chart
const ctx = document.getElementById('attendanceChart').getContext('2d');
const attendanceData = <?php echo json_encode($trend_data); ?>;

const labels = attendanceData.map(d => `Week ${d.week}`);
const data = attendanceData.map(d => d.total);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Attendance',
            data: data,
            borderColor: 'rgb(102, 126, 234)',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
.border-left-success {
    border-left: 4px solid #1cc88a !important;
}
.border-left-info {
    border-left: 4px solid #36b9cc !important;
}
.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}
.text-xs {
    font-size: 0.75rem;
}
</style>

<?php include 'includes/footer.php'; ?>