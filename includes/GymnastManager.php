<?php
// includes/GymnastManager.php
require_once 'config/db.php';

class GymnastManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function getAllGymnasts() {
        $sql = "SELECT * FROM gymnasts ORDER BY created_at DESC";
        $stmt = $this->db->executeQuery($sql);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getGymnastById($id) {
        $sql = "SELECT * FROM gymnasts WHERE id = ?";
        $stmt = $this->db->executeQuery($sql, "i", [$id]);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function updateGymnast($id, $data) {
        $errors = [];
        
        // Validation
        if (empty($data['full_name'])) {
            $errors[] = "Full name is required";
        } elseif (!preg_match("/^[a-zA-Z\s'-]+$/", $data['full_name'])) {
            $errors[] = "Full name should contain only letters, spaces, apostrophes, and hyphens";
        }
        
        if (empty($data['email'])) {
            $errors[] = "Email is required";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($data['contact_no'])) {
            $errors[] = "Contact number is required";
        } elseif (!preg_match("/^[0-9+\-\s()]{10,20}$/", $data['contact_no'])) {
            $errors[] = "Contact number should be valid (10-20 digits)";
        }
        
        if (empty($data['date_of_birth'])) {
            $errors[] = "Date of birth is required";
        } else {
            $dob = new DateTime($data['date_of_birth']);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            if ($age < 4 || $age > 100) {
                $errors[] = "Age must be between 4 and 100 years";
            }
        }
        
        if (empty($data['training_program'])) {
            $errors[] = "Training program is required";
        }
        
        if (empty($data['enrollment_date'])) {
            $errors[] = "Enrollment date is required";
        }
        
        // If there are validation errors, return them
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Set the updated_by variable for the trigger
        $connection = $this->db->getConnection();
        $username = $_SESSION['username'] ?? 'system';
        $connection->query("SET @updated_by = '" . $connection->real_escape_string($username) . "'");
        
        // Perform the update
        $sql = "UPDATE gymnasts SET 
                full_name = ?, 
                email = ?, 
                contact_no = ?, 
                date_of_birth = ?, 
                training_program = ?, 
                enrollment_date = ?, 
                progress_status = ? 
                WHERE id = ?";
        
        $stmt = $this->db->executeQuery($sql, "sssssssi", [
            $data['full_name'],
            $data['email'],
            $data['contact_no'],
            $data['date_of_birth'],
            $data['training_program'],
            $data['enrollment_date'],
            $data['progress_status'],
            $id
        ]);
        
        if ($stmt && $stmt->affected_rows >= 0) {
            return ['success' => true, 'errors' => []];
        } else {
            return ['success' => false, 'errors' => ['Failed to update gymnast information.']];
        }
    }
    
    public function deleteGymnast($id) {
        // Set the deleted_by variable for the trigger
        $connection = $this->db->getConnection();
        $username = $_SESSION['username'] ?? 'system';
        $connection->query("SET @deleted_by = '" . $connection->real_escape_string($username) . "'");
        
        // Delete from database (trigger will log to deleted_records_log)
        $sql = "DELETE FROM gymnasts WHERE id = ?";
        $stmt = $this->db->executeQuery($sql, "i", [$id]);
        
        return $stmt && $stmt->affected_rows > 0;
    }
    
    public function getDeletedRecords() {
        $sql = "SELECT * FROM deleted_records_log ORDER BY deleted_at DESC LIMIT 100";
        $stmt = $this->db->executeQuery($sql);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function getUpdateLog($gymnast_id = null) {
        if ($gymnast_id) {
            $sql = "SELECT * FROM update_log WHERE gymnast_id = ? ORDER BY updated_at DESC";
            $stmt = $this->db->executeQuery($sql, "i", [$gymnast_id]);
        } else {
            $sql = "SELECT * FROM update_log ORDER BY updated_at DESC LIMIT 100";
            $stmt = $this->db->executeQuery($sql);
        }
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>