<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response
    ('
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>API REST-Posify</title>
    </head>
    <body>
        <h1>API REST: Posify</h1>
        <p>Laravel 11 - alexmargomez 2025</p>
    </body>
    </html>
    ', 200)
    ->header('Content-Type', 'text/html');
});
