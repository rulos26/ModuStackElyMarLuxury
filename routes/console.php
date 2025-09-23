<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Simple step command
Artisan::command('step', function () {
    $this->call('step');
})->purpose('Manage dashboard development steps');
