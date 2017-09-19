<?php
function message_msg_processing($message) {
    global $memory;

    Logger::debug("Processing text message", __FILE__);

    $chat_id = $message['chat']['id'];

    memory_load_for_user($chat_id);

    if (isset($message['text'])) {
        // We got an incoming text message
        $text = $message['text'];
        // Get user info to see if he has reached end of game
        $user_info = db_row_query("SELECT * FROM user_status WHERE telegram_id = $chat_id LIMIT 1");

        if (strpos($text, "/start") === 0) {
            perform_command_start($chat_id, mb_strtolower($text));
        }
        else if ($text === "/reset"){
            reset_game($chat_id);
            telegram_send_message($chat_id, "Il tuo progresso è stato resettato.\nScrivi /start per ricomincare o scansiona un QRCode del CodyMaze.");
        }
        else if($text == "/send_certificates" && $chat_id == 212567799){
            $result = db_table_query("SELECT * FROM user_status");
            if($result !== null && $result !== false){
                foreach ($result as $item) {
                    $pdf_path = "certificates/" . $item[USER_STATUS_CERTIFICATE_ID] . ".pdf";
                    $user_id = $item[0];
                    if($item[USER_STATUS_COMPLETED] == 1 && $item[USER_STATUS_CERTIFICATE_SENT] == 0) {
                        Logger::debug("Sending certificate to {$item[USER_STATUS_NAME]}");
                        $result = telegram_send_document($user_id, $pdf_path, "Certificato di Completamento");
                    }
                }
            }
        }
        else if (strpos($text, "/debug") === 0) {
            // Debugging commands received
            telegram_send_message($chat_id, "Received debug command...");
            debug_message_processing($chat_id, $text);
        }
        else if($user_info[USER_STATUS_COMPLETED] == 1) {
            if (isset($memory->nameRequested)) {
                // User is writing name for certificate
                request_name($chat_id, $text);
            }
            else {
                telegram_send_message($chat_id, "Hai completato CodyMaze!\nSe vuoi giocare nuovamente, invia il comando /reset.");
            }
        }
        else if ($user_info[USER_STATUS_COMPLETED] == 0) {
            // User is probably writing something instead of playing
            telegram_send_message($chat_id, "Non ho capito. Scansionare un QRCode del labirinto per continuare a giocare.");
        }
        else {
            telegram_send_message($chat_id, "Non ho capito.");
        }
    }
    else {
        telegram_send_message($chat_id, "Uhm… non capisco questo tipo di messaggi! 😑\nPer riprovare invia /start o scansiona un QRCode.");
    }

    memory_persist($chat_id);
}