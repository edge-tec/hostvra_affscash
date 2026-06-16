<?php
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    if (Auth::isImpersonating()) {
        Auth::stopImpersonating();
        
        $role = Auth::role();
        if (!$role) {
            throw new Exception("Session lost after stopping impersonation");
        }

        echo json_encode([
            'success' => true,
            'role' => $role,
            'user' => Auth::currentUser()
        ]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Not impersonating']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
