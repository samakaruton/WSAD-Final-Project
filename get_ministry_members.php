<?php
session_start();
require_once 'db_connect.php';

if (!is_logged_in()) {
    exit('Unauthorized');
}

$ministry_id = isset($_GET['ministry_id']) ? (int)$_GET['ministry_id'] : 0;

$query = "SELECT mm.id, mm.role, mm.date_joined, m.mem_id, m.first_name, m.last_name, m.contact_home 
          FROM ministry_members mm 
          JOIN members m ON mm.member_id = m.mem_id 
          WHERE mm.ministry_id = ? 
          ORDER BY m.last_name, m.first_name";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $ministry_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0):
?>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Date Joined</th>
                    <th>Contact</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($member = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($member['role'] ?: 'Member'); ?></td>
                        <td><?php echo $member['date_joined'] ? date('M d, Y', strtotime($member['date_joined'])) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($member['contact_home']); ?></td>
                        <td>
                            <form method="POST" action="ministries.php" style="display:inline;" onsubmit="return confirm('Remove this member?')">
                                <input type="hidden" name="action" value="remove_member">
                                <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-muted text-center">No members assigned to this ministry yet.</p>
<?php endif; ?>