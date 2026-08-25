<?php
/**
 * Telegram Chat ID Bot
 * Hosted on Vercel
 */

$botToken = "7948122316:AAHSTsu0-rVnCVuaCuli1kUoAlkcgdz2NdI";
$website = "https://api.telegram.org/bot" . $botToken;

// Read incoming JSON from Telegram Webhook
$update = file_get_contents('php://input');
$update = json_decode($update, TRUE);

// Helper function to send requests to Telegram API
function sendTelegramRequest($method, $parameters) {
    global $website;
    $url = $website . "/" . $method;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

// Helper function to get the largest profile picture file_id
function getUserProfilePic($userId) {
    $res = sendTelegramRequest('getUserProfilePhotos', ['user_id' => $userId, 'limit' => 1]);
    if (isset($res['result']['photos'][0][0]['file_id'])) {
        // Return the highest resolution photo (usually the last in the array for that photo)
        $photos = $res['result']['photos'][0];
        return end($photos)['file_id'];
    }
    return null;
}

// Fixed Reply Menu Keyboard
$keyboard = [
    "keyboard" => [
        [
            ["text" => "👤 My Info"],
            ["text" => "⏩ Forward Message (Get ID)"]
        ]
    ],
    "resize_keyboard" => true,
    "is_persistent" => true
];

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = isset($update["message"]["text"]) ? $update["message"]["text"] : "";

    // 1. Check if the message is a Forwarded message
    if (isset($update["message"]["forward_origin"]) || isset($update["message"]["forward_from"]) || isset($update["message"]["forward_sender_name"])) {
        
        // Handle Privacy Restricted Users (They hide their link on forwards)
        if (isset($update["message"]["forward_sender_name"]) || (isset($update["message"]["forward_origin"]) && $update["message"]["forward_origin"]["type"] == "hidden_user")) {
            $hiddenName = isset($update["message"]["forward_sender_name"]) ? $update["message"]["forward_sender_name"] : $update["message"]["forward_origin"]["sender_user_name"];
            sendTelegramRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => "⚠️ <b>Privacy Restricted</b>\n\nThe user <b>" . htmlspecialchars($hiddenName) . "</b> has hidden their account in forwarded messages. I cannot extract their Chat ID or Profile Picture.",
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
            exit;
        }

        // Handle Public Users
        $fUser = isset($update["message"]["forward_from"]) ? $update["message"]["forward_from"] : (isset($update["message"]["forward_origin"]["sender_user"]) ? $update["message"]["forward_origin"]["sender_user"] : null);

        if ($fUser) {
            $fUserId = $fUser["id"];
            $fName = trim(($fUser["first_name"] ?? "") . " " . ($fUser["last_name"] ?? ""));
            $fUsername = isset($fUser["username"]) ? "@" . $fUser["username"] : "No Username";

            $caption = "🎯 <b>Forwarded User Info:</b>\n\n";
            $caption .= "📛 <b>Name:</b> " . htmlspecialchars($fName) . "\n";
            $caption .= "👤 <b>Username:</b> " . htmlspecialchars($fUsername) . "\n";
            $caption .= "🆔 <b>Chat ID:</b> <code>" . $fUserId . "</code>";

            $photoId = getUserProfilePic($fUserId);
            if ($photoId) {
                sendTelegramRequest('sendPhoto', [
                    'chat_id' => $chatId,
                    'photo' => $photoId,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboard
                ]);
            } else {
                sendTelegramRequest('sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $caption . "\n\n<i>📷 No Profile Picture found.</i>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboard
                ]);
            }
        }
        exit;
    }

    // 2. Handle standard text buttons & commands
    if ($text == "/start" || $text == "👤 My Info") {
        $userId = $update["message"]["from"]["id"];
        $name = trim(($update["message"]["from"]["first_name"] ?? "") . " " . ($update["message"]["from"]["last_name"] ?? ""));
        $username = isset($update["message"]["from"]["username"]) ? "@" . $update["message"]["from"]["username"] : "No Username";

        $caption = "👋 <b>Hello! Here is your Info:</b>\n\n";
        $caption .= "📛 <b>Name:</b> " . htmlspecialchars($name) . "\n";
        $caption .= "👤 <b>Username:</b> " . htmlspecialchars($username) . "\n";
        $caption .= "🆔 <b>Chat ID:</b> <code>" . $userId . "</code>";

        $photoId = getUserProfilePic($userId);
        if ($photoId) {
            sendTelegramRequest('sendPhoto', [
                'chat_id' => $chatId,
                'photo' => $photoId,
                'caption' => $caption,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
        } else {
            sendTelegramRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => $caption . "\n\n<i>📷 No Profile Picture found.</i>",
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
        }
    } elseif ($text == "⏩ Forward Message (Get ID)") {
        sendTelegramRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => "📌 <b>How to check someone's ID:</b>\n\nJust <b>forward any text, photo, or message</b> from that person to me.\n\nI will instantly grab their Chat ID and Profile Picture!",
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    } else {
        // Unknown text (not a command, not a forward)
        sendTelegramRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => "Please use the buttons below, or forward a message to me to check the user's ID.",
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    }
}

// Return 200 OK so Telegram knows we received the message
http_response_code(200);
echo "OK";
?>
