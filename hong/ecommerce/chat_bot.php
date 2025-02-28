<?php
require_once 'db_connection.php';

class ChatBot
{
    private $api_key;
    private $pdo;

    public function __construct($pdo, $api_key)
    {
        $this->pdo = $pdo;
        $this->api_key = $api_key;
    }

    public function generateResponse($message)
    {
        try {
            // First, get relevant product information
            $product_info = $this->getRelevantProductInfo($message);

            // Construct the prompt with context
            $prompt = $this->constructPrompt($message, $product_info);

            // Call Gemini API
            $response = $this->callGeminiAPI($prompt);

            return $this->formatResponse($response);
        } catch (Exception $e) {
            error_log("ChatBot Error: " . $e->getMessage());
            return "I'm having trouble understanding right now. Could you please rephrase your question?";
        }
    }

    private function getRelevantProductInfo($message)
    {
        // Extract potential product-related keywords
        $keywords = $this->extractKeywords($message);

        if (empty($keywords)) {
            return null;
        }

        try {
            // Search in products table
            $placeholders = str_repeat('?,', count($keywords) - 1) . '?';
            $sql = "SELECT * FROM products WHERE " . implode(' OR ', array_fill(0, count($keywords), "LOWER(name) LIKE LOWER(?)"));
            $params = array_map(function ($keyword) {
                return "%$keyword%";
            }, $keywords);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            return null;
        }
    }

    private function extractKeywords($message)
    {
        $message = strtolower($message);
        $keywords = [];

        // Product categories
        $categories = [
            'phone',
            'smartphone',
            'iphone',
            'android',
            'samsung',
            'xiaomi',
            'huawei',
            'oppo',
            'vivo',
            'realme',
            'oneplus',
            'google',
            'pixel'
        ];

        // Features and common queries
        $features = [
            'camera',
            'battery',
            'storage',
            'memory',
            'screen',
            'display',
            'price',
            'processor',
            'cpu',
            'ram',
            'charging',
            'wireless',
            '5g',
            'wifi',
            'warranty',
            'delivery',
            'payment',
            'installment',
            'discount',
            'offer',
            'promotion',
            'compare',
            'best',
            'latest',
            'new',
            'cheap',
            'premium'
        ];

        // Extract matching keywords
        $words = explode(' ', $message);
        foreach ($words as $word) {
            $word = trim($word);
            if (in_array($word, $categories) || in_array($word, $features)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    private function constructPrompt($message, $product_info)
    {
        $prompt = "You are a helpful customer service representative for Gigabyte Phone Shop. ";
        $prompt .= "Your name is Gigabyte Assistant. Be friendly, professional, and concise. ";
        $prompt .= "Our store specializes in smartphones, laptops and accessories. ";
        $prompt .= "We offer various payment methods including cash, bank transfer, and credit card. ";

        if ($product_info) {
            $prompt .= "\nRelevant products in our store:\n";
            foreach ($product_info as $product) {
                $prompt .= "- {$product['name']} (RM{$product['price']}) - {$product['description']}\n";
            }
        }

        $prompt .= "\nCustomer: " . $message;
        $prompt .= "\nAssistant: ";

        return $prompt;
    }

    private function callGeminiAPI($prompt)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $this->api_key;

        $data = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $prompt
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
            curl_close($ch);
            throw new Exception("Failed to connect to the AI service");
        }

        curl_close($ch);

        if ($http_code !== 200) {
            error_log("API Error Response: " . $response);
            throw new Exception("API returned error code: " . $http_code);
        }

        $decoded_response = json_decode($response, true);
        error_log("API Response: " . json_encode($decoded_response)); // Debug log
        return $decoded_response;
    }

    private function formatResponse($api_response)
    {
        try {
            if (!isset($api_response['candidates'][0]['content']['parts'][0]['text'])) {
                error_log("Unexpected API response format: " . json_encode($api_response));
                return "I'm having trouble formulating a response. Could you please try again?";
            }

            $response = $api_response['candidates'][0]['content']['parts'][0]['text'];
            return trim($response);
        } catch (Exception $e) {
            error_log("Response formatting error: " . $e->getMessage());
            return "I'm having trouble processing the response. Please try again.";
        }
    }
}

// Handle incoming chat requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    try {
        $api_key = "AIzaSyC84DrjMa3XCr8xmEDRgeEMBOJfEYbCPwk";
        $chatbot = new ChatBot($pdo, $api_key);

        $user_message = trim($_POST['message']);
        $response = $chatbot->generateResponse($user_message);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'response' => $response
        ]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
