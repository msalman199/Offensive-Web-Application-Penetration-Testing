<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    
    // VULNERABLE: External entities enabled
    libxml_disable_entity_loader(false);
    $dom = new DOMDocument();
    $dom->loadXML($input, LIBXML_NOENT | LIBXML_DTDLOAD);
    
    $users = $dom->getElementsByTagName('user');
    $result = [];
    
    foreach ($users as $user) {
        $name = $user->getElementsByTagName('name')->item(0);
        $email = $user->getElementsByTagName('email')->item(0);
        
        if ($name && $email) {
            $result[] = [
                'name' => $name->nodeValue,
                'email' => $email->nodeValue
            ];
        }
    }
    
    echo json_encode(['users' => $result]);
} else {
    echo json_encode(['message' => 'Send POST request with XML data']);
}
?>
