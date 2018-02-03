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
        else if($text === '/help') {
            $move_count = db_scalar_query("SELECT count(*) FROM `moves` WHERE `telegram_id` = {$chat_id}");

            $txt = __("I am the <b>CodyMaze bot</b> and I will guide you through the game.") . " 🤖 " . __("The game is composed of <b>13 challenges</b>: for each one, I will send you new instructions that you must follow exactly in order to reach the final destination on the game’s chessboard.");
            if($move_count == 0) {
                $txt .= "\n\n" . __("In order to start playing, please scan the QR Code on one of the outer squares of the chessboard (that is, any square along the first or last row, or the first or last column). You may use any QR Code scanner application to do so.");
            }
            $txt .= "\n\n" . __("The instructions I will send you may contain the following commands:\n<code>f</code>: move forward,\n<code>l</code>: turn left,\n<code>r</code>: turn right,\nand other commands such as <code>if</code>, <code>else</code>, and <code>while</code>. Code blocks are indicated in <code>{}</code> and can be repeated. For instance, <code>2{fr}</code> tells you to move forward and turn right twice.");
            $txt .= "\n\n" . sprintf(__("For further help, check out the <a href=\"%s\">official CodyMaze website</a>."), "https://github.com/CodeMOOC/CodyMazeBot");

            telegram_send_message(
                $chat_id,
                $txt,
                array("parse_mode" => "HTML")
            );
        }
        else if($text === "/send_my_certificates") {
            telegram_send_message(
                $chat_id,
                __("Sorry, feature under development. Come back later.")
            );
            return;

            $result = db_table_query("SELECT * FROM `certificates_list` WHERE telegram_id = '{$chat_id}'");
            if($result !== null && $result !== false){
                foreach ($result as $item) {
                    $pdf_path = "certificates/" . $item[0] . ".png";
                    if(!file_exists($pdf_path)) {
                        Logger::warning("Certificate file '{$pdf_path}' does not exist, trying PDF", __FILE__);

                        $pdf_path = "certificates/" . $item[0] . ".pdf";
                        if(!file_exists($pdf_path)) {
                            Logger::error("Certificate file '{$pdf_path}' does not exist, ignoring", __FILE__);
                            continue;
                        }
                    }
                    $short_guid = substr($item[0], 0, 18);

                    $result = telegram_send_document(
                        $chat_id,
                        $pdf_path,
                        sprintf(__("Completion certificate. Code: %s."), $short_guid)
                    );
                }
            }
            else {
                telegram_send_message(
                    $chat_id,
                    __("You’ve never completed CodyMaze yet, I have no certificate to send you.") . ' 😔'
                );
            }
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