<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('blipblap:hello', function (): void {
    $this->comment('BlipBlap Laravel/Inertia scaffold is ready.');
});
