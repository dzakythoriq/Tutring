<?php
require_once 'includes/config.php';

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
     * @param array $userData User data to update
     * @return boolean True if updated, false otherwise
     */
    public function update($id, $userData) {
        $sql = "UPDATE users SET name = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bind_param("si", 
            $userData['name'], 
            $id
        );
        
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
}
?>