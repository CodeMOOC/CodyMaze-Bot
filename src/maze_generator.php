<?php

function generate_maze($lvl, $telegram_id, $current_coordinate){
    Logger::debug("Genero comandi per lvl $lvl coordinate $current_coordinate");
    switch ($lvl){
        case 1:
            return command_1($telegram_id, $current_coordinate);
            break;
        case 2:
            return command_2($telegram_id, $current_coordinate);
            break;
        case 3:
            return command_3($telegram_id, $current_coordinate);
            break;
        case 4:
            return command_4($telegram_id, $current_coordinate);
            break;
        case 5:
            return command_5($telegram_id, $current_coordinate);
            break;
        case 6:
            return command_6($telegram_id, $current_coordinate);
            break;
        case 7:
            return command_7($telegram_id, $current_coordinate);
            break;
        case 8:
            return command_8($telegram_id, $current_coordinate);
            break;
        case 9:
            return command_9($telegram_id, $current_coordinate);
            break;
        case 10:
            return command_10($telegram_id, $current_coordinate);
            break;
        case 11:
            return command_11($telegram_id, $current_coordinate);
            break;
        case 12:
            return command_12($telegram_id, $current_coordinate);
            break;
        case 13:
            return command_13($telegram_id, $current_coordinate);
            break;
    }
}