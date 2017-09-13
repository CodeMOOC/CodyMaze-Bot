<?php

function callback_msg_processing($callback){
    Logger::debug("telegram update - callback query");

    $callback_data = $callback['data'];
    $chat_id = $callback['message']['chat']['id'];

    if(strpos($callback_data, 'card ') === 0) {
        cardinal_message_processing($chat_id, $callback_data);
    } elseif(strpos($callback_data, 'name ') === 0) {
        name_message_processing($chat_id, $callback_data);
    }
    else {
        // Huh?
        Logger::error("Unknown callback, data: {$callback_data}");
    }
}

function cardinal_message_processing($chat_id, $callback_data){
    // Get cardinal position
    global $cardinal_position_to_name_map;
    $card_code = substr($callback_data, 5, 1);
    $cardinal_info = substr($callback_data, 7,1);

    Logger::debug("position data: {$card_code}");
    Logger::debug("cardinal info: {$cardinal_info}");

    if(isset($cardinal_position_to_name_map[$card_code])) {
        telegram_send_message($chat_id, "Ok, al momento stai guardando verso {$cardinal_position_to_name_map[$card_code]}!");

        // Get current game state
        $user_status = db_scalar_query("SELECT COUNT(*) FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL");
        $user_null_status = db_scalar_query("SELECT COUNT(*) FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL");

        // If user has started a game, get position, else set to step 1
        if ($user_status !== NULL && $user_status !== false) {
            if ($user_null_status != NULL && $user_null_status !== false) {
                $lvl = $user_status + 1;
            } else {
                $lvl = $user_status;
            }
        } else {
            Logger::debug("Can't find user status. Setting user lvl to 0.");
            $lvl = 1;
        }

        Logger::debug("Game lvl: {$lvl}");

        // Get user's coordinate
        $current_coordinate = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL LIMIT 1");
        if($current_coordinate === null || $current_coordinate === false)
            $current_coordinate = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL ORDER BY reached_on DESC LIMIT 1");

        //$expected_block = get_position_no_direction($current_coordinate);
        $expected_direction = get_direction($current_coordinate);
        Logger::debug("Current user's coordinate: {$current_coordinate}");

        if($expected_direction !== $card_code){
            Logger::debug("user direction [{$card_code}] is different from expected one in {$current_coordinate}");
            if($cardinal_info == CARD_ANSWERING_QUIZ) {
                // User is looking in wrong direction
                // Remove end of maze position tuple and send back to last position for new maze
                $success = db_perform_action("DELETE FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL");
                if($success == 0 || $success == false || $success == null){
                    Logger::error("couldn't remove user's current objective - execution flow might break. Trying query again: DELETE FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL");
                    $success = db_perform_action("DELETE FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NULL");
                    Logger::debug("Success of second remove from moves query: {$success}");
                }

                //$success = db_perform_action("UPDATE moves SET reached_on = NULL WHERE telegram_id = {$chat_id} ORDER BY reached_on DESC LIMIT 1");
                Logger::debug("Success of updating moves table: {$success}");

                $beginning_position = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL ORDER BY reached_on DESC LIMIT 1");
                $last_position_no_direction = get_position_no_direction($beginning_position);
                $last_position_direction = get_direction($beginning_position);
                telegram_send_message($chat_id, "Stai guardando nella direzione sbagliata :( Riposizionati sul blocco <code>{$last_position_no_direction}</code> guardando verso <code>{$cardinal_position_to_name_map[$last_position_direction]}</code> e scansiona nuovamente il QRCode.\n", array("parse_mode" => "HTML"));
            } else {
                // User is looking in wrong direction, but was already sent back to last position so user show stay there
                $beginning_position = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL ORDER BY reached_on DESC LIMIT 1");
                $expected_block = get_position_no_direction($beginning_position);
                $expected_direction = get_direction($beginning_position);
                telegram_send_message($chat_id, "Stai guardando nella direzione sbagliata :( Riposizionati su questo blocco (<code>{$expected_block}</code>) guardando verso <code>{$cardinal_position_to_name_map[$expected_direction]}</code>", array("parse_mode" => "HTML"));
                request_cardinal_position($chat_id, CARD_NOT_ANSWERING_QUIZ);
            }
            return;
        }

        // Update position with arrival timestamp
        $ts = date("Y-m-d H:i:s", time());
        db_perform_action("UPDATE moves SET reached_on = '$ts' WHERE telegram_id = {$chat_id} AND reached_on IS NULL");

        if($cardinal_info == CARD_ENDGAME_POSITION){
            end_of_game($chat_id);
            return;
        }
        if($lvl > 0 && $cardinal_info == CARD_ANSWERING_QUIZ)
            telegram_send_message($chat_id, "Benissimo! Hai trovato il punto di arrivo.\n");

        // Prepare maze
        $new_current_coordinate = db_scalar_query("SELECT cell FROM moves WHERE telegram_id = {$chat_id} AND reached_on IS NOT NULL ORDER BY reached_on DESC LIMIT 1");
        $maze_data = generate_maze($lvl, $chat_id, $new_current_coordinate);
        $maze_arrival_position = $maze_data[1];
        $maze_message = $maze_data[0];
        Logger::debug("maze data[0]: $maze_data[0]");
        Logger::debug("maze data[1]: $maze_data[1]");

        $success = db_perform_action("INSERT INTO moves (telegram_id, cell) VALUES($chat_id, '$maze_arrival_position')");
        Logger::debug("Success of insertion: {$success}");

        // Send maze
        telegram_send_message($chat_id, "{$lvl}. Segui queste indicazioni per risolvere il prossimo passo e scansiona il QRCode all'arrivo:\n\n <code>{$maze_message}</code>.", array("parse_mode" => "HTML"));
    }
    else {
        Logger::error("Invalid callback data: {$callback_data}");
        telegram_send_message($chat_id, "Codice non valido. 😑");
    }
}

function name_message_processing($chat_id, $callback_data){
    $data = substr($callback_data, 5);
    if ($data === "error"){
        // Request name again
        telegram_send_message($chat_id, "Riscrivimi il tuo nome e cognome:\n");
    } else {
        send_pdf($chat_id, $data);
    }
}