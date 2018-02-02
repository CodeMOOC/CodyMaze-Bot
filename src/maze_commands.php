<?php
require_once(dirname(__FILE__) . '/lib_log.php');
require_once(dirname(__FILE__) . '/lib_localization.php');
require_once(dirname(__FILE__) . '/maze_utility.php');

/*
 * Nil command, does not execute any steps and returns the current coordinate.
 */
function command_nil($telegram_id, $current_coordinate) {
    return array('', $current_coordinate);
}

function command_safe($telegram_id, $current_coordinate) {
    // TODO
    return command_nil($telegram_id, $current_coordinate);
}

function command_1($telegram_id, $current_coordinate) {
    $target = coordinate_advance($current_coordinate);
    if($target == null) {
        Logger::fatal('Cannot execute command_1 from position (null target)');
    }

    return array(
        __("f"),
        $target
    );
}

/* Turn right or left (if ahead is not out) */
function command_2($telegram_id, $current_coordinate) {
    $possible_directions = array();
    $possible_directions_coords = array();

    $c_turned_right = coordinate_turn_right($current_coordinate);
    if(!coordinate_out_ahead($c_turned_right)){
        $possible_directions_coords[] = $c_turned_right;
        $possible_directions[] = __("r");
    }

    $c_turned_left = coordinate_turn_left($current_coordinate);
    if(!coordinate_out_ahead($c_turned_left)){
        $possible_directions_coords[] = $c_turned_left;
        $possible_directions[] = __("l");
    }

    if(count($possible_directions) < 1) {
        Logger::fatal('Cannot execute command_2 from position (no valid option)');
    }

    $direction_index = array_rand($possible_directions);

    return array(
        $possible_directions[$direction_index],
        $possible_directions_coords[$direction_index]
    );
}

function command_3($telegram_id, $current_coordinate) {
    return command_1($telegram_id, $current_coordinate);
}

function command_4($telegram_id, $current_coordinate) {
    if(!coordinate_out_ahead(coordinate_turn_left($current_coordinate), 3)) {
        return array(
            __("l") . __("f"),
            coordinate_advance(coordinate_turn_left($current_coordinate))
        );
    }
    else if(!coordinate_out_ahead(coordinate_turn_right($current_coordinate), 3)) {
        return array(
            __("r") . __("f"),
            coordinate_advance(coordinate_turn_right($current_coordinate))
        );
    }
    else {
        return command_safe($telegram_id, $current_coordinate);
    }
}

function command_5($telegram_id, $current_coordinate) {
    if(coordinate_out_ahead($current_coordinate, 2)){
        Logger::fatal('Cannot execute command_5 from position (no valid path)');
    }

    return array(
        '2{' . __("f") . '}',
        coordinate_advance(coordinate_advance($current_coordinate))
    );
}

function command_6($telegram_id, $current_coordinate) {
    $possible_directions = array();
    $possible_directions_coords = array();
    $possible_advancements = array();

    $c_turned_right = coordinate_turn_right($current_coordinate);
    if(!coordinate_out_ahead($c_turned_right)){
        $possible_directions_coords []= $c_turned_right;
        $possible_directions[] = __("r");
        $possible_advancements[] = coordinate_max_ahead($c_turned_right);
    }

    $c_turned_left = coordinate_turn_left($current_coordinate);
    if(!coordinate_out_ahead($c_turned_left)){
        $possible_directions_coords []= $c_turned_left;
        $possible_directions[] = __("l");
        $possible_advancements[] = coordinate_max_ahead($c_turned_left);
    }

    if(count($possible_directions) < 1) {
        Logger::fatal('Cannot execute command_6 from position (no valid option)');
    }

    $direction_index = array_rand($possible_directions);

    $new_coordinates = $possible_directions_coords[$direction_index];
    for($i = 0; $i < $possible_advancements[$direction_index]; $i++){
        $new_coordinates = coordinate_advance($new_coordinates);
    }

    return array(
        $possible_directions[$direction_index] . $possible_advancements[$direction_index] . '{' . __("f") . '}',
        $new_coordinates
    );
}

function command_7_internal($current_coordinate, $turn_command, $turn_callable) {
    $final_coordinates = $current_coordinate;
    $count = 0;

    for($count = 0; $count < 3; ++$count) {
        $tentative_target = coordinate_advance(call_user_func($turn_callable, $final_coordinates));
        if($tentative_target == null) {
            // Would go off-board
            break;
        }

        $final_coordinates = $tentative_target;
    }

    if($count == 0) {
        Logger::error('Whops, 0 repeats');
    }

    return array(
        "{$count}{{$turn_command}" . __("f") . '}',
        $final_coordinates
    );
}

function command_7($telegram_id, $current_coordinate) {
    if(coordinate_out_left($current_coordinate)) {
        // Pick right side
        return command_7_internal($current_coordinate, __("r"), function($coord) {
            return coordinate_turn_right($coord);
        });
    }
    else {
        // Pick left side
        return command_7_internal($current_coordinate, __("l"), function($coord) {
            return coordinate_turn_left($coord);
        });
    }
}

function command_8($telegram_id, $current_coordinate) {
    $actual_color = coordinate_is_black($current_coordinate) ? __("star") : __("no star");

    $right_instructions = __("l") . __("f");
    $next_coordinate = coordinate_advance(coordinate_turn_left($current_coordinate));

    if(!coordinate_out_right($current_coordinate)){
        $right_instructions = __("r") . __("f");
        $next_coordinate = coordinate_advance(coordinate_turn_right($current_coordinate));
    }

    $str_instructions = sprintf("%s(%s){%s}", __("if"), $actual_color, $right_instructions);

    return array(
        $str_instructions,
        $next_coordinate
    );
}

function command_9($telegram_id, $current_coordinate) {
    $instructions = array(
        __("l") . __("f"),
        __("r") . __("f")
    );

    $next_coords = array(
        coordinate_advance(coordinate_turn_left($current_coordinate)),
        coordinate_advance(coordinate_turn_right($current_coordinate))
    );


    if(!coordinate_out_right($current_coordinate)){
        $instructions = array_reverse($instructions);
        $next_coords = array_reverse($next_coords);
    }

    if(!coordinate_is_black($current_coordinate)){
        $instructions = array_reverse($instructions);
        $next_coords = array_reverse($next_coords);
    }

    $str_instructions = sprintf("%s(%s){%s}%s{%s}", __("if"), __("star"), $instructions[0], __("else"), $instructions[1]);

    return array(
        $str_instructions,
        coordinate_is_black($current_coordinate) ? $next_coords[0] : $next_coords[1]
    );
}

function command_10($telegram_id, $current_coordinate, $count = null) {
    // Optional determinism for testability (🎓)
    if($count == null) {
        $count = rand(3, 5);
    }

    $final_coordinates = $current_coordinate;
    for($i = 0; $i < $count; $i++) {
        $final_coordinates = coordinate_standard_crawler($final_coordinates, true);
    }

    return array(
        sprintf("%d{%s(%s){%s}%s{%s(%s){%s}%s{%s}}}", $count, __("if"), __("path ahead"), __("f"), __("else"), __("if"), __("path right"), __("r"), __("else"), __("l")),
        $final_coordinates
    );
}

function command_11($telegram_id, $current_coordinate, $count = null) {
    // Optional determinism for testability (🎓)
    if($count == null) {
        $count = rand(3, 5);
    }

    $final_coordinates = $current_coordinate;
    for($i = 0; $i < $count; $i++) {
        $final_coordinates = coordinate_standard_crawler($final_coordinates, false);
    }

    return array(
        sprintf("%d{%s(%s){%s}%s{%s(%s){%s}%s{%s}}}", $count, __("if"), __("path ahead"), __("f"), __("else"), __("if"), __("path left"), __("l"), __("else"), __("r")),
        $final_coordinates
    );
}

function command_12($telegram_id, $current_coordinate) {
    return array(
        sprintf("%s(%s){%s}", __("while"), __("path ahead"), __("f")),
        coordinate_move_to_end($current_coordinate)
    );
}

function command_13($telegram_id, $current_coordinate) {
    $final_coordinate = $current_coordinate;
    while(!coordinate_is_black($final_coordinate)) {
        $final_coordinate = coordinate_standard_crawler($final_coordinate, true);
    }

    return array(
        sprintf("%s(%s){%s(%s){%s}%s{%s(%s){%s}%s{%s}}}", __("while"), __("no star"), __("if"), __("path ahead"), __("f"), __("else"), __("if"), __("path right"), __("r"), __("else"), __("l")),
        $final_coordinate
    );
}
