<?php
require_once 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$room_id = $_POST['room_id'] ?? null;
$dates = $_POST['dates'] ?? '';
$guests = $_POST['guests'] ?? 2;

if (!$room_id || !$dates) {
    $_SESSION['error'] = "Missing required booking information. Please select your dates and try again.";
    header('Location: room.php?id=' . $room_id);
    exit;
}

// Split the date range into check-in and check-out dates
$date_parts = explode(' - ', $dates);
if (count($date_parts) !== 2) {
    $_SESSION['error'] = "Please select both check-in and check-out dates.";
    header('Location: room.php?id=' . $room_id);
    exit;
}

$check_in = date('Y-m-d', strtotime($date_parts[0]));
$check_out = date('Y-m-d', strtotime($date_parts[1]));

// Validate dates
if (!$check_in || !$check_out || $check_in === '1970-01-01' || $check_out === '1970-01-01') {
    $_SESSION['error'] = "Invalid date format. Please select your dates again.";
    header('Location: room.php?id=' . $room_id);
    exit;
}

// Check if dates are in the past
$today = new DateTime();
$today->setTime(0, 0);
$check_in_obj = new DateTime($check_in);
$check_out_obj = new DateTime($check_out);

if ($check_in_obj < $today) {
    $_SESSION['error'] = "Check-in date cannot be in the past.";
    header('Location: room.php?id=' . $room_id);
    exit;
}

if ($check_in_obj >= $check_out_obj) {
    $_SESSION['error'] = "Check-out date must be after check-in date.";
    header('Location: room.php?id=' . $room_id);
    exit;
}

// Calculate total nights
$nights = $check_out_obj->diff($check_in_obj)->days;
if ($nights < 1) {
    $_SESSION['error'] = "Minimum stay is 1 night.";
    header('Location: room.php?id=' . $room_id);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Call the stored procedure to add reservation
    $stmt = $conn->prepare("CALL add_reservation(?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $room_id, $check_in, $check_out);
    $stmt->execute();
    
    // Get the booking ID from the result
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $booking_id = $row['id'];
        $_SESSION['success'] = "Booking successfully created! Proceed to payment.";
        header('Location: payment.php?booking_id=' . $booking_id);
        exit;
    } else {
        throw new Exception("Failed to get booking ID");
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
    if (strpos($error_message, 'Room is not available') !== false) {
        $_SESSION['error'] = "Sorry, this room is not available for the selected dates.";
    } else {
        error_log("Booking error: " . $error_message);
        $_SESSION['error'] = "An error occurred while processing your booking. Please try again later.";
    }
    header('Location: room.php?id=' . $room_id);
    exit;
}
?>
