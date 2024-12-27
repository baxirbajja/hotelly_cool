<?php
require_once '../includes/functions.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle booking deletion
if (isset($_POST['delete_booking'])) {
    $bookingId = $_POST['booking_id'];
    if (deleteBooking($bookingId)) {
        $successMessage = "Booking deleted successfully!";
    } else {
        $errorMessage = "Error deleting booking. Please try again.";
    }
}

// Handle status update
if (isset($_POST['update_status'])) {
    $bookingId = $_POST['booking_id'];
    $newStatus = $_POST['status'];
    if (updateBookingStatus($bookingId, $newStatus)) {
        $successMessage = "Booking status updated successfully!";
    } else {
        $errorMessage = "Error updating booking status. Please try again.";
    }
}

// Get all bookings
$bookings = getAllBookings();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Hotelly Admin</title>
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
            <a href="bookings.php" class="nav-link active">Bookings</a>
            <a href="users.php" class="nav-link">Users</a>
            <a href="../includes/logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="admin-container">
        <h1 class="admin-title">Booking Management</h1>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>

        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                        <th>Total Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['id']); ?></td>
                        <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($booking['check_in'])); ?></td>
                        <td><?php echo date('M j, Y', strtotime($booking['check_out'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                                <?php echo htmlspecialchars($booking['status']); ?>
                            </span>
                        </td>
                        <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                        <td>
                            <div class="action-buttons">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to update this booking\'s status?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                                
                                <button class="admin-btn admin-btn-primary btn-sm btn-icon" onclick="viewBookingDetails(<?php echo htmlspecialchars(json_encode($booking)); ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="delete_booking" class="admin-btn admin-btn-danger btn-sm btn-icon">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function viewBookingDetails(booking) {
            // Implement view details modal here
            alert('Viewing details for booking #' + booking.id);
        }
    </script>
</body>
</html>
