<?php
/*
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 */

require_once(dirname(__FILE__) . '/data.php');
require_once(dirname(__FILE__) . '/callback_msg_processing.php');
require_once(dirname(__FILE__) . '/message_msg_processing.php');
require_once(dirname(__FILE__) . '/debug_msg_processing.php');
require_once(dirname(__FILE__) . '/maze_generator.php');
require_once(dirname(__FILE__) . '/maze_commands.php');
require_once(dirname(__FILE__) . '/htmltopdf.php');

// This file assumes to be included by pull.php or
// hook.php right after receiving a new Telegram update.
// It also assumes that the update data is stored
// inside a $update variable.

// Input: $update
if(isset($update['message'])) {
    // Standard message
    message_msg_processing($update['message']);
}
else if(isset($update['callback_query'])) {
    // Callback query
    callback_msg_processing($update['callback_query']);
}

/**
 * @param $chat_id
 * @param $message
 */
function perform_command_start($chat_id, $message) {
    Logger::debug("Start command");

    // Check if user is already registered
    $user_exists = db_scalar_query("SELECT `telegram_id` FROM `user_status` WHERE `telegram_id` = {$chat_id} LIMIT 1");

    if(!$user_exists || $message === '/start') {
        // New user - register and start game
        start_command_new_conversation($chat_id);
        return;
    }

    // User exists - check /start command to see if it's a valid position
    $board_pos = substr($message, 7);
    Logger::debug("QRCode --> board position: {$board_pos}");

    if ($board_pos === "" || $board_pos === null){
        // Start command with no coordinate - check if is error
        $user_info = db_row_query("SELECT * FROM user_status WHERE telegram_id = {$chat_id} LIMIT 1");
        if($user_info[1] == 0){
            // Error - wrong /start command
            telegram_send_message($chat_id, "Ops! Sembra che mi sia arrivato un comando sbagliato, prova a fare una nuova scansione!\n");
        } else {
            if($user_info[2] === null){
                // Error - waiting for user's name
                telegram_send_message($chat_id, "Ops! Se non erro dovresti indicarmi il tuo nome ora, prova a riscrivermelo!\n");
            }
        }
        return;
    }

    // Get user's position from db if available
    $user_status = db_scalar_query("SELECT telegram_id FROM moves WHERE telegram_id = {$chat_id} LIMIT 1");
    Logger::debug("Telegram user: {$user_status}");

    // Check if user has a last position
    $last_position = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL ORDER BY reached_on DESC LIMIT 1");
    Logger::debug("User's last position: {$last_position}");

    // Check if there's an open maze being solved
    $has_null_timestamp = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL");
    Logger::debug("User has null timestamp: {$has_null_timestamp}");

    // If user exists but hasn't begun the first step, send first step command
    if ($user_status === null || $user_status === false) {
        $success = db_perform_action("INSERT INTO moves (telegram_id, reached_on, cell) VALUES($chat_id, NOW(), '$board_pos')");
        Logger::debug("Success of insertion query: {$success}");

        start_command_first_step($chat_id, $board_pos);
        return;
    }

    // If all else fails, then the game is in progress
    start_command_continue_conversation($chat_id, $board_pos);
}

/**
 * @param $chat_id
 */
function start_command_new_conversation($chat_id) {
    Logger::debug("Start new conversation");

    $move_count = db_scalar_query("SELECT count(*) FROM `moves` WHERE `telegram_id` = {$chat_id}");

    $txt = "Ciao, sono il bot CodyMaze! 🤖";
    if($move_count === 0) {
        $txt .= "\n\nPosizionati lungo il bordo della scacchiera e scansiona un QRCode!";
    }

    // Send message to user
    telegram_send_message($chat_id, $txt);
}

/**
 * @param $chat_id
 * @param $board_pos
 */
function start_command_first_step($chat_id, $board_pos){
    Logger::debug("Start first step - board position {$board_pos}");
    global $cardinal_position_to_name_map;
    $cardinal_pos = coordinate_find_initial_direction($board_pos);

    if($cardinal_pos == null){
        // Remove record and warn user of wrong position
        $success = db_perform_action("DELETE FROM moves WHERE telegram_id = {$chat_id} AND cell = '{$board_pos}'");
        Logger::debug("Removed record on wrong beginning position: {$success}");

        telegram_send_message($chat_id, "Ops! Dovresti posizionarti lungo il perimetro della scacchiera per iniziare.\n");
    } else {
        // Update db with initial cardinal position
        $full_pos = mb_strtolower($board_pos.$cardinal_pos);
        db_perform_action("UPDATE moves SET cell = '$full_pos' WHERE telegram_id = {$chat_id} ORDER BY reached_on ASC");

        $row_column_pos = substr($board_pos, 0, 2);
        telegram_send_message($chat_id, "Benissimo, hai trovato il blocco di partenza in <code>{$row_column_pos}</code>! Ora dovresti posizionarti in modo da guardare verso <code>{$cardinal_pos}</code> se non lo stai già facendo.\n\n", array("parse_mode" => "HTML"));

        request_cardinal_position($chat_id, CARD_NOT_ANSWERING_QUIZ);
    }
}

/**
 * @param $chat_id
 * @param null $user_position_id
 */
function start_command_continue_conversation($chat_id, $user_position_id = null) {
    Logger::debug("Resuming old conversation");

    global $cardinal_position_to_name_map;

    // Get current game position of user
    $reached_count = db_scalar_query("SELECT COUNT(*) FROM `moves` WHERE `telegram_id` = {$chat_id} AND `reached_on` IS NOT NULL");
    $unreached_count = db_scalar_query("SELECT COUNT(*) FROM `moves` WHERE `telegram_id` = {$chat_id} AND `reached_on` IS NULL");
    Logger::debug("Reached: {$reached_count}, unreached: {$unreached_count}");

    $user_game_status = $reached_count - 1;

    if($unreached_count !== 1) {
        // User is back-tracking to an already solved position
        $target = db_scalar_query("SELECT `cell` FROM `moves` WHERE `telegram_id` = {$chat_id} ORDER BY `reached_on` DESC LIMIT 1");

        if(strcmp(substr($target, 0, 2), $user_position_id) === 0) {
            request_cardinal_position($chat_id, CARD_NOT_ANSWERING_QUIZ);
        }
        else {
            // Wrong destination, direct to correct target explicitly
            $target_pos = get_position_no_direction($target);
            $target_dir = get_direction($target);

            Logger::info("User failed back-tracking, position '{$user_position_id}', expected '{$target}'", __FILE__, $chat_id);

            telegram_send_message($chat_id, "Ci siamo persi?\n\nRaggiungi la posizione <code>{$target_pos}</code> guardando verso <code>{$cardinal_position_to_name_map[$target_dir]}</code>!", array("parse_mode" => "HTML"));
        }

        return;
    }

    // User has a tuple with null timestamp: he's solving a maze
    // AND if position is == NUMBER_OF_GAMES, it's the end of the game
    if($user_game_status < NUMBER_OF_GAMES) {
        $answer = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL LIMIT 1");
        Logger::debug("Expecting answer: {$answer}");

        // Check for correct answer and update db
        if(strcmp(substr($answer, 0, 2), $user_position_id) === 0) {
            Logger::info("Correctly reached {$user_position_id} for level #" . $user_game_status, __FILE__, $chat_id);

            // Correct answer - continue or end game if reached last maze
            if($user_game_status == (NUMBER_OF_GAMES-1)){
                request_cardinal_position($chat_id, CARD_ENDGAME_POSITION);
            }
            else {
                // Continue with next maze
                if($reached_count > 0)
                    request_cardinal_position($chat_id, CARD_ANSWERING_QUIZ);
                else
                    request_cardinal_position($chat_id, CARD_NOT_ANSWERING_QUIZ);
            }
        }
        else {
            Logger::info("Reached {$user_position_id} instead of {$answer}", __FILE__, $chat_id);

            // Wrong answer - remove end of maze position tuple and send back to last position for new maze
            $success = db_perform_action("DELETE FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL");
            // TODO db_perform_action("UPDATE moves SET reached_on = NULL WHERE telegram_id = {$chat_id} ORDER BY reached_on DESC LIMIT 1");

            Logger::debug("Success of remove query: {$success}");

            $previous_position = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} ORDER BY reached_on DESC LIMIT 1");
            $previous_position_no_direction = get_position_no_direction($previous_position);
            $previous_position_direction = get_direction($previous_position);
            telegram_send_message($chat_id, "Ops! Hai sbagliato!\n\nRitorna alla posizione <code>{$previous_position_no_direction}</code> guardando verso <code>{$cardinal_position_to_name_map[$previous_position_direction]}</code> e scansiona nuovamente il codice.", array("parse_mode" => "HTML"));
        }
    }
    else {
        //This should never happen because of the cardinal position request
        Logger::warning("Continuing conversation with {$user_game_status} reached steps, ending game", __FILE__, $chat_id);

        end_of_game($chat_id);
    }
}

/**
 * @param $chat_id
 */
function end_of_game($chat_id) {
    global $memory;
    $memory->nameRequested = true;

    $result = db_perform_action("UPDATE user_status SET completed = 1 WHERE telegram_id = {$chat_id}");
    telegram_send_message($chat_id, "Complimenti! Hai completato CodyMaze! 👏");
    telegram_send_message($chat_id, "Scrivi il nome e cognome da visualizzare sul certificato di completamento:");
}

/**
 * @param $chat_id
 * @param $name
 */
function send_pdf($chat_id, $name){
    Logger::info("Game completed", __FILE__, $chat_id);

    db_perform_action("UPDATE `user_status` SET `completed_on` = NOW(), name = '" . db_escape($name) . "' WHERE `telegram_id` = {$chat_id}");

    $result = htmlToPdf($name);
    Logger::debug("RESULT:");
    Logger::debug("pdf_valid: ". $result["pdf_valid"]);
    Logger::debug("pdf_guid: " . $result["pdf_guid"]);
    Logger::debug("pdf_date: " . $result["pdf_date"]);
    Logger::debug("pdf_name: " . $result["pdf_name"]);
    Logger::debug("pdf_file: " . $result["pdf_file"]);

    if($result["pdf_valid"]){
        $guid = $result["pdf_guid"];
        $date = $result["pdf_date"];
        $pdf_path = "certificates/".$result["pdf_file"];

        // update user_status
        db_perform_action("UPDATE `user_status` SET `certificate_id` = '" . db_escape($guid) . "' WHERE `telegram_id` = {$chat_id}");

        // update certificates_list
        $result = db_perform_action("INSERT INTO `certificates_list` (`certificate_id`, `telegram_id`, `name`, `date`) VALUES ('" . db_escape($guid) . "', {$chat_id}, '" . db_escape($name) . "', NOW())");

        $result = telegram_send_document($chat_id, $pdf_path, "Certificato di Completamento");
        if($result !== false) {
            Logger::info("Generated and sent certificate {$guid}", __FILE__, $chat_id);

            // remove temp pdf
            unlink($pdf_path);

            telegram_send_message($chat_id, "Grazie per aver giocato con CodyMaze!");
        }
    }
    else {
        Logger::error("Failed to generate certificate", __FILE__, $chat_id);
    }
}

/**
 * @param $chat_id
 * @param $state
 */
function request_cardinal_position($chat_id, $state) {
    set_new_callback_keyboard(telegram_send_message($chat_id, "In che direzione stai guardando?",
        array("reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => "Nord", "callback_data" => "card n {$state}"),
                ),
                array(
                    array("text" => "Ovest", "callback_data" => "card w {$state}"),
                    array("text" => "Est", "callback_data" => "card e {$state}")
                ),
                array(
                    array("text" => "Sud", "callback_data" => "card s {$state}"),
                )
            )
        ))
    ));
}

/**
 * @param $chat_id
 * @param $name
 */
function request_name($chat_id, $name) {
    set_new_callback_keyboard(telegram_send_message($chat_id, "Confermi che il nome inviato è {$name}?",
        array("reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => "Sì", "callback_data" => "name {$name}"),
                ),
                array(
                    array("text" => "No", "callback_data" => "name error"),
                )
            )
        ))
    ));
}

/**
 * @param $chat_id
 */
function reset_game($chat_id) {
    db_perform_action("DELETE FROM moves WHERE telegram_id = $chat_id");
    db_perform_action("DELETE FROM user_status WHERE telegram_id = $chat_id");
}

function catastrofical_failure($chat_id) {
    reset_game($chat_id);

    telegram_send_message($chat_id, "Ops! Sembra che ci sia stato un errore. Ti chiediamo di ricominciare da capo. 😞");
}

/**
 * @param $min
 * @param $max
 * @param $value
 * @return mixed
 */
function clamp($min, $max, $value) {
    if($value < $min)
        return $min;
    else if($value > $max)
        return $max;
    else
        return $value;
}

/**
 * @param $position
 * @return bool|string
 */
function get_position_no_direction($position){
    return substr($position, 0,2);
}

/**
 * @param $position
 * @return bool|string
 */
function get_direction($position){
    return substr($position, 2,1);
}

/**
 * Remembers callback data for a new inline keyboard.
 */
function set_new_callback_keyboard($telegram_update_result) {
    global $memory;

    if($telegram_update_result['message_id']) {
        $message_id = (int)$telegram_update_result['message_id'];
        Logger::debug("Remembering {$message_id} as last callback ID", __FILE__, $chat_id);
        $memory->lastCallbackMessageId = $message_id;
    }
    else {
        Logger::error("telegram_send_message returned unexpected data: {$telegram_update_result}", __FILE__, $chat_id);
    }
}
