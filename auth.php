<?php
// auth.php
require_once 'config/db.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($username, $password, $role) {
        $sql = "SELECT * FROM users WHERE username = ? AND role = ?";
        $stmt = $this->db->executeQuery($sql, "ss", [$username, $role]);
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['gymnast_id'] = $user['gymnast_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                return true;
            }
        }
        return false;
    }
    
    public function register($data) {
        $db = Database::getInstance();
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        if ($data['role'] === 'gymnast') {
            // Start transaction
            $db->getConnection()->begin_transaction();
            
            try {
                // Generate membership ID
                $year = date('Y');
                $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $membership_id = "GYM{$year}{$random}";
                
                // Insert into gymnasts table
                $sql = "INSERT INTO gymnasts (membership_id, full_name, email, contact_no, date_of_birth, training_program, enrollment_date, progress_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')";
                $stmt = $db->executeQuery($sql, "sssssss", [
                    $membership_id,
                    $data['full_name'],
                    $data['email'],
                    $data['contact_no'],
                    $data['date_of_birth'],
                    $data['training_program'],
                    $data['enrollment_date']
                ]);
                
                if ($stmt && $stmt->affected_rows > 0) {
                    $gymnast_id = $stmt->insert_id;
                    
                    // Insert into users table
                    $user_sql = "INSERT INTO users (username, password, role, email, full_name, contact_no, gymnast_id) 
                                VALUES (?, ?, 'gymnast', ?, ?, ?, ?)";
                    $user_stmt = $db->executeQuery($user_sql, "ssssss", [
                        $data['username'],
                        $hashed_password,
                        $data['email'],
                        $data['full_name'],
                        $data['contact_no'],
                        $gymnast_id
                    ]);
                    
                    if ($user_stmt && $user_stmt->affected_rows > 0) {
                        $db->getConnection()->commit();
                        return ['success' => true, 'message' => 'Gymnast registered successfully!', 'membership_id' => $membership_id];
                    } else {
                        throw new Exception("Failed to create user account");
                    }
                } else {
                    throw new Exception("Failed to create gymnast record");
                }
            } catch (Exception $e) {
                $db->getConnection()->rollback();
                return ['success' => false, 'message' => $e->getMessage()];
            }
        } else if ($data['role'] === 'admin') {
            // Insert admin user
            $sql = "INSERT INTO users (username, password, role, email, full_name, contact_no, admin_position, department, employee_id) 
                    VALUES (?, ?, 'admin', ?, ?, ?, ?, ?, ?)";
            $stmt = $db->executeQuery($sql, "ssssssss", [
                $data['username'],
                $hashed_password,
                $data['email'],
                $data['full_name'],
                $data['contact_no'],
                $data['admin_position'],
                $data['department'],
                $data['employee_id']
            ]);
            
            if ($stmt && $stmt->affected_rows > 0) {
                return ['success' => true, 'message' => 'Admin registered successfully!'];
            } else {
                return ['success' => false, 'message' => 'Failed to register admin'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid role'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function isAdmin() {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function isGymnast() {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'gymnast';
    }
    
    public function logout() {
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        session_destroy();
        return true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) return null;
        
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->db->executeQuery($sql, "i", [$_SESSION['user_id']]);
        return $stmt->get_result()->fetch_assoc();
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        
        if (empty($username) || empty($password) || empty($role)) {
            $_SESSION['error'] = "Please fill in all fields";
            header('Location: login.php?role=' . urlencode($role));
            exit();
        }
        
        $auth = new Auth();
        
        if ($auth->login($username, $password, $role)) {
            $_SESSION['success'] = "Welcome back, " . htmlspecialchars($username) . "!";
            header('Location: dashboard.php');
            exit();
        } else {
            $_SESSION['error'] = "Invalid username, password, or role. Please try again.";
            header('Location: login.php?role=' . urlencode($role));
            exit();
        }
    } elseif ($_POST['action'] === 'register') {
        $auth = new Auth();
        $result = $auth->register($_POST);
        
        session_start();
        if ($result['success']) {
            $_SESSION['success'] = $result['message'] . " You can now login.";
            header('Location: login.php');
        } else {
            $_SESSION['error'] = $result['message'];
            header('Location: register.php');
        }
        exit();
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $auth = new Auth();
    $auth->logout();
    $_SESSION['success'] = "You have been successfully logged out";
    header('Location: login.php');
    exit();
}
?>