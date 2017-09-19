<?php
require_once(dirname(__FILE__) . '/data.php');
require_once(dirname(__FILE__) . '/maze_commands.php');
require_once(dirname(__FILE__) . '/maze_utility.php');

function create_random_entry_coord() {
    switch(rand(1, 4)) {
        case 1:
            return coordinate_create(rand(0, 4), 0, 's');

        case 2:
            return coordinate_create(rand(0, 4), 4, 'n');

        case 3:
            return coordinate_create(0, rand(0, 4), 'e');

        case 4:
            return coordinate_create(4, rand(0, 4), 'w');
    }
}

for($run = 1; $run <= 10; ++$run) {
    echo "= Run {$run} =\n\n";

    $coord = create_random_entry_coord();
    echo "`{$coord}`\n";

    for($step = 1; $step <= 13; ++$step) {
        $command = call_user_func("command_{$step}", 123, $coord);
        echo "{$step}. `{$command[0]}` ► `{$command[1]}`\n";

        $coord = $command[1];
    }

    echo "\n";
}
