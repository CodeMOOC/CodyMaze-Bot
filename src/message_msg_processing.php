<?php
require_once(dirname(__FILE__) . '/lib_localization.php');

function message_msg_processing($message) {
    global $memory;

    Logger::debug("Processing text message", __FILE__);

    $chat_id = $message['chat']['id'];

    memory_load_for_user($chat_id);
    localization_load_user($chat_id, $message['from']['language_code']);

    if (isset($message['text'])) {
        // We got an incoming text message
        $text = $message['text'];

        // Get user info to see if he has reached end of game
        $user_info = db_row_query("SELECT * FROM user_status WHERE telegram_id = $chat_id LIMIT 1");

        if ($text === "/reset"){
            reset_game($chat_id);
            telegram_send_message($chat_id, __("Your progress has been reset.\nWrite /start to start anew or scan in a QR Code."));
        }
        else if(strpos($text, "/debug") === 0) {
            // Debugging commands received
            telegram_send_message($chat_id, "Received debug command...");
            debug_message_processing($chat_id, $text);
        }
        else if($text === '/setlanguage') {
            // Prep language keyboard
            $lang_keyboard = array();
            $i = 0;
            foreach(LANGUAGE_NAME_MAP as $code => $lang) {
                if($i % 3 == 0) {
                    $lang_keyboard[] = array();
                }
                $lang_keyboard[sizeof($lang_keyboard) - 1][] = array(
                    'text' => $lang,
                    'callback_data' => 'language ' . $code
                );

                $i++;
            }

            $response = telegram_send_message($chat_id, __("Which language do you speak?"), array(
                'reply_markup' => array(
                    'inline_keyboard' => $lang_keyboard
                )
            ));

            set_new_callback_keyboard($response);
        }
        else if($user_info[USER_STATUS_COMPLETED] == 1) {
            // Game is completed

            if (isset($memory->nameRequested)) {
                // User is writing name for certificate
                request_name($chat_id, $text);
            }
            else {
                telegram_send_message($chat_id, __("You completed CodyMaze!\nIf you want to play again, please send the /reset command."));
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

                        $result = telegram_send_document(
                            $chat_id,
                            $pdf_path,
                            sprintf(__("Completion certificate. Code: %s."), $short_guid)
                        );
                    }
                } else {
                    telegram_send_message(
                        $chat_id,
                        __("You’ve never completed CodyMaze yet, I have no certificate to send you.") . ' 😔'
                    );
                }
            }
            else {
                // User is probably writing something instead of playing
                telegram_send_message(
                    $chat_id,
                    __("Didn’t get that. Please scan one of the QR Codes in the maze.")
                );
            }
        }
    }
    else {
        telegram_send_message($chat_id, __("I don’t understand this kind of message!"));
    }

    memory_persist($chat_id);
}