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
require_once(dirname(__FILE__) . '/lib.php');
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
    $board_pos = substr($message, mb_strlen('/start '));
    Logger::debug("Start command payload: '{$board_pos}'", __FILE__, $chat_id);

    if (!$board_pos || mb_strlen($board_pos) != 2) {
        // Start command with wrong coordinates
        telegram_send_message(
            $chat_id,
            __("Hmmm, the command you sent is not valid. Try scanning the CodyMaze QR Code again."),
            array("parse_mode" => "HTML")
        );
        return;
    }

    // Get user's position from db if available
    $user_status = db_scalar_query("SELECT `telegram_id` FROM `moves` WHERE `telegram_id` = {$chat_id} LIMIT 1");
    Logger::debug("Telegram user in DB: {$user_status}");

    // If user exists but hasn't begun the first step, send first step command
    if(!$user_status) {
        start_command_first_step($chat_id, $board_pos);
        return;
    }

    // Otherwise, the game is in progress
    start_command_continue_conversation($chat_id, $board_pos);
}

function start_command_new_conversation($chat_id) {
    Logger::debug("Start new conversation");

    $move_count = db_scalar_query("SELECT count(*) FROM `moves` WHERE `telegram_id` = {$chat_id}");

    $txt = __("Hello, I am the <b>CodyMaze</b> bot!") . ' 🤖';
    if($move_count === 0) {
        $txt .= "\n\n";
        $txt .= __("Please go to any of the grid’s outer squares and scan the QR Code you find there.");
    }

    // Send message to user
    telegram_send_message($chat_id, $txt, array("parse_mode" => "HTML"));
}

/**
 * Handles location received as first step on board.
 * @param $chat_id Telegram ID.
 * @param $board_pos Two-letter coordinate on the board.
 */
function start_command_first_step($chat_id, $board_pos) {
    Logger::debug("Attempting first step on board at position {$board_pos}", __FILE__);
    $cardinal_pos = coordinate_find_initial_direction($board_pos);

    if($cardinal_pos == null) {
        Logger::error("Wrong initial position {$board_pos}", __FILE__, $chat_id);

        telegram_send_message(
            $chat_id,
            __("Whoops! You should start from any of the grid’s outer squares.")
        );
    }
    else {
        $full_pos = $board_pos.$cardinal_pos;
        Logger::debug("Valid initial coordinates: {$full_pos}", __FILE__, $chat_id);

        $success = db_perform_action("INSERT INTO `moves` (`telegram_id`, `reached_on`, `cell`) VALUES($chat_id, NULL, '$full_pos')");
        Logger::debug("Database insertion of initial position: {$success}", __FILE__, $chat_id);

        telegram_send_message($chat_id,
            sprintf(__("Very well, you’re at the starting position in <code>%s</code>! Now please turn in order to face <i>%s</i>."), $board_pos, cardinal_direction_to_description($cardinal_pos)),
            array("parse_mode" => "HTML")
        );

        request_cardinal_position($chat_id, CARD_NOT_ANSWERING_QUIZ);
    }
}

function start_command_continue_conversation($chat_id, $user_position_id = null) {
    Logger::debug("Resuming old conversation");

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

            telegram_send_message($chat_id,
                sprintf(__("Did you get lost?\n\nPlease reach square <code>%s</code> and face <i>%s</i> to continue!"), $target_pos, cardinal_direction_to_description($target_dir)),
                array("parse_mode" => "HTML")
            );
        }

        return;
    }

    // User has a tuple with null timestamp: he/she's solving a maze
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

            Logger::debug("Success of remove query: {$success}");

            $previous_position = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} ORDER BY reached_on DESC LIMIT 1");
            $previous_position_no_direction = get_position_no_direction($previous_position);
            $previous_position_direction = get_direction($previous_position);

            telegram_send_message(
                $chat_id,
                sprintf(__("Whoops, wrong!\n\nGet back to position <code>%s</code>, turn to face <i>%s</i>, and scan the QR Code again."), $previous_position_no_direction, $cardinal_direction_to_description($previous_position_direction)),
                array("parse_mode" => "HTML")
            );
        }
    }
    else {
        // This should never happen because of the cardinal position request
        Logger::warning("Continuing conversation with {$user_game_status} reached steps, ending game", __FILE__, $chat_id);

        end_of_game($chat_id);
    }
}

function end_of_game($chat_id) {
    global $memory;
    $memory->nameRequested = true;

    db_perform_action("UPDATE user_status SET completed = 1 WHERE telegram_id = {$chat_id}");

    telegram_send_message($chat_id, __("Congratulations! You’ve completed <b>CodyMaze</b>!") . ' 👏', array("parse_mode" => "HTML"));
    telegram_send_message($chat_id, __("Write down your full name for the completion certificate:"), array("parse_mode" => "HTML"));
}

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
        $short_guid = substr($guid, 0, 18);

        $result = telegram_send_document($chat_id, $pdf_path, sprintf(__("Completion certificate. Code: %s."), $short_guid));
        if($result !== false) {
            Logger::info("Generated and sent certificate {$guid}", __FILE__, $chat_id);

            // remove temp pdf
            unlink($pdf_path);

            telegram_send_message($chat_id, __("Thanks for playing!"), array("parse_mode" => "HTML"));
        }
    }
    else {
        Logger::error("Failed to generate certificate", __FILE__, $chat_id);
    }
}

function request_cardinal_position($chat_id, $state) {
    set_new_callback_keyboard(telegram_send_message(
        $chat_id,
        __("What direction are you facing?"),
        array("reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => __("North"), "callback_data" => "card n {$state}"),
                ),
                array(
                    array("text" => __("West"), "callback_data" => "card w {$state}"),
                    array("text" => __("East"), "callback_data" => "card e {$state}")
                ),
                array(
                    array("text" => __("South"), "callback_data" => "card s {$state}"),
                )
            )
        ))
    ));
}

function request_name($chat_id, $name) {
    set_new_callback_keyboard(telegram_send_message(
        $chat_id,
        sprintf(__("Your full name is %s. Is this correct?"), $name),
        array("reply_markup" => array(
            "inline_keyboard" => array(
                array(
                    array("text" => __("Yes"), "callback_data" => "name {$name}"),
                ),
                array(
                    array("text" => __("No"), "callback_data" => "name error"),
                )
            )
        ))
    ));
}

function reset_game($chat_id) {
    db_perform_action("DELETE FROM `moves` WHERE `telegram_id` = {$chat_id}");
    db_perform_action("UPDATE `user_status` SET `completed` = 0, `completed_on` = NULL, `name` = NULL, `certificate_id` = NULL, `certificate_sent` = 0, `last_memory_update` = NULL, `memory` = '' WHERE `telegram_id` = $chat_id");
}

function get_position_no_direction($position){
    return substr($position, 0,2);
}

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
        Logger::debug("Remembering {$message_id} as last callback ID", __FILE__);
        $memory->lastCallbackMessageId = $message_id;
    }
    else {
        Logger::error("telegram_send_message returned unexpected data: {$telegram_update_result}", __FILE__);
    }
}
