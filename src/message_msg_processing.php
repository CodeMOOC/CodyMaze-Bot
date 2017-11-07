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

        if ($text === "/reset"){
            reset_game($chat_id);
            telegram_send_message($chat_id, "Il tuo progresso è stato resettato.\nScrivi /start per ricomincare o scansiona un QRCode del CodyMaze.");
        }
        else if (strpos($text, "/debug") === 0) {
            // Debugging commands received
            telegram_send_message($chat_id, "Received debug command...");
            debug_message_processing($chat_id, $text);
        }
        else if($user_info[USER_STATUS_COMPLETED] == 1) {
            // Game is completed

            if (isset($memory->nameRequested)) {
                // User is writing name for certificate
                request_name($chat_id, $text);
            }
            else {
                telegram_send_message($chat_id, "Hai completato CodyMaze!\nSe vuoi giocare nuovamente, invia il comando /reset.");
            }
        }
        else {
            // Game is not completed

            if (strpos($text, "/start") === 0) {
                perform_command_start($chat_id, mb_strtolower($text));
            }
            else if($text == "/send_my_certificates"){
                $result = db_table_query("SELECT * FROM certificates_list WHERE telegram_id = '{$chat_id}'");
                if($result !== null && $result !== false){
                    foreach ($result as $item) {
                        $pdf_path = "certificates/" . $item[0] . ".pdf";
                        $short_guid = substr($item[0], 0, 18);

                        $result = telegram_send_document($chat_id, $pdf_path, "Certificato di Completamento. Codice certificato: {$short_guid}");
                    }
                } else {
                    telegram_send_message($chat_id, "Non hai ancora completato il CodyMaze, non hai alcun certificato assegnato :(.");
                }
            }
            else {
                // User is probably writing something instead of playing
                telegram_send_message($chat_id, "Non ho capito. Scansionare un QRCode del labirinto per continuare a giocare.");
            }
        }
    }
    else {
        telegram_send_message($chat_id, "Uhm… non capisco questo tipo di messaggi! 😑\nPer riprovare invia /start o scansiona un QRCode.");
    }

    memory_persist($chat_id);
}