<?php

namespace App\Service;

use Random\RandomException;

/**
 * Mock function for random_int used in testing.
 * This function will override the global random_int function in the App\Service namespace
 * when loaded before the service is used.
 */
function random_int(int $min, int $max): int
{
    // Check if we should throw exception for testing
    if (isset($GLOBALS['test_random_int_should_throw']) && $GLOBALS['test_random_int_should_throw'] === true) {
        throw new RandomException('Mocked RandomException for testing');
    }

    // Fallback to the global random_int function
    return \random_int($min, $max);
}
