<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controller\CustomerStakeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// this is the part that is listing all categorie
Route::get('/categories/list', ['App\Http\Controllers\CategoriesController', 'index']);

//Route::get('retrieve', [CustomerStakeController::class, 'index']);
Route::group(['prefix' => 'v1/patient'], function () {
    Route::post('/add-service', ['App\Http\Controllers\GeneralController', 'addService']);
    Route::post('/add-a-session', ['App\Http\Controllers\BookingController', 'store']);
    Route::get('/all-sessions', ['App\Http\Controllers\BookingController', 'index']);
    Route::post('/add-payment', ['App\Http\Controllers\PatientController', 'addPayment']);
    Route::get('/payments', ['App\Http\Controllers\PatientController', 'payments']);

    Route::get('/logout', ['App\Http\Controllers\Auth\AuthController', 'logout']);
});
Route::group(['prefix' => 'v1'], function () {
    Route::get('/services', ['App\Http\Controllers\GeneralController', 'services']);
    Route::post('/login', ['App\Http\Controllers\Auth\AuthController', 'login']);
    Route::post('/register', ['App\Http\Controllers\Auth\AuthController', 'registerUser']);
});

Route::group(['prefix' => 'v1/admin'], function () {
    Route::post('/add-service', ['App\Http\Controllers\GeneralController', 'addService']);
    Route::get('/payments', ['App\Http\Controllers\PatientController', 'adminPayments']);

});
