<?php
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'login';

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Email and password are required']);
        exit;
    }

    $result = Auth::login($email, $password, true); // True to remember session
    
    if ($result['success']) {
        echo json_encode([
            'success' => true, 
            'role' => $result['role'],
            'session_id' => session_id(),
            'message' => 'Login successful'
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
} elseif ($action === 'logout') {
    Auth::logout();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Action not found']);
}
