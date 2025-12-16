# Church Management Information System (CMIS)

## Overview
A comprehensive web-based Church Management System built with PHP, MySQL, and Bootstrap 5. This system digitizes church record-keeping, automates reports, and provides role-based access for different ministry users.

## Features

### Core Functionality
- **Membership Management**: Register, update, and deactivate members with complete contact and spiritual information
- **Attendance Tracking**: Record attendance for services, ministries, and Sunday School
- **Ministry Management**: Create and manage ministry groups with member assignments
- **Event Management**: Track weddings, baptisms, birthdays, anniversaries, and other events
- **Comprehensive Reporting**: Generate various reports including attendance summaries, birthday lists, and growth reports
- **Role-Based Access Control**: Five user roles with specific permissions
- **Interactive Dashboard**: Visual statistics with charts and quick alerts

### Security Features
- Password hashing (PHP password_hash)
- Role-based access control
- SQL injection prevention (prepared statements)
- Session management
- CSRF protection through form validation

## Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5, Chart.js
- **Backend**: PHP 8+
- **Database**: MySQL/MariaDB
- **Server**: XAMPP (Apache + MySQL)

## Installation Instructions

### Prerequisites
1. **XAMPP** installed on your system
   - Download from: https://www.apachefriends.org/
   - Install with Apache and MySQL components

### Step 1: Download and Extract Files
1. Download all project files
2. Extract to your XAMPP `htdocs` directory
3. Rename the folder to `church_cmis` (or your preferred name)
   - Path should be: `C:\xampp\htdocs\church_cmis\` (Windows)
   - Or: `/Applications/XAMPP/htdocs/church_cmis/` (Mac)

### Step 2: Database Setup
1. Start XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Open your browser and go to: `http://localhost/phpmyadmin`
4. Create a new database:
   - Click "New" in the left sidebar
   - Database name: `church_cmis`
   - Collation: `utf8mb4_general_ci`
   - Click "Create"
5. Import the database structure:
   - Click on the `church_cmis` database
   - Click "Import" tab
   - Choose the `CMIS_Database_Schema.sql` file
   - Click "Go" to execute

### Step 3: Configure Database Connection
The `db_connect.php` file is already configured with default XAMPP settings:
```php
$dbserver = "localhost";
$user = "root";
$pass = "";
$dbas = "church_cmis";
```

If you have custom MySQL settings, update these values accordingly.

### Step 4: Access the System
1. Open your browser
2. Navigate to: `http://localhost/church_cmis/login.php`
3. Login with default administrator credentials:
   - **Username**: `admin`
   - **Password**: `admin123`

### Step 5: Change Default Password (Important!)
1. After first login, go to Users Management
2. Edit the admin user
3. Set a strong new password
4. Save changes

## File Structure
```
church_cmis/
├── db_connect.php          # Database connection
├── login.php               # Login page
├── logout.php              # Logout script
├── dashboard.php           # Main dashboard
├── members.php             # Member management
├── attendance.php          # Attendance tracking
├── ministries.php          # Ministry management
├── get_ministry_members.php # AJAX helper for ministries
├── events.php              # Event management
├── reports.php             # Report generation
├── users.php               # User management (Admin only)
├── includes/
│   ├── header.php          # Header template
│   ├── sidebar.php         # Navigation sidebar
│   └── footer.php          # Footer template
└── CMIS_Database_Schema.sql # Database structure
```

## User Roles & Permissions

### 1. Administrator
- Full system access
- Manage all users
- Generate all reports
- Access all modules

### 2. Pastor/Clergy
- View and edit membership
- View attendance records
- Generate member reports
- Manage events

### 3. Ministry Leader
- Update group attendance
- View only their ministry's records
- Enter attendance for meetings

### 4. Clerk/Secretary
- Enter member data
- Manage events
- Generate standard reports

### 5. Member
- Update limited personal information
- View own profile

## Main Features Guide

### Dashboard
- View total members, active ministries, and attendance statistics
- Interactive attendance trend chart
- Upcoming birthdays widget
- Recent events list

### Members Management
- Add new members with complete information
- Edit existing member records
- Search and filter members
- Track spiritual status (Member/Adherent/Visitor)
- Store next of kin information

### Attendance Tracking
- Record service attendance
- Track ministry meeting attendance
- Sunday School attendance by age groups
- View attendance summaries and trends

### Ministry Management
- Create and manage ministry groups
- Assign members to ministries
- Track member roles in ministries
- View ministry participation

### Events Management
- Record various event types (Wedding, Birthday, Anniversary, Baptism, Death)
- Link events to members
- Filter by event type and date
- Add notes for each event

### Reports
1. **Monthly Attendance Summary**: View attendance trends by month
2. **Birthday List**: Generate lists by month
3. **Membership Growth**: Track new members over time
4. **Ministry Participation**: Analyze ministry attendance
5. **Member Demographics**: View membership distribution
6. **Upcoming Anniversaries**: Plan for upcoming events

## Default Sample Data
The system includes:
- 1 Administrator account (admin/admin123)
- 6 Sample ministries (Senior Choir, Youth Ministry, Ushering Team, etc.)
- 5 User roles with descriptions

## Security Best Practices
1. **Change default admin password immediately**
2. **Create separate users** for each staff member
3. **Assign appropriate roles** based on responsibilities
4. **Regularly backup** the database
5. **Keep PHP and MySQL updated**
6. **Use HTTPS** in production environments

## Database Backup
### Manual Backup
1. Go to phpMyAdmin
2. Select `church_cmis` database
3. Click "Export" tab
4. Choose "Quick" export method
5. Click "Go" to download SQL file

### Restore from Backup
1. Go to phpMyAdmin
2. Select `church_cmis` database
3. Click "Import" tab
4. Choose your backup SQL file
5. Click "Go"

## Troubleshooting

### Cannot Connect to Database
- Ensure MySQL is running in XAMPP
- Verify database name is `church_cmis`
- Check credentials in `db_connect.php`

### Login Issues
- Verify you're using correct credentials
- Check if user status is "active"
- Clear browser cache and cookies

### Pages Not Loading
- Ensure Apache is running
- Check file paths are correct
- Verify PHP is enabled

### Permission Denied Errors
- Check user role has access to the module
- Verify session is active
- Re-login if necessary

## System Requirements
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.3+
- **Apache**: 2.4 or higher
- **Web Browser**: Modern browser (Chrome, Firefox, Safari, Edge)
- **RAM**: Minimum 512MB
- **Storage**: Minimum 100MB

## Development Notes
- Uses prepared statements for SQL queries
- Bootstrap 5 for responsive design
- Chart.js for data visualization
- Session-based authentication
- AJAX for dynamic content loading

## Support & Maintenance
- Regularly update member records
- Monitor attendance data
- Generate monthly reports
- Backup database weekly
- Review user accounts quarterly

## License
This project is developed for educational purposes as part of the Web System Analysis and Design course.

## Credits
- Bootstrap 5 Framework
- Bootstrap Icons
- Chart.js Library
- PHP Community

---

**Version**: 1.0  
**Last Updated**: December 2025  
**Developed By**: WSAD Group 3 

For questions or support, contact your system administrator.