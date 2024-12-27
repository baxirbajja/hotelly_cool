<?php
require_once 'includes/functions.php';
session_start();

$room_id = $_GET['id'] ?? null;
if (!$room_id) {
    header('Location: rooms.php');
    exit;
}

// Get room details with hotel information
$sql = "SELECT r.*, h.name as hotel_name, h.city as hotel_city 
        FROM rooms r 
        LEFT JOIN hotels h ON r.hotel_id = h.id 
        WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) {
    header('Location: rooms.php');
    exit;
}

// Get booked dates for this room
$booked_dates = getBookedDates($room_id);
$booked_dates_json = json_encode($booked_dates);

// Get room reviews
$sql = "SELECT r.*, u.name as user_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.room_id = ? 
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate average rating
$average_rating = 0;
if (count($reviews) > 0) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $average_rating = round($total_rating / count($reviews), 1);
}

// Get user name if logged in
$user_name = '';
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT name FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $user_name = $user['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($room['name']); ?> - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        .booking-calendar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .daterangepicker {
            font-family: 'Montserrat', sans-serif;
        }
        .daterangepicker td.active {
            background-color: var(--primary-color) !important;
        }
        .booking-form {
            margin-top: 20px;
        }
        .booking-total {
            margin-top: 15px;
            padding: 15px;
            background: #f8f8f8;
            border-radius: 4px;
        }
        .booking-total h3 {
            margin: 0;
            color: var(--primary-color);
        }
        .booked-date {
            background-color: #ffebee !important;
            color: #d32f2f !important;
            text-decoration: line-through;
        }
        .room-details {
            margin-top: 120px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="nav-container">
        <a href="index.php" class="logo">HOTELLY</a>
        <div class="nav-right">
            <div class="nav-links">
                <a href="index.php" class="nav-link">Home</a>
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

    <!-- Room Details -->
    <section class="room-details">
        <div class="container">
            <div class="room-header" data-aos="fade-up">
                <div class="room-title">
                    <div class="room-type"><?php echo htmlspecialchars($room['type']); ?></div>
                    <h1 class="serif"><?php echo htmlspecialchars($room['name']); ?></h1>
                    <p class="room-location"><?php echo htmlspecialchars($room['hotel_name']); ?> - <?php echo htmlspecialchars($room['hotel_city']); ?></p>
                    <?php if ($average_rating > 0): ?>
                        <div class="room-rating">
                            <span class="stars"><?php echo str_repeat('★', round($average_rating)); ?></span>
                            <span class="rating-text"><?php echo $average_rating; ?> (<?php echo count($reviews); ?> reviews)</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="room-price">
                    <span class="price">$<?php echo number_format($room['price'], 2); ?></span>
                    <span class="per-night">per night</span>
                </div>
            </div>

            <div class="room-gallery" data-aos="fade-up">
                <img src="<?php echo htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['name']); ?>">
            </div>

            <div class="room-content">
                <div class="room-info" data-aos="fade-up">
                    <div class="room-description">
                        <h2 class="serif">Room Description</h2>
                        <p><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
                    </div>

                    <div class="room-features">
                        <h2 class="serif">Room Features</h2>
                        <ul>
                            <li>Room Size: <?php echo htmlspecialchars($room['size']); ?> m²</li>
                            <li>Capacity: <?php echo htmlspecialchars($room['capacity']); ?> guests</li>
                            <li>View: <?php echo htmlspecialchars($room['view_type']); ?></li>
                            <?php 
                            $amenities = json_decode($room['amenities'], true);
                            if ($amenities) {
                                foreach ($amenities as $amenity) {
                                    echo "<li>" . htmlspecialchars($amenity) . "</li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="booking-calendar">
                        <h2 class="serif">Book Your Stay</h2>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-error">
                                <?php 
                                echo htmlspecialchars($_SESSION['error']);
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>
                        <form class="booking-form" method="POST" action="process_booking.php" onsubmit="return validateBooking()">
                            <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                            <input type="hidden" name="price_per_night" value="<?php echo $room['price']; ?>">
                            
                            <div class="form-group">
                                <label>Select Dates</label>
                                <input type="text" name="dates" id="date-range" class="form-control" required>
                            </div>

                            <div class="booking-total">
                                <div class="total-nights">Total nights: <span id="total-nights">0</span></div>
                                <h3>Total: $<span id="total-price">0.00</span></h3>
                            </div>

                            <button type="submit" class="btn btn-primary" id="book-now-btn" disabled>Book Now</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="booking-calendar">
                        <p>Please <a href="login.php">login</a> to book this room.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($reviews): ?>
            <div class="room-reviews" data-aos="fade-up">
                <h2 class="serif">Guest Reviews</h2>
                <div class="reviews-grid">
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-name"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                            <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="serif">HOTELLY</h3>
                    <p>Experience luxury and comfort in the heart of Copenhagen.</p>
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
                    <p>123 Hotel Street<br>Copenhagen, Denmark<br>Phone: +45 12 34 56 78<br>Email: info@hotelly.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Hotelly. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        AOS.init();

        // Get booked dates from PHP
        const bookedDates = <?php echo $booked_dates_json; ?>;
        let selectedStartDate = null;
        let selectedEndDate = null;

        // Initialize date range picker
        $('#date-range').daterangepicker({
            minDate: moment(),
            opens: 'center',
            autoApply: true,
            isInvalidDate: function(date) {
                return bookedDates.includes(date.format('YYYY-MM-DD'));
            }
        }, function(start, end) {
            selectedStartDate = start;
            selectedEndDate = end;
            updateBookingDetails();
        });

        // Clear initial dates
        $('#date-range').val('');

        function updateBookingDetails() {
            if (selectedStartDate && selectedEndDate) {
                const nights = selectedEndDate.diff(selectedStartDate, 'days');
                const pricePerNight = <?php echo $room['price']; ?>;
                const totalPrice = nights * pricePerNight;

                $('#total-nights').text(nights);
                $('#total-price').text(totalPrice.toFixed(2));
                $('#book-now-btn').prop('disabled', false);
            } else {
                $('#total-nights').text('0');
                $('#total-price').text('0.00');
                $('#book-now-btn').prop('disabled', true);
            }
        }

        function validateBooking() {
            if (!selectedStartDate || !selectedEndDate) {
                alert('Please select your check-in and check-out dates');
                return false;
            }

            const nights = selectedEndDate.diff(selectedStartDate, 'days');
            if (nights < 1) {
                alert('Please select at least one night');
                return false;
            }

            // Check if any selected date is in booked dates
            let currentDate = moment(selectedStartDate);
            while (currentDate < selectedEndDate) {
                if (bookedDates.includes(currentDate.format('YYYY-MM-DD'))) {
                    alert('Some of your selected dates are not available');
                    return false;
                }
                currentDate.add(1, 'days');
            }

            return true;
        }

        // Reset dates when the page loads
        updateBookingDetails();
    </script>
</body>
</html>
