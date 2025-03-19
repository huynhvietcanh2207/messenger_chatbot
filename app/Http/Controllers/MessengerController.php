<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Message;
use App\Models\User;
class MessengerController extends Controller
{
    public function showMessages($userId = null)
    {
        // Lấy danh sách user từ bảng users
        $users = User::orderBy('created_at', 'desc')->get(['messenger_id', 'name']);

        $messages = [];

        if ($userId) {
            $messages = Message::where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId)
                    ->orWhere(function ($query) use ($userId) {
                        $query->where('sender_id', 'bot')->where('receiver_id', $userId);
                    });
            })
                ->orderBy('created_at', 'asc')
                ->get();
            
        }

        return view('messages', compact('users', 'messages', 'userId'));
    }

    public function getLatestMessages($userId)
    {
        $messages = Message::where(function ($query) use ($userId) {
            $query->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }




    public function sendMessage(Request $request)
    {
        $text = $request->text;
        $userId = $request->userId; // Lấy userId từ request

        if (!$userId) {
            return response()->json(['status' => 'error', 'message' => 'User ID không hợp lệ!'], 400);
        }

        // Lưu tin nhắn vào database
        Message::create([
            'sender_id' => 'bot',
            'receiver_id' => $userId ?? null, // Thêm receiver_id vào để biết tin này gửi cho ai
            'text' => $text
        ]);

        // Gửi tin nhắn đến Messenger của khách hàng
        $this->sendToMessenger($userId, $text);

        return response()->json(['status' => 'success']);
    }

    private function sendToMessenger($recipientId, $messageText)
    {
        \Log::info("Gửi tin nhắn đến: " . $recipientId);
        \Log::info("Nội dung: " . $messageText);

        $accessToken = env('MESSENGER_PAGE_ACCESS_TOKEN');
        $response = Http::post("https://graph.facebook.com/v12.0/me/messages?access_token=$accessToken", [
            'recipient' => ['id' => $recipientId],
            'message' => ['text' => $messageText],
        ]);

        \Log::info("Response từ Facebook: " . $response->body());
    }


    public function verifyWebhook(Request $request)
    {
        \Log::info(request()->headers->all());

        $verifyToken = env('MESSENGER_VERIFY_TOKEN');
        if ($request->hub_verify_token === $verifyToken) {
            return response($request->hub_challenge, 200);
        }
        return response('Error, invalid token', 403);
    }

    public function handleWebhook(Request $request)
    {
        $data = $request->all();

        if (!empty($data['entry'][0]['messaging'][0])) {
            $message = $data['entry'][0]['messaging'][0];

            if (isset($message['message']['text'])) {
                $senderId = $message['sender']['id'];
                $text = $message['message']['text'];

                // Lấy thông tin user từ Messenger
                $userInfo = $this->getMessengerUserInfo($senderId);
                $userName = $userInfo['name'] ?? 'Khách';

                // Cập nhật hoặc tạo mới user
                User::updateOrCreate(
                    ['messenger_id' => $senderId],
                    ['name' => $userName]
                );

                // Lưu tin nhắn của user vào database
                Message::create([
                    'sender_id' => $senderId,
                    'receiver_id' => 'bot', // Bot là người nhận
                    'text' => $text
                ]);

                // Tạo phản hồi từ bot
                $reply = $this->generateBotResponse($text);
                $this->sendToMessenger($senderId, $reply);

                // Lưu tin nhắn bot vào database
                Message::create([
                    'sender_id' => 'bot',
                    'receiver_id' => $senderId, // Gửi đến user
                    'text' => $reply
                ]);
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function generateBotResponse($userMessage)
    {
        $responses = [
            'giá' => 'Sản phẩm của chúng tôi có giá từ 100k đến 500k. Bạn muốn tư vấn thêm không?',
            'mở cửa' => 'Chúng tôi mở cửa từ 8h sáng đến 10h tối. Bạn cần hỗ trợ thêm gì không?',
            'ship' => 'Chúng tôi có hỗ trợ giao hàng toàn quốc với phí ship từ 20k. Bạn muốn đặt hàng chứ?',
            'khuyến mãi' => 'Hiện tại chúng tôi đang có chương trình giảm giá 10% cho đơn hàng đầu tiên.',
            'địa chỉ' => 'Chúng tôi ở số 123, Đường ABC, TP. HCM. Bạn có muốn ghé thăm cửa hàng không?',
            'default' => 'Xin chào! Tôi là trợ lý ảo, bạn cần tư vấn gì? 😊'
        ];

        foreach ($responses as $key => $response) {
            if (strpos(strtolower($userMessage), $key) !== false) {
                return $response;
            }
        }

        return $responses['default'];
    }

    private function getMessengerUserInfo($senderId)
    {
        $accessToken = env('MESSENGER_PAGE_ACCESS_TOKEN'); // Lấy token từ .env
        $url = "https://graph.facebook.com/v18.0/$senderId?fields=first_name,last_name&access_token=$accessToken";

        $response = Http::get($url);
        if ($response->successful()) {
            $data = $response->json();
            return [
                'name' => $data['first_name'] . ' ' . $data['last_name']
            ];
        }
        return ['name' => 'Khách'];
    }

    // private function sendMessage($recipientId, $messageText)
    // {
    //     \Log::info("Đang gửi tin nhắn đến: " . $recipientId);
    //     \Log::info("Nội dung: " . $messageText);

    //     $accessToken = env('MESSENGER_PAGE_ACCESS_TOKEN');
    //     $response = Http::post("https://graph.facebook.com/v12.0/me/messages?access_token=$accessToken", [
    //         'recipient' => ['id' => $recipientId],
    //         'message' => ['text' => $messageText],
    //     ]);

    //     \Log::info("Response từ Facebook: " . $response->body());
    // }

}