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
