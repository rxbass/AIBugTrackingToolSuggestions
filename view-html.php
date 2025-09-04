<?php
// Read raw POST input (from Postman)
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// Validate JSON
if (!$data || !isset($data['suggestions'])) {
    header("Content-Type: application/json");
    echo json_encode(["error" => "Invalid JSON input"]);
    exit;
}

// Extract values
$suggestions = $data['suggestions'];
$timestamp   = $data['timestamp'] ?? date("Y-m-d H:i:s");

// Convert markdown-like formatting to HTML
$suggestions = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $suggestions); // bold
$suggestions = nl2br($suggestions); // newlines to <br>
$suggestions = str_replace('---', '<hr>', $suggestions); // separators

// HTML Output
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Bug Report Suggestions</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background:#f9f9f9; }
        h2 { color: #2c3e50; }
        .suggestion { background:#fff; padding:15px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        strong { color: #d35400; }
        hr { border: 0; border-top: 1px solid #ccc; margin: 20px 0; }
        .timestamp { font-size: 0.85em; color: #555; margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Bug Report Suggestions</h2>
    <div class='suggestion'>{$suggestions}</div>
    <div class='timestamp'>Generated on: {$timestamp}</div>
</body>
</html>
";

// Return HTML response
header("Content-Type: text/html");
echo $html;
