<?php
require_once 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;
if (!$booking_id) {
    header('Location: bookings.php');
    exit;
}

// Verify booking belongs to user
$sql = "SELECT * FROM bookings WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: bookings.php');
    exit;
}

// Update booking status to "not_paid"
$sql = "UPDATE bookings SET status = 'not_paid' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Booking confirmed! Payment will be required at check-in.";
} else {
    $_SESSION['error'] = "Failed to process your request. Please try again.";
}

header('Location: bookings.php');
exit;
