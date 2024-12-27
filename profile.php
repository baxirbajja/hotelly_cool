<?php
require_once 'includes/functions.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get user's bookings
$sql = "SELECT b.*, r.name as room_name, r.image as room_image 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    if ($name && $email) {
        // Update basic info
        $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $name, $email, $_SESSION['user_id']);
        $stmt->execute();
        
        // Update password if provided
        if ($current_password && $new_password) {
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                $stmt->execute();
            }
        }
        
        // Refresh page to show updated info
        header('Location: profile.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .profile-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .btn-admin {
            background: #2c3e50;
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-admin:hover {
            background: #34495e;
        }
        
        .btn-admin::before {
            content: '⚙️';
            font-size: 1.2em;
        }
        
        .profile-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-info {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .profile-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-top: 1rem;
        }

        .info-group {
            margin-bottom: 1.5rem;
        }

        .info-group label {
            display: block;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-group p {
            color: #2c3e50;
            font-size: 1.1rem;
            margin: 0;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .update-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-top: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .update-form h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        .profile-form .form-group {
            margin-bottom: 1.5rem;
        }
        
        .profile-form label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .profile-form input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .profile-form input:focus {
            border-color: #2c3e50;
            outline: none;
        }

        .form-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #34495e;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid #2c3e50;
            color: #2c3e50;
        }

        .btn-outline:hover {
            background: #2c3e50;
            color: white;
        }

        .bookings-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #ddd;
        }

        .booking-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 1rem;
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 1rem;
        }

        .booking-image {
            width: 120px;
            height: 120px;
        }

        .booking-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .booking-info {
            padding: 1rem;
        }

        .booking-info h3 {
            margin: 0 0 0.5rem;
            color: #2c3e50;
        }

        .booking-info p {
            margin: 0.3rem 0;
            color: #666;
        }

        .status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-pending {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .no-bookings {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .no-bookings a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }

        .no-bookings a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .booking-card {
                grid-template-columns: 1fr;
            }

            .booking-image {
                width: 100%;
                height: 200px;
            }
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
                    <a href="profile.php" class="nav-link active"><?php echo htmlspecialchars($user['name']); ?></a>
                    <a href="logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">Login</a>
                    <a href="register.php" class="nav-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container" style="padding-top: 8rem;">
        <div class="profile-section">
            <div class="profile-header">
                <h1>My Profile</h1>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin/index.php" class="btn btn-admin">Go to Dashboard</a>
                <?php endif; ?>
            </div>
            <div class="profile-content">
                <div class="profile-info">
                    <h2>Profile Information</h2>
                    <div class="profile-card">
                        <div class="info-group">
                            <label>Name</label>
                            <p><?php echo htmlspecialchars($user['name']); ?></p>
                        </div>
                        <div class="info-group">
                            <label>Email</label>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <button type="button" class="btn btn-outline" onclick="toggleUpdateForm()">Update Information</button>
                    </div>

                    <div class="update-form" style="display: none;">
                        <h3>Update Profile</h3>
                        <form method="POST" class="profile-form">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <h3>Change Password</h3>
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password">
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password">
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn">Save Changes</button>
                                <button type="button" class="btn btn-outline" onclick="toggleUpdateForm()">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <div class="bookings-section">
                        <h2>Recent Bookings</h2>
                        <div class="recent-bookings">
                            <?php if (!empty($bookings)): ?>
                                <?php foreach (array_slice($bookings, 0, 3) as $booking): ?>
                                    <div class="booking-card">
                                        <div class="booking-image">
                                            <?php if (!empty($booking['room_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($booking['room_image']); ?>" alt="<?php echo htmlspecialchars($booking['room_name']); ?>">
                                            <?php else: ?>
                                                <div class="no-image">No Image Available</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="booking-info">
                                            <h3><?php echo htmlspecialchars($booking['room_name']); ?></h3>
                                            <p>Check-in: <?php echo date('M d, Y', strtotime($booking['check_in'])); ?></p>
                                            <p>Check-out: <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></p>
                                            <p class="status status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <a href="bookings.php" class="btn btn-outline">View All Bookings</a>
                            <?php else: ?>
                                <p class="no-bookings">You haven't made any bookings yet. <a href="rooms.php">Browse our rooms</a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();

        function toggleUpdateForm() {
            const updateForm = document.querySelector('.update-form');
            updateForm.style.display = updateForm.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>
