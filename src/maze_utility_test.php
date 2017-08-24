<?php
require('maze_utility.php');

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

echo 'All OK.' . PHP_EOL;
