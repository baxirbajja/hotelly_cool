<?php
require_once 'includes/functions.php';
session_start();

// Get filter parameters
$search = $_GET['search'] ?? '';
$city = $_GET['city'] ?? '';

// Get all hotels with filters
$sql = "SELECT * FROM hotels WHERE 1=1";
if ($search) {
    $sql .= " AND (name LIKE '%" . $conn->real_escape_string($search) . "%')";
}
if ($city) {
    $sql .= " AND city = '" . $conn->real_escape_string($city) . "'";
}
$result = $conn->query($sql);
$hotels = $result->fetch_all(MYSQLI_ASSOC);

// Get all unique cities for filter
$cities_result = $conn->query("SELECT DISTINCT city FROM hotels ORDER BY city");
$cities = $cities_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Hotels - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/rooms.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <header class="page-header">
        <div class="header-content">
            <h1 class="serif" data-aos="fade-up">Our Hotels</h1>
            <p data-aos="fade-up" data-aos-delay="100">Discover luxury and comfort across Morocco's most beautiful cities</p>
        </div>
    </header>

    <!-- Hotel Filters -->
    <section class="filters-section">
        <div class="container">
            <form class="filters-form" data-aos="fade-up" method="GET">
                <div class="filter-group">
                    <label>Search Hotel</label>
                    <input type="text" name="search" class="filter-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Hotel name...">
                </div>
                <div class="filter-group">
                    <label>City</label>
                    <select name="city" class="filter-input">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $city_option): ?>
                            <option value="<?php echo htmlspecialchars($city_option['city']); ?>" 
                                    <?php echo $city == $city_option['city'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city_option['city']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-solid">Search Hotels</button>
            </form>
        </div>
    </section>

    <!-- Hotels Grid -->
    <section class="rooms-section">
        <div class="container">
            <div class="rooms-grid" data-aos="fade-up">
                <?php foreach ($hotels as $hotel): ?>
                    <div class="room-card">
                        <div class="room-image">
                            <img src="<?php echo htmlspecialchars($hotel['image']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                            <div class="room-overlay">
                                <a href="hotel.php?id=<?php echo $hotel['id']; ?>" class="btn btn-outline">View Rooms</a>
                            </div>
                        </div>
                        <div class="room-info">
                            <div class="room-type"><?php echo htmlspecialchars($hotel['city']); ?></div>
                            <h3 class="serif"><?php echo htmlspecialchars($hotel['name']); ?></h3>
                            <div class="room-features">
                                <?php 
                                $amenities = explode(',', $hotel['amenities']);
                                $displayed_amenities = array_slice($amenities, 0, 3);
                                echo implode(' • ', array_map('htmlspecialchars', $displayed_amenities));
                                ?>
                            </div>
                            <div class="room-bottom">
                                <a href="hotel.php?id=<?php echo $hotel['id']; ?>" class="btn-text">View Details</a>
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
