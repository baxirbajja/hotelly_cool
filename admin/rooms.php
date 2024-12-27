<?php
session_start();
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get all rooms and hotels
$rooms = getAllRooms();
$hotels = getAllHotels();

// Function to get hotel name
function getHotelName($hotel_id) {
    global $hotels;
    foreach ($hotels as $hotel) {
        if ($hotel['id'] == $hotel_id) {
            return $hotel['name'];
        }
    }
    return 'Unknown Hotel';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $room_data = [
                    'hotel_id' => $_POST['hotel_id'],
                    'name' => $_POST['name'],
                    'type' => $_POST['type'],
                    'price' => $_POST['price'],
                    'description' => $_POST['description'],
                    'capacity' => $_POST['capacity'],
                    'size' => $_POST['size'],
                    'view_type' => $_POST['view_type'],
                    'amenities' => json_encode(explode(',', $_POST['amenities'])),
                    'image' => $_POST['image_url']  // Use image URL directly
                ];

                if (addRoom($room_data)) {
                    $_SESSION['success'] = "Room added successfully!";
                    echo "<script>window.location.href = 'rooms.php';</script>";
                    exit;
                } else {
                    $_SESSION['error'] = "Failed to add room.";
                    echo "<script>window.location.href = 'rooms.php';</script>";
                    exit;
                }
                break;

            case 'edit':
                $room_id = $_POST['room_id'];
                $room_data = [
                    'hotel_id' => $_POST['hotel_id'],
                    'name' => $_POST['name'],
                    'type' => $_POST['type'],
                    'price' => $_POST['price'],
                    'description' => $_POST['description'],
                    'capacity' => $_POST['capacity'],
                    'size' => $_POST['size'],
                    'view_type' => $_POST['view_type'],
                    'amenities' => json_encode(explode(',', $_POST['amenities'])),
                    'image' => $_POST['image_url']  // Use image URL directly
                ];

                if (updateRoom($room_id, $room_data)) {
                    $_SESSION['success'] = "Room updated successfully!";
                    echo "<script>window.location.href = 'rooms.php';</script>";
                    exit;
                } else {
                    $_SESSION['error'] = "Failed to update room.";
                    echo "<script>window.location.href = 'rooms.php';</script>";
                    exit;
                }
                break;

            case 'delete':
                if (deleteRoom($_POST['room_id'])) {
                    $_SESSION['success'] = "Room deleted successfully!";
                    echo "<script>window.location.href = 'rooms.php';</script>";
                    exit;
                } else {
                    $_SESSION['error'] = "Failed to delete room.";
                    echo "<script>window.location.href = 'rooms.php';</script>";
                    exit;
                }
                break;
        }
    }
}

// Get room types
$room_types = ['Standard', 'Deluxe', 'Suite', 'Family'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Hotelly Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .admin-nav {
            background: var(--secondary-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        .admin-nav-left {
            display: flex;
            align-items: center;
        }
        .admin-nav .logo {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            font-family: 'Playfair Display', serif;
            transition: color 0.3s;
        }
        .admin-nav .logo:hover {
            color: #fff;
        }
        .admin-nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .admin-nav-right .nav-link {
            color: #fff;
            text-decoration: none;
            margin-left: 2rem;
            font-family: 'Montserrat', sans-serif;
            transition: color 0.3s;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .admin-nav-right .nav-link:hover,
        .admin-nav-right .nav-link.active {
            color: var(--primary-color);
        }
        .admin-container {
            padding: 6rem 2rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        .admin-title {
            margin-bottom: 2rem;
            color: var(--secondary-color);
            font-family: 'Playfair Display', serif;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .admin-table th,
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .admin-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--secondary-color);
        }
        .admin-table tr:hover {
            background: #f8f9fa;
        }
        .room-thumbnail {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .admin-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin: 0 0.25rem;
        }
        .admin-btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .admin-btn-danger {
            background: #dc3545;
            color: white;
        }
        .admin-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--secondary-color);
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="url"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: 'Montserrat', sans-serif;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }

        .room-thumbnail {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .room-image-cell {
            width: 120px;
        }
    </style>
</head>
<body>
    <!-- Admin Navigation -->
    <nav class="admin-nav">
        <div class="admin-nav-left">
            <a href="index.php" class="logo">HOTELLY ADMIN</a>
        </div>
        <div class="admin-nav-right">
            <a href="index.php" class="nav-link">Dashboard</a>
            <a href="hotels.php" class="nav-link">Hotels</a>
            <a href="rooms.php" class="nav-link active">Rooms</a>
            <a href="bookings.php" class="nav-link">Bookings</a>
            <a href="users.php" class="nav-link">Users</a>
            <a href="../logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Room Management</h1>
            <button class="admin-btn admin-btn-primary" onclick="document.getElementById('addRoomModal').style.display='block'">Add New Room</button>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Rooms Table -->
        <div class="table-responsive" data-aos="fade-up">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Hotel</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Capacity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td class="room-image-cell">
                                <?php if (!empty($room['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($room['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($room['name']); ?>" 
                                         class="room-thumbnail"
                                         onerror="this.onerror=null; this.src='../assets/images/default-room.jpg';">
                                <?php else: ?>
                                    <img src="../assets/images/default-room.jpg" 
                                         alt="Default room image" 
                                         class="room-thumbnail">
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($room['name']); ?></td>
                            <td><?php echo htmlspecialchars(getHotelName($room['hotel_id'])); ?></td>
                            <td><?php echo htmlspecialchars($room['type']); ?></td>
                            <td>$<?php echo number_format($room['price'], 2); ?></td>
                            <td><?php echo $room['capacity']; ?> persons</td>
                            <td>
                                <button class="admin-btn admin-btn-primary" onclick='editRoom(<?php echo str_replace("'", "\\'", json_encode($room)); ?>)'>Edit</button>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this room?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                    <button type="submit" class="admin-btn admin-btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div id="addRoomModal" class="modal">
        <div class="modal-content">
            <h2>Add New Room</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="hotel_id">Hotel:</label>
                    <select name="hotel_id" id="hotel_id" required>
                        <?php foreach ($hotels as $hotel): ?>
                            <option value="<?php echo $hotel['id']; ?>"><?php echo htmlspecialchars($hotel['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="name">Room Name:</label>
                    <input type="text" name="name" id="name" required>
                </div>

                <div class="form-group">
                    <label for="type">Room Type:</label>
                    <input type="text" name="type" id="type" required>
                </div>

                <div class="form-group">
                    <label for="price">Price per Night:</label>
                    <input type="number" name="price" id="price" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="image_url">Image URL:</label>
                    <input type="url" name="image_url" id="image_url" required placeholder="https://example.com/image.jpg">
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="capacity">Capacity (persons):</label>
                    <input type="number" name="capacity" id="capacity" required>
                </div>

                <div class="form-group">
                    <label for="size">Room Size (sq m):</label>
                    <input type="number" name="size" id="size" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="view_type">View Type:</label>
                    <input type="text" name="view_type" id="view_type" required>
                </div>

                <div class="form-group">
                    <label for="amenities">Amenities (comma-separated):</label>
                    <input type="text" name="amenities" id="amenities" required>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="admin-btn admin-btn-primary">Add Room</button>
                    <button type="button" class="admin-btn admin-btn-secondary" onclick="document.getElementById('addRoomModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();

        function editRoom(room) {
            // Create the hotel options HTML
            let hotelOptions = '';
            <?php foreach ($hotels as $hotel): ?>
                hotelOptions += `<option value="<?php echo $hotel['id']; ?>"><?php echo htmlspecialchars($hotel['name']); ?></option>`;
            <?php endforeach; ?>

            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';  // Make sure modal is visible
            modal.innerHTML = `
                <div class="modal-content">
                    <h2>Edit Room</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="room_id" value="${room.id}">
                        
                        <div class="form-group">
                            <label for="edit_hotel_id">Hotel:</label>
                            <select name="hotel_id" id="edit_hotel_id" required>
                                ${hotelOptions}
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_name">Room Name:</label>
                            <input type="text" name="name" id="edit_name" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_type">Room Type:</label>
                            <input type="text" name="type" id="edit_type" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_price">Price per Night:</label>
                            <input type="number" name="price" id="edit_price" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_image_url">Image URL:</label>
                            <input type="url" name="image_url" id="edit_image_url" required placeholder="https://example.com/image.jpg">
                        </div>

                        <div class="form-group">
                            <label for="edit_description">Description:</label>
                            <textarea name="description" id="edit_description" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="edit_capacity">Capacity (persons):</label>
                            <input type="number" name="capacity" id="edit_capacity" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_size">Room Size (sq m):</label>
                            <input type="number" name="size" id="edit_size" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_view_type">View Type:</label>
                            <input type="text" name="view_type" id="edit_view_type" required>
                        </div>

                        <div class="form-group">
                            <label for="edit_amenities">Amenities (comma-separated):</label>
                            <input type="text" name="amenities" id="edit_amenities" required>
                        </div>

                        <div class="form-buttons">
                            <button type="submit" class="admin-btn admin-btn-primary">Update Room</button>
                            <button type="button" class="admin-btn admin-btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);

            // Set current values
            document.getElementById('edit_hotel_id').value = room.hotel_id;
            document.getElementById('edit_name').value = room.name;
            document.getElementById('edit_type').value = room.type;
            document.getElementById('edit_price').value = room.price;
            document.getElementById('edit_image_url').value = room.image || '';
            document.getElementById('edit_description').value = room.description;
            document.getElementById('edit_capacity').value = room.capacity;
            document.getElementById('edit_size').value = room.size;
            document.getElementById('edit_view_type').value = room.view_type;
            document.getElementById('edit_amenities').value = Array.isArray(room.amenities) ? room.amenities.join(',') : (room.amenities ? JSON.parse(room.amenities).join(',') : '');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
