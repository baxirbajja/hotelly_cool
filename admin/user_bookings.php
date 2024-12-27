<?php
require_once '../includes/functions.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $bookingId = $_POST['booking_id'] ?? null;
        
        if ($bookingId) {
            if ($_POST['action'] === 'update_status') {
                $newStatus = $_POST['status'];
                if (updateBookingStatus($bookingId, $newStatus)) {
                    $_SESSION['success'] = "Booking status updated successfully!";
                } else {
                    $_SESSION['error'] = "Failed to update booking status.";
                }
            } elseif ($_POST['action'] === 'delete') {
                if (deleteBooking($bookingId)) {
                    $_SESSION['success'] = "Booking deleted successfully!";
                } else {
                    $_SESSION['error'] = "Failed to delete booking.";
                }
            }
        }
        
        // Redirect to the same page to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $_GET['id']);
        exit;
    }
}

// Get user ID from URL
$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header('Location: users.php');
    exit;
}

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: users.php');
    exit;
}

// Get user's bookings
$sql = "SELECT b.*, r.name as room_name, r.image as room_image, r.type as room_type, h.name as hotel_name, h.city as hotel_city 
        FROM bookings b 
        LEFT JOIN rooms r ON b.room_id = r.id 
        LEFT JOIN hotels h ON r.hotel_id = h.id 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Bookings - Hotelly Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .admin-nav {
            background: var(--secondary-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        .admin-nav-left {
            display: flex;
            align-items: center;
        }
        .admin-nav .logo {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            font-family: 'Playfair Display', serif;
            transition: color 0.3s;
        }
        .admin-nav .logo:hover {
            color: #fff;
        }
        .admin-nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .admin-nav-right .nav-link {
            color: #fff;
            text-decoration: none;
            margin-left: 2rem;
            font-family: 'Montserrat', sans-serif;
            transition: color 0.3s;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .admin-nav-right .nav-link:hover,
        .admin-nav-right .nav-link.active {
            color: var(--primary-color);
        }
        .admin-container {
            padding: 6rem 2rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        .booking-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            display: flex;
        }
        .booking-image {
            width: 200px;
            height: 150px;
            overflow: hidden;
        }
        .booking-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .booking-info {
            flex: 1;
            padding: 1.5rem;
        }
        .booking-header {
            margin-bottom: 1rem;
        }
        .booking-hotel {
            color: var(--primary-color);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .room-type {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        .booking-dates {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
        }
        .date-group {
            display: flex;
            flex-direction: column;
        }
        .date-label {
            font-size: 0.8rem;
            color: #666;
        }
        .date-value {
            font-weight: 500;
        }
        .booking-price {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .price-label {
            font-size: 0.9rem;
            color: #666;
        }
        .price-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        .booking-status {
            margin-top: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            display: inline-block;
            font-size: 0.9rem;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .user-info {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .user-info h2 {
            margin: 0 0 1rem 0;
            color: var(--secondary-color);
        }
        .user-detail {
            margin-bottom: 0.5rem;
        }
        .user-detail span {
            font-weight: 500;
            color: var(--primary-color);
        }
        .booking-actions {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .status-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            min-width: 150px;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .alert {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="hotels.php" class="nav-link">Hotels</a>
            <a href="rooms.php" class="nav-link">Rooms</a>
            <a href="bookings.php" class="nav-link">Bookings</a>
            <a href="users.php" class="nav-link">Users</a>
            <a href="../includes/logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="admin-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="user-info">
            <h2>User Details</h2>
            <div class="user-detail">Name: <span><?php echo htmlspecialchars($user['name']); ?></span></div>
            <div class="user-detail">Email: <span><?php echo htmlspecialchars($user['email']); ?></span></div>
            <div class="user-detail">Role: <span><?php echo htmlspecialchars($user['role']); ?></span></div>
            <div class="user-detail">Joined: <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span></div>
        </div>

        <h2 class="admin-title">Booking History</h2>

        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <div class="booking-image">
                        <img src="<?php echo htmlspecialchars($booking['room_image']); ?>" alt="<?php echo htmlspecialchars($booking['room_name']); ?>">
                    </div>
                    <div class="booking-info">
                        <div class="booking-header">
                            <div class="booking-hotel"><?php echo htmlspecialchars($booking['hotel_name']); ?> - <?php echo htmlspecialchars($booking['hotel_city']); ?></div>
                            <h3><?php echo htmlspecialchars($booking['room_name']); ?></h3>
                            <div class="room-type"><?php echo htmlspecialchars($booking['room_type']); ?></div>
                        </div>
                        <div class="booking-dates">
                            <div class="date-group">
                                <span class="date-label">Check-in</span>
                                <span class="date-value"><?php echo date('M d, Y', strtotime($booking['check_in'])); ?></span>
                            </div>
                            <div class="date-group">
                                <span class="date-label">Check-out</span>
                                <span class="date-value"><?php echo date('M d, Y', strtotime($booking['check_out'])); ?></span>
                            </div>
                            <div class="date-group">
                                <span class="date-label">Booked On</span>
                                <span class="date-value"><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="booking-price">
                            <span class="price-label">Total Price:</span>
                            <span class="price-value">$<?php echo number_format($booking['total_price'], 2); ?></span>
                        </div>
                        <div class="booking-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <select name="status" onchange="this.form.submit()" class="status-select">
                                    <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <button type="submit" class="delete-btn">Delete Booking</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <p>No bookings found for this user.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>
