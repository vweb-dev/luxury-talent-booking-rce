<?php
session_start();

// Check if config exists
if (!file_exists(__DIR__ . '/../../config/config.php')) {
    header('Location: /setup/');
    exit;
}

require_once __DIR__ . '/../../config/config.php';

// Check if user is logged in and has client role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Feed — Luxury Talent Booking RCE</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#000000">
</head>
<body>
    <div class="feed-container">
        <div class="feed-main">
            <div class="reels-container">
                <!-- Reels will be loaded here by JavaScript -->
                <div class="reel-wrapper">
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f0f0f0; color: #666;">
                        <div style="text-align: center;">
                            <div class="loading" style="margin: 0 auto 1rem;"></div>
                            <p>Loading talent feed...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="feed-sidebar">
            <div class="sidebar-header">
                <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
                    <h2>Client Dashboard</h2>
                    <div class="user-menu" style="position: relative;">
                        <button class="btn btn-secondary" style="padding: 0.5rem;" onclick="toggleUserMenu()">Menu</button>
                        <div id="userDropdown" class="more-dropdown" style="right: 0; top: 100%;">
                            <a href="/client/profile">My Profile</a>
                            <a href="/client/bookings">My Bookings</a>
                            <a href="/client/settings">Settings</a>
                            <a href="/api/auth/logout.php">Logout</a>
                        </div>
                    </div>
                </div>
                <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Client'); ?>!</p>
            </div>
            
            <div class="shortlist-section" style="margin-bottom: 2rem;">
                <h3>Shortlist</h3>
                <div class="shortlist-container">
                    <!-- Shortlist will be loaded here -->
                    <p class="text-muted">Loading shortlist...</p>
                </div>
            </div>
            
            <div class="filters-section" style="margin-bottom: 2rem;">
                <h4>Filters</h4>
                <form id="feedFilters" style="margin-top: 1rem;">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" placeholder="City or region">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <option value="model">Model</option>
                            <option value="actor">Actor</option>
                            <option value="dancer">Dancer</option>
                            <option value="musician">Musician</option>
                            <option value="voice_artist">Voice Artist</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="experience">Experience Level</label>
                        <select id="experience" name="experience">
                            <option value="">Any Level</option>
                            <option value="beginner">Beginner (0-2 years)</option>
                            <option value="intermediate">Intermediate (3-5 years)</option>
                            <option value="experienced">Experienced (6-10 years)</option>
                            <option value="expert">Expert (10+ years)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">Apply Filters</button>
                </form>
            </div>
            
            <div class="quick-actions">
                <h4>Quick Actions</h4>
                <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                    <a href="/client/bookings" class="btn btn-secondary btn-full">View Bookings</a>
                    <a href="/client/broadcasts" class="btn btn-secondary btn-full">Create Broadcast</a>
                    <a href="/client/messages" class="btn btn-secondary btn-full">Messages</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/assets/js/http.js"></script>
    <script src="/assets/js/ui.js"></script>
    <script>
        let userMenuOpen = false;
        
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            userMenuOpen = !userMenuOpen;
            dropdown.classList.toggle('show', userMenuOpen);
        }
        
        // Close user menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu') && userMenuOpen) {
                toggleUserMenu();
            }
        });
        
        // Override API call for client feed with demo data
        window.api.getClientFeed = async function(filters = {}) {
            // Simulate API delay
            await new Promise(resolve => setTimeout(resolve, 500));
            
            return {
                success: true,
                reels: [
                    {
                        id: 1,
                        title: "Elite Fashion Model",
                        description: "International runway experience, available for high-end fashion shoots and events",
                        type: "image",
                        url: "data:image/svg+xml;base64," + btoa(`
                            <svg width="420" height="747" viewBox="0 0 420 747" xmlns="http://www.w3.org/2000/svg">
                                <rect width="420" height="747" fill="#f8f9fa"/>
                                <rect x="160" y="280" width="100" height="160" rx="50" fill="#e9ecef"/>
                                <circle cx="210" cy="230" r="45" fill="#e9ecef"/>
                                <text x="210" y="480" font-family="Arial, sans-serif" font-size="18" font-weight="bold" text-anchor="middle" fill="#333">Sarah Chen</text>
                                <text x="210" y="500" font-family="Arial, sans-serif" font-size="14" text-anchor="middle" fill="#666">Elite Fashion Model</text>
                                <text x="210" y="520" font-family="Arial, sans-serif" font-size="12" text-anchor="middle" fill="#999">New York • 5'9" • Available</text>
                            </svg>
                        `),
                        talent_id: 1,
                        category: "model",
                        location: "New York"
                    },
                    {
                        id: 2,
                        title: "Professional Dancer",
                        description: "Contemporary, jazz, and commercial dance specialist with Broadway experience",
                        type: "image",
                        url: "data:image/svg+xml;base64," + btoa(`
                            <svg width="420" height="747" viewBox="0 0 420 747" xmlns="http://www.w3.org/2000/svg">
                                <rect width="420" height="747" fill="#f1f3f4"/>
                                <rect x="160" y="280" width="100" height="160" rx="50" fill="#dee2e6"/>
                                <circle cx="210" cy="230" r="45" fill="#dee2e6"/>
                                <text x="210" y="480" font-family="Arial, sans-serif" font-size="18" font-weight="bold" text-anchor="middle" fill="#333">Marcus Johnson</text>
                                <text x="210" y="500" font-family="Arial, sans-serif" font-size="14" text-anchor="middle" fill="#666">Professional Dancer</text>
                                <text x="210" y="520" font-family="Arial, sans-serif" font-size="12" text-anchor="middle" fill="#999">Los Angeles • Broadway Exp • Available</text>
                            </svg>
                        `),
                        talent_id: 2,
                        category: "dancer",
                        location: "Los Angeles"
                    },
                    {
                        id: 3,
                        title: "Versatile Actor",
                        description: "Film, TV, and theater actor with extensive range and professional training",
                        type: "image",
                        url: "data:image/svg+xml;base64," + btoa(`
                            <svg width="420" height="747" viewBox="0 0 420 747" xmlns="http://www.w3.org/2000/svg">
                                <rect width="420" height="747" fill="#e8f4f8"/>
                                <rect x="160" y="280" width="100" height="160" rx="50" fill="#d1ecf1"/>
                                <circle cx="210" cy="230" r="45" fill="#d1ecf1"/>
                                <text x="210" y="480" font-family="Arial, sans-serif" font-size="18" font-weight="bold" text-anchor="middle" fill="#333">Emma Rodriguez</text>
                                <text x="210" y="500" font-family="Arial, sans-serif" font-size="14" text-anchor="middle" fill="#666">Versatile Actor</text>
                                <text x="210" y="520" font-family="Arial, sans-serif" font-size="12" text-anchor="middle" fill="#999">Chicago • Film & TV • Available</text>
                            </svg>
                        `),
                        talent_id: 3,
                        category: "actor",
                        location: "Chicago"
                    }
                ],
                shortlist: [
                    {
                        id: 1,
                        name: "Sarah Chen",
                        location: "New York",
                        avatar: ""
                    },
                    {
                        id: 4,
                        name: "David Kim",
                        location: "Miami",
                        avatar: ""
                    }
                ]
            };
        };
        
        // Handle filter form submission
        document.getElementById('feedFilters').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const filters = Object.fromEntries(formData.entries());
            
            try {
                const feedData = await api.getClientFeed(filters);
                ui.reels = feedData.reels || [];
                ui.currentReel = 0;
                ui.renderReels();
            } catch (error) {
                console.error('Failed to apply filters:', error);
                ui.showError('Failed to apply filters. Please try again.');
            }
        });
    </script>
</body>
</html>
