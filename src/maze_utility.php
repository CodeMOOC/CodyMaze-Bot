<?php
/* UTILITY FUNCTIONS FOR QUERYING THE MAZE STRUCTURE */

require_once(dirname(__FILE__) . '/data.php');

const DEFAULT_MAZE = array(
    array(true , true , false, false, false),
    array(false, false, false, false, true ),
    array(false, false, false, false, false),
    array(true , false, false, false, false),
    array(false, false, true , false, false)
);

const DIRECTIONS = 'nesw';

/*
 * Creates a coordinate from indexes and a direction (single-char string).
 */
function coordinate_create($column, $row, $direction) {
    if($column < 0 || $column >= BOARD_SIDE_SIZE) {
        die('Column index out of range');
    }
    if($row < 0 || $row >= BOARD_SIDE_SIZE) {
        die('Row index out of range');
    }
    if(!is_string($direction) || strlen($direction) != 1) {
        die('Direction is not a single character string');
    }

    $ret  = chr(ord('a') + $column);
    $ret .= ($row + 1);
    $ret .= $direction;

    return $ret;
}

function coordinate_to_column($coordinate) {
    if(strlen($coordinate) != 3) {
        die('Coordinate with ' . strlen($coordinate) . ' values');
    }

    $asciiCode = ord(substr($coordinate, 0, 1));
    return $asciiCode - ord('a');
}

function coordinate_to_row($coordinate) {
    if(strlen($coordinate) != 3) {
        die('Coordinate with ' . strlen($coordinate) . ' values');
    }

    return ((int)substr($coordinate, 1, 1)) - 1;
}

function coordinate_to_direction($coordinate) {
    if(strlen($coordinate) != 3) {
        die('Coordinate with ' . strlen($coordinate) . ' values');
    }

    return substr($coordinate, 2, 1);
}

function coordinate_advance($coordinate) {
    $c = coordinate_to_column($coordinate);
    $r = coordinate_to_row($coordinate);
    $dir = coordinate_to_direction($coordinate);

    switch($dir) {
        case 'n':
            if($r <= 0)
                return null;
            else
                return coordinate_create($c, $r-1, $dir);
            break;

        case 'e':
            if($c >= BOARD_SIDE_SIZE - 1)
                return null;
            else
                return coordinate_create($c+1, $r, $dir);
            break;

        case 's':
            if($r >= BOARD_SIDE_SIZE - 1)
                return null;
            else
                return coordinate_create($c, $r+1, $dir);
            break;

        case 'w':
            if($c <= 0)
                return null;
            else
                return coordinate_create($c-1, $r, $dir);
            break;
    }

    die('Invalid direction');
}

function coordinate_turn_left($coordinate) {
    $row = coordinate_to_row($coordinate);
    $column = coordinate_to_column($coordinate);
    $direction = coordinate_to_direction($coordinate);

    $direction_index = strpos(DIRECTIONS, $direction);

    $new_direction = substr(DIRECTIONS, ($direction_index == 0? 3: $direction_index - 1),1);

    return coordinate_create($column, $row, $new_direction);

}

function coordinate_turn_right($coordinate) {
    $row = coordinate_to_row($coordinate);
    $column = coordinate_to_column($coordinate);
    $direction = coordinate_to_direction($coordinate);

    $direction_index = strpos(DIRECTIONS, $direction);

    $new_direction = substr(DIRECTIONS, ($direction_index == 3? 0: $direction_index + 1),1);

    return coordinate_create($column, $row, $new_direction);
}

function coordinate_turn_180($coordinate) {
    $row = coordinate_to_row($coordinate);
    $column = coordinate_to_column($coordinate);
    $direction = coordinate_to_direction($coordinate);

    $direction_index = strpos(DIRECTIONS, $direction);

    $new_direction = substr(DIRECTIONS, ($direction_index + 2) % strlen(DIRECTIONS), 1);

    return coordinate_create($column, $row, $new_direction);
}

function coordinate_is_black($coordinate) {

    $row = coordinate_to_row($coordinate);
    $column = coordinate_to_column($coordinate);

    return DEFAULT_MAZE[$row][$column];

}

/*
 * Checks if executing an advance command the player would end up
 * out of the board
 */
function coordinate_out_ahead($coordinate, $num = 1) {
    $new_coordinates = $coordinate;

    for($i = 0; $i < $num; $i++) {
        $new_coordinates = coordinate_advance($new_coordinates);
        if(is_null($new_coordinates)) {
            return true;
        }
    }

    return false;
}


/* Checks whether the square left is over the maze board */
function coordinate_out_left($coordinate) {
    return coordinate_out_ahead(coordinate_turn_left($coordinate), 1);
}

/* Checks whether the square right is over the maze board */
function coordinate_out_right($coordinate) {
    return coordinate_out_ahead(coordinate_turn_right($coordinate), 1);
}

/*
 * Executes one step of the standard maze crawler algorithm,
 * i.e., if empty ahead, advance,
 *       if empty on preferred side, turn to preferred side,
 *       else, turn on other side
 * Guaranteed to work from all coordinates ✌
 */
function coordinate_standard_crawler($coordinate, $prefer_right = true) {
    if(!coordinate_out_ahead($coordinate, 1)) {
        return coordinate_advance($coordinate);
    }
    if($prefer_right) {
        if(!coordinate_out_right($coordinate)) {
            return coordinate_turn_right($coordinate);
        }
        else {
            return coordinate_turn_left($coordinate);
        }
    }
    else {
        if(!coordinate_out_left($coordinate)) {
            return coordinate_turn_left($coordinate);
        }
        else {
            return coordinate_turn_right($coordinate);
        }
    }
}

function coordinate_find_initial_direction($position) {
    $column = substr($position, 0, 1);
    $row = substr($position, 1, 1);
    Logger::debug("Initial position: {$column}{$row}", __FILE__);

    if($column == 'a')
        return 'e';
    elseif($column == 'e')
        return 'w';
    else {
        if($row == '1')
            return 's';
        elseif($row == '5')
            return 'n';
        else {
            Logger::error("User in initial position {$column}{$row} is not on maze border", __FILE__);
            return null;
        }
    }
}

/*
 * Moves ahead as long as possible.
 */
function coordinate_move_to_end($coordinate) {
    while(!coordinate_out_ahead($coordinate, 1)) {
        $coordinate = coordinate_advance($coordinate);
    }
    return $coordinate;
}

function coordinate_max_ahead($coordinate) {
    $i = 0;
    do {
        $i++;
    }
    while(!coordinate_out_ahead($coordinate, $i));

    return $i-1;
}
