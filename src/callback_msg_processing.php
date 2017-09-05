<?php

function callback_msg_processing($callback){
    Logger::debug("telegram update - callback query");

    global $cardinal_position_to_name_map;
    $callback_data = $callback['data'];
    $chat_id = $callback['message']['chat']['id'];

    if(strpos($callback_data, 'card ') === 0) {
        // Set disability
        $card_code = substr($callback_data, 5);
        if(isset($cardinal_position_to_name_map[$card_code])) {
            telegram_send_message($chat_id, "Ok, al momento stai guardando verso {$cardinal_position_to_name_map[$card_code]}!");

            // Get current game state
            $user_status = db_scalar_query("SELECT COUNT(*) FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL");
            Logger::debug("Game lvl: {$user_status}");

            // If user has started a game, check position by removing first step
            if($user_status !== NULL && $user_status !== false)
                $lvl = $user_status;
            else {
                Logger::debug("Can't find user status. Setting user lvl to 1.");
                $lvl = 1;
            }

            // Get user's coordinate
            $current_coordinate = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL ORDER BY reached_on DESC LIMIT 1");
            Logger::debug("Current user's coordinate: {$current_coordinate}");

            // Prepare maze
            $maze_data = generate_maze($lvl, $chat_id, $current_coordinate.$card_code);
            $maze_arrival_position = substr($maze_data[1], 0,2);
            $maze_message = $maze_data[0];
            Logger::debug("maze data[0]: $maze_data[0]");
            Logger::debug("maze data[1]: $maze_data[1]");

            $success = db_perform_action("INSERT INTO moves (telegram_id, cell) VALUES($chat_id, '$maze_arrival_position')");
            Logger::debug("Success of insertion: {$success}");

            // Send maze
            telegram_send_message($chat_id, "Segui queste indicazioni per risolvere il prossimo passo e scansiona il QRCode all'arrivo:\n\n <code>{$maze_message}</code>.", array("parse_mode" => "HTML"));
        }
        else {
            Logger::error("Invalid callback data: {$callback_data}");
            telegram_send_message($chat_id, "Codice non valido. 😑");
        }
    } elseif(strpos($callback_data, 'name ') === 0) {
        $data = substr($callback_data, 5);
        if ($data === "error"){
            // Request name again
            telegram_send_message($chat_id, "Riscrivimi il tuo nome e cognome:\n");
        } else {
            send_pdf($chat_id, $data);
        }
    }
    else {
        // Huh?
        Logger::error("Unknown callback, data: {$callback_data}");
    }
}