<?php
require_once '../includes/functions.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get statistics
$stats = [
    'total_bookings' => count(getAllBookings()),
    'active_bookings' => count(array_filter(getAllBookings(), function($booking) {
        return $booking['status'] === 'confirmed';
    })),
    'total_users' => count(getAllUsers()),
    'total_revenue' => array_reduce(getAllBookings(), function($carry, $booking) {
        return $carry + $booking['total_price'];
    }, 0),
    'total_hotels' => count(getAllHotels()),
    'total_rooms' => count(getAllRooms())
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-right: 15px;
            color: #4a90e2;
        }

        .stat-info h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #666;
        }

        .stat-number {
            margin: 5px 0 0;
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-left">
            <a href="index.php" class="logo">HOTELLY ADMIN</a>
        </div>
        <div class="admin-nav-right">
            <a href="index.php" class="nav-link active">Dashboard</a>
            <a href="hotels.php" class="nav-link">Hotels</a>
            <a href="rooms.php" class="nav-link">Rooms</a>
            <a href="bookings.php" class="nav-link">Bookings</a>
            <a href="users.php" class="nav-link">Users</a>
            <a href="../logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="admin-container">
        <h1 class="admin-title">Dashboard Overview</h1>
        
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card" data-aos="fade-up">
                <div class="stat-icon">🏨</div>
                <div class="stat-info">
                    <h3>Total Hotels</h3>
                    <p class="stat-number"><?php echo $stats['total_hotels']; ?></p>
                </div>
            </div>

            <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-icon">🛏️</div>
                <div class="stat-info">
                    <h3>Total Rooms</h3>
                    <p class="stat-number"><?php echo $stats['total_rooms']; ?></p>
                </div>
            </div>

            <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-icon">📅</div>
                <div class="stat-info">
                    <h3>Total Bookings</h3>
                    <p class="stat-number"><?php echo $stats['total_bookings']; ?></p>
                </div>
            </div>

            <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3>Total Users</h3>
                    <p class="stat-number"><?php echo $stats['total_users']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>
