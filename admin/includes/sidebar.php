<aside class="admin-sidebar">
    <nav>
        <ul>
            <li><a href="index.php" <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'class="active"' : ''; ?>>Dashboard</a></li>
            <li><a href="hotels.php" <?php echo basename($_SERVER['PHP_SELF']) == 'hotels.php' ? 'class="active"' : ''; ?>>Hotels</a></li>
            <li><a href="rooms.php" <?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'class="active"' : ''; ?>>Rooms</a></li>
            <li><a href="bookings.php" <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'class="active"' : ''; ?>>Bookings</a></li>
            <li><a href="users.php" <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'class="active"' : ''; ?>>Users</a></li>
            <li><a href="reviews.php" <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'class="active"' : ''; ?>>Reviews</a></li>
        </ul>
    </nav>
</aside>
