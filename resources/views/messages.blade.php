<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat với khách hàng</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #f5f5f5;
        }

        .container {
            display: flex;
            width: 600px;
            height: 500px;
            background: white;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .user-list {
            width: 200px;
            border-right: 1px solid #ddd;
            padding: 10px;
            overflow-y: auto;
            background: #f1f1f1;
        }

        .user-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #ddd;
        }

        .user-item:hover,
        .user-item.active {
            background: #0078ff;
            color: white;
        }

        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: #0078ff;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 18px;
        }

        .chat-box {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            display: flex;
            flex-direction: column;
        }

        .message {
            max-width: 75%;
            padding: 10px;
            margin: 5px;
            border-radius: 10px;
            font-size: 14px;
            word-wrap: break-word;
        }

        .message.customer {
            background: #e3f2fd;
            align-self: flex-start;
        }

        .message.bot {
            background: #0078ff;
            color: white;
            align-self: flex-end;
        }

        .chat-footer {
            display: flex;
            padding: 10px;
            border-top: 1px solid #ddd;
            background: white;
        }

        .chat-footer input {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
        }

        .chat-footer button {
            margin-left: 10px;
            background: #0078ff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
        }

        .chat-footer button:hover {
            background: #005ecb;
        }

        /* .message-time {
            font-size: 12px;
            color: #000;
            margin-top: 3px;
            text-align: right;
        } */
        .message-time-divider {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin: 10px 0;
            padding: 5px;
            background: #e0e0e0;
            border-radius: 5px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Danh sách user -->
        <div class="user-list">
            @foreach($users as $user)
                <div class="user-item {{ $user->messenger_id == $userId ? 'active' : '' }}"
                    onclick="window.location.href='{{ route('messages', ['userId' => $user->messenger_id]) }}'">
                    {{ $user->name ? $user->name : 'Khách ' . $user->messenger_id }}
                </div>
            @endforeach
        </div>


        <!-- Hộp chat -->
        <div class="chat-container">
            <div class="chat-header">
                @if($userId)
                    Đang chat với khách {{ $userId }}
                @else
                    Chọn một khách hàng để bắt đầu
                @endif
            </div>
            <div class="chat-box" id="chatBox">
                @foreach($messages as $message)
                                <!-- <div class="message {{ $message->sender_id == 'bot' ? 'bot' : 'customer' }}">
                                                                                            <strong>{{ $message->sender_id == 'bot' ? 'Bot' : 'Khách' }}:</strong> {{ $message->text }}
                                                                                            <div class="message-time">
                                                                                                {{ \Carbon\Carbon::parse($message->created_at)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                                                                                            </div>
                                                                                        </div> -->

                                @php
                                    $lastTimestamp = null; // Biến lưu mốc thời gian của tin nhắn trước
                                @endphp

                                @foreach($messages as $message)
                                            @php
                                                $currentTimestamp = \Carbon\Carbon::parse($message->created_at)->setTimezone('Asia/Ho_Chi_Minh');
                                                $showTime = false;

                                                if (!$lastTimestamp || $currentTimestamp->diffInMinutes($lastTimestamp) > 30) {
                                                    $showTime = true;
                                                    $lastTimestamp = $currentTimestamp;
                                                }
                                            @endphp

                                            @if($showTime)
                                                <div class="message-time-divider">
                                                    {{ $currentTimestamp->format('d/m/Y H:i') }}
                                                </div>
                                            @endif

                                            <div class="message {{ $message->sender_id == 'bot' ? 'bot' : 'customer' }}">
                                                <strong>{{ $message->sender_id == 'bot' ? 'Bot' : 'Khách' }}:</strong> {{ $message->text }}
                                            </div>
                                @endforeach

                @endforeach

            </div>

            @if($userId)
                <div class="chat-footer">
                    <input type="text" id="messageInput" placeholder="Nhập tin nhắn...">
                    <button onclick="sendMessage()">Gửi</button>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.getElementById("messageInput").addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                sendMessage();
            }
        });

        function sendMessage() {
            let message = document.getElementById("messageInput").value;
            if (message.trim() === "") return;

            // Lấy userId từ giao diện
            let userId = "{{ $userId }}";
            if (!userId) {
                alert("Vui lòng chọn một khách hàng để nhắn tin!");
                return;
            }

            let chatBox = document.getElementById("chatBox");
            let newMessage = document.createElement("div");
            newMessage.classList.add("message", "bot");
            newMessage.innerHTML = `<strong>Bot:</strong> ${message}`;
            chatBox.appendChild(newMessage);

            // Gửi tin nhắn kèm userId
            fetch("{{ route('send.message') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ text: message, userId: userId })
            }).then(response => response.json())
                .then(data => {
                    console.log("Message sent successfully:", data);
                }).catch(error => {
                    console.error("Error sending message:", error);
                });

            document.getElementById("messageInput").value = "";
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        let userId = "{{ $userId }}"; // Lấy userId từ Blade
        let lastMessageCount = {{ count($messages) }}; // Số tin nhắn ban đầu

        function fetchLatestMessages() {
            console.log("Fetching latest messages for user:", userId);
            fetch(`/messages/${userId}/latest`)
            .then(response => response.json())
                .then(data => {
                    console.log("Latest messages data:", data);
                    let chatBox = document.getElementById("chatBox");

                    if (data.length > lastMessageCount) {
                        data.slice(lastMessageCount).forEach(msg => {
                            let messageDiv = document.createElement("div");
                            messageDiv.classList.add("message", msg.sender_id === 'bot' ? 'bot' : 'customer');
                            messageDiv.innerHTML = `<strong>${msg.sender_id === 'bot' ? 'Bot' : 'Khách'}:</strong> ${msg.text}`;
                            chatBox.appendChild(messageDiv);
                        });

                        chatBox.scrollTop = chatBox.scrollHeight; // Luôn cuộn xuống tin nhắn mới nhất
                        lastMessageCount = data.length; // Cập nhật số lượng tin nhắn
                    }
                }).catch(error => {
                    console.error("Error fetching latest messages:", error);
                });
        }

        // Cập nhật tin nhắn mới mỗi 2 giây
        setInterval(fetchLatestMessages, 2000);
        // Cuộn xuống cuối hộp chat ngay khi trang tải xong
        document.addEventListener("DOMContentLoaded", function () {
            let chatBox = document.getElementById("chatBox");
            chatBox.scrollTop = chatBox.scrollHeight;
        });
    </script>

</body>

</html>