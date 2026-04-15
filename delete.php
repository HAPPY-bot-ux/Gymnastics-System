<?php
session_start();
require_once 'auth.php';
require_once 'includes/GymnastManager.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: login.php');
    exit();
}

// Set the deleted_by variable for the trigger
$db = Database::getInstance();
$connection = $db->getConnection();
$connection->query("SET @deleted_by = '" . $db->escapeString($_SESSION['username']) . "'");

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $gymnastManager = new GymnastManager();
    $id = intval($_GET['id']);
    
    if ($gymnastManager->deleteGymnast($id)) {
        $_SESSION['success'] = "Gymnast record deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete gymnast record.";
    }
}

header('Location: dashboard.php');
exit();
?>