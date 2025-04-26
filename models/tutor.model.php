<?php
require_once 'includes/config.php';

class Tutor {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get tutor by ID
     * 
     * @param int $id Tutor ID
     * @return array|false Tutor data if found, false otherwise
     */
    public function getById($id) {
        $sql = "SELECT t.*, u.name, u.email, u.created_at 
                FROM tutors t
                JOIN users u ON t.user_id = u.id
                WHERE t.id = ?";
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
     * Get tutor by user ID
     * 
     * @param int $userId User ID
     * @return array|false Tutor data if found, false otherwise
     */
    public function getByUserId($userId) {
        $sql = "SELECT t.*, u.name, u.email, u.created_at 
                FROM tutors t
                JOIN users u ON t.user_id = u.id
                WHERE t.user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    /**
     * Create a new tutor profile
     * 
     * @param array $tutorData Tutor data (user_id, bio, subject, hourly_rate)
     * @return int|false New tutor ID if created, false otherwise
     */
    public function create($tutorData) {
        $sql = "INSERT INTO tutors (user_id, bio, subject, hourly_rate) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param("issd", 
            $tutorData['user_id'], 
            $tutorData['bio'], 
            $tutorData['subject'], 
            $tutorData['hourly_rate']
        );
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update tutor profile
     * 
     * @param int $id Tutor ID
     * @param array $tutorData Tutor data to update
     * @return boolean True if updated, false otherwise
     */
    public function update($id, $tutorData) {
        $sql = "UPDATE tutors SET bio = ?, subject = ?, hourly_rate = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param("ssdi", 
            $tutorData['bio'], 
            $tutorData['subject'], 
            $tutorData['hourly_rate'], 
            $id
        );
        
        return $stmt->execute();
    }
    
    /**
     * Get all tutors
     * 
     * @return array List of tutors
     */
    public function getAll() {
        $sql = "SELECT t.*, u.name, u.email, u.created_at 
                FROM tutors t
                JOIN users u ON t.user_id = u.id
                ORDER BY t.id DESC";
        $result = $this->conn->query($sql);
        
        $tutors = [];
        while ($row = $result->fetch_assoc()) {
            $tutors[] = $row;
        }
        
        return $tutors;
    }
    
    /**
     * Search tutors by subject
     * 
     * @param string $subject Subject to search for
     * @return array List of tutors matching the subject
     */
    public function searchBySubject($subject) {
        $sql = "SELECT t.*, u.name, u.email, u.created_at 
                FROM tutors t
                JOIN users u ON t.user_id = u.id
                WHERE t.subject LIKE ?
                ORDER BY t.id DESC";
        $stmt = $this->conn->prepare($sql);
        
        $searchTerm = "%{$subject}%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tutors = [];
        while ($row = $result->fetch_assoc()) {
            $tutors[] = $row;
        }
        
        return $tutors;
    }
    
    /**
     * Get tutor's average rating
     * 
     * @param int $tutorId Tutor ID
     * @return float|null Average rating if available, null otherwise
     */
    public function getAverageRating($tutorId) {
        $sql = "SELECT AVG(r.rating) as avg_rating
                FROM reviews r
                JOIN bookings b ON r.booking_id = b.id
                JOIN schedules s ON b.schedule_id = s.id
                WHERE s.tutor_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $tutorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['avg_rating'] ? round($row['avg_rating'], 1) : null;
        }
        
        return null;
    }
    
    /**
     * Count total number of tutors
     * 
     * @return int Total number of tutors
     */
    public function countTotal() {
        $sql = "SELECT COUNT(*) as total FROM tutors";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        
        return $row['total'];
    }
}