<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you return to your test case provides a fresh instance of your
| application for each test case. You may also use the setUp method for
| additional setup.
|
*/

uses(Tests\TestCase::class)->in('Feature', 'Unit');
uses(Illuminate\Foundation\Testing\RefreshDatabase::class)->in('Feature', 'Unit');
