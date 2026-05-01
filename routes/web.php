<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Route::get('/zidan', function() {
    return 'hello zidan!';
});