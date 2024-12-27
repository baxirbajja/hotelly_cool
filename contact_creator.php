<?php
require_once 'includes/functions.php';
session_start();

// Get user name if logged in
$user_name = '';
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT name FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_name = $user['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Creator - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .contact-creator {
            padding: 100px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .contact-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .creator-profile {
            text-align: center;
            margin-bottom: 50px;
        }
        .creator-profile img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            border: 5px solid white;
        }
        .creator-profile h1 {
            font-size: 2.8em;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .creator-profile p {
            font-size: 1.2em;
            color: #34495e;
            max-width: 600px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }
        .contact-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }
        .contact-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }
        .contact-card:hover {
            transform: translateY(-5px);
        }
        .contact-card i {
            font-size: 2.5em;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .contact-card h3 {
            font-size: 1.4em;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .contact-card p, .contact-card a {
            color: #34495e;
            text-decoration: none;
            transition: color 0.3s;
        }
        .contact-card a:hover {
            color: #2c3e50;
        }
        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }
        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #2c3e50;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .social-link:hover {
            transform: translateY(-3px);
            background: #34495e;
        }
        .social-link i {
            font-size: 1.5em;
        }
        .back-button {
            display: inline-block;
            margin-top: 40px;
            padding: 15px 30px;
            background: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #34495e;
        }
    </style>
</head>
<body>


    <!-- Contact Creator Section -->
    <section class="contact-creator">
        <div class="contact-content" data-aos="fade-up">
            <div class="creator-profile">
                <a href="https://portfoli-os-git-main-baxirbajjas-projects.vercel.app/" target="_blank">
                    <img src="img\other\me.png" alt="Bachir Bajja" style="cursor: pointer;">
                </a>
                <a href="https://portfoli-os-git-main-baxirbajjas-projects.vercel.app/" target="_blank" style="text-decoration: none;">
                    <h1 class="serif" style="cursor: pointer;">Bachir Bajja</h1>
                </a>
                <p>A passionate web developer dedicated to creating elegant and functional web applications. Connect with me through any of the following channels.</p>
            </div>

            <div class="contact-cards">
                <div class="contact-card" data-aos="fade-up" data-aos-delay="100">
                    <i class="fas fa-envelope"></i>
                    <h3>Email</h3>
                    <a href="mailto:medbachirbajja@gmail.com">medbachirbajja@gmail.com</a>
                </div>

                <div class="contact-card" data-aos="fade-up" data-aos-delay="200">
                    <i class="fas fa-phone"></i>
                    <h3>Phone</h3>
                    <a href="tel:+212637492006">+212 637 492 006</a>
                </div>

                <div class="contact-card" data-aos="fade-up" data-aos-delay="300">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Location</h3>
                    <p>Tamesna, Morocco</p>
                </div>
            </div>

            <div class="social-links" data-aos="fade-up" data-aos-delay="400">
                <a href="https://www.instagram.com/baxir_bj/" class="social-link" target="_blank">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="https://web.facebook.com/mohamed.e.bachir.3/" class="social-link" target="_blank">
                    <i class="fab fa-facebook-f"></i>
                </a>
            </div>

            <div style="text-align: center; margin-top: 50px;">
                <a href="about_project.php" class="back-button">Back to Project Info</a>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="serif">HOTELLY</h3>
                    <p>An academic project showcasing web development skills.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="hotels.php">Hotels</a></li>
                        <li><a href="rooms.php">Rooms</a></li>
                        <li><a href="about_project.php">About Project</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Tamesna, Morocco<br>Phone: +212 637 492 006<br>Email: medbachirbajja@gmail.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Hotelly/baxir. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>
