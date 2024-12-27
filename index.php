<?php
require_once 'includes/functions.php';
session_start();

// Get featured rooms with their hotel information
$sql = "SELECT r.*, h.name as hotel_name, h.city 
        FROM rooms r 
        JOIN hotels h ON r.hotel_id = h.id 
        ORDER BY RAND() 
        LIMIT 3";
$result = $conn->query($sql);
$featured_rooms = $result->fetch_all(MYSQLI_ASSOC);

// Get user name if logged in
$user_name = '';
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT name FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_name = $user['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotelly - Luxury Hotel</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="nav-container">
        <a href="index.php" class="logo">HOTELLY</a>
        <div class="nav-right">
            <div class="nav-links">
                <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin'): ?>
                    <a href="index.php" class="nav-link">Home</a>
                <?php endif; ?>
                <a href="hotels.php" class="nav-link">Hotels</a>
                <a href="rooms.php" class="nav-link">Rooms</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="bookings.php" class="nav-link">My Bookings</a>
                    <a href="profile.php" class="nav-link"><?php echo htmlspecialchars($user_name); ?></a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="nav-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero">
        <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
            <h1 class="serif">A NEW HOTEL IN<br>MOROCCO</h1>
            <p class="subtitle">Experience the perfect blend of modern comfort and timeless elegance in the heart of the city.</p>
            <div class="buttons">
                <a href="hotels.php" class="btn btn-outline">View Hotels</a>
                <a href="about_project.php" class="btn btn-solid">Learn More</a>
            </div>
        </div>
    </div>

    <!-- Featured Rooms -->
    <section class="featured-rooms">
        <div class="container">
            <h2 class="section-title serif" data-aos="fade-up">Featured Rooms</h2>
            <div class="rooms-grid" data-aos="fade-up" data-aos-delay="100">
                <?php foreach ($featured_rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-image">
                            <img src="<?php echo htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
                            <div class="room-overlay">
                                <a href="room.php?id=<?php echo $room['id']; ?>" class="btn btn-outline">View Details</a>
                            </div>
                        </div>
                        <div class="room-info">
                            <h3 class="serif"><?php echo htmlspecialchars($room['name']); ?></h3>
                            <p class="room-description"><?php echo htmlspecialchars($room['description']); ?></p>
                            <div class="room-features">
                                <span><?php echo $room['capacity']; ?> Persons</span>
                                <span>•</span>
                                <span><?php echo $room['size']; ?>m²</span>
                                <span>•</span>
                                <span><?php echo htmlspecialchars($room['view_type']); ?></span>
                            </div>
                            <p class="room-price">From $<?php echo number_format($room['price'], 2); ?> per night</p>
                            <p class="room-hotel">Hotel: <?php echo htmlspecialchars($room['hotel_name']); ?></p>
                            <p class="room-city">City: <?php echo htmlspecialchars($room['city']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="serif">HOTELLY</h3>
                    <p>Experience luxury and comfort in the heart of MOROCCO.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="rooms.php">Rooms</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="bookings.php">My Bookings</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>123 Hotel Street<br>Tamesna, Morocco<br>Phone: +212 63 74 92 006<br>Email: medbachirbajja@gmail.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Hotelly/baxir. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>
