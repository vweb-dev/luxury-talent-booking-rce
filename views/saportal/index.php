<?php
session_start();

// Check if config exists
if (!file_exists(__DIR__ . '/../../config/config.php')) {
    header('Location: /setup/');
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Check if user is logged in and has super admin role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'super_admin') {
    header('Location: /saportal/login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Portal — RCE</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <header style="background: #000; color: #fff;">
            <div class="container">
                <div class="header-content">
                    <h1 style="color: #fff; margin: 0; font-size: 1.5rem;">Super Admin Portal</h1>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="color: #ccc;">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                        <a href="/api/auth/logout.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main style="flex: 1; padding: 2rem 0;">
            <div class="container">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem;">
                    <!-- System Overview -->
                    <div class="admin-card" style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1rem; color: #000;">System Overview</h3>
                        <div class="stats-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="stat-item">
                                <div style="font-size: 2rem; font-weight: 700; color: #000;">12</div>
                                <div style="color: #666; font-size: 0.9rem;">Total Companies</div>
                            </div>
                            <div class="stat-item">
                                <div style="font-size: 2rem; font-weight: 700; color: #000;">1,247</div>
                                <div style="color: #666; font-size: 0.9rem;">Total Users</div>
                            </div>
                            <div class="stat-item">
                                <div style="font-size: 2rem; font-weight: 700; color: #000;">89</div>
                                <div style="color: #666; font-size: 0.9rem;">Pending Approvals</div>
                            </div>
                            <div class="stat-item">
                                <div style="font-size: 2rem; font-weight: 700; color: #000;">456</div>
                                <div style="color: #666; font-size: 0.9rem;">Active Bookings</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="admin-card" style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1rem; color: #000;">Quick Actions</h3>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <a href="/saportal/companies" class="btn btn-secondary btn-full">Manage Companies</a>
                            <a href="/saportal/users" class="btn btn-secondary btn-full">User Management</a>
                            <a href="/saportal/approvals" class="btn btn-secondary btn-full">Media Approvals</a>
                            <a href="/saportal/settings" class="btn btn-secondary btn-full">System Settings</a>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="admin-card" style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1rem; color: #000;">Recent Activity</h3>
                        <div class="activity-list">
                            <div class="activity-item" style="padding: 0.75rem 0; border-bottom: 1px solid #eee;">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">New company registered</div>
                                <div style="color: #666; font-size: 0.9rem;">Elite Talent Agency - 2 hours ago</div>
                            </div>
                            <div class="activity-item" style="padding: 0.75rem 0; border-bottom: 1px solid #eee;">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">Media approval required</div>
                                <div style="color: #666; font-size: 0.9rem;">15 new submissions - 4 hours ago</div>
                            </div>
                            <div class="activity-item" style="padding: 0.75rem 0; border-bottom: 1px solid #eee;">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">System backup completed</div>
                                <div style="color: #666; font-size: 0.9rem;">Daily backup - 6 hours ago</div>
                            </div>
                            <div class="activity-item" style="padding: 0.75rem 0;">
                                <div style="font-weight: 500; margin-bottom: 0.25rem;">User role updated</div>
                                <div style="color: #666; font-size: 0.9rem;">Client promoted to Tenant Admin - 8 hours ago</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Sections -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                    <!-- Companies Management -->
                    <div class="admin-section" style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3 style="margin: 0; color: #000;">Companies</h3>
                            <a href="/saportal/companies/new" class="btn btn-primary">Add Company</a>
                        </div>
                        
                        <div class="table-container" style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #eee;">
                                        <th style="text-align: left; padding: 0.75rem 0; font-weight: 600;">Company</th>
                                        <th style="text-align: left; padding: 0.75rem 0; font-weight: 600;">Plan</th>
                                        <th style="text-align: left; padding: 0.75rem 0; font-weight: 600;">Users</th>
                                        <th style="text-align: left; padding: 0.75rem 0; font-weight: 600;">Status</th>
                                        <th style="text-align: left; padding: 0.75rem 0; font-weight: 600;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 0.75rem 0;">
                                            <div style="font-weight: 500;">Elite Talent Agency</div>
                                            <div style="color: #666; font-size: 0.9rem;">elite-talent.com</div>
                                        </td>
                                        <td style="padding: 0.75rem 0;">
                                            <span style="background: #000; color: #fff; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Elite</span>
                                        </td>
                                        <td style="padding: 0.75rem 0;">247</td>
                                        <td style="padding: 0.75rem 0;">
                                            <span style="background: #d4edda; color: #155724; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Active</span>
                                        </td>
                                        <td style="padding: 0.75rem 0;">
                                            <a href="/saportal/companies/1" style="color: #000; text-decoration: none; margin-right: 1rem;">View</a>
                                            <a href="/saportal/companies/1/edit" style="color: #666; text-decoration: none;">Edit</a>
                                        </td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 0.75rem 0;">
                                            <div style="font-weight: 500;">Premier Models Inc</div>
                                            <div style="color: #666; font-size: 0.9rem;">premiermodels.com</div>
                                        </td>
                                        <td style="padding: 0.75rem 0;">
                                            <span style="background: #f8f9fa; color: #333; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Basic</span>
                                        </td>
                                        <td style="padding: 0.75rem 0;">89</td>
                                        <td style="padding: 0.75rem 0;">
                                            <span style="background: #d4edda; color: #155724; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Active</span>
                                        </td>
                                        <td style="padding: 0.75rem 0;">
                                            <a href="/saportal/companies/2" style="color: #000; text-decoration: none; margin-right: 1rem;">View</a>
                                            <a href="/saportal/companies/2/edit" style="color: #666; text-decoration: none;">Edit</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem 0;">
                                            <div style="font-weight: 500;">Creative Casting Co</div>
                                            <div style="color: #666; font-size: 0.9rem;">creativecasting.net</div>
                                        </td>
                                        <td style="padding: 0.75rem 0;">
                                            <span style="background: #000; color: #fff; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Elite</span>
                                        </td>
                                        <td style="padding: 0.75rem 0;">156</td>
                                        <td style="padding: 0.75rem 0;">
                                            <span style="background: #fff3cd; color: #856404; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Pending</span>
                                        </td>
                                        <td style="padding: 0.75rem 0;">
                                            <a href="/saportal/companies/3" style="color: #000; text-decoration: none; margin-right: 1rem;">View</a>
                                            <a href="/saportal/companies/3/edit" style="color: #666; text-decoration: none;">Edit</a>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- System Health -->
                    <div class="admin-section" style="background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1.5rem; color: #000;">System Health</h3>
                        
                        <div class="health-metrics">
                            <div class="metric-item" style="margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 500;">Server Load</span>
                                    <span style="color: #28a745; font-weight: 600;">23%</span>
                                </div>
                                <div style="background: #e9ecef; height: 8px; border-radius: 4px;">
                                    <div style="background: #28a745; height: 100%; width: 23%; border-radius: 4px;"></div>
                                </div>
                            </div>
                            
                            <div class="metric-item" style="margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 500;">Database</span>
                                    <span style="color: #28a745; font-weight: 600;">Healthy</span>
                                </div>
                                <div style="color: #666; font-size: 0.9rem;">Last backup: 2 hours ago</div>
                            </div>
                            
                            <div class="metric-item" style="margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 500;">Storage</span>
                                    <span style="color: #ffc107; font-weight: 600;">67%</span>
                                </div>
                                <div style="background: #e9ecef; height: 8px; border-radius: 4px;">
                                    <div style="background: #ffc107; height: 100%; width: 67%; border-radius: 4px;"></div>
                                </div>
                            </div>
                            
                            <div class="metric-item">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 500;">API Response</span>
                                    <span style="color: #28a745; font-weight: 600;">145ms</span>
                                </div>
                                <div style="color: #666; font-size: 0.9rem;">Average response time</div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
                            <a href="/saportal/system-logs" class="btn btn-secondary btn-full">View System Logs</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="/assets/js/http.js"></script>
    <script src="/assets/js/ui.js"></script>
</body>
</html>
