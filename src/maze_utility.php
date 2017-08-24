<?php
/* UTILITY FUNCTIONS FOR QUERYING THE MAZE STRUCTURE */

require_once('data.php');

const DEFAULT_MAZE = array(
    array(true , true , false, false, false),
    array(false, false, false, false, true ),
    array(false, false, false, false, false),
    array(true , false, false, false, false),
    array(false, false, true , false, false)
);

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
    $ret .= $row;
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

function coordinate_advance($coordinate) {

}

function coordinate_turn_left($coordinate) {

}

function coordinate_turn_right($coordinate) {

}

function coordinate_is_black($coordinate) {

}

function coordinate_empty_ahead($coordinate, $num = 1) {
    
}
