<?php

use Weap\Junction\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind the package's base TestCase to every test in the suite so each test
| boots a Testbench application with the Junction service provider loaded.
|
*/

uses(TestCase::class)->in(__DIR__);
