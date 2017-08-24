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
assert(command_4(123, 'd4w')[0] == 'da');
assert(command_4(123, 'd4w')[1] == 'd3n');
assert(command_4(123, 'a1n')[0] == 'da');
assert(command_4(123, 'a1n')[1] == 'b1e');
assert(command_4(123, 'd4e')[0] == 'sa');
assert(command_4(123, 'd4e')[1] == 'd3n');

// Command 5
assert(command_5(123, 'a1e')[1] == 'c1e');
assert(command_5(123, 'a1e')[0] == '2{a}');
assert(command_5(123, 'c5e')[1] == 'e5e');
assert(command_5(123, 'c3w')[1] == 'a3w');
assert(command_5(123, 'd2s')[1] == 'd4s');
assert(command_5(123, 'a5n')[1] == 'a3n');

// Command 6
assert(command_6(123, 'a1e')[0] == 'd 4{a}');
assert(command_6(123, 'a1e')[1] == 'a5s');
assert(command_6(123, 'c1w')[0] == 's 4{a}');
assert(command_6(123, 'c1w')[1] == 'c5s');
$tc = command_6(123, 'b3w'); assert($tc[0] == 's 2{a}' || $tc[0] == 'd 2{a}');
$tc = command_6(123, 'b3w'); assert($tc[1] == 'b1n' || $tc[1] == 'b5s');
$tc = command_6(123, 'd4n'); assert($tc[0] == 's 3{a}' || $tc[0] == 'd 1{a}');
$tc = command_6(123, 'd4n'); assert($tc[1] == 'e4e' || $tc[1] == 'a4w');

// Command 7
$c7_1 = command_7(123, 'a1e');
assert(strlen($c7_1[0]) >= 3 && strlen($c7_1[0]) <= 4);
assert($c7_1 != null);

$c7_2 = command_7(123, 'e4e');
assert(strlen($c7_2[0]) >= 3 && strlen($c7_2[0]) <= 4);
assert($c7_2 != null);


echo 'All OK.' . PHP_EOL;
