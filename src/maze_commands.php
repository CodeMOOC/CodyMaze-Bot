<?php
require_once('lib_log.php');
require_once('maze_utility.php');

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
        'a',
        $target
    );
}

/* Turn right or left (if ahead is not out) */
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
            'sa',
            coordinate_advance(coordinate_turn_left($current_coordinate))
        );
    }
    else if(!coordinate_out_ahead(coordinate_turn_right($current_coordinate), 3)) {
        return array(
            'da',
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
        '2{a}',
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
        $possible_directions []= 'd';
        $possible_advancements []=  coordinate_max_ahead($c_turned_right);
    }

    $c_turned_left = coordinate_turn_left($current_coordinate);
    if(!coordinate_out_ahead($c_turned_left)){
        $possible_directions_coords []= $c_turned_left;
        $possible_directions []= 's';
        $possible_advancements []=  coordinate_max_ahead($c_turned_left);
    }

    if(count($possible_directions) < 1) {
        Logger::fatal('Cannot execute command_6 from position (no valid option)');
    }

    $direction_index = array_rand($possible_directions);

    $new_coordinates = $possible_directions_coords[$direction_index];
    for($i=0; $i < $possible_advancements[$direction_index]; $i++){
        $new_coordinates = coordinate_advance($new_coordinates);
    }

    return array(
        $possible_directions[$direction_index]." ".$possible_advancements[$direction_index]."{a}",
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
        "{$count}{{$turn_command}a}",
        $final_coordinates
    );
}

function command_7($telegram_id, $current_coordinate) {
    if(coordinate_out_left($current_coordinate)) {
        // Pick right side
        return command_7_internal($current_coordinate, 'd', function($coord) {
            return coordinate_turn_right($coord);
        });
    }
    else {
        // Pick left side
        return command_7_internal($current_coordinate, 's', function($coord) {
            return coordinate_turn_left($coord);
        });
    }


    $command = '';
    $final_coordinates = $current_coordinate;
    while(true) {
        $command = '';
        $final_coordinates = $current_coordinate;
        $turns = rand(2, 3);
        for($i = 0; $i < $turns; $i++) {
            if(rand(1, 2) == 1) {
                $command .= 's';
                $final_coordinates = coordinate_turn_left($final_coordinates);
            }
            else {
                $command .= 'd';
                $final_coordinates = coordinate_turn_right($final_coordinates);
            }
        }

        $command .= 'a';
        $final_coordinates = coordinate_advance($final_coordinates);
        if($final_coordinates != null) {
            Logger::info("Command 7 generated {$current_coordinate} + '{$command}' = {$final_coordinates}");

            return array(
                $command,
                $final_coordinates
            );
        }

        // Unreachable location generated
        Logger::debug("Command 7 rejected {$current_coordinate} + '{$command}'");
    }
}

function command_8($telegram_id, $current_coordinate) {

    $actual_color = coordinate_is_black($current_coordinate)? 'nero':'bianco';

    $right_instructions = "sa";
    $next_coordinate = coordinate_advance(coordinate_turn_left($current_coordinate));

    if(!coordinate_out_right($current_coordinate)){
        $right_instructions = "da";
        $next_coordinate = coordinate_advance(coordinate_turn_right($current_coordinate));
    }

    $str_instructions = sprintf("se[%s]{%s}", $actual_color, $right_instructions);

    return array(
        $str_instructions,
        $next_coordinate
    );
}

function command_9($telegram_id, $current_coordinate) {


    $instructions = array(
        "sa",
        "da"
    );

    $next_coords = array(
        coordinate_advance(coordinate_turn_left($current_coordinate)),
        coordinate_advance(coordinate_turn_right($current_coordinate))
    );


    if(!coordinate_out_right($current_coordinate)){
        $instructions = array_reverse($instructions );
        $next_coords = array_reverse($next_coords );
    }

    if(!coordinate_is_black($current_coordinate)){
        $instructions = array_reverse($instructions);
        $next_coords = array_reverse($next_coords);
    }

    $str_instructions = sprintf("se[nero]{%s}altrimenti{%s}", $instructions[0], $instructions[1]);

    return array(
        $str_instructions,
        coordinate_is_black($current_coordinate)? $next_coords[0]: $next_coords[1]
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
        "{$count}{se(strada davanti){a}se(strada a dx){d}altrimenti{s}}",
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
        "{$count}{se(strada davanti){a}se(strada a sx){s}altrimenti{d}}",
        $final_coordinates
    );
}

function command_12($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}

function command_13($telegram_id, $current_coordinate) {
    command_nil($telegram_id, $current_coordinate);
}
