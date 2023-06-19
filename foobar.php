<?php

foreach (range(1, 100) as $number) {
    if ($number % 15 == 0) {
        echo "foobar,";
    } elseif ($number % 3 == 0) {
        echo "foo,";
    } elseif ($number % 5 == 0) {
        echo "bar,";
    } else {
        echo "$number,";
    }
}
