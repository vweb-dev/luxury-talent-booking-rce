# Database Setup Guide - Luxury Talent Booking RCE

This guide provides detailed instructions for setting up the MySQL database for Luxury Talent Booking — Red Carpet Edition.

## Prerequisites

- MySQL 5.7+ or MariaDB 10.3+
- Database administration access (phpMyAdmin, MySQL Workbench, or command line)
- Basic understanding of SQL and database operations

## Method 1: Automatic Setup (Recommended)

The easiest way to set up the database is through the built-in setup wizard:

1. **Access Setup Wizard**
   ```
   https://yourdomain.com/setup
   ```

2. **Follow Setup Steps**
   - Enter database connection details
   - The wizard will automatically import schema and seed data
   - Complete system configuration

3. **Verify Installation**
   - Check that all tables are created
   - Verify demo data is populated
   - Test login with demo credentials

## Method 2: Manual Database Setup

If you need to set up the database manually or troubleshoot issues:

### Step 1: Create Database and User

#### Using cPanel MySQL Databases
1. Log into cPanel
2. Go to "MySQL® Databases"
3. Create a new database (e.g., `username_rce`)
4. Create a new user with a strong password
5. Add the user to the database with ALL PRIVILEGES

#### Using phpMyAdmin
1. Log into phpMyAdmin
2. Click "Databases" tab
3. Enter database name and click "Create"
4. Go to "User accounts" tab
5. Click "Add user account"
6. Set username, password, and grant ALL PRIVILEGES

#### Using MySQL Command Line
```sql
-- Create database
CREATE DATABASE rce_talent_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace 'username' and 'password')
CREATE USER 'rce_user'@'localhost' IDENTIFIED BY 'secure_password_here';

-- Grant privileges
GRANT ALL PRIVILEGES ON rce_talent_booking.* TO 'rce_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 2: Import Database Schema

#### Using phpMyAdmin
1. Select your database from the left sidebar
2. Click the "Import" tab
3. Click "Choose File" and select `/db/schema.sql`
4. Ensure format is set to "SQL"
5. Click "Go" to import

#### Using MySQL Command Line
```bash
# Navigate to your application directory
cd /path/to/your/rce/installation

# Import schema
mysql -u rce_user -p rce_talent_booking < db/schema.sql
```

### Step 3: Import Seed Data (Optional)

The seed data includes demo users, sample content, and initial configuration:

#### Using phpMyAdmin
1. With your database selected, click "Import" tab
2. Choose the `/db/seeds.sql` file
3. Click "Go" to import

#### Using MySQL Command Line
```bash
# Import seed data
mysql -u rce_user -p rce_talent_booking < db/seeds.sql
```

### Step 4: Verify Database Structure

After importing, your database should contain these tables:

#### Core Tables
- `roles` - User role definitions
- `users` - User accounts
- `companies` - Company/tenant information
- `talent_profiles` - Talent profile data
- `talent_media` - Media files and metadata

#### Content Tables
- `media_approvals` - Media approval workflow
- `status_posts` - Temporary status content
- `bookings` - Booking records
- `payments` - Payment transactions (stub)

#### Broadcast System
- `event_broadcasts` - Event broadcast definitions
- `event_targets` - Targeted talent for broadcasts
- `event_responses` - Talent responses to broadcasts

#### Utility Tables
- `client_shortlist` - Client talent shortlists
- `settings` - Application settings
- `audit_logs` - System audit trail

#### Verification Query
```sql
-- Check that all tables exist
SHOW TABLES;

-- Verify sample data
SELECT COUNT(*) as user_count FROM users;
SELECT COUNT(*) as talent_count FROM talent_profiles;
SELECT COUNT(*) as media_count FROM talent_media;
```

## Database Configuration

### Connection Settings

Create `/config/database.php` with your database details:

```php
<?php

return [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'your_database_name',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset' => 'utf8mb4',
    'timezone' => '+00:00'
];
```

### Performance Optimization

#### Recommended MySQL Settings
```ini
# Add to my.cnf or my.ini
[mysqld]
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
max_connections = 200
query_cache_size = 32M
tmp_table_size = 64M
max_heap_table_size = 64M
```

#### Index Optimization
The schema includes optimized indexes for:
- Geographic queries (latitude/longitude)
- Search filters (age, height, hair color, eye color)
- Performance-critical lookups
- Foreign key relationships

## Troubleshooting

### Common Database Issues

#### Connection Failed
```
Error: Database connection failed
```
**Solutions:**
- Verify database credentials in `/config/database.php`
- Check that MySQL service is running
- Ensure database user has proper privileges
- Test connection with MySQL client

#### Import Errors
```
Error: SQL syntax error during import
```
**Solutions:**
- Ensure MySQL version is 5.7+ or MariaDB 10.3+
- Check that database charset is utf8mb4
- Verify SQL file is not corrupted
- Import schema before seeds

#### Permission Denied
```
Error: Access denied for user
```
**Solutions:**
- Grant ALL PRIVILEGES to database user
- Check user exists and password is correct
- Verify host permissions (localhost vs %)

#### Table Already Exists
```
Error: Table 'users' already exists
```
**Solutions:**
- Drop existing tables or use fresh database
- Use `DROP DATABASE` and recreate if needed
- Check for partial imports

### Database Maintenance

#### Regular Backups
```bash
# Create backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# Restore from backup
mysql -u username -p database_name < backup_file.sql
```

#### Cleanup Old Data
```sql
-- Remove old audit logs (older than 90 days)
DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Remove expired status posts
DELETE FROM status_posts WHERE expires_at < NOW() - INTERVAL 7 DAY;

-- Remove old inactive broadcasts
DELETE FROM event_broadcasts 
WHERE status = 'expired' 
AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

#### Performance Monitoring
```sql
-- Check table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'your_database_name'
ORDER BY (data_length + index_length) DESC;

-- Check slow queries
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Slow_queries';
```

## Security Considerations

### Database Security
- Use strong passwords for database users
- Limit database user privileges to necessary operations only
- Enable SSL connections if available
- Regular security updates for MySQL/MariaDB

### Backup Strategy
- Daily automated backups
- Store backups in secure, separate location
- Test backup restoration regularly
- Include both schema and data in backups

### Access Control
- Restrict database access to application server only
- Use firewall rules to limit database port access
- Monitor database access logs
- Regular audit of user accounts and privileges

## Advanced Configuration

### Replication Setup
For high-availability deployments:

```sql
-- Master configuration
[mysqld]
server-id = 1
log-bin = mysql-bin
binlog-format = ROW

-- Slave configuration
[mysqld]
server-id = 2
relay-log = relay-log
read-only = 1
```

### Partitioning Large Tables
For high-volume installations:

```sql
-- Partition audit_logs by date
ALTER TABLE audit_logs 
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

## Support

### Getting Help
- Check application logs in `/logs/` directory
- Review MySQL error logs
- Verify PHP PDO extension is installed
- Test database connection independently

### Useful Commands
```bash
# Check MySQL version
mysql --version

# Test connection
mysql -u username -p -h hostname database_name

# Show database size
mysql -u username -p -e "
SELECT 
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' 
FROM information_schema.tables 
WHERE table_schema='database_name';"
```

---

For additional support, refer to the main README.md file or check the application logs for specific error messages.
