<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Message;

class MessengerController extends Controller
{
    public function showMessages()
    {
        $messages = Message::orderBy('created_at', 'desc')->get();
        return view('messages', compact('messages'));
    }
    public function sendMessage(Request $request)
    {
        $text = $request->text;
    
        // Lưu tin nhắn vào database
        Message::create([
            'sender_id' => 'bot',
            'text' => $text
        ]);
    
        // Gửi tin nhắn đến Messenger của khách hàng
        $this->sendToMessenger($text);
    
        return response()->json(['status' => 'success']);
    }
    private function sendToMessenger($messageText)
    {
        // Lấy ID của khách hàng cuối cùng từ database
        $customerMessage = Message::where('sender_id', '!=', 'bot')->orderBy('created_at', 'desc')->first();
    
        if (!$customerMessage) {
            \Log::error("Không tìm thấy khách hàng để gửi tin nhắn.");
            return;
        }
    
        $recipientId = $customerMessage->sender_id; // Lấy ID khách hàng cuối cùng
    
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

                // Lưu vào database
                Message::create([
                    'sender_id' => $senderId,
                    'text' => $text
                ]);

                $this->sendMessage($senderId, "Bạn cần giúp gì không? " . $text);
            }
        }
        return response('EVENT_RECEIVED', 200);
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
