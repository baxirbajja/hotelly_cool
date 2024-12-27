<?php
require_once '../includes/functions.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $data = [
                    'name' => $_POST['name'],
                    'city' => $_POST['city'],
                    'address' => $_POST['address'],
                    'description' => $_POST['description'],
                    'amenities' => json_encode(explode(',', $_POST['amenities'])),
                    'image' => ''  // Initialize with empty string
                ];
                
                // Handle image based on type
                if ($_POST['image_type'] === 'url') {
                    $data['image'] = $_POST['image_url'];
                } else if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
                    try {
                        $data['image'] = uploadImage($_FILES['image_upload']);
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Error uploading image: " . $e->getMessage();
                        header('Location: hotels.php');
                        exit;
                    }
                }
                if (addHotel($data)) {
                    $_SESSION['success'] = "Hotel added successfully!";
                    header('Location: hotels.php');
                    exit;
                } else {
                    $_SESSION['error'] = "Error adding hotel.";
                    header('Location: hotels.php');
                    exit;
                }
                break;

            case 'edit':
                $data = [
                    'id' => $_POST['hotel_id'],
                    'name' => $_POST['name'],
                    'city' => $_POST['city'],
                    'address' => $_POST['address'],
                    'description' => $_POST['description'],
                    'amenities' => json_encode(explode(',', $_POST['amenities'])),
                    'image' => $_POST['current_image']  // Keep current image by default
                ];

                // Handle image update
                if ($_POST['image_type'] === 'url' && !empty($_POST['image_url'])) {
                    $data['image'] = $_POST['image_url'];
                } else if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
                    try {
                        $data['image'] = uploadImage($_FILES['image_upload']);
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Error uploading image: " . $e->getMessage();
                        header('Location: hotels.php');
                        exit;
                    }
                }
                if (updateHotel($_POST['hotel_id'], $data)) {
                    $_SESSION['success'] = "Hotel updated successfully!";
                    header('Location: hotels.php');
                    exit;
                } else {
                    $_SESSION['error'] = "Error updating hotel.";
                    header('Location: hotels.php');
                    exit;
                }
                break;

            case 'delete':
                if (deleteHotel($_POST['hotel_id'])) {
                    $_SESSION['success'] = "Hotel deleted successfully!";
                    header('Location: hotels.php');
                    exit;
                } else {
                    $_SESSION['error'] = "Cannot delete hotel that has rooms assigned to it.";
                    header('Location: hotels.php');
                    exit;
                }
                break;
        }
    }
}

// Get all hotels
$hotels = getAllHotels();

// Get success/error messages
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Management - Hotelly Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            overflow-y: auto;
            z-index: 1000;
        }
        .modal-content {
            background: #fff;
            width: 90%;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border-radius: 8px;
            position: relative;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group textarea {
            min-height: 100px;
        }
        .form-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .image-input-group {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
        }
        .input-option {
            margin-bottom: 10px;
        }
        .input-option:last-child {
            margin-bottom: 0;
        }
        .image-input {
            width: 100%;
            margin-top: 5px;
        }
        .input-option label {
            margin-left: 5px;
            display: inline-block;
        }
        .hotel-thumbnail {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            transition: transform 0.2s;
        }
        .hotel-thumbnail:hover {
            transform: scale(1.1);
            cursor: pointer;
        }
        .admin-table td {
            vertical-align: middle;
            padding: 10px;
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
            <a href="hotels.php" class="nav-link active">Hotels</a>
            <a href="rooms.php" class="nav-link">Rooms</a>
            <a href="bookings.php" class="nav-link">Bookings</a>
            <a href="users.php" class="nav-link">Users</a>
            <a href="../logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-header">
            <div class="admin-header-content">
                <h1>Manage Hotels</h1>
                <button class="admin-btn" onclick="showAddHotelModal()">Add New Hotel</button>
            </div>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
        </div>

        <div class="admin-content">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>City</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $hotels = getAllHotels();
                        foreach ($hotels as $hotel): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($hotel['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($hotel['name']); ?>" 
                                         class="hotel-thumbnail">
                                </td>
                                <td><?php echo htmlspecialchars($hotel['name']); ?></td>
                                <td><?php echo htmlspecialchars($hotel['city']); ?></td>
                                <td>
                                    <button class="admin-btn admin-btn-primary" onclick='editHotel(<?php echo json_encode($hotel); ?>)'>Edit</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this hotel?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                                        <button type="submit" class="admin-btn admin-btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Hotel Modal -->
    <div id="addHotelModal" class="modal">
        <div class="modal-content">
            <h2>Add New Hotel</h2>
            <form method="POST" class="admin-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Hotel Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" required>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="amenities">Amenities (comma-separated)</label>
                    <input type="text" id="amenities" name="amenities" placeholder="Pool, Spa, Restaurant, etc." required>
                </div>

                <div class="form-group">
                    <label>Image Source</label>
                    <div class="radio-group">
                        <input type="radio" id="url" name="image_type" value="url" checked>
                        <label for="url">URL</label>
                        <input type="radio" id="upload" name="image_type" value="upload">
                        <label for="upload">Upload</label>
                    </div>
                </div>

                <div class="form-group" id="url-input">
                    <label for="image_url">Image URL</label>
                    <input type="url" id="image_url" name="image_url">
                </div>

                <div class="form-group" id="upload-input" style="display: none;">
                    <label for="image_upload">Upload Image</label>
                    <input type="file" id="image_upload" name="image_upload" accept="image/*">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Add Hotel</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('addHotelModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Hotel Modal -->
    <div id="editHotelModal" class="modal">
        <div class="modal-content">
            <h2>Edit Hotel</h2>
            <form method="POST" class="admin-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="hotel_id" id="edit_hotel_id">
                <input type="hidden" name="current_image" id="edit_current_image">

                <div class="form-group">
                    <label for="edit_name">Hotel Name</label>
                    <input type="text" id="edit_name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="edit_city">City</label>
                    <input type="text" id="edit_city" name="city" required>
                </div>

                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <input type="text" id="edit_address" name="address" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" required></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_amenities">Amenities (comma-separated)</label>
                    <input type="text" id="edit_amenities" name="amenities" required>
                </div>

                <div class="form-group">
                    <label>Image Source</label>
                    <div class="radio-group">
                        <input type="radio" id="url" name="image_type" value="url" checked>
                        <label for="url">URL</label>
                        <input type="radio" id="upload" name="image_type" value="upload">
                        <label for="upload">Upload</label>
                    </div>
                </div>

                <div class="form-group" id="url-input">
                    <label for="image_url">Image URL</label>
                    <input type="url" id="image_url" name="image_url">
                </div>

                <div class="form-group" id="upload-input" style="display: none;">
                    <label for="image_upload">Upload Image</label>
                    <input type="file" id="image_upload" name="image_upload" accept="image/*">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Update Hotel</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('editHotelModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();

        // Modal functions
        const addModal = document.getElementById('addHotelModal');
        const editModal = document.getElementById('editHotelModal');

        function showAddHotelModal() {
            addModal.style.display = 'block';
        }

        function editHotel(hotel) {
            // Parse amenities if it's a string
            let amenities = hotel.amenities;
            if (typeof amenities === 'string') {
                try {
                    amenities = JSON.parse(amenities);
                } catch (e) {
                    console.error('Error parsing amenities:', e);
                    amenities = [];
                }
            }
            
            // Populate form fields
            document.getElementById('edit_hotel_id').value = hotel.id;
            document.getElementById('edit_current_image').value = hotel.image;
            document.getElementById('edit_name').value = hotel.name;
            document.getElementById('edit_city').value = hotel.city;
            document.getElementById('edit_address').value = hotel.address;
            document.getElementById('edit_description').value = hotel.description;
            document.getElementById('edit_amenities').value = Array.isArray(amenities) ? amenities.join(', ') : '';
            
            // Show modal
            editModal.style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === addModal) {
                addModal.style.display = 'none';
            }
            if (event.target === editModal) {
                editModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
