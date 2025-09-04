<?php
/**
 * Bug Tracking Comment Suggestions Backend
 * Uses OpenAI API to generate professional bug tracking comments
 */

class OpenAIBugCommentSuggestions {
    private $apiKey;
    private $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private $model = 'gpt-4o-mini'; // You can change to gpt-4o, gpt-4.1, gpt-3.5-turbo, etc.
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Generate comment suggestions for a bug report
     */
    public function generateCommentSuggestions($bugData) {
        try {
            $prompt = $this->buildPrompt($bugData);
            $response = $this->callOpenAIAPI($prompt);
            
            return [
                'success' => true,
                'suggestions' => $response,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Build the prompt for OpenAI
     */
    private function buildPrompt($bugData) {
        $systemPrompt = "Role & Objective
You are an expert assistant for a software development team. Your job is to draft professional, polite, and effective comments for a bug-tracking tool (e.g., Jira, Azure DevOps, Linear).

Style Guidelines

Always be clear, polite, and helpful.

Use pronouns to address the reporter/developer.

Keep comments short and crisp when giving status/progress updates.

Example: “Looking into it.”, “Working on it, Jo.”

Use slightly longer, professional notes for resolutions, triage, and closure.

Example: “Thanks for confirming, Jo. A fix has been deployed to staging — please verify.”

Use placeholders where applicable: [Reporter's Name], [Browser], [Environment], [UX Team].

Do not mix reporter and developer roles in the same comment.

Output Format
Organize comment suggestions under the following lifecycle stages. Only include the stages relevant to the given bug:

Initial Triage & Request for Details – when information is missing or unclear. This will be addressed to the reporter.

Confirmation of Issue – when the bug is reproducible or obvious (e.g., with a screenshot). This will be addressed to the reporter.

Status Update / In Progress – short updates on investigation or fixes. This will be addressed to the reporter.

Resolution & Request for Verification – when a fix is deployed and needs testing. This will be addressed to the reporter.

Closing the Ticket – when the fix is verified and deployed. This will be addressed to the developer.

Reopening the Ticket – when the issue persists and requires reopening. This will be addressed to the developer.

Instruction
Output only comment suggestions, grouped by lifecycle stage, written in a way that could be directly pasted into a bug-tracking system.";

        $userPrompt = "Please provide comment suggestions for this bug report:\n\n";
        $userPrompt .= "Title: " . ($bugData['title'] ?? 'Not provided') . "\n";
        $userPrompt .= "Description: " . ($bugData['description'] ?? 'Not provided') . "\n";
        $userPrompt .= "Reporter: " . ($bugData['reporter'] ?? 'Not provided') . "\n";
        $userPrompt .= "Screenshot Attached: " . ($bugData['has_screenshot'] ? 'Yes' : 'No') . "\n";
        
        if (!empty($bugData['recent_comments'])) {
            $userPrompt .= "Recent Comments:\n";
            foreach ($bugData['recent_comments'] as $index => $comment) {
                // handle string or array format
                if (is_array($comment)) {
                    $userPrompt .= ($index + 1) . ". " . $comment['author'] . ": " . $comment['text'] . "\n";
                } else {
                    $userPrompt .= ($index + 1) . ". " . $comment . "\n";
                }
            }
        }
        
        return [
            'system' => $systemPrompt,
            'user' => $userPrompt
        ];
    }
    
    /**
     * Call OpenAI API
     */
    private function callOpenAIAPI($prompt) {
        $headers = [
            'Content-Type: application/json',
            'Authorization: ' . 'Bearer ' . $this->apiKey
        ];
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $prompt['system']
                ],
                [
                    'role' => 'user',
                    'content' => $prompt['user']
                ]
            ],
            'max_tokens' => 1200,
            'temperature' => 0.3
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            throw new Exception("cURL Error: " . $curlError);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode . " - " . $response);
        }
        
        $decoded = json_decode($response, true);
        
        if (!$decoded || !isset($decoded['choices'][0]['message']['content'])) {
            throw new Exception("Invalid API response format");
        }
        
        return $decoded['choices'][0]['message']['content'];
    }
    
    /**
     * Validate bug data input
     */
    public function validateBugData($bugData) {
        $errors = [];
        
        if (empty($bugData['title'])) {
            $errors[] = "Bug title is required";
        }
        
        if (empty($bugData['description'])) {
            $errors[] = "Bug description is required";
        }
        
        if (!isset($bugData['has_screenshot'])) {
            $bugData['has_screenshot'] = false;
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $bugData
        ];
    }
}

/**
 * REST API Endpoint Handler
 */
class BugTrackingAPI {
    private $openaiService;
    
    public function __construct($apiKey) {
        $this->openaiService = new OpenAIBugCommentSuggestions($apiKey);
    }
    
    public function handleRequest() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }
            
            $validation = $this->openaiService->validateBugData($input);
            
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Validation failed',
                    'details' => $validation['errors']
                ]);
                exit();
            }
            
            $result = $this->openaiService->generateCommentSuggestions($validation['data']);
            
            if (!$result['success']) {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Failed to generate suggestions',
                    'details' => $result['error']
                ]);
                exit();
            }
            
            http_response_code(200);
            echo json_encode($result);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Server error',
                'details' => $e->getMessage()
            ]);
        }
    }
}

/**
 * Configuration
 */
define('OPENAI_API_KEY', [YOUR_API_KEY]);

// Initialize API handler
$api = new BugTrackingAPI(OPENAI_API_KEY);
$api->handleRequest();
