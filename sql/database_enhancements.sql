-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS before_reservation_insert;
DROP TRIGGER IF EXISTS after_reservation_insert;
DROP TRIGGER IF EXISTS after_reservation_delete;

-- Drop existing procedures and functions
DROP PROCEDURE IF EXISTS add_reservation;
DROP PROCEDURE IF EXISTS cancel_reservation;
DROP PROCEDURE IF EXISTS generate_occupancy_report;
DROP FUNCTION IF EXISTS is_room_available;
DROP FUNCTION IF EXISTS calculate_total_price;

DELIMITER //

-- Function to check room availability
CREATE FUNCTION is_room_available(
    p_room_id INT,
    p_check_in DATE,
    p_check_out DATE
) RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
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
END //

-- Function to calculate total price
CREATE FUNCTION calculate_total_price(
    p_room_id INT,
    p_check_in DATE,
    p_check_out DATE
) RETURNS DECIMAL(10,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE price_per_night DECIMAL(10,2);
    DECLARE num_nights INT;
    
    -- Get room price
    SELECT price INTO price_per_night 
    FROM rooms 
    WHERE id = p_room_id;
    
    -- Calculate number of nights
    SET num_nights = DATEDIFF(p_check_out, p_check_in);
    
    RETURN price_per_night * num_nights;
END //

-- Procedure to add a reservation
CREATE PROCEDURE add_reservation(
    IN p_user_id INT,
    IN p_room_id INT,
    IN p_check_in DATE,
    IN p_check_out DATE
)
BEGIN
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
END //

-- Procedure to cancel a reservation
CREATE PROCEDURE cancel_reservation(
    IN p_booking_id INT,
    IN p_user_id INT
)
BEGIN
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
END //

-- Procedure to generate occupancy report
CREATE PROCEDURE generate_occupancy_report(
    IN start_date DATE,
    IN end_date DATE
)
BEGIN
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
END //

DELIMITER ;
