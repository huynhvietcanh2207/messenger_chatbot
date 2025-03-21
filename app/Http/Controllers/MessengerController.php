<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Message;
use App\Models\User3; // Change to User3 model

class MessengerController extends Controller
{
    public function showMessages($userId = null)
    {
        // Lấy danh sách user từ bảng users
        $users = User3::orderBy('created_at', 'desc')->get(['messenger_id', 'name']); // Change to User3 model

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
                User3::updateOrCreate( // Change to User3 model
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
            'giá' => 'Hiện tại, ABCXYZ cung cấp nhiều gói dịch vụ với mức giá linh hoạt, phù hợp với từng nhu cầu. Quý khách vui lòng để lại thông tin hoặc liên hệ hotline để được tư vấn chi tiết.',

            'mở cửa' => 'ABCXYZ hoạt động từ 8h00 đến 18h00 từ thứ Hai đến thứ Bảy. Nếu quý khách cần hỗ trợ ngoài giờ, vui lòng liên hệ trước để được sắp xếp.',

            'dịch vụ' => 'ABCXYZ chuyên cung cấp các dịch vụ về **giải pháp công nghệ, thiết kế website, phần mềm, và tư vấn hệ thống CNTT**. Quý khách cần hỗ trợ về dịch vụ nào ạ?',

            'khuyến mãi' => 'Hiện tại, ABCXYZ có chương trình ưu đãi đặc biệt **giảm 15% cho khách hàng mới** khi đăng ký dịch vụ trong tháng này. Quý khách có muốn nhận ưu đãi không?',

            'hỗ trợ' => 'Đội ngũ kỹ thuật ABCXYZ luôn sẵn sàng hỗ trợ 24/7. Quý khách có thể gọi hotline **[Số hotline]** hoặc nhắn tin qua đây để được tư vấn nhanh chóng.',

            'địa chỉ' => 'ABCXYZ có trụ sở tại **[Địa chỉ cụ thể]**. Quý khách có thể ghé thăm trực tiếp hoặc đặt lịch hẹn để được phục vụ tốt nhất.',

            'thanh toán' => 'ABCXYZ hỗ trợ nhiều phương thức thanh toán linh hoạt như **chuyển khoản ngân hàng, thanh toán qua ví điện tử, và tiền mặt**. Quý khách muốn thanh toán theo hình thức nào ạ?',

            'bảo hành' => 'Chúng tôi cam kết chất lượng dịch vụ với **chính sách bảo hành lên đến 12 tháng**. Nếu có bất kỳ vấn đề nào, quý khách có thể liên hệ ngay để được hỗ trợ.',

            'thời gian hoàn thành' => 'Tùy theo từng dự án, thời gian triển khai dịch vụ tại ABCXYZ thường từ **3 - 15 ngày làm việc**. Quý khách có nhu cầu gấp vui lòng thông báo trước để chúng tôi hỗ trợ tốt nhất.',

            'tư vấn miễn phí' => 'ABCXYZ luôn sẵn sàng tư vấn miễn phí để giúp quý khách tìm được giải pháp phù hợp nhất. Quý khách có thể để lại thông tin hoặc gọi hotline để được hỗ trợ ngay.',

            'default' => 'Xin chào! Tôi là trợ lý hỗ trợ khách hàng của ABCXYZ. Quý khách cần tư vấn về dịch vụ nào ạ? 😊 
Dưới đây là một số câu hỏi phổ biến mà quý khách có thể quan tâm: 
- "Giá dịch vụ ABCXYZ là bao nhiêu?" 
- "ABCXYZ có những dịch vụ nào?" 
- "Chính sách bảo hành của ABCXYZ ra sao?" 
- "Hiện tại có chương trình khuyến mãi nào không?" 
Quý khách có thể nhập một trong những câu trên hoặc nhắn nội dung khác để tôi có thể hỗ trợ tốt hơn!'
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