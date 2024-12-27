<?php
require_once 'config.php';

// Simple error logging function
function logError($message) {
    $log = date('Y-m-d H:i:s') . " - " . $message . "\n";
    error_log($log, 3, '../logs/error.log');
}

// Image handling functions
function handleImage($file, $current_image = '') {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return $current_image;
    }

    $upload_dir = "uploads/";
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . "/new_hotelly/" . $upload_dir;
    
    // Create directory if needed
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Basic validation
    if (!getimagesize($file["tmp_name"]) || $file["size"] > 5000000) {
        throw new Exception("Invalid image or file too large (max 5MB)");
    }
    
    // Process upload
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        throw new Exception("Only JPG, JPEG, PNG & GIF files allowed");
    }
    
    $filename = uniqid() . '.' . $extension;
    $target_file = $target_dir . $filename;
    
    return move_uploaded_file($file["tmp_name"], $target_file) 
        ? "/new_hotelly/" . $upload_dir . $filename 
        : $current_image;
}

// Room functions
function getAllRooms($limit = null) {
    global $conn;
    $sql = "SELECT r.*, h.name as hotel_name FROM rooms r 
            LEFT JOIN hotels h ON r.hotel_id = h.id 
            ORDER BY r.created_at DESC" . 
            ($limit ? " LIMIT " . (int)$limit : "");
    
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getRoomById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT r.*, h.name as hotel_name FROM rooms r 
                           LEFT JOIN hotels h ON r.hotel_id = h.id 
                           WHERE r.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function addRoom($data) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO rooms (hotel_id, name, type, price, description, 
                           image, capacity, size, view_type, amenities) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdssiisd", 
        $data['hotel_id'], $data['name'], $data['type'], $data['price'], 
        $data['description'], $data['image'], $data['capacity'], $data['size'], 
        $data['view_type'], $data['amenities']
    );
    return $stmt->execute();
}

function updateRoom($id, $data) {
    global $conn;
    $stmt = $conn->prepare("UPDATE rooms SET hotel_id = ?, name = ?, type = ?, 
                           price = ?, description = ?, image = ?, capacity = ?, 
                           size = ?, view_type = ?, amenities = ? WHERE id = ?");
    $stmt->bind_param("issdssiiisi", 
        $data['hotel_id'], $data['name'], $data['type'], $data['price'], 
        $data['description'], $data['image'], $data['capacity'], $data['size'], 
        $data['view_type'], $data['amenities'], $id
    );
    return $stmt->execute();
}

function deleteRoom($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Hotel functions
function getAllHotels($limit = null) {
    global $conn;
    $sql = "SELECT * FROM hotels ORDER BY name" . 
           ($limit ? " LIMIT " . (int)$limit : "");
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getHotelById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function addHotel($data) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO hotels (name, city, address, description, 
                           image, amenities) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", 
        $data['name'], $data['city'], $data['address'], 
        $data['description'], $data['image'], $data['amenities']
    );
    return $stmt->execute();
}

function updateHotel($id, $data) {
    global $conn;
    $stmt = $conn->prepare("UPDATE hotels SET name = ?, city = ?, address = ?, 
                           description = ?, image = ?, amenities = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", 
        $data['name'], $data['city'], $data['address'], 
        $data['description'], $data['image'], $data['amenities'], $id
    );
    return $stmt->execute();
}

function deleteHotel($id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM hotels WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// Booking functions
function createBooking($room_id, $user_id, $check_in, $check_out, $total_price) {
    global $conn;
    
    // Check availability first
    if (!isRoomAvailable($room_id, $check_in, $check_out)) {
        throw new Exception("Room is not available for selected dates");
    }
    
    $stmt = $conn->prepare("INSERT INTO bookings (room_id, user_id, check_in, 
                           check_out, total_price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iissd", $room_id, $user_id, $check_in, $check_out, $total_price);
    return $stmt->execute() ? $conn->insert_id : false;
}

function getBookingsByUser($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT b.*, r.name as room_name, r.image as room_image, 
                           h.name as hotel_name, h.city as hotel_city
                           FROM bookings b 
                           INNER JOIN rooms r ON b.room_id = r.id 
                           INNER JOIN hotels h ON r.hotel_id = h.id 
                           WHERE b.user_id = ? 
                           ORDER BY b.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($result as &$booking) {
        $booking['room_image'] = fixImagePath($booking['room_image']);
    }
    return $result;
}

function getAllBookings() {
    global $conn;
    $sql = "SELECT b.*, r.name as room_name, u.name as user_name 
            FROM bookings b 
            LEFT JOIN rooms r ON b.room_id = r.id 
            LEFT JOIN users u ON b.user_id = u.id 
            ORDER BY b.created_at DESC";
    
    $result = $conn->query($sql);
    if (!$result) {
        logError("Database error: " . $conn->error);
        return [];
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function deleteBooking($booking_id) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete related payments
        $stmt = $conn->prepare("DELETE FROM payments WHERE booking_id = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();
        
        // Then delete the booking
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $stmt->close();
        
        // If we got here, commit the transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Something went wrong, rollback the transaction
        $conn->rollback();
        logError("Error deleting booking: " . $e->getMessage());
        return false;
    }
}

function updateBookingStatus($booking_id, $status) {
    global $conn;
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($status, $valid_statuses)) {
        logError("Invalid booking status: " . $status);
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    if (!$stmt) {
        logError("Database error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("si", $status, $booking_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Get booked dates for a room
function getBookedDates($room_id) {
    global $conn;
    
    $sql = "SELECT check_in, check_out FROM bookings WHERE room_id = ? AND status != 'cancelled'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $booked_dates = [];
    while ($row = $result->fetch_assoc()) {
        $current = new DateTime($row['check_in']);
        $end = new DateTime($row['check_out']);
        
        while ($current < $end) {
            $booked_dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
        }
    }
    
    return $booked_dates;
}

// User functions
function createUser($name, $email, $password) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, password_hash($password, PASSWORD_DEFAULT));
    return $stmt->execute();
}

function getUserByEmail($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function validateUser($email, $password) {
    $user = getUserByEmail($email);
    return $user && password_verify($password, $user['password']) ? $user : false;
}

function isAdmin($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result && $result['role'] === 'admin';
}

function getAllUsers() {
    global $conn;
    $sql = "SELECT id, name, email, role, created_at 
            FROM users 
            ORDER BY created_at DESC";
    
    $result = $conn->query($sql);
    if (!$result) {
        logError("Database error: " . $conn->error);
        return [];
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

function updateUserRole($user_id, $role) {
    global $conn;
    
    // Validate role
    $valid_roles = ['user', 'admin'];
    if (!in_array($role, $valid_roles)) {
        logError("Invalid user role: " . $role);
        return false;
    }
    
    // Don't allow changing the last admin's role
    if ($role !== 'admin') {
        $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result['admin_count'] <= 1) {
            $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($user['role'] === 'admin') {
                logError("Cannot remove the last admin user");
                return false;
            }
        }
    }
    
    // Update the user role
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    if (!$stmt) {
        logError("Database error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("si", $role, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function deleteUser($user_id) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if this is the last admin
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($user['role'] === 'admin') {
            $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if ($result['admin_count'] <= 1) {
                throw new Exception("Cannot delete the last admin user");
            }
        }
        
        // Delete related payments
        $stmt = $conn->prepare("DELETE FROM payments WHERE booking_id IN (SELECT id FROM bookings WHERE user_id = ?)");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Delete related bookings
        $stmt = $conn->prepare("DELETE FROM bookings WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        
        // If we got here, commit the transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Something went wrong, rollback the transaction
        $conn->rollback();
        logError("Error deleting user: " . $e->getMessage());
        return false;
    }
}

// Room availability check
function isRoomAvailable($room_id, $check_in, $check_out) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings 
                           WHERE room_id = ? AND status != 'cancelled' 
                           AND ((check_in BETWEEN ? AND ?) 
                           OR (check_out BETWEEN ? AND ?))");
    $stmt->bind_param("issss", $room_id, $check_in, $check_out, $check_in, $check_out);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'] == 0;
}

function fixImagePath($path) {
    if (empty($path)) {
        return 'images/default-room.jpg';
    }
    return $path;
}
?>
