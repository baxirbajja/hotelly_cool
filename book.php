<?php
require_once 'includes/functions.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'];
    $dates = $_POST['dates'];
    $total_price = $_POST['total_price'];
    $user_id = $_SESSION['user_id'];

    // Split the date range
    $date_parts = explode(' - ', $dates);
    if (count($date_parts) !== 2) {
        $error = "Invalid date range";
    } else {
        $check_in = date('Y-m-d', strtotime($date_parts[0]));
        $check_out = date('Y-m-d', strtotime($date_parts[1]));

        // Validate dates
        $check_in_date = strtotime($check_in);
        $check_out_date = strtotime($check_out);
        $today = strtotime(date('Y-m-d'));

        if ($check_in_date < $today) {
            $error = "Check-in date cannot be in the past";
        } elseif ($check_out_date <= $check_in_date) {
            $error = "Check-out date must be after check-in date";
        } else {
            // Verify dates are still available
            $booked_dates = getBookedDates($room_id);
            $is_available = true;
            
            $current_date = new DateTime($check_in);
            $end_date = new DateTime($check_out);
            
            while ($current_date < $end_date) {
                if (in_array($current_date->format('Y-m-d'), $booked_dates)) {
                    $is_available = false;
                    break;
                }
                $current_date->modify('+1 day');
            }

            if (!$is_available) {
                $error = "Selected dates are no longer available";
            } else {
                if (createBooking($room_id, $user_id, $check_in, $check_out, $total_price)) {
                    $_SESSION['success'] = "Booking created successfully!";
                    header('Location: bookings.php');
                    exit;
                } else {
                    $error = "Failed to create booking. Please try again.";
                }
            }
        }
    }
}

// Get room details
$room = null;
if (isset($_GET['room_id'])) {
    $room = getRoomById($_GET['room_id']);
    if (!$room) {
        header('Location: rooms.php');
        exit;
    }
}

// Get booked dates for the room
$booked_dates = [];
if ($room) {
    $booked_dates = getBookedDates($room['id']);
}

// Convert booked dates to JSON for JavaScript
$booked_dates_json = json_encode($booked_dates);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - <?php echo htmlspecialchars($room['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        .booking-form {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .booking-total {
            margin-top: 20px;
            padding: 15px;
            background: #f8f8f8;
            border-radius: 4px;
        }
        .error-message {
            color: #d32f2f;
            margin-bottom: 15px;
        }
        .booked-date {
            background-color: #ffebee !important;
            color: #d32f2f !important;
            text-decoration: line-through;
        }
        main {
            margin-top: 120px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <div class="container">
            <div class="booking-form">
                <h1 class="serif">Book <?php echo htmlspecialchars($room['name']); ?></h1>
                
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                    <input type="hidden" name="price_per_night" value="<?php echo $room['price']; ?>">

                    <div class="form-group">
                        <label>Select Dates</label>
                        <input type="text" name="dates" id="date-range" class="form-control" required>
                    </div>

                    <div class="booking-total">
                        <div class="total-nights">Total nights: <span id="total-nights">0</span></div>
                        <h3>Total: $<span id="total-price">0.00</span></h3>
                        <input type="hidden" name="total_price" id="total-price-input" value="0">
                    </div>

                    <button type="submit" class="btn btn-primary">Confirm Booking</button>
                </form>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        const bookedDates = <?php echo $booked_dates_json; ?>;
        const pricePerNight = <?php echo $room['price']; ?>;

        $(document).ready(function() {
            $('#date-range').daterangepicker({
                opens: 'center',
                minDate: moment(),
                isInvalidDate: function(date) {
                    const dateStr = date.format('YYYY-MM-DD');
                    return bookedDates.includes(dateStr);
                },
                isCustomDate: function(date) {
                    const dateStr = date.format('YYYY-MM-DD');
                    if (bookedDates.includes(dateStr)) {
                        return 'booked-date';
                    }
                }
            }, function(start, end, label) {
                // Calculate total nights and price
                const nights = end.diff(start, 'days');
                const totalPrice = nights * pricePerNight;
                
                $('#total-nights').text(nights);
                $('#total-price').text(totalPrice.toFixed(2));
                $('#total-price-input').val(totalPrice);
            });
        });
    </script>
</body>
</html>
