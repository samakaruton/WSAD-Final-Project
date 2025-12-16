<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="sidebar-sticky pt-3">
        <div class="text-center mb-3">
            <div class="text-white">
                <i class="bi bi-person-circle fs-1"></i>
                <p class="mb-0 mt-2 small"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                <small class="text-white-50"><?php echo htmlspecialchars($_SESSION['role_name']); ?></small>
            </div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>Dashboard
                </a>
            </li>
            
            <?php if (has_role(['Administrator', 'Pastor/Clergy', 'Clerk/Secretary'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : ''; ?>" 
                   href="members.php">
                    <i class="bi bi-people"></i>Members
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (has_role(['Administrator', 'Pastor/Clergy', 'Ministry Leader', 'Clerk/Secretary'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>" 
                   href="attendance.php">
                    <i class="bi bi-person-check"></i>Attendance
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (has_role(['Administrator', 'Pastor/Clergy', 'Ministry Leader'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ministries.php' ? 'active' : ''; ?>" 
                   href="ministries.php">
                    <i class="bi bi-grid-3x3-gap"></i>Ministries
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (has_role(['Administrator', 'Pastor/Clergy', 'Clerk/Secretary'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>" 
                   href="events.php">
                    <i class="bi bi-calendar-event"></i>Events
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (has_role(['Administrator', 'Pastor/Clergy', 'Clerk/Secretary'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" 
                   href="reports.php">
                    <i class="bi bi-file-earmark-bar-graph"></i>Reports
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (has_role(['Administrator'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" 
                   href="users.php">
                    <i class="bi bi-person-gear"></i>Users
                </a>
            </li>
            <?php endif; ?>
            
            <?php if (has_role(['Member'])): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" 
                   href="profile.php">
                    <i class="bi bi-person-badge"></i>My Profile
                </a>
            </li>
            <?php endif; ?>
        </ul>
        
        <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
        
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>Logout
                </a>
            </li>
        </ul>
    </div>
</nav>