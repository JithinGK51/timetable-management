# Teacher Timetable Management System (TTMS)

A comprehensive web-based application for managing teacher timetables in educational institutions including schools, colleges, and universities.

## Features

### Core Modules

1. **Authentication & Authorization**
   - Secure login system with password hashing
   - Role-based access control (RBAC)
   - Super Admin and Sub-admin management
   - Password visibility and management

2. **Institution Management**
   - Multi-institution support
   - Institution profile management
   - Working days configuration

3. **Academic Structure**
   - **Departments**: Organize by departments
   - **Classes**: Manage different classes/grades
   - **Sections**: Handle multiple sections per class
   - **Subjects**: Subject management with weekly hours

4. **Teacher Management**
   - Teacher profiles with employee IDs
   - Department assignments
   - Subject assignments
   - Workload tracking (daily/weekly limits)
   - Availability management

5. **Time Slot Configuration**
   - Customizable period durations
   - Break and lunch slot management
   - Institution-specific time schedules

6. **Timetable Management**
   - **Automatic Generation**: AI-powered timetable generation with conflict detection
   - **Manual Editing**: Click-to-edit interface with modal forms
   - **Grid View**: Visual timetable representation
   - **Conflict Detection**: Prevents teacher double-booking
   - **Event/Holiday Support**: Mark special events and holidays
   - **Export**: PDF export functionality

7. **Events & Holidays**
   - Create and manage academic events
   - Holiday tracking
   - Event types: Event, Holiday, Exam, Other
   - Institution-specific or global events

8. **Role Management**
   - Custom role creation
   - Permission matrix (View, Create, Edit, Delete, Export, Publish)
   - Module-wise permission control

9. **Sub-Admin Management**
   - Create sub-administrators
   - Assign roles and permissions
   - Password management with visibility
   - Status management (Active/Inactive)

10. **Reports & Analytics**
    - Teacher workload reports
    - Timetable statistics
    - Institution-wise reports

## Technology Stack

- **Backend**: PHP 8.x (Native, no framework)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Icons**: Font Awesome 6.4.0
- **Server**: Apache (XAMPP/WAMP)

## Installation

### Prerequisites
- XAMPP/WAMP/MAMP installed
- PHP 8.0 or higher
- MySQL 5.7 or higher

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/JithinGK51/timetable-management.git
   cd timetable-management
   ```

2. **Move to htdocs**
   ```bash
   # Windows (XAMPP)
   Copy project folder to C:\xampp\htdocs\ttc
   
   # Linux/Mac
   sudo cp -r timetable-management /var/www/html/ttc
   ```

3. **Database Setup**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create database: `ttc_system`
   - Import `database/schema.sql`

4. **Configuration**
   - Edit `config/database.php`
   - Update database credentials if needed:
     ```php
     'host' => '127.0.0.1',
     'port' => 3306,
     'database' => 'ttc_system',
     'username' => 'root',
     'password' => ''
     ```

5. **Run Setup Script**
   - Visit: http://localhost/ttc/setup_db.php
   - This adds required columns for passwords and events

6. **Access Application**
   - URL: http://localhost/ttc
   - Default Login:
     - Username: `admin`
     - Password: `admin123`

## Default Credentials

| Role | Username | Password |
|------|----------|----------|
| Super Admin | admin | admin123 |
| Timetable Manager | manager1 | admin123 |
| Teacher Coordinator | teacher_mgr | admin123 |
| Report Viewer | viewer1 | admin123 |

## Directory Structure

```
ttc/
├── api/                    # AJAX API endpoints
│   ├── get_classes.php
│   ├── get_departments.php
│   ├── get_sections.php
│   ├── get_subjects.php
│   └── timetable_generate.php
├── assets/
│   ├── css/style.css      # Main stylesheet
│   └── js/main.js         # JavaScript utilities
├── config/
│   └── database.php       # Database configuration
├── database/
│   ├── schema.sql         # Database schema
│   └── orig.sql          # Original schema backup
├── includes/
│   ├── auth_check.php     # Authentication middleware
│   ├── footer.php         # Common footer
│   ├── functions.php      # Helper functions
│   └── header.php         # Common header
├── modules/               # Feature modules
│   ├── auth/             # Login/logout
│   ├── class/            # Class management
│   ├── dashboard/        # Dashboard
│   ├── event/            # Events & holidays
│   ├── export/           # PDF export
│   ├── institution/      # Institution management
│   ├── role/             # Role management
│   ├── section/          # Section management
│   ├── settings/         # System settings
│   ├── subadmin/         # Sub-admin management
│   ├── subject/          # Subject management
│   ├── teacher/          # Teacher management
│   ├── timeslot/         # Time slot configuration
│   └── timetable/        # Timetable operations
├── index.php             # Entry point
├── setup_db.php          # Database setup script
└── README.md             # This file
```

## Key Features Explained

### Timetable Generation Algorithm
- Automatically assigns subjects to time slots
- Respects teacher availability
- Prevents conflicts (same teacher, same time)
- Balances teacher workload
- Considers subject weekly hours requirement

### Event/Holiday Management
- Create events in Events & Holidays module
- Select events when editing timetable slots
- Events display in yellow on the timetable grid
- Supports: Events, Holidays, Exams, Other

### Permission System
- Granular permissions per module
- Permissions: View, Create, Edit, Delete, Export, Publish
- Assign different roles to sub-admins
- Super Admin has full access

### Password Management
- Passwords stored with bcrypt hashing
- Plain password storage for admin visibility
- One-click password reveal
- Password change functionality

## Browser Compatibility
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Security Features
- Password hashing (bcrypt)
- SQL injection prevention (PDO prepared statements)
- XSS protection (htmlspecialchars)
- CSRF protection ready
- Session-based authentication
- Role-based access control

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check `config/database.php` credentials
   - Ensure MySQL is running
   - Verify database exists

2. **Login Issues**
   - Run `setup_db.php` to ensure columns exist
   - Check admin credentials in database
   - Use `reset_admin.php` to reset admin password

3. **Timetable Generation Fails**
   - Check teacher availability data
   - Verify subject assignments
   - Ensure enough time slots for weekly hours

4. **Events Not Showing**
   - Create events in Events & Holidays module
   - Check institution matching
   - Verify database columns with `setup_db.php`

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License.

## Support

For support, email support@ttc.com or create an issue in the repository.

## Acknowledgments

- Font Awesome for icons
- XAMPP for local development environment
- MySQL for database management

---

**Developed with ❤️ for Educational Institutions**
