<?php
require_once 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$user_name = $user['name'];

// Get bookings using the function
$bookings = getBookingsByUser($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        main {
            margin-top: 140px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="bookings-page">
        <div class="container">
            <h1 class="page-title">My Bookings</h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (empty($bookings)): ?>
                <div class="no-bookings">
                    <p>You don't have any bookings yet.</p>
                    <a href="rooms.php" class="btn">Browse Rooms</a>
                </div>
            <?php else: ?>
                <div class="bookings-grid">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card">
                            <div class="booking-image">
                                <img src="<?php echo htmlspecialchars(fixImagePath($booking['room_image'])); ?>" alt="<?php echo htmlspecialchars($booking['room_name']); ?>">
                            </div>
                            <div class="booking-details">
                                <h3><?php echo htmlspecialchars($booking['room_name']); ?></h3>
                                <p class="hotel-name"><?php echo htmlspecialchars($booking['hotel_name']); ?> - <?php echo htmlspecialchars($booking['hotel_city']); ?></p>
                                <div class="dates">
                                    <p><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in'])); ?></p>
                                    <p><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></p>
                                </div>
                                <p class="price"><strong>Total:</strong> $<?php echo number_format($booking['total_price'], 2); ?></p>
                                <div class="status">
                                    <?php if ($booking['status'] === 'confirmed'): ?>
                                        <span class="status-paid">Paid</span>
                                        <?php if ($booking['payment_method']): ?>
                                            <span class="payment-method">via <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $booking['payment_method']))); ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="status-pending">Pending Payment</span>
                                        <button class="btn pay-now" onclick="window.location.href='payment.php?booking_id=<?php echo $booking['id']; ?>'">Pay Now</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>
