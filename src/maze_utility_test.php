<?php
require_once('maze_utility.php');

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

echo 'All OK.' . PHP_EOL;
