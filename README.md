# Voting System - XAMPP Edition

A modern PHP voting system with a clean neo-brutalist design, built to run on XAMPP with MySQL.

## Features

- ✅ User Authentication (Register/Login)
- ✅ Role-based Access (Admin/Client)
- ✅ Create and manage polls (Admin only)
- ✅ Vote on active polls (Client)
- ✅ Real-time results display
- ✅ Modern neo-brutalist UI design
- ✅ Secure password hashing
- ✅ Vote tracking (one vote per user per poll)
- ✅ MySQL database backend

## Requirements

- XAMPP (or any LAMP/WAMP stack)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- PDO PHP Extension (usually included with PHP)

## Installation Instructions

### Step 1: Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** server
3. Start **MySQL** server

### Step 2: Create Database

1. Open phpMyAdmin by visiting: `http://localhost/phpmyadmin`
2. Click on "SQL" tab
3. Copy and paste the contents of `database/schema.sql`
4. Click "Go" to execute the SQL

Alternatively, you can use the MySQL command line:
```bash
mysql -u root -p < database/schema.sql
```

### Step 3: Configure Database Connection (Optional)

If you have a different MySQL configuration, edit `config/config.php`:

```php
define('DB_HOST', 'localhost');     // Your MySQL host
define('DB_USER', 'root');          // Your MySQL username
define('DB_PASS', '');              // Your MySQL password
define('DB_NAME', 'voting_system'); // Database name
```

### Step 4: Access the Application

Open your browser and navigate to:
```
http://localhost/votingsystem/
```

## Default Admin Account

After running the database schema, you can login as admin:

- **Email:** admin@voting.com
- **Password:** admin123

**⚠️ Important:** Change this password after first login!

## Project Structure

```
votingsystem/
├── config/
│   └── config.php          # Database and app configuration
├── database/
│   └── schema.sql          # MySQL database schema
├── public/
│   └── style.css           # Additional styles (if needed)
├── src/
│   ├── Auth/
│   │   └── Auth.php        # Authentication logic
│   ├── Database/
│   │   └── Database.php    # Database connection & queries
│   └── Models/
│       └── Polls.php       # Poll management logic
├── templates/
│   ├── dashboard_admin.php # Admin dashboard
│   ├── dashboard_client.php# Client dashboard
│   ├── layout.php          # Main layout template
│   ├── login.php           # Login page
│   └── register.php        # Registration page
├── index.php               # Application entry point
└── README.md              # This file
```

## Usage Guide

### For Administrators

1. Login with admin credentials
2. Create new polls from the admin dashboard
3. Add candidates (one per line)
4. View real-time results

### For Voters (Clients)

1. Register a new account
2. Login with your credentials
3. View active polls
4. Cast your vote
5. You can only vote once per poll

## Features Explanation

### Authentication System
- Password hashing using PHP's `password_hash()`
- Session-based authentication
- Role-based access control

### Voting System
- One vote per user per poll
- Transaction-safe voting (prevents race conditions)
- Vote tracking in database

### Database Schema

**Tables:**
- `users` - User accounts with roles
- `polls` - Poll definitions
- `candidates` - Candidates for each poll
- `votes` - Vote tracking

## Troubleshooting

### Database Connection Error
- Make sure MySQL is running in XAMPP
- Check database credentials in `config/config.php`
- Verify database exists: `voting_system`

### Page Not Found (404)
- Ensure you're accessing: `http://localhost/votingsystem/`
- Check that files are in `C:\xampp\htdocs\votingsystem\`

### Can't Login
- Verify database schema was created correctly
- Check that admin user exists in `users` table
- Clear browser cache and try again

### Permission Errors
- Make sure XAMPP has write permissions to the directory
- Windows: Right-click folder → Properties → Security

## Development Notes

### Adding New Routes

Edit `index.php` and add a new case in the switch statement:

```php
case '/newpage':
    require __DIR__ . '/templates/newpage.php';
    break;
```

### Database Queries

Use the Database class methods:
- `fetchAll()` - Get multiple rows
- `fetchOne()` - Get single row
- `insert()` - Insert and get ID
- `update()` - Update rows
- `delete()` - Delete rows

### Creating New Models

Follow the pattern in `src/Models/Polls.php`:

```php
namespace Src\Models;
use Src\Database\Database;

class YourModel {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Your methods here
}
```

## Security Considerations

- ✅ SQL Injection prevention (prepared statements)
- ✅ Password hashing
- ✅ Session-based authentication
- ✅ Input validation
- ✅ XSS prevention (htmlspecialchars)

## Credits

Built with PHP, MySQL, and modern web standards.
Neo-brutalist design inspired by contemporary web design trends.

## License

Free to use for educational purposes.
