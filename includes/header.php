<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Online Car Rental System</title>
    <link rel="stylesheet" href="assets/style.css?v=20261116-login-register-prompt">
</head>
<body>
    <video class="page-background-video" autoplay muted loop playsinline preload="auto" aria-hidden="true" tabindex="-1" poster="https://images.unsplash.com/photo-1556189250-72ba954cfc2b?auto=format&fit=crop&fm=jpg&q=80&w=2200">
        <source src="assets/bg_car.mp4" type="video/mp4">
    </video>
    <nav>
        <h1>ONLINE CAR RENTAL SYSTEM</h1>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="browse.php">Browse Cars</a></li>
            <?php if (isAuthenticated()): ?>
                <?php if (isStaffAccount()): ?>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="vehicles.php">Vehicles</a></li>
                    <li><a href="add_rental.php">New Rental</a></li>
                    <li><a href="customers.php">Customers &amp; Receipts</a></li>
                    <?php if (isAdminAccount()): ?><li><a href="staff.php">Staff users</a></li><?php endif; ?>
                <?php else: ?>
                    <li><a href="index.php">My Account</a></li>
                    <li><a href="profile.php">Edit Profile</a></li>
                <?php endif; ?>
                <li><a href="change_password.php">Change Password</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <marquee class="welcome-marquee" style="color:#fff !important;"><span class="marquee-text" style="color:#fff !important; -webkit-text-fill-color:#fff;">WELCOME TO THE BEST CAR RENTING SERVICE PLATFORM</span></marquee>

    <section class="site-hero" aria-label="DriveEasy vehicle highlights">
        <div class="hero-gallery" aria-hidden="true">
            <img src="uploads/benz.jpg" alt="">
            <img src="uploads/civic.jpg" alt="">
            <img src="uploads/rav4.jpg" alt="">
        </div>
        <div class="hero-shade"></div>
        <div class="hero-copy">
            <h2>SAFE DRIVING CAR RENTAL</h2>
            <h1>Find the right car for every journey</h1>
            <p>Comfort, choice, and a smooth ride from pick-up to return.</p>
            <a href="browse.php" class="hero-link">Explore the fleet</a>
        </div>
    </section>

    <a href="https://wa.me/250789339641" class="whatsapp-float" target="_blank" rel="noopener noreferrer" aria-label="Chat with DriveEasy on WhatsApp" title="Chat with us on WhatsApp">
        <svg viewBox="0 0 32 32" width="35" height="35" fill="white" aria-hidden="true" focusable="false">
            <path d="M16 2a13 13 0 0 0-11.2 19.8L3 30l8.4-2.2A13 13 0 1 0 16 2zm0 23.5a10.5 10.5 0 0 1-5.4-1.5l-.4-.2-4.2 1.1 1.1-4.1-.3-.4A10.5 10.5 0 1 1 16 25.5zm5.8-7.9c-.3-.2-1.9-.9-2.2-1s-.5-.1-.7.2-.8 1-.9 1.2-.3.2-.6 0a8.6 8.6 0 0 1-2.5-1.5 9.4 9.4 0 0 1-1.7-2.1c-.2-.3 0-.5.1-.6l.5-.6c.2-.2.2-.3.3-.5s0-.4 0-.6-.7-1.7-.9-2.3-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.9.4 3.6 3.6 0 0 0-1.1 2.7 6.3 6.3 0 0 0 1.3 3.3 14.4 14.4 0 0 0 5.5 4.8c.8.3 1.4.5 1.8.6a4.3 4.3 0 0 0 2 .1 3.3 3.3 0 0 0 2.2-1.5 2.7 2.7 0 0 0 .2-1.5c-.1-.2-.3-.3-.6-.4z"/>
        </svg>
    </a>

    <div class="container">
