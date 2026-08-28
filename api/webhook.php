<?php
/**
 * Telegram Chat ID Bot
 * Hosted on Vercel
 */

$botToken = "7948122316:AAHSTsu0-rVnCVuaCuli1kUoAlkcgdz2NdI";
$adminId = "6743390968"; // <-- Change this to your Telegram Chat ID
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

// Helper function to get Channel/Group profile picture
function getChatProfilePic($chatId) {
    $res = sendTelegramRequest('getChat', ['chat_id' => $chatId]);
    if (isset($res['result']['photo']['big_file_id'])) {
        return $res['result']['photo']['big_file_id'];
    }
    return null;
}

// Fixed Reply Menu Keyboard with Unicode Fonts, Emojis, and User Request feature
$keyboard = [
    "keyboard" => [
        [
            [
                "text" => "👤 𝐌𝐲 𝐈𝐧𝐟𝐨",
                "style" => "primary"
            ]
        ],
        [
            [
                "text" => "🔍 𝐒𝐞𝐥𝐞𝐜𝐭 𝐔𝐬𝐞𝐫 (𝐆𝐞𝐭 𝐈𝐃)",
                "style" => "primary",
                "request_users" => [
                    "request_id" => 1,
                    "user_is_bot" => false,
                    "request_name" => true,
                    "request_username" => true,
                    "request_photo" => true
                ]
            ],
            [
                "text" => "🤖 𝐒𝐞𝐥𝐞𝐜𝐭 𝐁𝐨𝐭 (𝐆𝐞𝐭 𝐈𝐃)",
                "style" => "success",
                "request_users" => [
                    "request_id" => 2,
                    "user_is_bot" => true,
                    "request_name" => true,
                    "request_username" => true,
                    "request_photo" => true
                ]
            ]
        ],
        [
            [
                "text" => "📢 𝐒ᴇʟᴇᴄᴛ 𝐂ʜᴀɴɴᴇʟ",
                "style" => "primary",
                "request_chat" => [
                    "request_id" => 3,
                    "chat_is_channel" => true,
                    "request_title" => true,
                    "request_username" => true,
                    "request_photo" => true
                ]
            ],
            [
                "text" => "👥 𝐒ᴇʟ𝐞ᴄ𝐭 𝐆ʀᴏᴜᴘ",
                "style" => "danger",
                "request_chat" => [
                    "request_id" => 4,
                    "chat_is_channel" => false,
                    "request_title" => true,
                    "request_username" => true,
                    "request_photo" => true
                ]
            ]
        ]
    ],
    "resize_keyboard" => true
];

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = isset($update["message"]["text"]) ? $update["message"]["text"] : "";

    // 1. Check if user was selected via "Select User" button (Bot API 7.0+)
    if (isset($update["message"]["users_shared"])) {
        $shared = $update["message"]["users_shared"]["users"][0];
        $sUserId = $shared["user_id"];
        $sName = trim(($shared["first_name"] ?? "") . " " . ($shared["last_name"] ?? ""));
        if (empty($sName)) $sName = "No Name";
        $sUsername = isset($shared["username"]) ? "@" . $shared["username"] : "No Username";

        $caption = "🎯 <b>𝐒ᴇʟᴇᴄᴛᴇᴅ 𝐓ᴀʀɢᴇᴛ 𝐈ɴғᴏ:</b>\n\n";
        $caption .= "📛 <b>𝐍ᴀᴍᴇ:</b> " . htmlspecialchars($sName) . "\n";
        $caption .= "👤 <b>𝐔sᴇʀɴᴀᴍᴇ:</b> " . htmlspecialchars($sUsername) . "\n";
        $caption .= "🆔 <b>𝐂ʜᴀᴛ 𝐈𝐃:</b> <code>" . $sUserId . "</code>";

        // Try getting photo directly from payload first, then fallback
        $photoId = null;
        if (isset($shared["photo"]) && is_array($shared["photo"]) && count($shared["photo"]) > 0) {
            $photoId = end($shared["photo"])["file_id"];
        } else {
            $photoId = getUserProfilePic($sUserId);
        }

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
                'text' => $caption . "\n\n<i>📷 𝐍ᴏ 𝐏ʀᴏғɪʟᴇ 𝐏ɪᴄᴛᴜʀᴇ ғᴏᴜɴᴅ (𝐏ʀɪᴠᴀᴄʏ ʀᴇsᴛʀɪᴄᴛᴇᴅ ᴏʀ ɴᴏ ᴘʜᴏᴛᴏ).</i>",
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
        }
        exit;
    }

    // Handle Channel/Group shared via button
    if (isset($update["message"]["chat_shared"])) {
        $shared = $update["message"]["chat_shared"];
        $sChatId = $shared["chat_id"];
        $sTitle = isset($shared["title"]) ? $shared["title"] : "No Title";
        $sUsername = isset($shared["username"]) ? "@" . $shared["username"] : "No Link/Username";
        $reqId = $shared["request_id"];

        $cType = ($reqId == 3) ? "𝐂ʜᴀɴɴᴇʟ" : "𝐆ʀᴏᴜᴘ";

        $caption = "📢 <b>𝐒ᴇʟᴇᴄᴛᴇᴅ " . $cType . " 𝐈ɴғᴏ:</b>\n\n";
        $caption .= "📛 <b>𝐍ᴀᴍᴇ:</b> " . htmlspecialchars($sTitle) . "\n";
        $caption .= "🔗 <b>𝐋ɪɴᴋ:</b> " . htmlspecialchars($sUsername) . "\n";
        $caption .= "🆔 <b>𝐂ʜᴀᴛ 𝐈𝐃:</b> <code>" . $sChatId . "</code>";

        // Try getting photo directly from payload first, then fallback
        $photoId = null;
        if (isset($shared["photo"]) && is_array($shared["photo"]) && count($shared["photo"]) > 0) {
            $photoId = end($shared["photo"])["file_id"];
        } else {
            $photoId = getChatProfilePic($sChatId);
        }

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
                'text' => $caption . "\n\n<i>📷 𝐍ᴏ 𝐏ʀᴏғɪʟᴇ 𝐏ɪᴄᴛᴜʀᴇ ғᴏᴜɴᴅ (ᴏʀ 𝐁ᴏᴛ ɪs ɴᴏᴛ ɪɴ ᴛʜᴇ ᴄʜᴀᴛ).</i>",
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
        }
        exit;
    }

    // 2. Fallback for older API (user_shared)
    if (isset($update["message"]["user_shared"])) {
        $sUserId = $update["message"]["user_shared"]["user_id"];
        $caption = "🎯 <b>𝐒ᴇʟᴇᴄᴛᴇᴅ 𝐓ᴀʀɢᴇᴛ 𝐈ɴғᴏ:</b>\n\n";
        $caption .= "🆔 <b>𝐂ʜᴀᴛ 𝐈𝐃:</b> <code>" . $sUserId . "</code>\n\n";
        $caption .= "<i>📌 𝐍ᴏᴛᴇ: 𝐔ᴘᴅᴀᴛᴇ ʏᴏᴜʀ 𝐓ᴇʟᴇɢʀᴀᴍ ᴀᴘᴘ ᴛᴏ sᴇᴇ 𝐍ᴀᴍᴇ & 𝐔sᴇʀɴᴀᴍᴇ ᴅɪʀᴇᴄᴛʟʏ.</i>";

        $photoId = getUserProfilePic($sUserId);
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
                'text' => $caption,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
        }
        exit;
    }

    // 3. Check if the message is a Forwarded message
    if (isset($update["message"]["forward_origin"]) || isset($update["message"]["forward_from"]) || isset($update["message"]["forward_sender_name"]) || isset($update["message"]["forward_from_chat"])) {
        
        $fChat = null;
        if (isset($update["message"]["forward_from_chat"])) {
            $fChat = $update["message"]["forward_from_chat"];
        } elseif (isset($update["message"]["forward_origin"])) {
            if ($update["message"]["forward_origin"]["type"] == "channel" && isset($update["message"]["forward_origin"]["chat"])) {
                $fChat = $update["message"]["forward_origin"]["chat"];
            } elseif ($update["message"]["forward_origin"]["type"] == "chat" && isset($update["message"]["forward_origin"]["sender_chat"])) {
                $fChat = $update["message"]["forward_origin"]["sender_chat"];
            }
        }

        if ($fChat) {
            // Handle Channel/Group
            $fTitle = $fChat["title"] ?? "No Title";
            $fUsername = isset($fChat["username"]) ? "@" . $fChat["username"] : "No Link/Username";
            $fChatId = $fChat["id"];
            
            // Format type correctly (Channel, Supergroup, Group)
            $cType = $fChat["type"] ?? "Chat";
            if ($cType == "supergroup") $cType = "Group";
            $cType = ucfirst($cType);

            $caption = "📢 <b>𝐅ᴏʀᴡᴀʀᴅᴇᴅ " . $cType . " 𝐈ɴғᴏ:</b>\n\n";
            $caption .= "📛 <b>𝐍ᴀᴍᴇ:</b> " . htmlspecialchars($fTitle) . "\n";
            $caption .= "🔗 <b>𝐋ɪɴᴋ:</b> " . htmlspecialchars($fUsername) . "\n";
            $caption .= "🆔 <b>𝐂ʜᴀᴛ 𝐈𝐃:</b> <code>" . $fChatId . "</code>";

            $photoId = getChatProfilePic($fChatId);
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
                    'text' => $caption . "\n\n<i>📷 𝐍ᴏ 𝐏ʀᴏғɪʟᴇ 𝐏ɪᴄᴛᴜʀᴇ ғᴏᴜɴᴅ (ᴏʀ 𝐁ᴏᴛ ɪs ɴᴏᴛ ɪɴ ᴛʜᴇ ᴄʜᴀᴛ).</i>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboard
                ]);
            }
            exit;
        }

        // Handle Privacy Restricted Users
        if (isset($update["message"]["forward_sender_name"]) || (isset($update["message"]["forward_origin"]) && $update["message"]["forward_origin"]["type"] == "hidden_user")) {
            $hiddenName = isset($update["message"]["forward_sender_name"]) ? $update["message"]["forward_sender_name"] : $update["message"]["forward_origin"]["sender_user_name"];
            sendTelegramRequest('sendMessage', [
                'chat_id' => $chatId,
                'text' => "⚠️ <b>𝐏ʀɪᴠᴀᴄʏ 𝐑ᴇsᴛʀɪᴄᴛᴇᴅ</b>\n\n𝐓ʜᴇ ᴜsᴇʀ <b>" . htmlspecialchars($hiddenName) . "</b> ʜᴀs ʜɪᴅᴅᴇɴ ᴛʜᴇɪʀ ᴀᴄᴄᴏᴜɴᴛ ɪɴ ғᴏʀᴡᴀʀᴅᴇᴅ ᴍᴇssᴀɢᴇs. 𝐈 ᴄᴀɴɴᴏᴛ ᴇxᴛʀᴀᴄᴛ ᴛʜᴇɪʀ 𝐂ʜᴀᴛ 𝐈𝐃.",
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

            $caption = "🎯 <b>𝐅ᴏʀᴡᴀʀᴅᴇᴅ 𝐔sᴇʀ 𝐈ɴғᴏ:</b>\n\n";
            $caption .= "📛 <b>𝐍ᴀᴍᴇ:</b> " . htmlspecialchars($fName) . "\n";
            $caption .= "👤 <b>𝐔sᴇʀɴᴀᴍᴇ:</b> " . htmlspecialchars($fUsername) . "\n";
            $caption .= "🆔 <b>𝐂ʜᴀᴛ 𝐈𝐃:</b> <code>" . $fUserId . "</code>";

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
                    'text' => $caption . "\n\n<i>📷 𝐍ᴏ 𝐏ʀᴏғɪʟᴇ 𝐏ɪᴄᴛᴜʀᴇ ғᴏᴜɴᴅ.</i>",
                    'parse_mode' => 'HTML',
                    'reply_markup' => $keyboard
                ]);
            }
        }
        exit;
    }

    // 4. Handle standard text buttons & commands
    if ($text == "/start" || $text == "👤 𝐌𝐲 𝐈𝐧𝐟𝐨") {
        $userId = $update["message"]["from"]["id"];
        $name = trim(($update["message"]["from"]["first_name"] ?? "") . " " . ($update["message"]["from"]["last_name"] ?? ""));
        $username = isset($update["message"]["from"]["username"]) ? "@" . $update["message"]["from"]["username"] : "No Username";

        $caption = "👋 <b>𝐇ᴇʟʟᴏ! 𝐇ᴇʀᴇ ɪs ʏᴏᴜʀ 𝐈ɴғᴏ:</b>\n\n";
        $caption .= "📛 <b>𝐍ᴀᴍᴇ:</b> " . htmlspecialchars($name) . "\n";
        $caption .= "👤 <b>𝐔sᴇʀɴᴀᴍᴇ:</b> " . htmlspecialchars($username) . "\n";
        $caption .= "🆔 <b>𝐂ʜᴀᴛ 𝐈𝐃:</b> <code>" . $userId . "</code>";

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
                'text' => $caption . "\n\n<i>📷 𝐍ᴏ 𝐏ʀᴏғɪʟᴇ 𝐏ɪᴄᴛᴜʀᴇ ғᴏᴜɴᴅ.</i>",
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
        }

        // Notify Admin on /start
        if ($text == "/start" && $adminId != "YOUR_ADMIN_CHAT_ID" && $userId != $adminId) {
            
            // Temporary storage to track if the user has already joined (prevents spam)
            $dataFile = __DIR__ . '/user_data.json';
            
            // Vercel serverless functions have a read-only filesystem. 
            // If the folder is not writable, fallback to the /tmp directory.
            if (!is_writable(__DIR__)) {
                $dataFile = sys_get_temp_dir() . '/user_data.json';
            }

            $joinedUsers = [];
            if (file_exists($dataFile)) {
                $joinedUsers = json_decode(file_get_contents($dataFile), true) ?: [];
            }
            
            // Check if user is NEW (not in the list)
            if (!in_array($userId, $joinedUsers)) {
                
                // Add user to the list and save it
                $joinedUsers[] = $userId;
                file_put_contents($dataFile, json_encode($joinedUsers, JSON_PRETTY_PRINT));

                $adminCaption = "🚨 <b>𝐍ᴇᴡ 𝐔sᴇʀ 𝐉ᴏɪɴᴇᴅ!</b>\n\n";
                $adminCaption .= "📛 <b>𝐍ᴀᴍᴇ:</b> " . htmlspecialchars($name) . "\n";
                $adminCaption .= "👤 <b>𝐔sᴇʀɴᴀᴍᴇ:</b> " . htmlspecialchars($username) . "\n";
                $adminCaption .= "🆔 <b>𝐂ʜᴀᴛ 𝐈𝐃:</b> <code>" . $userId . "</code>";

                if ($photoId) {
                    sendTelegramRequest('sendPhoto', [
                        'chat_id' => $adminId,
                        'photo' => $photoId,
                        'caption' => $adminCaption,
                        'parse_mode' => 'HTML'
                    ]);
                } else {
                    sendTelegramRequest('sendMessage', [
                        'chat_id' => $adminId,
                        'text' => $adminCaption . "\n\n<i>📷 𝐍ᴏ 𝐏ʀᴏғɪʟᴇ 𝐏ɪᴄᴛᴜʀᴇ ғᴏᴜɴᴅ.</i>",
                        'parse_mode' => 'HTML'
                    ]);
                }
            }
        }
    } else {
        // Unknown text (not a command, not a forward)
        sendTelegramRequest('sendMessage', [
            'chat_id' => $chatId,
            'text' => "⚠️ <b>𝐏ʟᴇᴀsᴇ ᴜsᴇ ᴛʜᴇ ʙᴜᴛᴛᴏɴs ʙᴇʟᴏᴡ ᴛᴏ ɪɴᴛᴇʀᴀᴄᴛ, ᴏʀ ᴄʟɪᴄᴋ '𝐒ᴇʟᴇᴄᴛ 𝐔sᴇʀ' ᴛᴏ ғɪɴᴅ ᴀɴ 𝐈𝐃.</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => $keyboard
        ]);
    }
}

// Return 200 OK so Telegram knows we received the message
http_response_code(200);
echo "OK";
?>
