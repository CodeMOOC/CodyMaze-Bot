<?php

function message_msg_processing($message){
    Logger::debug("telegram update - message");

    $chat_id = $message['chat']['id'];
    //$message_id = $message['message_id'];
    //$from_id = $message['from']['id'];

    if (isset($message['text'])) {
        // We got an incoming text message
        $text = $message['text'];
        // Get user info to see if he has reached end of game
        $user_info = db_row_query("SELECT * FROM user_status WHERE telegram_id = $chat_id LIMIT 1");

        if (strpos($text, "/start") === 0) {
            Logger::debug("/start command");
            perform_command_start($chat_id, mb_strtolower($text));
            return;
        } elseif (strpos($text, "/request_certificate") === 0) {
            $pdf_path = "certificates/" . $user_info[USER_STATUS_CERTIFICATE_ID] . ".pdf";
            // send user's last certificate
            $result = telegram_send_document($chat_id, $pdf_path, "Certificato di Completamento");

        } elseif (strpos($text, "/reset") === 0){
            reset_game($chat_id);
            telegram_send_message($chat_id, "Il tuo progresso è stato resettato.\n Scrivi /start per ricomincare o scansiona un QRCode del CodyMaze.");
        } elseif(strpos($text, "/send_certificates") === 0 && $chat_id == 212567799){
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
        } elseif (strpos($text, "/start") !== 0 && $user_info[USER_STATUS_COMPLETED] == 1 && $user_info[USER_STATUS_CERTIFICATE_SENT] == 0) {
            // User is probably writing name for certificate
            request_name($chat_id, $text);
        } else {
            telegram_send_message($chat_id, "Non ho capito.");
        }
    }
    else {
        telegram_send_message($chat_id, "Uhm… non capisco questo tipo di messaggi! 😑\nPer riprovare invia /start o scansiona un QRCode.");
    }
}