<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('storage/{path}', function (string $path) {
    $relativePath = ltrim(str_replace('\\', '/', $path), '/');

    if ($relativePath === '' || str_contains($relativePath, '..')) {
        abort(404);
    }

    $filePath = storage_path('app/public/'.$relativePath);

    if (! is_file($filePath)) {
        abort(404);
    }

    return response()->file($filePath);
})
    ->where('path', '.*')
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
    ]);


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});
