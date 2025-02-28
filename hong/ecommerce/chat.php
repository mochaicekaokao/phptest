<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connection.php';

// Initialize chat history if not exists
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

// Handle incoming messages and clear chat requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['action']) && $_POST['action'] === 'clear_chat') {
        $_SESSION['chat_history'] = [];
        echo json_encode(['success' => true]);
        exit;
    } elseif (isset($_POST['message'])) {
        $user_message = trim($_POST['message']);

        if (!empty($user_message)) {
            try {
                // Store user message
                $_SESSION['chat_history'][] = [
                    'type' => 'user',
                    'message' => $user_message,
                    'timestamp' => time()
                ];

                // Get response from chatbot
                require_once 'chat_bot.php';
                $chatbot = new ChatBot($pdo, 'AIzaSyC84DrjMa3XCr8xmEDRgeEMBOJfEYbCPwk');
                $response = $chatbot->generateResponse($user_message);

                // Store bot response
                $_SESSION['chat_history'][] = [
                    'type' => 'bot',
                    'message' => $response,
                    'timestamp' => time()
                ];

                echo json_encode([
                    'success' => true,
                    'response' => $response
                ]);
            } catch (Exception $e) {
                error_log("Chat Error: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'error' => "I apologize, but I'm having trouble responding right now. Please try again in a moment."
                ]);
            }
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Support</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .chat-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        .chat-widget.minimized {
            height: 50px;
            overflow: hidden;
        }

        .chat-header {
            background: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header h3 {
            margin: 0;
            font-size: 16px;
        }

        .chat-body {
            height: 300px;
            overflow-y: auto;
            padding: 15px;
            background: #f9f9f9;
        }

        .chat-message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .message-content {
            max-width: 80%;
            padding: 10px;
            border-radius: 10px;
            margin: 5px 0;
        }

        .user-message {
            align-self: flex-end;
            background: #4CAF50;
            color: white;
        }

        .bot-message {
            align-self: flex-start;
            background: #e9ecef;
            color: #333;
        }

        .message-time {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }

        .chat-footer {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            outline: none;
        }

        .chat-send {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .chat-send:hover {
            background: #45a049;
        }

        .chat-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            z-index: 1001;
        }

        .chat-toggle i {
            font-size: 20px;
        }

        .typing-indicator {
            display: none;
            padding: 10px;
            color: #666;
            font-style: italic;
        }

        .clear-chat {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 0.9em;
            padding: 5px;
            margin-top: 10px;
        }

        .clear-chat:hover {
            color: #333;
        }
    </style>
</head>

<body>
    <div class="chat-toggle" onclick="toggleChat()">
        <i class="fas fa-comments"></i>
    </div>

    <div class="chat-widget minimized" id="chatWidget">
        <div class="chat-header" onclick="toggleChat()">
            <h3><i class="fas fa-headset"></i> Gigabyte Support</h3>
            <span class="minimize-btn"><i class="fas fa-minus"></i></span>
        </div>

        <div class="chat-body" id="chatBody">
            <?php foreach ($_SESSION['chat_history'] as $message): ?>
                <div class="chat-message">
                    <div class="message-content <?php echo $message['type'] ?>-message">
                        <?php echo htmlspecialchars($message['message']); ?>
                        <div class="message-time">
                            <?php echo date('H:i', $message['timestamp']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="typing-indicator" id="typingIndicator">
            Bot is typing...
        </div>

        <div class="chat-footer">
            <input type="text" class="chat-input" id="chatInput" placeholder="Type your message...">
            <button class="chat-send" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>

        <button onclick="clearChat()" class="clear-chat">
            Clear Chat History
        </button>
    </div>

    <script>
        function toggleChat() {
            const widget = document.getElementById('chatWidget');
            widget.classList.toggle('minimized');
        }

        function scrollToBottom() {
            const chatBody = document.getElementById('chatBody');
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        function showTypingIndicator() {
            document.getElementById('typingIndicator').style.display = 'block';
        }

        function hideTypingIndicator() {
            document.getElementById('typingIndicator').style.display = 'none';
        }

        function clearChat() {
            if (confirm('Are you sure you want to clear the chat history?')) {
                fetch('chat.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=clear_chat'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('chatBody').innerHTML = '';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        }

        function addMessage(message, isUser = true) {
            const chatBody = document.getElementById('chatBody');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message';

            const time = new Date().toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });

            messageDiv.innerHTML = `
                <div class="message-content ${isUser ? 'user' : 'bot'}-message">
                    ${message}
                    <div class="message-time">${time}</div>
                </div>
            `;

            chatBody.appendChild(messageDiv);
            scrollToBottom();
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();

            if (message) {
                addMessage(message, true);
                input.value = '';

                showTypingIndicator();

                fetch('chat.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `message=${encodeURIComponent(message)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideTypingIndicator();
                        if (data.success) {
                            addMessage(data.response, false);
                        } else if (data.error) {
                            addMessage(data.error, false);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        hideTypingIndicator();
                        addMessage("I'm sorry, but I'm having trouble connecting right now. Please try again later.", false);
                    });
            }
        }

        // Handle Enter key
        document.getElementById('chatInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });

        // Scroll to bottom on load
        scrollToBottom();
    </script>
</body>

</html>