<?php
/* General data */
define('BOARD_SIDE_SIZE', 5);
define('BOARD_SIZE', BOARD_SIDE_SIZE * BOARD_SIDE_SIZE);
define("NUMBER_OF_GAMES", 13);

$cardinal_code = array(
    "n",
    "s",
    "e",
    "w"
);

$board_code = generate_board_code();

$cardinal_position_to_name_map = array(
    "n" => "Nord",
    "s" => "Sud",
    "e" => "Est",
    "w" => "Ovest"
);

function get_user_cardinal_position($telegram_id) {
    global $cardinal_code;

    // TODO
    $card = db_scalar_query("SELECT `cardinal_position` FROM `status` WHERE `telegram_id` = {$telegram_id} LIMIT 1");

    if($card)
        return $card;
    else
        return $cardinal_code[0];
}

function get_user_maze_position($telegram_id) {
    global $board_code;

    // TODO
    $position = db_scalar_query("SELECT `maze_position` FROM `status` WHERE `telegram_id` = {$telegram_id} LIMIT 1");

    if($position)
        return $position;
    else
        return $board_code[0];
}

function generate_board_code()
{
    $row_size = sqrt(BOARD_SIZE);
    $alphabet = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j');
    $board = array();

    for ($row = 0; $row < $row_size; $row++) {
        $row_name = $alphabet[$row];
        for ($column = 1; $column <= $row_size; $column++) {
            $board[] = $row_name . $column;
        }
    }

    return $board;
}


