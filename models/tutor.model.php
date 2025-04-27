<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';

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
        $sql = "SELECT t.*, u.name, u.email, u.photo, u.created_at 
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
        $sql = "SELECT t.*, u.name, u.email, u.photo, u.created_at 
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
     * Add education or experience details
     * 
     * @param int $tutorId Tutor ID
     * @param array $data Education/experience data
     * @return int|false New record ID if created, false otherwise
     */
    public function addEducation($tutorId, $data) {
        $sql = "INSERT INTO tutor_education (tutor_id, degree, institution, year, description) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param("issis", 
            $tutorId,
            $data['degree'],
            $data['institution'],
            $data['year'],
            $data['description']
        );
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get education/experience details for a tutor
     * 
     * @param int $tutorId Tutor ID
     * @return array List of education/experience items
     */
    public function getEducation($tutorId) {
        $sql = "SELECT * FROM tutor_education WHERE tutor_id = ? ORDER BY year DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $tutorId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    /**
     * Delete education/experience item
     * 
     * @param int $itemId Item ID
     * @param int $tutorId Tutor ID (for security check)
     * @return boolean True if deleted, false otherwise
     */
    public function deleteEducation($itemId, $tutorId) {
        $sql = "DELETE FROM tutor_education WHERE id = ? AND tutor_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $itemId, $tutorId);
        
        return $stmt->execute();
    }
    
    /**
     * Get all tutors
     * 
     * @return array List of tutors
     */
    public function getAll() {
        $sql = "SELECT t.*, u.name, u.email, u.photo, u.created_at 
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
     * Get all unique subjects from tutors
     * 
     * @return array List of unique subjects
     */
    public function getAllSubjects() {
        $sql = "SELECT DISTINCT subject FROM tutors ORDER BY subject ASC";
        $result = $this->conn->query($sql);
        
        $subjects = [];
        while ($row = $result->fetch_assoc()) {
            $subjects[] = $row['subject'];
        }
        
        return $subjects;
    }
    
    /**
     * Search tutors by subject
     * 
     * @param string $query Search query for subject or name
     * @return array List of tutors matching the subject
     */
    public function searchBySubject($query) {
        $sql = "SELECT t.*, u.name, u.email, u.photo, u.created_at 
                FROM tutors t
                JOIN users u ON t.user_id = u.id
                WHERE t.subject LIKE ? OR u.name LIKE ?
                ORDER BY t.id DESC";
        $stmt = $this->conn->prepare($sql);
        
        $searchTerm = "%{$query}%";
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tutors = [];
        while ($row = $result->fetch_assoc()) {
            $tutors[] = $row;
        }
        
        return $tutors;
    }
    
    /**
     * Search tutors by multiple parameters
     * 
     * @param array $params Search parameters (subject, price_min, price_max, min_rating)
     * @return array List of tutors matching parameters
     */
    public function searchTutors($params) {
        $conditions = [];
        $bindTypes = "";
        $bindValues = [];
        
        $sql = "SELECT t.*, u.name, u.email, u.photo, u.created_at 
                FROM tutors t
                JOIN users u ON t.user_id = u.id";
        
        // Search by name or subject
        if (!empty($params['query'])) {
            $conditions[] = "(t.subject LIKE ? OR u.name LIKE ?)";
            $bindTypes .= "ss";
            $searchTerm = "%" . $params['query'] . "%";
            $bindValues[] = $searchTerm;
            $bindValues[] = $searchTerm;
        }
        
        // Filter by subjects
        if (!empty($params['subjects']) && is_array($params['subjects'])) {
            $placeholders = implode(',', array_fill(0, count($params['subjects']), '?'));
            $conditions[] = "t.subject IN ($placeholders)";
            $bindTypes .= str_repeat("s", count($params['subjects']));
            $bindValues = array_merge($bindValues, $params['subjects']);
        }
        
        // Filter by price range
        if (isset($params['price_min'])) {
            $conditions[] = "t.hourly_rate >= ?";
            $bindTypes .= "d";
            $bindValues[] = $params['price_min'];
        }
        
        if (isset($params['price_max'])) {
            $conditions[] = "t.hourly_rate <= ?";
            $bindTypes .= "d";
            $bindValues[] = $params['price_max'];
        }
        
        // Add WHERE clause if conditions exist
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $sql .= " ORDER BY t.id DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($bindValues)) {
            // Create the bind_param arguments dynamically
            $bindParams = [$bindTypes];
            foreach ($bindValues as $key => $value) {
                $bindParams[] = &$bindValues[$key];
            }
            
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tutors = [];
        while ($row = $result->fetch_assoc()) {
            $tutors[] = $row;
        }
        
        // Filter by minimum rating if specified
        if (isset($params['min_rating']) && $params['min_rating'] > 0) {
            $tutors = array_filter($tutors, function($tutor) use ($params) {
                $rating = $this->getAverageRating($tutor['id']) ?: 0;
                return $rating >= (float)$params['min_rating'];
            });
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
     * Get tutors by availability
     * 
     * @param array $availability Array of availability options (morning, afternoon, evening, weekend)
     * @return array List of tutors with matching availability
     */
    public function getTutorsByAvailability($availability) {
        // This is a placeholder - you would need to implement the actual logic
        // to filter tutors based on their available schedules
        // For now, we'll return all tutors
        return $this->getAll();
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