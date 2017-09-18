<?php
require_once(dirname(__FILE__) . '/maze_commands.php');

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
assert(command_6(123, 'a1e')[0] == 'd4{a}');
assert(command_6(123, 'a1e')[1] == 'a5s');
assert(command_6(123, 'c1w')[0] == 's4{a}');
assert(command_6(123, 'c1w')[1] == 'c5s');
$tc = command_6(123, 'b3w'); assert($tc[0] == 's2{a}' || $tc[0] == 'd2{a}');
$tc = command_6(123, 'b3w'); assert($tc[1] == 'b1n' || $tc[1] == 'b5s');
$tc = command_6(123, 'd4n'); assert($tc[0] == 's3{a}' || $tc[0] == 'd1{a}');
$tc = command_6(123, 'd4n'); assert($tc[1] == 'e4e' || $tc[1] == 'a4w');

// Command 7
$c7_1 = command_7(123, 'a1e');
assert($c7_1[0] == '1{da}');
assert($c7_1[1] == 'a2s');

$c7_2 = command_7(123, 'e4e');
assert($c7_2[0] == '3{sa}');
assert($c7_2[1] == 'd4s');

//Command 8
assert(command_8(123, 'a1w')[0] == 'se(stella){sa}');
assert(command_8(123, 'a1w')[1] == 'a2s');
assert(command_8(123, 'e5n')[0] == 'se(non stella){sa}');
assert(command_8(123, 'e5n')[1] == 'd5w');
assert(command_8(123, 'b1e')[0] == 'se(stella){da}');
assert(command_8(123, 'b1e')[1] == 'b2s');
assert(command_8(123, 'd3s')[0] == 'se(non stella){da}');
assert(command_8(123, 'd3s')[1] == 'c3w');

//Command 9
assert(command_9(123, 'a1w')[0] == 'se(stella){sa}altrimenti{da}');
assert(command_9(123, 'a1w')[1] == 'a2s');
assert(command_9(123, 'e5n')[0] == 'se(stella){da}altrimenti{sa}');
assert(command_9(123, 'e5n')[1] == 'd5w');
assert(command_9(123, 'b1e')[0] == 'se(stella){da}altrimenti{sa}');
assert(command_9(123, 'b1e')[1] == 'b2s');
assert(command_9(123, 'd3s')[0] == 'se(stella){sa}altrimenti{da}');
assert(command_9(123, 'd3s')[1] == 'c3w');


// Command 10
$c10_1 = command_10(123, 'a1e', 5);
assert($c10_1[0] == '5{se(strada davanti){a}altrimenti{se(strada a dx){d}altrimenti{s}}}');
assert($c10_1[1] == 'e1s');
assert(command_10(123, 'c3w', 4)[1] == 'a2n');
assert(command_10(123, 'e5e', 5)[1] == 'e1n');

// Command 11
$c11_1 = command_11(123, 'a1e', 5);
assert($c11_1[0] == '5{se(strada davanti){a}altrimenti{se(strada a sx){s}altrimenti{d}}}');
assert($c11_1[1] == 'e1s');
assert(command_11(123, 'c3w', 4)[1] == 'a4s');
assert(command_11(123, 'e5e', 5)[1] == 'e1n');
assert(command_11(123, 'e5s', 5)[1] == 'a5w');

// Command 12
assert(command_12(123, 'b2e')[1] == 'e2e');
assert(command_12(123, 'd1s')[1] == 'd5s');
assert(command_12(123, 'c2n')[1] == 'c1n');
assert(command_12(123, 'a5s')[1] == 'a5s');
assert(command_12(123, 'a1w')[1] == 'a1w');

// Command 13
assert(command_13(123, 'a1e')[1] == 'a1e');
assert(command_13(123, 'b1s')[1] == 'b1s');
assert(command_13(123, 'c1n')[1] == 'e2s');
assert(command_13(123, 'd1w')[1] == 'b1w');
assert(command_13(123, 'b2s')[1] == 'a4n');
assert(command_13(123, 'c3e')[1] == 'c5w');

echo 'All OK.' . PHP_EOL;
