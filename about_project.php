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
    <title>About Project - Hotelly</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .about-project {
            padding: 100px 0;
            background-color: #f8f9fa;
        }
        .about-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
            text-align: center;
        }
        .about-content h1 {
            font-size: 2.5em;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .about-content p {
            font-size: 1.1em;
            line-height: 1.8;
            margin-bottom: 20px;
            color: #34495e;
        }
        .creator-info {
            margin-top: 50px;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .creator-info h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .contact-links {
            margin-top: 30px;
        }
        .contact-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background-color: #2c3e50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .contact-links a:hover {
            background-color: #34495e;
        }
    </style>
</head>
<body>
   

    <!-- About Project Section -->
    <section class="about-project">
        <div class="about-content" data-aos="fade-up">
            <h1 class="serif">About This Project</h1>
            <p>Welcome to Hotelly! This website is an academic project created by Bachir Bajja. It's important to note that this is not a real hotel booking platform, but rather a demonstration of web development skills and capabilities.</p>
            
            <p>This project was developed as part of academic coursework to showcase various web development concepts including:</p>
            <ul style="text-align: left; margin: 20px 0;">
                <li>Full-stack web development</li>
                <li>Database management</li>
                <li>User authentication and authorization</li>
                <li>Responsive design</li>
                <li>Hotel booking system implementation</li>
            </ul>

            <div class="creator-info">
                <h2 class="serif">About the Creator</h2>
                <p>Bachir Bajja is a passionate web developer with a focus on creating intuitive and user-friendly web applications. This project represents a combination of technical skills and creative design thinking.</p>
                
                <div class="contact-links">
                    <a href="contact_creator.php">Contact Creator</a>
                    <a href="index.php">Back to Home</a>
                </div>
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
                    <p>Tamesna, Morocco<br>Phone: +212 63 74 92 006<br>Email: medbachirbajja@gmail.com</p>
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
