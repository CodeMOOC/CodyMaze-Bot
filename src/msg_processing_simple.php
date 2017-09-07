<?php
/*
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing functionality,
 * used by both pull and push scripts.
 */

require_once('data.php');
require_once('callback_msg_processing.php');
require_once('message_msg_processing.php');
require_once('maze_generator.php');
require_once('maze_commands.php');
require_once ('htmltopdf.php');

// This file assumes to be included by pull.php or
// hook.php right after receiving a new Telegram update.
// It also assumes that the update data is stored
// inside a $update variable.

// Input: $update
if(isset($update['message'])) {
    // Standard message
    message_msg_processing($update['message']);

} else if(isset($update['callback_query'])) {
    // Callback query
    callback_msg_processing($update['callback_query']);
}

/**
 * @param $chat_id
 * @param $message
 */
function perform_command_start($chat_id, $message)
{
    Logger::debug("Start command");

    // Check if user is already registered
    $user_exists = db_scalar_query("SELECT telegram_id FROM user_status WHERE telegram_id = {$chat_id} LIMIT 1");

    if($user_exists === false || $user_exists === null){
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

    // Get current time
    $ts = date("Y-m-d H:i:s", time());

    // Add user's new position to db if new position and if maze isn't being solved
    if (strcmp($last_position, $board_pos) !== 0 && ($has_null_timestamp === null || $has_null_timestamp === false)) {
        $success = db_perform_action("INSERT INTO moves (telegram_id, reached_on, cell) VALUES($chat_id, '$ts', '$board_pos')");
        Logger::debug("Success of insertion query: {$success}");
    }

    // If user exists but hasn't begun the first step, send first step command
    if ($user_status === null || $user_status === false) {
        start_command_first_step($chat_id, $board_pos);
        return;
    }

    // If all else fails, then the game is in progress
    start_command_continue_conversation($chat_id, $board_pos);
}

/**
 * @param $chat_id
 */
function start_command_new_conversation($chat_id){
    Logger::debug("Start new conversation");

    // Insert new user into DB
    db_perform_action("INSERT INTO user_status (telegram_id, completed) VALUES ($chat_id, 0)");

    // Send message to user
    telegram_send_message($chat_id, "Ciao, sono il bot CodyMaze! 🤖\n\n Posizionati lungo il bordo della scacchiera e scansiona un QRCode!\n");
}

/**
 * @param $chat_id
 * @param $board_pos
 */
function start_command_first_step($chat_id, $board_pos){
    Logger::debug("Start first step - board position {$board_pos}");
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
        telegram_send_message($chat_id, "Benissimo, hai trovato il blocco di partenza in {$row_column_pos}! Ora dovresti posizionarti in modo da guardare verso {$cardinal_pos} se non lo stai già facendo.\n\n");
        request_cardinal_position($chat_id);
    }
}

/**
 * @param $chat_id
 * @param null $user_position_id
 */
function start_command_continue_conversation($chat_id, $user_position_id = null){
    Logger::debug("Start old conversation");

    // Get current game position of user
    $user_status = db_scalar_query("SELECT COUNT(*) FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL LIMIT 1");
    Logger::debug("User status: {$user_status}");

    // If user has started a game, check position by removing first step
    if($user_status !== NULL && $user_status !== false)
        $user_game_status = $user_status - 1;
    else {
        Logger::debug("Can't find user status. Setting user position to 0.");
        $user_game_status = 0;
    }

    // If user has a tuple with null timestamp he's solving a maze
    // Else he has to start a new maze
    // AND if position is == NUMBER_OF_GAMES, it's the end of the game
    $has_null_timestamp = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL");

    if($user_game_status < NUMBER_OF_GAMES) {
        if ($has_null_timestamp !== null || $has_null_timestamp !== false) {
            Logger::debug("Cell timestamp is not null.");

            $answer = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL LIMIT 1");
            Logger::debug("Expecting answer: {$answer}");

            // Check for correct answer and update db
            if(strcmp(substr($answer, 0,2), $user_position_id) === 0){
                // Correct answer - continue or end game if reached last maze
                $ts = date("Y-m-d H:i:s", time());
                db_perform_action("UPDATE moves SET reached_on = '$ts' WHERE telegram_id = {$chat_id} AND reached_on IS NULL");
                if($user_game_status == (NUMBER_OF_GAMES-1)){
                    end_of_game($chat_id);
                } else {
                    // Continue with next maze
                    telegram_send_message($chat_id, "Ho ricevuto la tua nuova posizione!\n");
                    request_cardinal_position($chat_id);
                }
            } else {
                // Wrong answer - remove end of maze position tuple and send back to last position for new maze
                $success = db_perform_action("DELETE FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL");
                db_perform_action("UPDATE moves SET reached_on = NULL WHERE telegram_id = {$chat_id} ORDER BY reached_on DESC LIMIT 1");

                Logger::debug("Success of remove query: {$success}");

                $beginning_position = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL ORDER BY reached_on DESC LIMIT 1");
                $beginning_position_no_direction = get_position_no_direction($beginning_position);
                telegram_send_message($chat_id, "Ops! Hai sbagliato!\n\n Ritorna alla posizione <code>{$beginning_position_no_direction}<\code> e prova un nuovo labirinto.\n", array("parse_mode" => "HTML"));
            }
        } else {
            // Request cardinal position
            Logger::debug("Cell with null timestamp: {$has_null_timestamp}");
            request_cardinal_position($chat_id);
        }
    } else {
        end_of_game($chat_id);
    }
}

/**
 * @param $chat_id
 */
function end_of_game($chat_id){
    $result = db_perform_action("UPDATE user_status SET completed = 1 WHERE telegram_id = {$chat_id}");
    telegram_send_message($chat_id, "Complimenti! Hai completato il CodyMaze!\n\n");
    telegram_send_message($chat_id, "Scrivimi il nome e cognome da visualizzare sul certificato di completamento:\n");
}

/**
 * @param $chat_id
 * @param $name
 */
function send_pdf($chat_id, $name){
    $result = htmlToPdf($name);
    Logger::debug("RESULT:");
    Logger::debug("pdf_valid: ". $result["pdf_valid"]);
    Logger::debug("pdf_guid: " . $result["pdf_guid"]);
    Logger::debug("pdf_date: " . $result["pdf_date"]);
    Logger::debug("pdf_name: " . $result["pdf_name"]);
    Logger::debug("pdf_file: " . $result["pdf_file"]);

    if($result["pdf_valid"]== true){

        $guid = $result["pdf_guid"];
        $date = $result["pdf_date"];
        $pdf_path = "certificates/".$result["pdf_file"];
        // update user_status
        $result = db_perform_action("UPDATE user_status SET completed_on = '$date', name = '$name', certificate_id = '$guid' WHERE telegram_id = {$chat_id}");
        // update certificates_list
        $result = db_perform_action("INSERT INTO certificates_list (certificate_id, telegram_id, name, date) VALUES ('$guid', $chat_id, '$name', '$date')");

        $result = telegram_send_document($chat_id, $pdf_path, "Certificato di Completamento");
        if($result !== false){
            // remove temp pdf
            //unlink($result["pdf_file"]);
            // Reset game
            //reset_game($chat_id);
            //telegram_send_message($chat_id, "Ora che hai completato il gioco e ricevuto il certificato puoi iniziare una nuova sfida.\n Scrivi /start per ricomincare o scansiona un QRCode del CodyMaze!");
        }

    }
}

/**
 * @param $chat_id
 */
function request_cardinal_position($chat_id){
    telegram_send_message($chat_id, "In che direzione stai guardando?",
        array("reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => "Nord", "callback_data" => "card n"),
                ),
                array(
                    array("text" => "Ovest", "callback_data" => "card w"),
                    array("text" => "Est", "callback_data" => "card e")
                ),
                array(
                    array("text" => "Sud", "callback_data" => "card s"),
                )
            )
        ))
    );
}

/**
 * @param $chat_id
 * @param $name
 */
function request_name($chat_id, $name){
    telegram_send_message($chat_id, "Confermi che il nome inviato è {$name}?",
        array("reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => "Si", "callback_data" => "name {$name}"),
                ),
                array(
                    array("text" => "No", "callback_data" => "name error"),
                )
            )
        ))
    );
}

/**
 * @param $chat_id
 */
function reset_game($chat_id){
    db_perform_action("DELETE FROM moves WHERE telegram_id = $chat_id");
    db_perform_action("DELETE FROM user_status WHERE telegram_id = $chat_id");
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