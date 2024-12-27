<?php
require_once 'includes/functions.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$booking_id = $data['booking_id'] ?? null;
$payment_id = $data['payment_id'] ?? null;
$payment_method = $data['payment_method'] ?? null;

if (!$booking_id || !$payment_id || !$payment_method) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    error_log("Starting payment process...");
    error_log("User ID: " . $_SESSION['user_id']);
    error_log("Booking ID: " . $booking_id);
    error_log("Payment ID: " . $payment_id);
    error_log("Payment Method: " . $payment_method);

    // Start transaction
    $conn->begin_transaction();
    error_log("Started transaction");

    // Verify booking belongs to user and is pending
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    error_log("Checked booking: " . ($booking ? "Found" : "Not found"));

    // Add debug logging
    error_log("Payment Processing Debug:");
    error_log("Booking ID: " . $booking_id);
    error_log("User ID: " . $_SESSION['user_id']);
    if (!$booking) {
        // Check why the booking was not found
        $stmt = $conn->prepare("SELECT id, user_id, status FROM bookings WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $debug_booking = $stmt->get_result()->fetch_assoc();
        
        if (!$debug_booking) {
            error_log("Error: Booking ID does not exist");
        } else {
            error_log("Found booking - Status: " . $debug_booking['status'] . ", User ID: " . $debug_booking['user_id']);
            if ($debug_booking['user_id'] != $_SESSION['user_id']) {
                error_log("Error: Booking belongs to different user");
            }
            if ($debug_booking['status'] != 'pending') {
                error_log("Error: Booking status is not pending");
            }
        }
        throw new Exception('Invalid booking or booking already processed');
    }

    // Update booking status
    $status = 'confirmed';
    $payment_date = date('Y-m-d H:i:s');
    error_log("Updating booking status to confirmed");
    
    // First, ensure all payment columns exist
    $conn->query("
        ALTER TABLE bookings 
        ADD COLUMN IF NOT EXISTS payment_id VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS payment_date DATETIME DEFAULT NULL
    ");

    // Update booking with payment information
    $stmt = $conn->prepare("UPDATE bookings SET status = ?, payment_id = ?, payment_method = ?, payment_date = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $status, $payment_id, $payment_method, $payment_date, $booking_id);
    
    if (!$stmt->execute()) {
        error_log("Error updating booking: " . $stmt->error);
        throw new Exception('Failed to update booking status');
    }
    error_log("Successfully updated booking status");

    // Ensure payments table exists
    $conn->query("
        CREATE TABLE IF NOT EXISTS payments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            booking_id INT NOT NULL,
            payment_id VARCHAR(255) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_date DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id)
        )
    ");

    // Record payment
    $stmt = $conn->prepare("INSERT INTO payments (booking_id, payment_id, payment_method, amount, payment_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issds", $booking_id, $payment_id, $payment_method, $booking['total_price'], $payment_date);
    
    if (!$stmt->execute()) {
        error_log("Error recording payment: " . $stmt->error);
        throw new Exception('Failed to record payment');
    }
    error_log("Successfully recorded payment");

    // Commit transaction
    $conn->commit();
    error_log("Transaction committed");

    // Set success message
    $_SESSION['success'] = "Payment processed successfully! Your booking is now confirmed.";
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'status' => 'confirmed',
        'payment_date' => $payment_date
    ]);

} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    // Rollback transaction on error
    $conn->rollback();
    error_log("Transaction rolled back");
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
