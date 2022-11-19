<?php

defined('DS') or exit('No direct script access.');

/*
|--------------------------------------------------------------------------
| Middleware
|--------------------------------------------------------------------------
|
| Middleware menyediakan cara untuk melampirkan fungsionalitas ke rute anda.
| Middleware bawaan 'before' dan 'after' akan dipanggil sebelum dan sesudah
| setiap request direspon.
|
*/

Route::middleware('csrf', function () {
    if (! Request::is_method('get') && Request::forged()) {
        return Response::error(422);
    }
});

Route::middleware('auth', function () {
    if (Auth::guest()) {
        return Response::error(401);
    }
});

Route::middleware('throttle', function ($limit, $minutes) {
    if (Throttle::exceeded($limit, $minutes)) {
        return Response::error(429);
    }
});


Route::middleware('before', function () {
    // ..
});

Route::middleware('after', function ($response) {
    // ..
});
