<?php
require_once('maze_commands.php');

error_reporting(E_ALL);
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

assert(command_1(123, 'a1e')[0] == 'a');
assert(command_1(123, 'a1e')[1] == 'b1e');
assert(command_1(123, 'c5n')[0] == 'a');
assert(command_1(123, 'c5n')[1] == 'c4n');
assert(command_1(123, 'c3e')[1] == 'd3e');
assert(command_1(123, 'e3w')[1] == 'd3w');

assert(command_2(123, 'a1e')[1] == 'a1s');
assert(command_2(123, 'a1e')[0] == 'd');
$tc = command_2(123, 'c3n'); assert($tc[1] == 'c3e' || $tc[1] == 'c3w');
assert(command_2(123, 'e5w')[1] == 'e5n');
assert(command_2(123, 'e5w')[0] == 'd');
assert(command_2(123, 'a5n')[1] == 'a5e');
assert(command_2(123, 'a5n')[0] == 'd');

echo 'All OK.' . PHP_EOL;
