# Luxury Talent Booking — Red Carpet Edition (RCE)

**Reels-first talent discovery + broadcast bookings.** Single-ZIP deploy on cPanel.

## Overview

Luxury Talent Booking — Red Carpet Edition is a comprehensive Progressive Web Application (PWA) designed for the entertainment industry. It provides a reels-first approach to talent discovery, allowing clients to find and book talent through immersive 9:16 video content.

## Features

### Core Features
- **9:16 Reels Across All Media** - Strict aspect ratio enforcement for consistent viewing experience
- **Event Broadcasts** - Geo-targeted talent discovery with profile filtering
- **Multi-Role Platform** - Super Admin, Tenant Admin, Talent, and Client roles
- **PWA Support** - Installable web app with offline capabilities
- **Media Approval Workflow** - Content moderation with approval gates
- **Auto-Expiring Status Posts** - 24-hour story-style content

### Business Model
- **Per-Company Installs** - SaaS-style deployment, not multi-tenant
- **Tiered Plans** - Basic and Elite tiers with different feature sets
- **Commission + Pay-Per-Action** - Flexible monetization options
- **Settings Scaffolds** - Ready for payment processor integration

### Technical Features
- **PHP 8.x + MySQL** - Modern server-side stack
- **Apache .htaccess** - Clean URLs and routing
- **MediaService** - 9:16 validator with optional ffmpeg normalization
- **Cron Jobs** - Automated cleanup and maintenance
- **Security** - Hidden admin URLs, approval gates, privacy controls

## Quick Start

### Requirements
- **Web Server**: Apache with mod_rewrite enabled
- **PHP**: Version 8.0 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Storage**: Minimum 1GB free space
- **Optional**: ffmpeg for video processing

### Installation

1. **Upload and Extract**
   ```bash
   # Upload the RCE ZIP file to your cPanel File Manager
   # Extract to your document root (public_html)
   ```

2. **Create Database**
   - Go to cPanel → MySQL® Databases
   - Create a new database
   - Create a database user with ALL PRIVILEGES
   - Note the database name, username, and password

3. **Run Setup Wizard**
   ```
   https://yourdomain.com/setup
   ```
   - Enter database credentials
   - Configure application settings
   - Create super admin account
   - Complete installation

4. **Security Cleanup**
   ```bash
   # Delete the setup directory after installation
   rm -rf /path/to/your/site/setup
   ```

## Application Routes

### Public Routes
- `/` - Public landing page
- `/explore` - Public reels teaser (read-only)
- `/login` - Authentication for all users

### Authenticated Routes
- `/saportal/login` - Hidden Super Admin login
- `/client/feed` - Client default dashboard (reels feed)
- `/talent/` - Talent dashboard
- `/admin/` - Tenant Admin dashboard
- `/saportal/` - Super Admin dashboard

### API Routes
- `/api/auth/*` - Authentication endpoints
- `/api/public/*` - Public data endpoints
- `/api/client/*` - Client-specific endpoints
- `/api/talent/*` - Talent-specific endpoints
- `/api/tenant/*` - Tenant admin endpoints
- `/api/saportal/*` - Super admin endpoints

## User Roles & Permissions

### Super Admin
- System-wide access
- Company management
- User management across all companies
- System settings and configuration
- Audit logs and analytics

### Tenant Admin
- Full company access
- User management within company
- Talent and booking management
- Company settings
- Media approvals

### Talent
- Profile and media management
- Booking management (view/respond)
- Status posts
- Broadcast responses

### Client
- Talent discovery and browsing
- Booking creation and management
- Shortlist management
- Event broadcast creation

## Demo Credentials

After setup completion, use these demo accounts:

### Super Admin Portal (`/saportal/login`)
- **Username**: `superadmin`
- **Password**: `admin123`
- **Security Token**: `rce-admin-2024`

### Tenant Admin
- **Username**: `demoadmin`
- **Password**: `demo123`

### Client Accounts
- **Username**: `eliteprod` / **Password**: `client123`
- **Username**: `fashionfw` / **Password**: `client123`

### Talent Accounts
- **Username**: `sarahchen` / **Password**: `talent123`
- **Username**: `marcusj` / **Password**: `talent123`
- **Username**: `emmar` / **Password**: `talent123`
- **Username**: `davidkim` / **Password**: `talent123`

## Cron Jobs Setup

Set up these cron jobs in cPanel for automated maintenance:

### Status Posts Cleanup (Hourly)
```bash
0 * * * * /usr/bin/php /path/to/your/site/cron/cleanup_status.php
```

### Broadcast Management (Every 15 minutes)
```bash
0,15,30,45 * * * * /usr/bin/php /path/to/your/site/cron/close_expired_broadcasts.php
```

## File Structure

```
public_html/
├── .htaccess                 # Apache rewrite rules
├── index.php                 # Public landing page
├── login.php                 # Authentication entry
├── manifest.webmanifest      # PWA manifest
├── service-worker.js         # PWA service worker
├── assets/                   # Static assets
│   ├── css/app.css          # Main stylesheet
│   ├── js/http.js           # HTTP client
│   ├── js/ui.js             # UI controller
│   └── img/logo.svg         # Application logo
├── views/                    # Page templates
│   ├── public/              # Public pages
│   ├── auth/                # Authentication pages
│   ├── client/              # Client dashboard
│   └── saportal/            # Super admin pages
├── api/                      # API endpoints
│   ├── auth/                # Authentication APIs
│   ├── public/              # Public APIs
│   ├── client/              # Client APIs
│   ├── talent/              # Talent APIs
│   ├── tenant/              # Tenant APIs
│   └── saportal/            # Super admin APIs
├── models/                   # Data models
│   ├── DB.php               # Database connection
│   └── User.php             # User model
├── controllers/              # Business logic
│   └── MediaService.php     # Media processing
├── config/                   # Configuration (created by setup)
│   ├── config.php           # App configuration
│   ├── database.php         # Database settings
│   └── security.php         # Security settings
├── db/                       # Database files
│   ├── schema.sql           # Database schema
│   └── seeds.sql            # Sample data
├── cron/                     # Cron job scripts
│   ├── cleanup_status.php   # Status cleanup
│   └── close_expired_broadcasts.php # Broadcast management
├── uploads/                  # Media uploads (writable)
│   ├── photos/
│   ├── videos/
│   ├── status/
│   └── norm/
└── logs/                     # Application logs (created by setup)
```

## Configuration

### Environment Variables
Configuration is stored in `/config/` files created during setup:

- `config.php` - Application settings
- `database.php` - Database connection
- `security.php` - Security keys and session settings

### Media Settings
- **Aspect Ratio**: Strict 9:16 enforcement
- **Max File Size**: 100MB for videos, 10MB for images
- **Supported Formats**: JPEG, PNG, WebP, MP4, WebM, MOV
- **Normalization**: Optional ffmpeg processing to 1080×1920

### Storage Options
- **Default**: cPanel filesystem (`/uploads/`)
- **Optional**: Cloudflare R2 (configuration scaffold included)

## Security Features

- **Hidden Super Admin URL** - `/saportal/login` not publicly linked
- **Security Token** - Additional authentication for super admin
- **Session Management** - Secure session handling with timeouts
- **Input Validation** - Comprehensive input sanitization
- **File Upload Security** - Media type validation and processing
- **Privacy Controls** - Public/Partial/Private profile settings
- **Audit Logging** - Complete action tracking

## Troubleshooting

### Common Issues

**Setup fails with database connection error**
- Verify database credentials
- Ensure database exists and user has ALL PRIVILEGES
- Check MySQL/MariaDB version compatibility

**Media uploads fail**
- Check `/uploads/` directory permissions (755 or 777)
- Verify PHP upload limits in php.ini
- Ensure sufficient disk space

**Rewrite rules not working**
- Verify Apache mod_rewrite is enabled
- Check .htaccess file permissions
- Ensure AllowOverride is enabled in Apache config

**Cron jobs not running**
- Verify PHP CLI path (`which php`)
- Check file permissions on cron scripts (755)
- Review cron logs for errors

### Log Files
- Application logs: `/logs/`
- Cron logs: `/logs/cron_*.log`
- Super admin access: `/logs/saportal_access.log`

## Support & Documentation

### Additional Resources
- Database setup guide: `README_DB_HOWTO.md`
- API documentation: Available in `/api/` endpoint comments
- Media processing: See `controllers/MediaService.php`

### System Requirements
- PHP 8.0+ with PDO, GD, and JSON extensions
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+ with mod_rewrite
- Minimum 512MB PHP memory limit
- 1GB+ available disk space

## License

Proprietary software. All rights reserved.

---

**Luxury Talent Booking — Red Carpet Edition**  
*Reels-first talent discovery platform*
