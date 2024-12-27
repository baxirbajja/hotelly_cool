<?php
require_once 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) {
    header('Location: bookings.php');
    exit;
}

// Get booking details
$sql = "SELECT b.*, r.name as room_name, r.image as room_image, h.name as hotel_name, h.city as hotel_city 
        FROM bookings b 
        LEFT JOIN rooms r ON b.room_id = r.id 
        LEFT JOIN hotels h ON r.hotel_id = h.id 
        WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: bookings.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        main {
            margin-top: 120px;
        }
        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .confirmation-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 30px;
        }
        .confirmation-icon {
            color: #2e7d32;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .booking-details {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .room-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .detail-group {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .detail-group:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
        }
        .booking-id {
            font-family: monospace;
            background: #f5f5f5;
            padding: 5px 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="confirmation-container">
            <div class="confirmation-card">
                <div class="confirmation-icon">✓</div>
                <h1 class="serif">Booking Confirmed!</h1>
                <p>Thank you for choosing Hotelly. Your booking has been successfully confirmed.</p>
                <p>Booking ID: <span class="booking-id"><?php echo $booking_id; ?></span></p>
            </div>

            <div class="booking-details">
                <img src="<?php echo htmlspecialchars($booking['room_image']); ?>" alt="<?php echo htmlspecialchars($booking['room_name']); ?>" class="room-image">

                <div class="detail-group">
                    <div class="detail-label">Room</div>
                    <div><?php echo htmlspecialchars($booking['room_name']); ?></div>
                </div>

                <div class="detail-group">
                    <div class="detail-label">Hotel</div>
                    <div><?php echo htmlspecialchars($booking['hotel_name']); ?> - <?php echo htmlspecialchars($booking['hotel_city']); ?></div>
                </div>

                <div class="detail-group">
                    <div class="detail-label">Check-in</div>
                    <div><?php echo date('l, F j, Y', strtotime($booking['check_in'])); ?></div>
                </div>

                <div class="detail-group">
                    <div class="detail-label">Check-out</div>
                    <div><?php echo date('l, F j, Y', strtotime($booking['check_out'])); ?></div>
                </div>

                <div class="detail-group">
                    <div class="detail-label">Total Amount Paid</div>
                    <div>$<?php echo number_format($booking['total_price'], 2); ?></div>
                </div>

                <a href="bookings.php" class="btn btn-primary">View All Bookings</a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
