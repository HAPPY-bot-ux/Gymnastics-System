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
                return true;
            }
        }
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function isGymnast() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'gymnast';
    }
    
    public function logout() {
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    session_start();
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    $auth = new Auth();
    
    if ($auth->login($username, $password, $role)) {
        // Login successful
        $_SESSION['success'] = "Welcome back, " . htmlspecialchars($username) . "!";
        
        // Redirect based on role
        if ($role === 'admin') {
            header('Location: dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit();
    } else {
        // Login failed
        $_SESSION['error'] = "Invalid username, password, or role. Please try again.";
        header('Location: login.php?role=' . urlencode($role));
        exit();
    }
}
?>