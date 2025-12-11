<?php
// Bot tokeni
define('API_KEY', '8479069588:AAF1RPDaqw5kHBk7A1D2uIn3pr-PJ9J07g8');

function bot($method, $datas = []) {
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

$update = json_decode(file_get_contents("php://input"), true);

if (isset($update["message"])) {
    $message = $update["message"];
    $text = $message["text"] ?? '';
    $chat_id = $message["chat"]["id"];

    
    if ($text == "/start") {
        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "👋 Assalomu alaykum!\n\n🎵 Musiqa nomini yoki Instagram video link yuboring.",
        ]);
    }

    // INSTAGRAM 
    elseif (strpos($text, "instagram.com") !== false) {
        $msg = bot("sendMessage", [
            'chat_id' => $chat_id,
            'text' => "📥 Yuklanmoqda, kuting ⏳...",
        ]);

        $api = 'https://68656dad1fa44.myxvest1.ru/save/instagram/index.php?url=' . urlencode($text);
        $res = json_decode(file_get_contents($api), true);

        if (isset($res['video'])) {
            bot("sendVideo", [
                "chat_id" => $chat_id,
                "video" => $res['video'],
                "caption" => "✅ Instagram videosi yuklandi!",
            ]);
        } else {
            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "❌ Instagram videosini yuklab bo‘lmadi.",
            ]);
        }

        bot("deleteMessage", [
            'chat_id' => $chat_id,
            'message_id' => $msg['result']['message_id'],
        ]);
    }

    // MUSIQA QIDIRISH
    else {
        $music_api = "https://68656dad1fa44.myxvest1.ru/shazam/itune.php?song=" . urlencode($text);
        $res = json_decode(file_get_contents($music_api), true);

        if (!empty($res['natijalar'])) {
            $tracks = array_slice($res['natijalar'], 0, 20); // maksimal 20 ta
            $json = [];

            foreach ($tracks as $i => $track) {
                $num = $i + 1;
                $json[$num] = [
                    "url" => $track['download_url'],
                    "title" => $track['artist'] . " - " . $track['track_name'] . " (" . $track['duration'] . ")"
                ];
            }

            file_put_contents(__DIR__ . "/musics/$chat_id.json", json_encode($json, JSON_UNESCAPED_UNICODE));

            // birinchi 10 ta chiqadi
            $textMsg = "🎵 <b>" . $text . "</b> uchun topilgan musiqalar (1–10):\n\n";
            foreach (array_slice($json, 0, 10, true) as $num => $track) {
                $textMsg .= "$num. " . $track['title'] . "\n";
            }

            $btns = [
                [
                    ['text' => '1', 'callback_data' => 'music_1'],
                    ['text' => '2', 'callback_data' => 'music_2'],
                    ['text' => '3', 'callback_data' => 'music_3'],
                    ['text' => '4', 'callback_data' => 'music_4'],
                    ['text' => '5', 'callback_data' => 'music_5'],
                ],
                [
                    ['text' => '6', 'callback_data' => 'music_6'],
                    ['text' => '7', 'callback_data' => 'music_7'],
                    ['text' => '8', 'callback_data' => 'music_8'],
                    ['text' => '9', 'callback_data' => 'music_9'],
                    ['text' => '10', 'callback_data' => 'music_10'],
                ],
                [
                    ['text' => '➡️', 'callback_data' => 'next_2'],
                ]
            ];

            bot("sendMessage", [
                'chat_id' => $chat_id,
                'text' => $textMsg,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $btns]),
            ]);
        } else {
            bot("sendMessage", [
                'chat_id' => $chat_id,
                'text' => "❌ Hech qanday musiqa topilmadi!",
            ]);
        }
    }
}

if (isset($update['callback_query'])) {
    $data = $update['callback_query']['data'];
    $chat_id = $update['callback_query']['message']['chat']['id'];
    $mid = $update['callback_query']['message']['message_id'];

    // Musiqa yuklash
    if (preg_match("/music_(\\d+)/", $data, $m)) {
        $num = $m[1];
        $file = __DIR__ . "/musics/$chat_id.json";
        if (file_exists($file)) {
            $json = json_decode(file_get_contents($file), true);
            if (isset($json[$num])) {
                $loading = bot("sendMessage", [
                    'chat_id' => $chat_id,
                    'text' => "🎧 Musiqa yuklanmoqda, kuting ⏳...",
                ]);

                bot("sendAudio", [
                    'chat_id' => $chat_id,
                    'audio' => $json[$num]['url'],
                    'title' => $json[$num]['title'],
                ]);

                bot("deleteMessage", [
                    'chat_id' => $chat_id,
                    'message_id' => $loading['result']['message_id'],
                ]);
            }
        }
    }

    // Sahifalash - 2-sahifa (11–20)
    elseif ($data == "next_2") {
        $file = __DIR__ . "/musics/$chat_id.json";
        if (file_exists($file)) {
            $json = json_decode(file_get_contents($file), true);
            $textMsg = "🎵 Musiqalar (11–20):\n\n";
            foreach (array_slice($json, 10, 10, true) as $num => $track) {
                $textMsg .= "$num. " . $track['title'] . "\n";
            }

            $btns = [
                [
                    ['text' => '11', 'callback_data' => 'music_11'],
                    ['text' => '12', 'callback_data' => 'music_12'],
                    ['text' => '13', 'callback_data' => 'music_13'],
                    ['text' => '14', 'callback_data' => 'music_14'],
                    ['text' => '15', 'callback_data' => 'music_15'],
                ],
                [
                    ['text' => '16', 'callback_data' => 'music_16'],
                    ['text' => '17', 'callback_data' => 'music_17'],
                    ['text' => '18', 'callback_data' => 'music_18'],
                    ['text' => '19', 'callback_data' => 'music_19'],
                    ['text' => '20', 'callback_data' => 'music_20'],
                ],
                [
                    ['text' => '⬅️', 'callback_data' => 'prev_1'],
                ]
            ];

            bot("editMessageText", [
                'chat_id' => $chat_id,
                'message_id' => $mid,
                'text' => $textMsg,
                'reply_markup' => json_encode(['inline_keyboard' => $btns])
            ]);
        }
    }

    // Sahifalash - 1-sahifaga qaytish
    elseif ($data == "prev_1") {
        $file = __DIR__ . "/musics/$chat_id.json";
        if (file_exists($file)) {
            $json = json_decode(file_get_contents($file), true);
            $textMsg = "🎵 Musiqalar (1–10):\n\n";
            foreach (array_slice($json, 0, 10, true) as $num => $track) {
                $textMsg .= "$num. " . $track['title'] . "\n";
            }

            $btns = [
                [
                    ['text' => '1', 'callback_data' => 'music_1'],
                    ['text' => '2', 'callback_data' => 'music_2'],
                    ['text' => '3', 'callback_data' => 'music_3'],
                    ['text' => '4', 'callback_data' => 'music_4'],
                    ['text' => '5', 'callback_data' => 'music_5'],
                ],
                [
                    ['text' => '6', 'callback_data' => 'music_6'],
                    ['text' => '7', 'callback_data' => 'music_7'],
                    ['text' => '8', 'callback_data' => 'music_8'],
                    ['text' => '9', 'callback_data' => 'music_9'],
                    ['text' => '10', 'callback_data' => 'music_10'],
                ],
                [
                    ['text' => '➡️', 'callback_data' => 'next_2'],
                ]
            ];

            bot("editMessageText", [
                'chat_id' => $chat_id,
                'message_id' => $mid,
                'text' => $textMsg,
                'reply_markup' => json_encode(['inline_keyboard' => $btns])
            ]);
        }
    }
}
?>
