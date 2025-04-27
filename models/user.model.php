<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';

class User {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get user by ID
     * 
     * @param int $id User ID
     * @return array|false User data if found, false otherwise
     */
    public function getById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
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
     * Get user by email
     * 
     * @param string $email User email
     * @return array|false User data if found, false otherwise
     */
    public function getByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return false;
    }
    
    /**
     * Create a new user
     * 
     * @param array $userData User data (name, email, password, role)
     * @return int|false New user ID if created, false otherwise
     */
    public function create($userData) {
        $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        $stmt->bind_param("ssss", 
            $userData['name'], 
            $userData['email'], 
            $hashedPassword, 
            $userData['role']
        );
        
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update user profile
     * 
     * @param int $id User ID
     * @param array $userData User data to update (name, email, photo)
     * @return boolean True if updated, false otherwise
     */
    public function update($id, $userData) {
        // Check if we're updating just the name or name and email
        if (isset($userData['email']) && isset($userData['photo'])) {
            $sql = "UPDATE users SET name = ?, email = ?, photo = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssi", 
                $userData['name'], 
                $userData['email'],
                $userData['photo'],
                $id
            );
        } elseif (isset($userData['email'])) {
            $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssi", 
                $userData['name'], 
                $userData['email'],
                $id
            );
        } elseif (isset($userData['photo'])) {
            $sql = "UPDATE users SET name = ?, photo = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssi", 
                $userData['name'], 
                $userData['photo'],
                $id
            );
        } else {
            $sql = "UPDATE users SET name = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", 
                $userData['name'], 
                $id
            );
        }
        
        return $stmt->execute();
    }
    
    /**
     * Update user password
     * 
     * @param int $id User ID
     * @param string $newPassword New password
     * @return boolean True if updated, false otherwise
     */
    public function updatePassword($id, $newPassword) {
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt->bind_param("si", 
            $hashedPassword, 
            $id
        );
        
        return $stmt->execute();
    }
    
    /**
     * Verify password for a user
     * 
     * @param int $id User ID
     * @param string $password Password to verify
     * @return boolean True if password matches, false otherwise
     */
    public function verifyPassword($id, $password) {
        $user = $this->getById($id);
        
        if ($user) {
            return password_verify($password, $user['password']);
        }
        
        return false;
    }
    
    /**
     * Get total count of users
     * 
     * @param string|null $role Optional role filter (student, tutor)
     * @return int Total number of users
     */
    public function countTotal($role = null) {
        if ($role) {
            $sql = "SELECT COUNT(*) as total FROM users WHERE role = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $role);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $sql = "SELECT COUNT(*) as total FROM users";
            $result = $this->conn->query($sql);
        }
        
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    /**
     * Delete user account (with all related data)
     * 
     * @param int $id User ID
     * @return boolean True if deleted, false otherwise
     */
    public function delete($id) {
        // Start transaction
        $this->conn->begin_transaction();
        
        try {
            // Get user data to check role
            $user = $this->getById($id);
            
            if (!$user) {
                return false;
            }
            
            // If user is a tutor, delete related data
            if ($user['role'] === 'tutor') {
                // Get tutor ID
                $tutorSql = "SELECT id FROM tutors WHERE user_id = ?";
                $tutorStmt = $this->conn->prepare($tutorSql);
                $tutorStmt->bind_param("i", $id);
                $tutorStmt->execute();
                $tutorResult = $tutorStmt->get_result();
                
                if ($tutorResult->num_rows === 1) {
                    $tutor = $tutorResult->fetch_assoc();
                    $tutorId = $tutor['id'];
                    
                    // Delete reviews for bookings with this tutor
                    $reviewSql = "DELETE r FROM reviews r 
                                  JOIN bookings b ON r.booking_id = b.id 
                                  JOIN schedules s ON b.schedule_id = s.id 
                                  WHERE s.tutor_id = ?";
                    $reviewStmt = $this->conn->prepare($reviewSql);
                    $reviewStmt->bind_param("i", $tutorId);
                    $reviewStmt->execute();
                    
                    // Delete bookings for this tutor
                    $bookingSql = "DELETE b FROM bookings b 
                                  JOIN schedules s ON b.schedule_id = s.id 
                                  WHERE s.tutor_id = ?";
                    $bookingStmt = $this->conn->prepare($bookingSql);
                    $bookingStmt->bind_param("i", $tutorId);
                    $bookingStmt->execute();
                    
                    // Delete schedules
                    $scheduleSql = "DELETE FROM schedules WHERE tutor_id = ?";
                    $scheduleStmt = $this->conn->prepare($scheduleSql);
                    $scheduleStmt->bind_param("i", $tutorId);
                    $scheduleStmt->execute();
                    
                    // Delete tutor
                    $deleteTutorSql = "DELETE FROM tutors WHERE id = ?";
                    $deleteTutorStmt = $this->conn->prepare($deleteTutorSql);
                    $deleteTutorStmt->bind_param("i", $tutorId);
                    $deleteTutorStmt->execute();
                }
            } else if ($user['role'] === 'student') {
                // If user is a student, delete related data
                
                // Delete reviews made by this student
                $reviewSql = "DELETE r FROM reviews r 
                              JOIN bookings b ON r.booking_id = b.id 
                              WHERE b.student_id = ?";
                $reviewStmt = $this->conn->prepare($reviewSql);
                $reviewStmt->bind_param("i", $id);
                $reviewStmt->execute();
                
                // Delete bookings made by this student
                $bookingSql = "DELETE FROM bookings WHERE student_id = ?";
                $bookingStmt = $this->conn->prepare($bookingSql);
                $bookingStmt->bind_param("i", $id);
                $bookingStmt->execute();
            }
            
            // Delete user
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Delete profile photo if exists
            if (!empty($user['photo'])) {
                $photoPath = ROOT_PATH . $user['photo'];
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
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
}
?>