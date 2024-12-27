<?php
require_once 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$booking_id = $_GET['booking_id'] ?? null;
if (!$booking_id) {
    header('Location: bookings.php');
    exit;
}

// Get booking details
$sql = "SELECT b.*, r.name as room_name, r.price as room_price, h.name as hotel_name 
        FROM bookings b 
        LEFT JOIN rooms r ON b.room_id = r.id 
        LEFT JOIN hotels h ON r.hotel_id = h.id 
        WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: bookings.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://www.paypal.com/sdk/js?client-id=test&currency=USD"></script>
    <style>
        .payment-container {
            max-width: 600px;
            margin: 120px auto 40px;
            padding: 20px;
        }
        .booking-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .payment-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .payment-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .payment-tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
        }
        .payment-tab.active {
            border-bottom: 2px solid #3498db;
            color: #3498db;
        }
        .payment-content > div {
            display: none;
        }
        .payment-content > div.active {
            display: block;
        }
        #credit-card-form {
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn-pay {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }
        .btn-pay:hover {
            background-color: #27ae60;
        }
        #paypal-button-container {
            margin-top: 20px;
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
                <a href="bookings.php" class="nav-link">My Bookings</a>
                <a href="profile.php" class="nav-link"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></a>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="payment-container">
        <div class="booking-summary">
            <h2>Booking Summary</h2>
            <p><strong>Hotel:</strong> <?php echo htmlspecialchars($booking['hotel_name']); ?></p>
            <p><strong>Room:</strong> <?php echo htmlspecialchars($booking['room_name']); ?></p>
            <p><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['check_in'])); ?></p>
            <p><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></p>
            <p><strong>Total Amount:</strong> $<?php echo number_format($booking['total_price'], 2); ?></p>
        </div>

        <div class="payment-section">
            <div class="payment-tabs">
                <button class="payment-tab active" data-tab="paypal">PayPal</button>
                <button class="payment-tab" data-tab="credit-card">Credit Card</button>
                <button class="payment-tab" data-tab="pay-later">Pay Later</button>
            </div>

            <div class="payment-content">
                <div id="paypal-tab" class="active">
                    <div id="paypal-button-container"></div>
                </div>

                <div id="credit-card-tab">
                    <form id="credit-card-form">
                        <div class="form-group">
                            <label for="card-number">Card Number</label>
                            <input type="text" id="card-number" class="form-control" placeholder="1234 5678 9012 3456" required>
                        </div>
                        <div class="form-group">
                            <label for="expiry">Expiry Date</label>
                            <input type="text" id="expiry" class="form-control" placeholder="MM/YY" required>
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" class="form-control" placeholder="123" required>
                        </div>
                        <button type="submit" class="btn-pay">Pay $<?php echo number_format($booking['total_price'], 2); ?></button>
                    </form>
                </div>

                <div id="pay-later-tab">
                    <form action="process_pay_later.php" method="POST">
                        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                        <p>By selecting Pay Later, you agree to pay the full amount at check-in.</p>
                        <p>Total amount to be paid: $<?php echo number_format($booking['total_price'], 2); ?></p>
                        <button type="submit" class="btn-pay">Confirm Pay Later</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Payment tab switching
        document.querySelectorAll('.payment-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Update tabs
                document.querySelectorAll('.payment-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // Update content
                document.querySelectorAll('.payment-content > div').forEach(content => content.classList.remove('active'));
                document.getElementById(tab.dataset.tab + '-tab').classList.add('active');
            });
        });

        // PayPal integration
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '<?php echo $booking['total_price']; ?>'
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    handlePaymentSuccess('paypal', details.id);
                });
            }
        }).render('#paypal-button-container');

        // Credit card form handling
        document.getElementById('credit-card-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const button = this.querySelector('button');
            button.disabled = true;
            button.textContent = 'Processing...';

            // Simulate payment processing
            const paymentId = 'CC_' + Date.now();
            handlePaymentSuccess('credit_card', paymentId);
        });

        function handlePaymentSuccess(method, paymentId) {
            fetch('process_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    booking_id: <?php echo $booking_id; ?>,
                    payment_id: paymentId,
                    payment_method: method
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'bookings.php?payment=success';
                } else {
                    throw new Error(data.message || 'Payment failed');
                }
            })
            .catch(error => {
                alert(error.message || 'Error processing payment. Please try again.');
                const button = document.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = false;
                    button.textContent = 'Pay $<?php echo number_format($booking['total_price'], 2); ?>';
                }
            });
        }

        // Format credit card number
        document.getElementById('card-number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            let formattedValue = '';
            for (let i = 0; i < value.length; i++) {
                if (i > 0 && i % 4 === 0) formattedValue += ' ';
                formattedValue += value[i];
            }
            e.target.value = formattedValue;
        });

        // Format expiry date
        document.getElementById('expiry').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 4) value = value.slice(0, 4);
            if (value.length > 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            e.target.value = value;
        });

        // Format CVV
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '').slice(0, 3);
        });
    </script>
</body>
</html>
