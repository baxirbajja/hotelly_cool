<?php
require_once 'includes/functions.php';
session_start();

if (!isset($_GET['id'])) {
    header('Location: hotels.php');
    exit;
}

// Get hotel details
$hotel_id = $_GET['id'];
$sql = "SELECT * FROM hotels WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$hotel = $stmt->get_result()->fetch_assoc();

if (!$hotel) {
    header('Location: hotels.php');
    exit;
}

// Get hotel's rooms
$sql = "SELECT * FROM rooms WHERE hotel_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $hotel_id);
$stmt->execute();
$rooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['name']); ?> - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/rooms.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hotel Header -->
    <header class="page-header">
        <div class="header-content">
            <h1 class="serif" data-aos="fade-up"><?php echo htmlspecialchars($hotel['name']); ?></h1>
            <p data-aos="fade-up" data-aos-delay="100"><?php echo htmlspecialchars($hotel['city']); ?>, Morocco</p>
        </div>
    </header>

    <!-- Hotel Details -->
    <section class="container">
        <div class="hotel-details" data-aos="fade-up">
            <img src="<?php echo htmlspecialchars($hotel['image']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" class="hotel-main-image">
            <div class="hotel-info">
                <h2 class="serif">About the Hotel</h2>
                <p><?php echo htmlspecialchars($hotel['description']); ?></p>
                <div class="hotel-amenities">
                    <h3>Hotel Amenities</h3>
                    <div class="amenities-grid">
                        <?php foreach (explode(',', $hotel['amenities']) as $amenity): ?>
                            <div class="amenity-item">
                                <span class="amenity-icon">✓</span>
                                <?php echo htmlspecialchars($amenity); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="hotel-location">
                    <h3>Location</h3>
                    <p><?php echo htmlspecialchars($hotel['address']); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Available Rooms -->
    <section class="rooms-section">
        <div class="container">
            <h2 class="section-title serif" data-aos="fade-up">Available Rooms</h2>
            <div class="rooms-grid" data-aos="fade-up">
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-image">
                            <img src="<?php echo htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
                            <div class="room-overlay">
                                <a href="book.php?room_id=<?php echo $room['id']; ?>" class="btn btn-outline">Book Now</a>
                            </div>
                        </div>
                        <div class="room-info">
                            <div class="room-type"><?php echo htmlspecialchars($room['type']); ?></div>
                            <h3 class="serif"><?php echo htmlspecialchars($room['name']); ?></h3>
                            <div class="room-features">
                                <span><?php echo $room['capacity']; ?> Persons</span>
                                <span>•</span>
                                <span><?php echo $room['size']; ?>m²</span>
                                <span>•</span>
                                <span><?php echo htmlspecialchars($room['view_type']); ?></span>
                            </div>
                            <div class="room-bottom">
                                <p class="room-price">From $<?php echo number_format($room['price'], 0); ?><span>/night</span></p>
                                <a href="book.php?room_id=<?php echo $room['id']; ?>" class="btn-text">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>
