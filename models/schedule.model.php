<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';

class Schedule {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get schedule by ID
     * 
     * @param int $id Schedule ID
     * @return array|false Schedule data if found, false otherwise
     */
    public function getById($id) {
        $sql = "SELECT * FROM schedules WHERE id = ?";
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
     * Create a new schedule
     * 
     * @param array $scheduleData Schedule data (tutor_id, date, start_time, end_time)
     * @return int|false New schedule ID if created, false otherwise
     */
    public function create($scheduleData) {
        $sql = "INSERT INTO schedules (tutor_id, date, start_time, end_time) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param("isss", 
            $scheduleData['tutor_id'], 
            $scheduleData['date'], 
            $scheduleData['start_time'], 
            $scheduleData['end_time']
        );
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update schedule
     * 
     * @param int $id Schedule ID
     * @param array $scheduleData Schedule data to update
     * @return boolean True if updated, false otherwise
     */
    public function update($id, $scheduleData) {
        $sql = "UPDATE schedules SET date = ?, start_time = ?, end_time = ? WHERE id = ? AND is_booked = 0";
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param("sssi", 
            $scheduleData['date'], 
            $scheduleData['start_time'], 
            $scheduleData['end_time'], 
            $id
        );
        
        return $stmt->execute();
    }
    
    /**
     * Delete schedule
     * 
     * @param int $id Schedule ID
     * @return boolean True if deleted, false otherwise
     */
    public function delete($id) {
        $sql = "DELETE FROM schedules WHERE id = ? AND is_booked = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Mark schedule as booked
     * 
     * @param int $id Schedule ID
     * @return boolean True if updated, false otherwise
     */
    public function markAsBooked($id) {
        $sql = "UPDATE schedules SET is_booked = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Mark schedule as available
     * 
     * @param int $id Schedule ID
     * @return boolean True if updated, false otherwise
     */
    public function markAsAvailable($id) {
        $sql = "UPDATE schedules SET is_booked = 0 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Get schedules by tutor
     * 
     * @param int $tutorId Tutor ID
     * @param string|null $fromDate Optional start date filter
     * @return array List of schedules
     */
    public function getByTutor($tutorId, $fromDate = null) {
        $sql = "SELECT s.*, b.id as booking_id 
                FROM schedules s 
                LEFT JOIN bookings b ON s.id = b.schedule_id 
                WHERE s.tutor_id = ?";
        
        if ($fromDate) {
            $sql .= " AND s.date >= ?";
        }
        
        $sql .= " ORDER BY s.date ASC, s.start_time ASC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($fromDate) {
            $stmt->bind_param("is", $tutorId, $fromDate);
        } else {
            $stmt->bind_param("i", $tutorId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $schedules = [];
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        
        return $schedules;
    }
    
    /**
     * Get all available (not booked) schedules for a tutor
     * 
     * @param int $tutorId Tutor ID
     * @param string|null $fromDate Optional start date filter
     * @return array List of available schedules
     */
    public function getAvailableByTutor($tutorId, $fromDate = null) {
        $sql = "SELECT * FROM schedules WHERE tutor_id = ? AND is_booked = 0";
        
        if ($fromDate) {
            $sql .= " AND date >= ?";
        }
        
        $sql .= " ORDER BY date ASC, start_time ASC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($fromDate) {
            $stmt->bind_param("is", $tutorId, $fromDate);
        } else {
            $stmt->bind_param("i", $tutorId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $schedules = [];
        while ($row = $result->fetch_assoc()) {
            $schedules[] = $row;
        }
        
        return $schedules;
    }
    
    /**
     * Check if a schedule slot is available (not booked)
     * 
     * @param int $id Schedule ID
     * @return boolean True if available, false otherwise
     */
    public function isAvailable($id) {
        $sql = "SELECT is_booked FROM schedules WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['is_booked'] == 0;
        }
        
        return false;
    }
}
?>