<?php
require_once 'config.php';

// Clear existing data
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE reviews");
$conn->query("TRUNCATE TABLE bookings");
$conn->query("TRUNCATE TABLE rooms");
$conn->query("TRUNCATE TABLE users");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Sample Users
$users = [
    ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'user'],
    ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => password_hash('password123', PASSWORD_DEFAULT), 'role' => 'user'],
    ['name' => 'Admin User', 'email' => 'admin@hotelly.com', 'password' => password_hash('admin123', PASSWORD_DEFAULT), 'role' => 'admin']
];

// Insert sample users
foreach ($users as $user) {
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $user['name'], $user['email'], $user['password'], $user['role']);
    $stmt->execute();
}

// Sample Rooms
$rooms = [
    [
        'name' => 'Luxury Suite',
        'type' => 'suite',
        'description' => 'Spacious suite with ocean view and private balcony',
        'price' => 300.00,
        'capacity' => 2,
        'size' => '50',
        'view_type' => 'ocean',
        'amenities' => 'King bed, Jacuzzi, Mini bar, Free WiFi',
        'image' => 'luxury-suite.jpg'
    ],
    [
        'name' => 'Deluxe Room',
        'type' => 'deluxe',
        'description' => 'Comfortable room with city view',
        'price' => 200.00,
        'capacity' => 2,
        'size' => '35',
        'view_type' => 'city',
        'amenities' => 'Queen bed, TV, Free WiFi',
        'image' => 'deluxe-room.jpg'
    ],
    [
        'name' => 'Family Suite',
        'type' => 'suite',
        'description' => 'Perfect for families with separate living area',
        'price' => 400.00,
        'capacity' => 4,
        'size' => '75',
        'view_type' => 'garden',
        'amenities' => '2 Queen beds, Kitchen, Living room, Free WiFi',
        'image' => 'family-suite.jpg'
    ]
];

// Insert sample rooms
foreach ($rooms as $room) {
    $sql = "INSERT INTO rooms (name, type, description, price, capacity, size, view_type, amenities, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdiisss", 
        $room['name'],
        $room['type'],
        $room['description'],
        $room['price'],
        $room['capacity'],
        $room['size'],
        $room['view_type'],
        $room['amenities'],
        $room['image']
    );
    $stmt->execute();
}

// Sample Bookings
$bookings = [
    [
        'user_id' => 1,
        'room_id' => 1,
        'check_in' => '2024-01-15',
        'check_out' => '2024-01-20',
        'total_price' => 1500.00,
        'status' => 'confirmed'
    ],
    [
        'user_id' => 2,
        'room_id' => 2,
        'check_in' => '2024-02-01',
        'check_out' => '2024-02-05',
        'total_price' => 800.00,
        'status' => 'confirmed'
    ],
    [
        'user_id' => 1,
        'room_id' => 3,
        'check_in' => '2024-03-10',
        'check_out' => '2024-03-15',
        'total_price' => 2000.00,
        'status' => 'pending'
    ]
];

// Insert sample bookings
foreach ($bookings as $booking) {
    $sql = "INSERT INTO bookings (user_id, room_id, check_in, check_out, total_price, status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissds", 
        $booking['user_id'],
        $booking['room_id'],
        $booking['check_in'],
        $booking['check_out'],
        $booking['total_price'],
        $booking['status']
    );
    $stmt->execute();
}

// Sample Reviews
$reviews = [
    [
        'booking_id' => 1,
        'user_id' => 1,
        'room_id' => 1,
        'rating' => 5,
        'comment' => 'Absolutely amazing stay! The ocean view was breathtaking and the room service was impeccable. Will definitely come back!',
        'status' => 'approved'
    ],
    [
        'booking_id' => 2,
        'user_id' => 2,
        'room_id' => 2,
        'rating' => 4,
        'comment' => 'Great room with comfortable beds. The city view was nice, especially at night. Only minor issue was the slow WiFi.',
        'status' => 'approved'
    ],
    [
        'booking_id' => 3,
        'user_id' => 1,
        'room_id' => 3,
        'rating' => 5,
        'comment' => 'Perfect for our family vacation! The kitchen was well-equipped and the kids loved the space. The garden view was peaceful.',
        'status' => 'pending'
    ],
    [
        'booking_id' => 1,
        'user_id' => 1,
        'room_id' => 1,
        'rating' => 3,
        'comment' => 'The room was clean but the air conditioning was a bit noisy. Otherwise, good value for money.',
        'status' => 'hidden'
    ]
];

// Insert sample reviews
foreach ($reviews as $review) {
    $sql = "INSERT INTO reviews (booking_id, user_id, room_id, rating, comment, status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiss", 
        $review['booking_id'],
        $review['user_id'],
        $review['room_id'],
        $review['rating'],
        $review['comment'],
        $review['status']
    );
    $stmt->execute();
}

echo "Sample data has been inserted successfully!";
?>
