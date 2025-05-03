<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';

class Booking {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get booking by ID
     * 
     * @param int $id Booking ID
     * @return array|false Booking data if found, false otherwise
     */
    public function getById($id) {
        $sql = "SELECT b.*, s.date, s.start_time, s.end_time, s.tutor_id,
                u.name as student_name, u.email as student_email,
                t.subject, t.hourly_rate,
                tu.name as tutor_name, tu.email as tutor_email
                FROM bookings b
                JOIN schedules s ON b.schedule_id = s.id
                JOIN users u ON b.student_id = u.id
                JOIN tutors t ON s.tutor_id = t.id
                JOIN users tu ON t.user_id = tu.id
                WHERE b.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    /**
     * Create a new booking
     * 
     * @param array $bookingData Booking data (student_id, schedule_id)
     * @return int|false New booking ID if created, false otherwise
     */
    public function create($bookingData) {
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Insert booking
            $sql = "INSERT INTO bookings (student_id, schedule_id, status) VALUES (?, ?, 'pending')";
            $stmt = $this->conn->prepare($sql);
            
            $stmt->bind_param("ii", 
                $bookingData['student_id'], 
                $bookingData['schedule_id']
            );
            
            $stmt->execute();
            $bookingId = $stmt->insert_id;
            
            // Mark schedule as booked
            $updateSql = "UPDATE schedules SET is_booked = 1 WHERE id = ?";
            $updateStmt = $this->conn->prepare($updateSql);
            $updateStmt->bind_param("i", $bookingData['schedule_id']);
            $updateStmt->execute();
            
            // Commit transaction
            $this->conn->commit();
            
            return $bookingId;
        } catch (Exception $e) {
            // Rollback transaction in case of error
            $this->conn->rollback();
            return false;
        }
    }
    
    /**
     * Update booking status
     * 
     * @param int $id Booking ID
     * @param string $status New status (pending, confirmed, cancelled)
     * @return boolean True if updated, false otherwise
     */
    public function updateStatus($id, $status) {
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Get schedule_id for this booking
            $getSql = "SELECT schedule_id FROM bookings WHERE id = ?";
            $getStmt = $this->conn->prepare($getSql);
            $getStmt->bind_param("i", $id);
            $getStmt->execute();
            $result = $getStmt->get_result();
            $booking = $result->fetch_assoc();
            
            if (!$booking) {
                return false;
            }
            
            // Update booking status
            $sql = "UPDATE bookings SET status = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            
            // If cancelled, mark schedule as available again
            if ($status === 'cancelled') {
                $updateSql = "UPDATE schedules SET is_booked = 0 WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->bind_param("i", $booking['schedule_id']);
                $updateStmt->execute();
            }
            
            // Commit transaction
            $this->conn->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction in case of error
            $this->conn->rollback();
            return false;
        }
    }
    
    /**
     * Get all bookings by student
     * 
     * @param int $studentId Student ID
     * @return array List of bookings
     */
    public function getByStudent($studentId) {
        $sql = "SELECT b.*, s.date, s.start_time, s.end_time,
                t.subject, t.hourly_rate, 
                u.name as tutor_name
                FROM bookings b
                JOIN schedules s ON b.schedule_id = s.id
                JOIN tutors t ON s.tutor_id = t.id
                JOIN users u ON t.user_id = u.id
                WHERE b.student_id = ?
                ORDER BY s.date DESC, s.start_time DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        return $bookings;
    }
    
    /**
     * Get all bookings for a tutor
     * 
     * @param int $tutorId Tutor ID
     * @return array List of bookings
     */
    public function getByTutor($tutorId) {
        $sql = "SELECT b.*, s.date, s.start_time, s.end_time,
                u.name as student_name, u.email as student_email
                FROM bookings b
                JOIN schedules s ON b.schedule_id = s.id
                JOIN users u ON b.student_id = u.id
                WHERE s.tutor_id = ?
                ORDER BY s.date DESC, s.start_time DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $tutorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $bookings = [];
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        return $bookings;
    }
    
    /**
     * Check if a booking has a review
     * 
     * @param int $bookingId Booking ID
     * @return boolean True if booking has a review, false otherwise
     */
    public function hasReview($bookingId) {
        $sql = "SELECT COUNT(*) as count FROM reviews WHERE booking_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    /**
     * Get review for a booking
     * 
     * @param int $bookingId Booking ID
     * @return array|false Review data if found, false otherwise
     */
    public function getReview($bookingId) {
        $sql = "SELECT * FROM reviews WHERE booking_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    /**
     * Add review for a booking
     * 
     * @param array $reviewData Review data (booking_id, rating, comment)
     * @return int|false New review ID if created, false otherwise
     */
    public function addReview($reviewData) {
        // Check if booking exists and is confirmed
        $checkSql = "SELECT status FROM bookings WHERE id = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $reviewData['booking_id']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $booking = $result->fetch_assoc();
        if ($booking['status'] !== 'confirmed') {
            return false;
        }
        
        // Check if review already exists
        if ($this->hasReview($reviewData['booking_id'])) {
            return false;
        }
        
        // Add review
        $sql = "INSERT INTO reviews (booking_id, rating, comment) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param("iis", 
            $reviewData['booking_id'], 
            $reviewData['rating'], 
            $reviewData['comment']
        );
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing review if it's still within the editable timeframe (24 hours)
     * 
     * @param int $reviewId Review ID to update
     * @param array $reviewData Updated review data (rating, comment)
     * @return boolean True if updated, false otherwise
     */
    public function updateReview($reviewId, $reviewData) {
        // Check if review exists and is still editable (within 24 hours)
        $checkSql = "SELECT r.id, r.created_at FROM reviews r WHERE r.id = ? AND 
                    TIMESTAMPDIFF(HOUR, r.created_at, NOW()) < 24";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("i", $reviewId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        // Update the review
        $sql = "UPDATE reviews SET rating = ?, comment = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param("isi", 
            $reviewData['rating'],
            $reviewData['comment'],
            $reviewId
        );
        
        return $stmt->execute();
    }
    
    /**
     * Check if a review is still editable (within 24 hours of creation)
     * 
     * @param int $reviewId Review ID
     * @return boolean True if editable, false otherwise
     */
    public function isReviewEditable($reviewId) {
        $sql = "SELECT COUNT(*) as count FROM reviews WHERE id = ? AND 
                TIMESTAMPDIFF(HOUR, created_at, NOW()) < 24";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['count'] > 0;
    }
    
    /**
     * Get the remaining time (in hours) before a review becomes permanent
     * 
     * @param int $reviewId Review ID
     * @return int|false Hours remaining or false if not found/already permanent
     */
    public function getReviewEditableTimeRemaining($reviewId) {
        $sql = "SELECT 24 - TIMESTAMPDIFF(HOUR, created_at, NOW()) as hours_remaining 
                FROM reviews WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            return max(0, $row['hours_remaining']);
        }
        
        return false;
    }
    
    /**
     * Count total bookings
     * 
     * @return int Total number of bookings
     */
    public function countTotal() {
        $sql = "SELECT COUNT(*) as total FROM bookings";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
    
    /**
     * Count bookings by status
     * 
     * @param string $status Status to count (pending, confirmed, cancelled)
     * @return int Number of bookings with the given status
     */
    public function countByStatus($status) {
        $sql = "SELECT COUNT(*) as total FROM bookings WHERE status = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
}
?>