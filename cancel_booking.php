<?php
require_once 'includes/functions.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my-bookings.php');
    exit;
}

$booking_id = $_POST['booking_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$booking_id) {
    $_SESSION['error'] = "Invalid booking ID";
    header('Location: my-bookings.php');
    exit;
}

try {
    // Call the stored procedure to cancel the reservation
    $stmt = $conn->prepare("CALL cancel_reservation(?, ?)");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    
    // Get the result from the stored procedure
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (isset($row['success']) && $row['success']) {
        $_SESSION['success'] = "Your booking has been successfully cancelled.";
    } else {
        throw new Exception($row['message'] ?? "Failed to cancel booking");
    }
} catch (Exception $e) {
    error_log("Cancellation error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while cancelling your booking. Please try again later.";
}

header('Location: my-bookings.php');
exit;
?>
