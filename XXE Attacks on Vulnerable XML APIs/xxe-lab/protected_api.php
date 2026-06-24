<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    
    // PROTECTED: External entities disabled
    libxml_disable_entity_loader(true);
    $dom = new DOMDocument();
    
    try {
        $dom->loadXML($input, LIBXML_NOENT);
        echo json_encode(['status' => 'protected', 'message' => 'External entities disabled']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'XML parsing failed']);
    }
}
?>
