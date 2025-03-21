<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dịch Vụ Tư Vấn ABCXYZ</title>
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
            background: #eef1f6;
        }

        .container {
            display: flex;
            width: 700px;
            height: 550px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            overflow: hidden;
        }

        /* Danh sách user */
        .user-list {
            width: 220px;
            border-right: 1px solid #ddd;
            padding: 10px;
            overflow-y: auto;
            background: rgb(157, 221, 236);
        }

        .user-item {
            padding: 12px;
            cursor: pointer;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .user-item:hover,
        .user-item.active {
            background: #0078ff;
            color: white;
        }

        /* Hộp chat */
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .chat-header {
            background: #0078ff;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }

        .chat-box {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            display: flex;
            flex-direction: column;
            scrollbar-width: thin;
            scrollbar-color: #0078ff #f0f0f0;
        }

        .chat-box::-webkit-scrollbar {
            width: 6px;
        }

        .chat-box::-webkit-scrollbar-thumb {
            background: #0078ff;
            border-radius: 5px;
        }

        /* Tin nhắn */
        .message {
            max-width: 75%;
            padding: 10px 15px;
            margin: 5px 0;
            border-radius: 12px;
            font-size: 14px;
            word-wrap: break-word;
            display: inline-block;
            position: relative;

            .message {
                box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
            }

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

        /* Dấu thời gian */
        .message-time {
            font-size: 12px;
            margin-top: 3px;
            text-align: right;
        }

        .message-time-divider {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin: 10px 0;
            padding: 5px;
            background: #e0e0e0;
            border-radius: 5px;
            width: fit-content;
            align-self: center;
        }

        /* Input gửi tin nhắn */
        .chat-footer {
            display: flex;
            padding: 12px;
            border-top: 1px solid #ddd;
            background: #fff;
            align-items: center;
        }

        .chat-footer input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
            font-size: 14px;
        }

        .chat-footer button {
            margin-left: 10px;
            background: #0078ff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .chat-footer button:hover {
            background: #005ecb;
        }

        body {
            background-image: url('https://cdn-media.sforum.vn/storage/app/media/wp-content/uploads/2023/12/hinh-nen-vu-tru-72.jpg');
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Danh sách user -->
        <div class="user-list">
            @foreach($users as $user)
                <div class="user-item {{ $user->messenger_id == $userId ? 'active' : '' }}"
                    onclick="window.location.href='{{ route('messages.show', ['userId' => $user->messenger_id]) }}'">
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
                @php
                    $lastTimestamp = null;
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
                                    {{ $message->text }}
                                    <div class="message-time">
                                        {{ $currentTimestamp->format('H:i') }}
                                    </div>
                                </div>
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

    let userId = "{{ $userId }}";
    if (!userId) {
        alert("Vui lòng chọn một khách hàng để nhắn tin!");
        return;
    }

    let chatBox = document.getElementById("chatBox");
    let newMessage = document.createElement("div");
    newMessage.classList.add("message", "bot");
    newMessage.innerHTML = `${message} <div class="message-time">${new Date().toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit' })}</div>`;
    chatBox.appendChild(newMessage);

    // Sử dụng đường dẫn tương đối
    fetch("/send-message", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        body: JSON.stringify({ text: message, userId: userId })
    }).then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    }).then(data => {
        console.log("Message sent successfully:", data);
    }).catch(error => {
        console.error("Error sending message:", error);
        alert("Có lỗi khi gửi tin nhắn: " + error.message);
    });

    document.getElementById("messageInput").value = "";
    chatBox.scrollTop = chatBox.scrollHeight;
}

        let userId = "{{ $userId }}"; // Lấy userId từ Blade
        let lastMessageCount = {{ count($messages) }}; // Số tin nhắn ban đầu

        function fetchLatestMessages() {
    console.log("Fetching latest messages for user:", userId);
    fetch(`/messages/${userId}/latest`)  // Sử dụng đường dẫn tương đối
        .then(response => response.json())
        .then(data => {
            console.log("Latest messages data:", data);
            let chatBox = document.getElementById("chatBox");

            if (data.length > lastMessageCount) {
                data.slice(lastMessageCount).forEach(msg => {
                    let messageDiv = document.createElement("div");
                    messageDiv.classList.add("message", msg.sender_id === 'bot' ? 'bot' : 'customer');
                    messageDiv.innerHTML = `${msg.text} <div class="message-time">${new Date(msg.created_at).toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit' })}</div>`;
                    chatBox.appendChild(messageDiv);
                });

                chatBox.scrollTop = chatBox.scrollHeight;
                lastMessageCount = data.length;
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