<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    
    // SECURE: Disable external entities
    libxml_disable_entity_loader(true);
    
    // Use secure parser configuration
    $dom = new DOMDocument();
    $dom->resolveExternals = false;
    $dom->substituteEntities = false;
    
    // Load XML without processing external entities
    $success = @$dom->loadXML($input, LIBXML_NOENT | LIBXML_DTDLOAD | LIBXML_NONET);
    
    if (!$success) {
        echo json_encode(['error' => 'Invalid XML']);
        exit;
    }
    
    // Process only expected elements
    $users = $dom->getElementsByTagName('user');
    $result = [];
    
    foreach ($users as $user) {
        $name = $user->getElementsByTagName('name')->item(0);
        $email = $user->getElementsByTagName('email')->item(0);
        
        if ($name && $email) {
            // Sanitize output
            $result[] = [
                'name' => htmlspecialchars($name->nodeValue),
                'email' => filter_var($email->nodeValue, FILTER_SANITIZE_EMAIL)
            ];
        }
    }
    
    echo json_encode(['users' => $result, 'secure' => true]);
}
?>
