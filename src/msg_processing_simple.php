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
require_once('maze_generator.php');
require_once('maze_commands.php');

// This file assumes to be included by pull.php or
// hook.php right after receiving a new Telegram update.
// It also assumes that the update data is stored
// inside a $update variable.

// Input: $update
if(isset($update['message'])) {
    // Standard message
    Logger::debug("message");

    $message = $update['message'];
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];
    $from_id = $message['from']['id'];

    if (isset($message['text'])) {
        // We got an incoming text message
        $text = $message['text'];

        if (strpos($text, "/start") === 0) {
            Logger::debug("/start command");

            perform_command_start($chat_id, mb_strtolower($text));
            return;
        }
        else {
            telegram_send_message($chat_id, "Non ho capito.");
        }
    }
    else {
        telegram_send_message($chat_id, "Uhm… non capisco questo tipo di messaggi! 😑\nPer riprovare invia /start.");
    }
} else if(isset($update['callback_query'])) {
    // Callback query
    Logger::debug("Callback query");

    $callback_data = $update['callback_query']['data'];
    $chat_id = $update['callback_query']['message']['chat']['id'];

    if(strpos($callback_data, 'card ') === 0) {
        // Set disability
        $card_code = substr($callback_data, 5);
        if(isset($cardinal_position_to_name_map[$card_code])) {
            // TODO: set correct text
            telegram_send_message($chat_id, "Ok, al momento stai guardando verso {$cardinal_position_to_name_map[$card_code]}!");

            // Get current game state
            $user_status = db_scalar_query("SELECT COUNT(*) FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL");
            Logger::debug("Game lvl: {$user_status}");

            // If user has started a game, check position by removing first step
            if($user_status !== NULL && $user_status !== false)
                $lvl = $user_status - 1;
            else {
                Logger::debug("Can't find user status. Setting user lvl to 1.");
                $lvl = 1;
            }

            // Get user's coordinate
            $current_coordinate = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL ORDER BY reached_on DESC LIMIT 1");
            Logger::debug("Current user's coordinate: {$current_coordinate}");

            // Prepare maze
            $maze_data = generate_maze($user_game_status, $chat_id, $current_coordinate);
            $maze_arrival_position = $maze_data[1];
            $maze_message = $maze_data[0];

            $success = db_perform_action("INSERT INTO moves (telegram_id, cell) VALUES($chat_id, '$maze_arrival_position')");
            Logger::debug("Success of insertion: {$success}");

            // Send maze
            // TODO: set correct text
            telegram_send_message($chat_id, "Segui queste indicazioni per risolvere il prossimo passo e scansiona il QRCode all'arrivo:\n\n {$maze_message}.");
        }
        else {
            Logger::error("Invalid callback data: {$callback_data}");
            telegram_send_message($chat_id, "Codice non valido. 😑");
        }
    }
    else {
        // Huh?
        Logger::error("Unknown callback, data: {$callback_data}");
    }
}

function perform_command_start($chat_id, $message)
{
    Logger::debug("Start command");

    // Get user's initial status from db
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

    // Board position from qr mapping
    $board_pos = substr($message, 7);
    Logger::debug("QRCode --> board position: {$board_pos}");

    // Add user's new position to db if new position and if maze isn't being solved
    if (strcmp($last_position, $board_pos) !== 0 && ($has_null_timestamp === null || $has_null_timestamp === false)) {
        $success = db_perform_action("INSERT INTO moves (telegram_id, reached_on, cell) VALUES($chat_id, '$ts', '$board_pos')");
        Logger::debug("Success of insertion query: {$success}");
    }

    // if new user, start new conversation - else restore game status for user
    if ($user_status === null || $user_status === false) {
        if ($board_pos != "" && $board_pos != null)
            start_command_first_step($chat_id, $board_pos);
        else
            start_command_new_conversation($chat_id);
    } else {
        if ($board_pos !== "" || $board_pos !== null)
            start_command_continue_conversation($chat_id, $board_pos);
        else
            Logger::error("A /start command has been sent without position info");
    }
}

function start_command_new_conversation($chat_id){
    Logger::debug("Start new conversation");

    // TODO: set proper message
    telegram_send_message($chat_id, "Ciao, sono il bot CodyMaze! 🤖\n\n Posizionati lungo il bordo della scacchiera e scansiona un QRCode!\n");
}

function start_command_first_step($chat_id, $board_pos){
    Logger::debug("Start first step");
    // TODO: ask user to look in certain direction
    $cardinal_pos = "[caridinal_pos]";
    $row_column_pos = substr($board_pos, 0, 2);
    // TODO: set text
    telegram_send_message($chat_id, "Benissimo, hai trovato il blocco di partenza in {$row_column_pos}! Ora dovresti posizionarti in modo da guardare verso {$cardinal_pos} se non lo stai già facendo.\n\n");
    start_command_continue_conversation($chat_id, $board_pos);
}

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
    Logger::debug("Cell with null timestamp: {$has_null_timestamp}");

    if($user_game_status < NUMBER_OF_GAMES) {
        if ($has_null_timestamp !== null || $has_null_timestamp !== false) {
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
                    // TODO: set text
                    telegram_send_message($chat_id, "Complimenti, hai trovato il punto di arrivo!\n\n Ora puoi passare al prossimo step.\n");
                    request_cardinal_position($chat_id);
                }
            } else {
                // Wrong answer - remove end of maze position tuple and send back to last position for new maze
                $success = db_perform_action("DELETE FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL");
                Logger::debug("Success of remove query: {$success}");

                $beginning_position = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL ORDER BY reached_on DESC LIMIT 1");
                // TODO: set correct text
                telegram_send_message($chat_id, "Ops! Hai sbagliato!\n\n Ritorna alla posizione {$beginning_position} e prova un nuovo labirinto.\n");
            }
        } else {
            // Request cardinal position
            request_cardinal_position($chat_id);
        }
    } else {
        end_of_game($chat_id);
    }
}

function end_of_game($chat_id){
    telegram_send_message($chat_id, "Complimenti! Hai completato il CodyMaze!.\n\n");
}

function request_cardinal_position($chat_id){
    // TODO: set proper text
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

function request_start($chat_id) {
    telegram_send_message($chat_id, "Per iniziare a registrare, clicca sul pulsante qui sotto.",
        array("reply_markup" => array(
            "keyboard" => array(
                array(
                    array("text" => "Inizia il percorso!", "request_location" => true)
                )
            ),
            "resize_keyboard" => true,
            "one_time_keyboard" => true
        ))
    );
}

function clamp($min, $max, $value) {
    if($value < $min)
        return $min;
    else if($value > $max)
        return $max;
    else
        return $value;
}