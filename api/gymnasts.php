<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';
require_once '../includes/GymnastManager.php';

$gymnastManager = new GymnastManager();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $gymnasts = $gymnastManager->getAllGymnasts();
        echo json_encode($gymnasts);
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $gymnastManager->registerGymnast($data);
        echo json_encode($result);
        break;
        
    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        $result = $gymnastManager->deleteGymnast($id);
        echo json_encode($result);
        break;
}
?>
