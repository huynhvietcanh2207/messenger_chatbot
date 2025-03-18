<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hộp chat khách hàng</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f5f5f5; }
        .chat-container { width: 400px; background: white; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); border-radius: 10px; overflow: hidden; }
        .chat-header { background: #0078ff; color: white; padding: 15px; text-align: center; font-size: 18px; }
        .chat-box { height: 400px; overflow-y: auto; padding: 10px; display: flex; flex-direction: column; }
        .message { max-width: 75%; padding: 10px; margin: 5px; border-radius: 10px; font-size: 14px; word-wrap: break-word; }
        .message.customer { background: #e3f2fd; align-self: flex-start; }
        .message.bot { background: #0078ff; color: white; align-self: flex-end; }
        .chat-footer { display: flex; padding: 10px; border-top: 1px solid #ddd; background: white; }
        .chat-footer input { flex: 1; padding: 10px; border: none; border-radius: 5px; }
        .chat-footer button { margin-left: 10px; background: #0078ff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; }
        .chat-footer button:hover { background: #005ecb; }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">Hộp Chat Khách Hàng</div>
        <div class="chat-box" id="chatBox">
            @foreach($messages as $message)
                <div class="message {{ $message->sender_id == 'bot' ? 'bot' : 'customer' }}">
                    <strong>{{ $message->sender_id == 'bot' ? 'Bot' : 'Khách' }}:</strong> {{ $message->text }}
                </div>
            @endforeach
        </div>
        <div class="chat-footer">
            <input type="text" id="messageInput" placeholder="Nhập tin nhắn...">
            <button onclick="sendMessage()">Gửi</button>
        </div>
    </div>

    <script>
          document.getElementById("messageInput").addEventListener("keydown", function(event) {
        if (event.key === "Enter") {
            sendMessage();
        }
    });
        function sendMessage() {
            let message = document.getElementById("messageInput").value;
            if (message.trim() === "") return;
            
            let chatBox = document.getElementById("chatBox");
            let newMessage = document.createElement("div");
            newMessage.classList.add("message", "bot");
            newMessage.innerHTML = `<strong>Bot:</strong> ${message}`;
            chatBox.appendChild(newMessage);

            // Gửi tin nhắn đến server qua AJAX
            fetch("{{ route('send.message') }}", {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
                body: JSON.stringify({ text: message })
            });

            document.getElementById("messageInput").value = "";
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</body>
</html>
