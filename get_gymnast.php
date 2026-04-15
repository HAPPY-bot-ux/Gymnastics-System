<?php
session_start();
require_once 'auth.php';
require_once 'includes/GymnastManager.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit();
}

$gymnastManager = new GymnastManager();
$gymnast = $gymnastManager->getGymnastById(intval($_GET['id']));

if ($gymnast) {
    echo json_encode(['success' => true, 'gymnast' => $gymnast]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gymnast not found']);
}
?>