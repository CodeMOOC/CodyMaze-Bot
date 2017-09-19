<?php
require_once(dirname(__FILE__) . '/maze_utility.php');

error_reporting(E_ALL);
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

assert(coordinate_create(0, 0, 'n') == 'a1n');
assert(coordinate_create(1, 1, 'e') == 'b2e');
assert(coordinate_create(2, 2, 'w') == 'c3w');
assert(coordinate_create(3, 3, 's') == 'd4s');
assert(coordinate_create(4, 4, 'n') == 'e5n');

assert(coordinate_to_column('a1n') == 0);
assert(coordinate_to_column('b3s') == 1);
assert(coordinate_to_column('c5e') == 2);
assert(coordinate_to_column('d1s') == 3);
assert(coordinate_to_column('e4w') == 4);

assert(coordinate_to_row('a1n') == 0);
assert(coordinate_to_row('b3s') == 2);
assert(coordinate_to_row('c5e') == 4);
assert(coordinate_to_row('d1s') == 0);
assert(coordinate_to_row('e4w') == 3);

assert(coordinate_to_direction('a1n') == 'n');
assert(coordinate_to_direction('b3s') == 's');
assert(coordinate_to_direction('c5e') == 'e');
assert(coordinate_to_direction('e4w') == 'w');

assert(coordinate_advance('a1n') == null);
assert(coordinate_advance('a1s') == 'a2s');
assert(coordinate_advance('a2s') == 'a3s');
assert(coordinate_advance('a5s') == null);
assert(coordinate_advance('a1w') == null);
assert(coordinate_advance('e2e') == null);
assert(coordinate_advance('e5s') == null);
assert(coordinate_advance('e5w') == 'd5w');
assert(coordinate_advance('c3n') == 'c2n');
assert(coordinate_advance('c3e') == 'd3e');
assert(coordinate_advance('c3w') == 'b3w');
assert(coordinate_advance('c3s') == 'c4s');
assert(coordinate_advance('a5s') == null);

assert(coordinate_turn_left('a1n') == 'a1w');
assert(coordinate_turn_left('c5e') == 'c5n');
assert(coordinate_turn_left('b3s') == 'b3e');
assert(coordinate_turn_left('e4w') == 'e4s');

assert(coordinate_turn_right('a1n') == 'a1e');
assert(coordinate_turn_right('c5e') == 'c5s');
assert(coordinate_turn_right('b3s') == 'b3w');
assert(coordinate_turn_right('e4w') == 'e4n');

assert(coordinate_turn_180('a1n') == 'a1s');
assert(coordinate_turn_180('e1e') == 'e1w');
assert(coordinate_turn_180('c4w') == 'c4e');
assert(coordinate_turn_180('b5s') == 'b5n');

assert(coordinate_is_black('a1n') == true);
assert(coordinate_is_black('a2n') == false);
assert(coordinate_is_black('a3n') == false);
assert(coordinate_is_black('a4n') == true);
assert(coordinate_is_black('a5n') == false);

assert(coordinate_is_black('b1n') == true);
assert(coordinate_is_black('b2n') == false);
assert(coordinate_is_black('b3n') == false);
assert(coordinate_is_black('b4n') == false);
assert(coordinate_is_black('b5n') == false);

assert(coordinate_is_black('c1n') == false);
assert(coordinate_is_black('c2n') == false);
assert(coordinate_is_black('c3n') == false);
assert(coordinate_is_black('c4n') == false);
assert(coordinate_is_black('c5n') == true);

assert(coordinate_is_black('d1n') == false);
assert(coordinate_is_black('d2n') == false);
assert(coordinate_is_black('d3n') == false);
assert(coordinate_is_black('d4n') == false);
assert(coordinate_is_black('d5n') == false);

assert(coordinate_is_black('e1n') == false);
assert(coordinate_is_black('e2n') == true);
assert(coordinate_is_black('e3n') == false);
assert(coordinate_is_black('e4n') == false);
assert(coordinate_is_black('e5n') == false);

assert(coordinate_out_ahead('a1s', 1) == false);
assert(coordinate_out_ahead('a1n', 1) == true);
assert(coordinate_out_ahead('b3e', 2) == false);
assert(coordinate_out_ahead('b5e', 2) == false);
assert(coordinate_out_ahead('b5e', 3) == false);
assert(coordinate_out_ahead('b5e', 4) == true);
assert(coordinate_out_ahead('e1w', 1) == false);
assert(coordinate_out_ahead('e1s', 1) == false);
assert(coordinate_out_ahead('e1s', 3) == false);
assert(coordinate_out_ahead('e1s', 5) == true);

assert(coordinate_max_ahead('a3e') == 4);
assert(coordinate_max_ahead('e3e') == 0);
assert(coordinate_max_ahead('d2w') == 3);
assert(coordinate_max_ahead('b4n') == 3);
assert(coordinate_max_ahead('c3n') == 2);
assert(coordinate_max_ahead('a4s') == 1);


echo 'All OK.' . PHP_EOL;
