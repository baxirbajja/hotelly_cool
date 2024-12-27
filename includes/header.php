<?php
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
