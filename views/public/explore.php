<?php
session_start();

// Check if config exists
if (!file_exists(__DIR__ . '/../../config/config.php')) {
    header('Location: /setup/');
    exit;
}

require_once __DIR__ . '/../../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Talent — Luxury Talent Booking RCE</title>
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
                            <p>Loading talent reels...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="feed-sidebar">
            <div class="sidebar-header">
                <h2>Explore Talent</h2>
                <p class="text-muted">Discover amazing talent through reels</p>
            </div>
            
            <div class="explore-info">
                <div style="margin-bottom: 2rem;">
                    <h4>How it works</h4>
                    <ul style="list-style: none; padding: 0; margin: 1rem 0;">
                        <li style="margin-bottom: 0.5rem; padding-left: 1rem; position: relative;">
                            <span style="position: absolute; left: 0; color: #000;">1.</span>
                            Browse talent reels
                        </li>
                        <li style="margin-bottom: 0.5rem; padding-left: 1rem; position: relative;">
                            <span style="position: absolute; left: 0; color: #000;">2.</span>
                            Use arrow keys or controls to navigate
                        </li>
                        <li style="margin-bottom: 0.5rem; padding-left: 1rem; position: relative;">
                            <span style="position: absolute; left: 0; color: #000;">3.</span>
                            Login to shortlist and book talent
                        </li>
                    </ul>
                </div>
                
                <div style="margin-bottom: 2rem;">
                    <h4>Ready to get started?</h4>
                    <p class="text-muted" style="margin-bottom: 1rem;">Join our platform to access full features</p>
                    <a href="/login" class="btn btn-primary btn-full">Login / Sign Up</a>
                </div>
                
                <div>
                    <h4>Navigation</h4>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                        <span style="background: #f0f0f0; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">← →</span>
                        <span style="font-size: 0.9rem;">Arrow keys to navigate</span>
                    </div>
                    <div style="display: flex; gap: 0.5rem;">
                        <span style="background: #f0f0f0; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">ESC</span>
                        <span style="font-size: 0.9rem;">Close menus</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div style="position: fixed; top: 1rem; left: 1rem; z-index: 100;">
        <a href="/" class="btn btn-secondary" style="padding: 0.5rem 1rem;">← Back to Home</a>
    </div>
    
    <script src="/assets/js/http.js"></script>
    <script src="/assets/js/ui.js"></script>
    <script>
        // Override API call for public explore page with demo data
        window.api.getPublicFeed = async function() {
            // Return demo reel data for explore page
            return {
                success: true,
                reels: [
                    {
                        id: 1,
                        title: "Professional Model",
                        description: "Experienced runway and commercial model available for bookings",
                        type: "image",
                        url: "data:image/svg+xml;base64," + btoa(`
                            <svg width="420" height="747" viewBox="0 0 420 747" xmlns="http://www.w3.org/2000/svg">
                                <rect width="420" height="747" fill="#e9ecef"/>
                                <rect x="160" y="300" width="100" height="147" rx="50" fill="#dee2e6"/>
                                <circle cx="210" cy="250" r="40" fill="#dee2e6"/>
                                <text x="210" y="500" font-family="Arial, sans-serif" font-size="16" text-anchor="middle" fill="#666">Demo Talent Profile</text>
                                <text x="210" y="520" font-family="Arial, sans-serif" font-size="12" text-anchor="middle" fill="#999">Professional Model</text>
                            </svg>
                        `),
                        talent_id: 1
                    },
                    {
                        id: 2,
                        title: "Dance Performer",
                        description: "Contemporary and commercial dance specialist with 10+ years experience",
                        type: "image",
                        url: "data:image/svg+xml;base64," + btoa(`
                            <svg width="420" height="747" viewBox="0 0 420 747" xmlns="http://www.w3.org/2000/svg">
                                <rect width="420" height="747" fill="#f8f9fa"/>
                                <rect x="160" y="300" width="100" height="147" rx="50" fill="#e9ecef"/>
                                <circle cx="210" cy="250" r="40" fill="#e9ecef"/>
                                <text x="210" y="500" font-family="Arial, sans-serif" font-size="16" text-anchor="middle" fill="#666">Demo Talent Profile</text>
                                <text x="210" y="520" font-family="Arial, sans-serif" font-size="12" text-anchor="middle" fill="#999">Dance Performer</text>
                            </svg>
                        `),
                        talent_id: 2
                    },
                    {
                        id: 3,
                        title: "Actor & Voice Artist",
                        description: "Versatile performer with experience in film, TV, and voice-over work",
                        type: "image",
                        url: "data:image/svg+xml;base64," + btoa(`
                            <svg width="420" height="747" viewBox="0 0 420 747" xmlns="http://www.w3.org/2000/svg">
                                <rect width="420" height="747" fill="#f1f3f4"/>
                                <rect x="160" y="300" width="100" height="147" rx="50" fill="#dee2e6"/>
                                <circle cx="210" cy="250" r="40" fill="#dee2e6"/>
                                <text x="210" y="500" font-family="Arial, sans-serif" font-size="16" text-anchor="middle" fill="#666">Demo Talent Profile</text>
                                <text x="210" y="520" font-family="Arial, sans-serif" font-size="12" text-anchor="middle" fill="#999">Actor & Voice Artist</text>
                            </svg>
                        `),
                        talent_id: 3
                    }
                ]
            };
        };
    </script>
</body>
</html>
