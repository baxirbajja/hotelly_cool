<?php
require_once 'includes/functions.php';
session_start();

if (isset($_POST['email'])) {
    $_SESSION['success'] = "Since this is a testing website, no real email will be sent. For testing purposes, you can log in with any registered email and password: 'password123'";
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .forgot-password-container {
            max-width: 400px;
            margin: 120px auto 40px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .form-title {
            font-family: 'Playfair Display', serif;
            text-align: center;
            margin-bottom: 30px;
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
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-submit:hover {
            background-color: #2980b9;
        }
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        .back-to-login a {
            color: #3498db;
            text-decoration: none;
        }
        .back-to-login a:hover {
            text-decoration: underline;
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
                <a href="login.php" class="nav-link">Login</a>
            </div>
        </div>
    </nav>

    <div class="forgot-password-container">
        <h2 class="form-title">Forgot Password</h2>
        
        <div class="test-notice">
            <strong>⚠️ Test Website Notice</strong>
            <p>This is a testing website. No real emails will be sent.</p>
            <p>For testing, use any registered email and password: 'password123'</p>
        </div>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <button type="submit" class="btn-submit">Reset Password</button>
        </form>

        <div class="back-to-login">
            <a href="login.php">Back to Login</a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
