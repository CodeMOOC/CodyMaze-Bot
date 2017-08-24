<?php
require_once('maze_commands.php');

error_reporting(E_ALL);
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

// Command 1
assert(command_1(123, 'a1e')[0] == 'a');
assert(command_1(123, 'a1e')[1] == 'b1e');
assert(command_1(123, 'c5n')[0] == 'a');
assert(command_1(123, 'c5n')[1] == 'c4n');
assert(command_1(123, 'c3e')[1] == 'd3e');
assert(command_1(123, 'e3w')[1] == 'd3w');

// Command 2
assert(command_2(123, 'a1e')[1] == 'a1s');
assert(command_2(123, 'a1e')[0] == 'd');
$tc = command_2(123, 'c3n'); assert($tc[1] == 'c3e' || $tc[1] == 'c3w');
assert(command_2(123, 'e5w')[1] == 'e5n');
assert(command_2(123, 'e5w')[0] == 'd');
assert(command_2(123, 'a5n')[1] == 'a5e');
assert(command_2(123, 'a5n')[0] == 'd');

// Command 3 (same as 1)

// Command 4
assert(command_4(123, 'd4w')[0] == 'd');
assert(command_4(123, 'd4w')[1] == 'd4n');
assert(command_4(123, 'a1n')[0] == 'd');
assert(command_4(123, 'a1n')[1] == 'a1e');
assert(command_4(123, 'e3e')[0] == 's');
assert(command_4(123, 'e3e')[1] == 'e3n');
assert(command_4(123, 'd4e')[0] == 's');
assert(command_4(123, 'd4e')[1] == 'd4n');

// Command 5
assert(command_5(123, 'a1e')[1] == 'c1e');
assert(command_5(123, 'a1e')[0] == '2{a}');
assert(command_5(123, 'c5e')[1] == 'e5e');
assert(command_5(123, 'c3w')[1] == 'a3w');
assert(command_5(123, 'd2s')[1] == 'd4s');
assert(command_5(123, 'a5n')[1] == 'a3n');

echo 'All OK.' . PHP_EOL;
