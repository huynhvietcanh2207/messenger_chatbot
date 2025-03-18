<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messenger Chatbox</title>
</head>
<body>
    <h1>Chatbox Messenger</h1>
    <div id="messages"></div>

    <script>
        async function fetchMessages() {
            let response = await fetch('/api/messages');
            let messages = await response.json();
            document.getElementById('messages').innerHTML = messages.map(m => `<p>${m.text}</p>`).join('');
        }

        setInterval(fetchMessages, 2000);
    </script>
</body>
</html>
