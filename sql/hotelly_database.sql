-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 27 déc. 2024 à 23:28
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `hotelly`
--
-- Create and use the database
CREATE DATABASE IF NOT EXISTS `hotelly`;
USE `hotelly`;

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_reservation` (IN `p_user_id` INT, IN `p_room_id` INT, IN `p_check_in` DATE, IN `p_check_out` DATE)   BEGIN
    DECLARE total_price DECIMAL(10,2);
    DECLARE booking_id INT;
    
    -- Check if room is available
    IF NOT is_room_available(p_room_id, p_check_in, p_check_out) THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Room is not available for the selected dates';
    END IF;
    
    -- Calculate total price
    SET total_price = calculate_total_price(p_room_id, p_check_in, p_check_out);
    
    -- Insert the booking
    INSERT INTO bookings (
        user_id,
        room_id,
        check_in,
        check_out,
        guests,
        total_price,
        status,
        created_at
    ) VALUES (
        p_user_id,
        p_room_id,
        p_check_in,
        p_check_out,
        2, -- Default guests value
        total_price,
        'pending',
        NOW()
    );
    
    -- Get the inserted booking ID
    SET booking_id = LAST_INSERT_ID();
    SELECT booking_id AS id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `cancel_reservation` (IN `p_booking_id` INT, IN `p_user_id` INT)   BEGIN
    -- Check if booking exists and belongs to user
    IF NOT EXISTS (
        SELECT 1 FROM bookings 
        WHERE id = p_booking_id 
        AND user_id = p_user_id
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Booking not found or does not belong to user';
    END IF;
    
    -- Update booking status
    UPDATE bookings
    SET status = 'cancelled'
    WHERE id = p_booking_id
    AND user_id = p_user_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_occupancy_report` (IN `start_date` DATE, IN `end_date` DATE)   BEGIN
    SELECT 
        h.name AS hotel_name,
        COUNT(DISTINCT r.id) AS total_rooms,
        COUNT(DISTINCT CASE 
            WHEN b.status != 'cancelled' 
            AND b.check_in <= end_date 
            AND b.check_out >= start_date 
            THEN b.id 
        END) AS total_bookings,
        ROUND(
            COUNT(DISTINCT CASE 
                WHEN b.status != 'cancelled' 
                AND b.check_in <= end_date 
                AND b.check_out >= start_date 
                THEN b.id 
            END) * 100.0 / NULLIF(COUNT(DISTINCT r.id), 0), 
            2
        ) AS occupancy_rate,
        COALESCE(
            SUM(CASE 
                WHEN b.status != 'cancelled' 
                AND b.check_in <= end_date 
                AND b.check_out >= start_date 
                THEN b.total_price 
                ELSE 0 
            END), 
            0
        ) AS total_revenue
    FROM hotels h
    LEFT JOIN rooms r ON h.id = r.hotel_id
    LEFT JOIN bookings b ON r.id = b.room_id
    GROUP BY h.id, h.name
    ORDER BY total_revenue DESC;
END$$

--
-- Fonctions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `calculate_total_price` (`p_room_id` INT, `p_check_in` DATE, `p_check_out` DATE) RETURNS DECIMAL(10,2) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE price_per_night DECIMAL(10,2);
    DECLARE num_nights INT;
    
    -- Get room price
    SELECT price INTO price_per_night 
    FROM rooms 
    WHERE id = p_room_id;
    
    -- Calculate number of nights
    SET num_nights = DATEDIFF(p_check_out, p_check_in);
    
    RETURN price_per_night * num_nights;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `is_room_available` (`p_room_id` INT, `p_check_in` DATE, `p_check_out` DATE) RETURNS TINYINT(1) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE room_count INT;
    
    SELECT COUNT(*) INTO room_count
    FROM bookings
    WHERE room_id = p_room_id
    AND status != 'cancelled'
    AND (
        (check_in BETWEEN p_check_in AND p_check_out)
        OR (check_out BETWEEN p_check_in AND p_check_out)
        OR (p_check_in BETWEEN check_in AND check_out)
        OR (p_check_out BETWEEN check_in AND check_out)
    );
    
    RETURN room_count = 0;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `guests` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `room_id`, `check_in`, `check_out`, `guests`, `total_price`, `status`, `payment_id`, `payment_method`, `payment_date`, `created_at`) VALUES
(2, 1, 39, '2025-01-07', '2025-01-31', 0, 28800.00, 'confirmed', 'CC_1735261775953', 'credit_card', '2024-12-27 02:09:36', '2024-12-27 01:09:21'),
(5, 1, 44, '2025-01-01', '2025-01-31', 0, 17400.00, 'confirmed', 'CC_1735337442411', 'credit_card', '2024-12-27 23:10:42', '2024-12-27 01:20:37'),
(6, 1, 37, '2025-01-01', '2025-01-31', 0, 13500.00, 'confirmed', NULL, NULL, NULL, '2024-12-27 01:21:19'),
(8, 1, 43, '2025-01-01', '2025-01-31', 0, 10500.00, 'cancelled', 'CC_1735316340564', 'credit_card', '2024-12-27 17:19:00', '2024-12-27 16:18:04'),
(10, 1, 51, '2025-01-01', '2025-01-31', 0, 24000.00, 'confirmed', 'CC_1735330268458', 'credit_card', '2024-12-27 21:11:08', '2024-12-27 20:10:57'),
(15, 5, 41, '2025-02-01', '2025-06-19', 0, 75900.00, 'confirmed', 'CC_1735334972156', 'credit_card', '2024-12-27 22:29:32', '2024-12-27 21:29:11'),
(17, 7, 51, '2025-02-02', '2025-02-28', 0, 20800.00, 'confirmed', 'CC_1735336518103', 'credit_card', '2024-12-27 22:55:18', '2024-12-27 21:55:05'),
(20, 1, 45, '2025-01-01', '2025-01-31', 0, 22500.00, 'pending', NULL, NULL, NULL, '2024-12-27 22:06:02'),
(21, 5, 40, '2025-01-01', '2025-04-30', 0, 214200.00, 'confirmed', 'CC_1735337601246', 'credit_card', '2024-12-27 23:13:21', '2024-12-27 22:13:07');

-- --------------------------------------------------------

--
-- Structure de la table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `hotels`
--

INSERT INTO `hotels` (`id`, `name`, `description`, `city`, `address`, `image`, `amenities`, `created_at`, `updated_at`) VALUES
(26, 'La Mamounia Marrakech', 'La Mamounia is a luxury hotel in Marrakech, Morocco. Set within the walls of the old city, it is one of the most famous hotels in Morocco.', 'Marrakech', 'Avenue Bab Jdid, 40040 Marrakech, Morocco', 'https://i.pinimg.com/originals/42/01/0e/42010e09dca6239196830e743ea86580.jpg', '[\"Swimming Pool\", \"Spa\", \"Restaurant\", \"Bar\", \"Garden\", \"Fitness Center\", \"Free WiFi\"]', '2024-12-26 18:27:43', '2024-12-27 16:24:02'),
(27, 'Royal Mansour Marrakech', 'The Royal Mansour Marrakech offers an unparalleled experience of wonder and authenticity, where Moroccan tradition and hospitality receive a modern touch.', 'Marrakech', 'Rue Abou Abbas El Sebti, 40000 Marrakech, Morocco', 'https://qtxasset.com/quartz/qcloud1/media/image/Royal%20Mansour%20Lobby.jpg?VersionId=G.O7TdsumQVZzuHTZ1Smx61u0aAjko9F', '[\"Private Pool\", \"Spa\", \"Fine Dining\", \"Butler Service\", \"Garden\", \"Hammam\"]', '2024-12-26 18:27:43', '2024-12-27 16:25:03'),
(28, 'Four Seasons Resort Marrakech', 'An oasis of year-round luxury in the heart of the Red City, with views of the Atlas Mountains.', 'Marrakech', '1 Boulevard de la Menara, 40000 Marrakech, Morocco', 'https://www.fourseasons.com/alt/img-opt/~70.1530.0,0000-163,2500-3000,0000-1687,5000/publish/content/dam/fourseasons/images/web/MRK/MRK_959_original.jpg', '[\"Outdoor Pools\", \"Spa\", \"Tennis Courts\", \"Kids Club\", \"Restaurants\", \"Bars\"]', '2024-12-26 18:27:43', '2024-12-27 16:25:47'),
(29, 'Mazagan Beach Resort', 'Located on the Atlantic coast, Mazagan Beach Resort offers breathtaking views and world-class amenities.', 'El Jadida', 'Route de Casablanca, 24000 El Jadida, Morocco', 'https://avatars.mds.yandex.net/get-altay/5445147/2a0000017d3069ccb25a8d1897d8fa2cae23/XXL_height', '[\"Beach Access\", \"Golf Course\", \"Casino\", \"Spa\", \"Kids Club\", \"Multiple Restaurants\"]', '2024-12-26 18:27:43', '2024-12-27 16:26:21'),
(30, 'Fairmont Royal Palm Marrakech', 'A luxury resort combining Arabic-Moorish architecture with Moroccan hospitality.', 'Marrakech', 'Route dAmizmiz, 42302 Marrakech, Morocco', 'https://www.olielo.com/wp-content/uploads/2014/12/Royal-Palm-Marrakech-luxury-hotel.jpg', '[\"Golf Course\", \"Spa\", \"Tennis Academy\", \"Kids Club\", \"Restaurants\"]', '2024-12-26 18:27:43', '2024-12-27 16:27:18'),
(31, 'Sofitel Agadir Royal Bay Resort', 'Luxury beachfront resort combining French elegance with Moroccan tradition.', 'Agadir', 'Baie des Palmiers, 80000 Agadir, Morocco', 'https://www.ahstatic.com/photos/b826_ho_02_p_1024x768.jpg', '[\"Private Beach\", \"Spa\", \"Pool\", \"Tennis Courts\", \"Water Sports\"]', '2024-12-26 18:27:43', '2024-12-27 16:28:03'),
(32, 'Mandarin Oriental Marrakech', 'Contemporary luxury meets traditional Moroccan charm in spacious villas and suites.', 'Marrakech', 'Route du Golf Royal, 40000 Marrakech, Morocco', 'https://avatars.dzeninfra.ru/get-zen_doc/271828/pub_659409d63c349263cd124410_65940b4652b09c112f89d5fd/scale_1200', '[\"Private Gardens\", \"Spa\", \"Golf\", \"Pools\", \"Fine Dining\"]', '2024-12-26 18:27:43', '2024-12-27 16:30:34'),
(33, 'Palais Faraj Suites & Spa', 'A luxurious palace hotel offering panoramic views of the Fes Medina, combining traditional Moroccan architecture with modern comfort.', 'Fes', 'Bab Ziat, Quartier Ziat, 30000 Fes, Morocco', 'https://avatars.mds.yandex.net/get-altay/1974402/2a0000017892e41c4920a7d14a3bffb3ed12/orig', '[\"Spa\", \"Restaurant\", \"Rooftop Terrace\", \"Pool\", \"Traditional Hammam\"]', '2024-12-26 18:27:43', '2024-12-27 16:30:50'),
(34, 'Riad Fes Maya Suite & Spa', 'An authentic riad in the heart of Fes, offering a peaceful retreat with traditional Moroccan hospitality.', 'Fes', 'Quartier Batha, 30000 Fes, Morocco', 'https://cf.bstatic.com/xdata/images/hotel/max1024x768/512415995.jpg?k=6e839ddeacadd9a5c05f86f04d91d47b6e0cb0243acb3ee5b08fc0725ae9e72e&o=&hp=1', '[\"Spa\", \"Indoor Pool\", \"Restaurant\", \"Courtyard Garden\", \"Hammam\"]', '2024-12-26 18:27:43', '2024-12-27 16:30:59'),
(35, 'Sahrai Hotel Fes', 'Contemporary luxury hotel with stunning views of the Fes Medina and Atlas Mountains.', 'Fes', 'Dhar El Mehraz, 30000 Fes, Morocco', 'https://i.pinimg.com/736x/95/35/e4/9535e416ae0cc397c20c6e556900cd1a.jpg', '[\"Infinity Pool\", \"Givenchy Spa\", \"Rooftop Bar\", \"Fitness Center\", \"Tennis Court\"]', '2024-12-26 18:27:43', '2024-12-27 16:31:10'),
(36, 'Movenpick Hotel Casablanca', 'Modern luxury in the heart of Casablancas business district with stunning city views.', 'Casablanca', 'Corner of Avenue Hassan II, 20070 Casablanca, Morocco', 'https://nomadpub.com/wp-content/uploads/2021/06/8976-s8.jpg', '[\"Rooftop Pool\", \"Spa\", \"Business Center\", \"Multiple Restaurants\", \"Fitness Center\"]', '2024-12-26 18:27:43', '2024-12-27 16:31:19'),
(37, 'Hyatt Regency Casablanca', 'Elegant hotel in the heart of Casablanca, offering views of the Hassan II Mosque and the Atlantic Ocean.', 'Casablanca', 'Place des Nations Unies, 20000 Casablanca, Morocco', 'https://media-cdn.tripadvisor.com/media/photo-s/16/bf/05/c7/caption.jpg', '[\"Swimming Pool\", \"Spa\", \"Multiple Restaurants\", \"Casino\", \"Business Center\"]', '2024-12-26 18:27:43', '2024-12-27 16:31:31'),
(38, 'Atlas Sky Hotel Tangier', 'Modern hotel with panoramic views of the Strait of Gibraltar and the Mediterranean beach.', 'Tangier', 'Route de Malabata, 90000 Tangier, Morocco', 'https://cf.bstatic.com/xdata/images/hotel/max1024x768/16235579.jpg?k=bf1849b4a4e3949f1f7ba8c2d7b7f1bc09ec1748e6857079bbdc9eaaa66b6b48&o=&hp=1', '[\"Infinity Pool\",\"   Beach Access\",\"   Spa\",\"   Multiple Restaurants\",\"   Fitness Center\"]', '2024-12-26 18:27:43', '2024-12-27 22:04:16'),
(39, 'Hilton Garden Inn Tanger City Center', 'Contemporary hotel in Tangiers business district with modern amenities.', 'Tangier', 'Place du Maghreb Arabe, 90000 Tangier, Morocco', 'https://ak-d.tripcdn.com/images/220p0u000000j2eq40242_R_960_660_R5_D.jpg', '[\"Business Center\", \"Restaurant\", \"Fitness Center\", \"Meeting Rooms\"]', '2024-12-26 18:27:43', '2024-12-27 16:32:49'),
(40, 'Le Medina Essaouira Hotel Thalassa Sea & Spa', 'Beachfront resort combining traditional Moroccan style with modern comfort.', 'Essaouira', 'Avenue Mohamed V, 44000 Essaouira, Morocco', 'https://hb.bankturov.ru/upload/media/hotel/0019/99/thumb_1898855_hotel_big.jpeg', '[\"Private Beach\", \"Thalassotherapy\", \"Pool\", \"Tennis Courts\", \"Water Sports\"]', '2024-12-26 18:27:43', '2024-12-27 16:33:25'),
(41, 'Four Seasons Hotel Rabat At Kasr Al BahR', 'luxurious hotel in rabat', 'Rabat', 'rabat ksar al bahr', 'https://avatars.mds.yandex.net/i?id=c5e891c6e8388ee903da49a709589e24b4f08977-7763867-images-thumbs&n=13', '[\"tv\",\"  wifi\",\"  speakers\",\" kitchen\"]', '2024-12-27 20:47:40', '2024-12-27 20:47:54');

-- --------------------------------------------------------

--
-- Structure de la table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_id` varchar(255) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `payment_id`, `payment_method`, `amount`, `payment_date`, `created_at`) VALUES
(2, 2, 'CC_1735261775953', 'credit_card', 28800.00, '2024-12-27 02:09:36', '2024-12-27 01:09:36'),
(6, 8, 'CC_1735316340564', 'credit_card', 10500.00, '2024-12-27 17:19:00', '2024-12-27 16:19:00'),
(7, 10, 'CC_1735330268458', 'credit_card', 24000.00, '2024-12-27 21:11:08', '2024-12-27 20:11:08'),
(10, 15, 'CC_1735334972156', 'credit_card', 75900.00, '2024-12-27 22:29:32', '2024-12-27 21:29:32'),
(11, 17, 'CC_1735336518103', 'credit_card', 20800.00, '2024-12-27 22:55:18', '2024-12-27 21:55:18'),
(12, 5, 'CC_1735337442411', 'credit_card', 17400.00, '2024-12-27 23:10:42', '2024-12-27 22:10:42'),
(13, 21, 'CC_1735337601246', 'credit_card', 214200.00, '2024-12-27 23:13:21', '2024-12-27 22:13:21');

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `size` decimal(5,2) DEFAULT NULL,
  `view_type` varchar(100) DEFAULT NULL,
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `rooms`
--

INSERT INTO `rooms` (`id`, `hotel_id`, `name`, `type`, `price`, `description`, `image`, `capacity`, `size`, `view_type`, `amenities`, `created_at`, `updated_at`) VALUES
(37, 26, 'Classic Hivernage Room', 'Classic', 450.00, 'Elegant room with traditional Moroccan decor and modern amenities.', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?q=80&w=1200', 2, 35.00, 'Garden View', '[\"King Bed\", \"Air Conditioning\", \"Mini Bar\", \"Safe\", \"Free WiFi\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(38, 26, 'Deluxe Koutoubia Room', 'Deluxe', 650.00, 'Spacious room with views of the Koutoubia Mosque and Atlas Mountains.', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=1200', 2, 45.00, 'City View', '[\"King Bed\", \"Sitting Area\", \"Marble Bathroom\", \"Balcony\", \"Butler Service\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(39, 27, 'Superior Riad', 'Riad', 1200.00, 'Three-story private riad with rooftop terrace and plunge pool.', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1200', 4, 140.00, 'Medina View', '[\"Private Pool\", \"Terrace\", \"Butler Service\", \"Living Room\", \"Dining Room\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(40, 27, 'Premier Riad', 'Riad', 1800.00, 'Luxurious riad with private garden and traditional Moroccan architecture.', 'https://images.unsplash.com/photo-1590490359683-658d3d23f972?q=80&w=1200', 6, 175.00, 'Garden View', '[\"Private Garden\", \"Multiple Bedrooms\", \"Private Kitchen\", \"Personal Butler\", \"Hammam\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(41, 28, 'Garden View Room', 'Deluxe', 550.00, 'Elegant room overlooking the resorts lush gardens.', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?q=80&w=1200', 2, 42.00, 'Garden View', '[\"King Bed\", \"Private Terrace\", \"Marble Bathroom\", \"Mini Bar\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(42, 28, 'Pool View Suite', 'Suite', 850.00, 'Spacious suite with views of the resort pool and Atlas Mountains.', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=1200', 3, 65.00, 'Pool View', '[\"Living Room\", \"Private Balcony\", \"Walk-in Closet\", \"Soaking Tub\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(43, 29, 'Ocean View Room', 'Deluxe', 350.00, 'Modern room with stunning views of the Atlantic Ocean.', 'https://images.unsplash.com/photo-1582719508461-905c673771fd?q=80&w=1200', 2, 42.00, 'Ocean View', '[\"King Bed\", \"Private Balcony\", \"Rain Shower\", \"Mini Bar\", \"Sea View\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(44, 29, 'Pool Suite', 'Suite', 580.00, 'Luxurious suite with direct access to a private pool area.', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?q=80&w=1200', 3, 65.00, 'Pool and Ocean View', '[\"Private Pool Access\", \"Separate Living Area\", \"Walk-in Closet\", \"Premium Amenities\", \"Butler Service\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(45, 30, 'Atlas Suite', 'Suite', 750.00, 'Luxurious suite with panoramic views of the Atlas Mountains.', 'https://images.unsplash.com/photo-1590490359683-658d3d23f972?q=80&w=1200', 2, 72.00, 'Mountain View', '[\"King Bed\", \"Living Room\", \"Private Terrace\", \"Butler Service\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(46, 30, 'Presidential Suite', 'Suite', 2500.00, 'Ultimate luxury with private pool and garden.', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1200', 4, 280.00, 'Golf Course View', '[\"Private Pool\", \"Kitchen\", \"Dining Room\", \"Butler Service\", \"Private Garden\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(47, 31, 'Ocean Deluxe Room', 'Deluxe', 280.00, 'Elegant room with direct ocean views.', 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?q=80&w=1200', 2, 45.00, 'Ocean View', '[\"King Bed\", \"Balcony\", \"Mini Bar\", \"Rain Shower\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(48, 31, 'Royal Suite', 'Suite', 680.00, 'Luxurious beachfront suite with premium amenities.', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=1200', 3, 90.00, 'Ocean Front', '[\"Living Room\", \"Private Terrace\", \"Dining Area\", \"Butler Service\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(49, 32, 'Atlas View Villa', 'Villa', 1500.00, 'Private villa with stunning views of the Atlas Mountains.', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?q=80&w=1200', 4, 200.00, 'Mountain View', '[\"Private Pool\", \"Garden\", \"Kitchen\", \"Butler Service\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(50, 32, 'Royal Penthouse', 'Penthouse', 3000.00, 'Ultimate luxury penthouse with panoramic views.', 'https://images.unsplash.com/photo-1590490359683-658d3d23f972?q=80&w=1200', 6, 400.00, 'Panoramic View', '[\"Rooftop Pool\", \"Private Spa\", \"Chef Kitchen\", \"Multiple Terraces\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(51, 33, 'Royal Suite', 'Suite', 800.00, 'Luxurious suite with traditional Moroccan decor and modern amenities.', 'https://images.unsplash.com/photo-1560448075-bb485b067938?q=80&w=1200', 2, 85.00, 'Medina View', '[\"King Bed\", \"Private Terrace\", \"Living Room\", \"Mini Bar\", \"Butler Service\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(52, 33, 'Ambassador Suite', 'Suite', 600.00, 'Elegant suite with panoramic views of the old Medina.', 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?q=80&w=1200', 2, 65.00, 'City View', '[\"King Bed\", \"Sitting Area\", \"Marble Bathroom\", \"Mini Bar\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(53, 35, 'Deluxe Atlas View', 'Deluxe', 450.00, 'Modern room with stunning views of the Atlas Mountains.', 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?q=80&w=1200', 2, 45.00, 'Mountain View', '[\"King Bed\", \"Private Balcony\", \"Rain Shower\", \"Mini Bar\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(54, 35, 'Junior Suite', 'Suite', 650.00, 'Spacious suite with separate living area and premium amenities.', 'https://images.unsplash.com/photo-1609949279531-cf48d64bed89?q=80&w=1200', 3, 65.00, 'Pool View', '[\"King Bed\", \"Living Room\", \"Luxury Bathroom\", \"Private Terrace\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(55, 36, 'Executive Room', 'Executive', 380.00, 'Modern room with city views and executive lounge access.', 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?q=80&w=1200', 2, 40.00, 'City View', '[\"King Bed\", \"Executive Lounge\", \"Work Desk\", \"Mini Bar\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(56, 36, 'Premium Suite', 'Suite', 580.00, 'Luxurious suite with panoramic city views.', 'https://images.unsplash.com/photo-1591088398332-8a7791972843?q=80&w=1200', 3, 75.00, 'City View', '[\"Living Room\", \"King Bed\", \"Executive Benefits\", \"Bathtub\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(57, 38, 'Mediterranean View Room', 'Deluxe', 320.00, 'Bright room with stunning views of the Mediterranean Sea.', 'https://images.unsplash.com/photo-1598928636135-d146006ff4be?q=80&w=1200', 2, 35.00, 'Sea View', '[\"Queen Bed\", \"Balcony\", \"Mini Bar\", \"Work Desk\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(58, 38, 'Panoramic Suite', 'Suite', 520.00, 'Luxurious suite with wraparound views of the sea and city.', 'https://images.unsplash.com/photo-1590490360182-c33d57733427?q=80&w=1200', 4, 80.00, 'Sea and City View', '[\"Two Bedrooms\", \"Living Room\", \"Private Terrace\", \"Mini Bar\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(59, 40, 'Ocean View Room', 'Deluxe', 280.00, 'Comfortable room with direct ocean views.', 'https://images.unsplash.com/photo-1615874959474-d609969a20ed?q=80&w=1200', 2, 32.00, 'Ocean View', '[\"Queen Bed\", \"Balcony\", \"Mini Bar\", \"Safe\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(60, 40, 'Beach Suite', 'Suite', 480.00, 'Spacious suite with private terrace and beach access.', 'https://images.unsplash.com/photo-1602002418082-a4443e081dd1?q=80&w=1200', 3, 70.00, 'Ocean Front', '[\"King Bed\", \"Living Area\", \"Private Terrace\", \"Direct Beach Access\"]', '2024-12-26 18:27:43', '2024-12-26 18:27:43'),
(66, 38, 'standar chambre', 'famillly', 322.00, 'luxurious chabre', 'https://avatars.mds.yandex.net/i?id=2770590923415cd6f74c18fa167ebf50de62d40d-8806475-images-thumbs&n=13', 4, 22.00, '0', '[\"tv\",\" wifi\",\" speakers\",\"kitchen\"]', '2024-12-27 21:26:43', '2024-12-27 21:49:47');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@hotelly.com', '$2y$12$7JnRlBJturPMDSlC3S7AE.zwtnsiNqcpMpmsAX9dAAbo7c1s/icj.', 'admin', '2024-12-26 11:21:33'),
(5, 'anas serghini', 'anas@anas.com', '$2y$10$0W1SP0uKCcbzRTtspJuDXuRyrLLek5q3bJt4xzs7qoLOXyb6q/ukG', 'user', '2024-12-26 16:24:39'),
(7, 'bachir', 'bachir@hotelly.com', '$2y$10$3NzdZ0iq2Xyx6e0LXMcEmuxiXGFdGQ7w1y42XQ11xYxoNsOW225x.', 'admin', '2024-12-27 21:54:01');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Index pour la table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Index pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Index pour la table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT pour la table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Contraintes pour la table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`);

--
-- Contraintes pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`);

--
-- Contraintes pour la table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
