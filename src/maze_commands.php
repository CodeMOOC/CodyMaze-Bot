<?php
require_once('maze_utility.php');

/*
 * Nil command, does not execute any steps and returns the current coordinate.
 */
function command_nil($telegram_id, $current_coordinate) {
    return array('', $current_coordinate);
}

function command_1($telegram_id, $current_coordinate) {
    $target = coordinate_advance($current_coordinate);
    if($target == null) {
        Log:fatal('Cannot execute command_1 from position (null target)');
    }

    return array(
        'a',
        $target
    );
}

/* Turn right or left (if ahead is not empty) */
function command_2($telegram_id, $current_coordinate) {

    $possible_directions = array();
    $possible_directions_coords = array();

    $c_turned_right = coordinate_turn_right($current_coordinate);
    if(!coordinate_out_ahead($c_turned_right)){
        $possible_directions_coords[]= $c_turned_right;
        $possible_directions[]= 'd';
    }

    $c_turned_left = coordinate_turn_left($current_coordinate);
    if(!coordinate_out_ahead($c_turned_left)){
        $possible_directions_coords[]= $c_turned_left;
        $possible_directions[]= 's';
    }

    if(count($possible_directions) < 1) {
        Log:fatal('Cannot execute command_2 from position (no valid option)');
    }

    $direction_index = array_rand($possible_directions);

    return array(
        $possible_directions[$direction_index],
        $possible_directions_coords[$direction_index]
    );
}

function command_3($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}

function command_4($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}

function command_5($telegram_id, $current_coordinate) {
    if(coordinate_out_ahead($current_coordinate, 2)){
        Log:fatal('Cannot execute command_5 from position (no valid path)');
    }

    return array(
        '2{a}',
        coordinate_advance(coordinate_advance($current_coordinate))
    );
}

function command_6($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}

function command_7($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}

function command_8($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}

function command_9($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}

function command_10($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}

function command_11($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}

function command_12($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}

function command_13($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}
