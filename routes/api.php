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

// this is the part that is listing all categories
Route::get('/categories/list', ['App\Http\Controllers\CategoriesController', 'index']);

//Route::get('retrieve', [CustomerStakeController::class, 'index']);
Route::group(['prefix' => 'v1/patient', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/add-a-session', ['App\Http\Controllers\BookingController', 'store']);
    Route::get('/all-sessions', ['App\Http\Controllers\BookingController', 'index']);
    Route::post('/add-payment', ['App\Http\Controllers\PatientController', 'addPayment']);
    Route::get('/get-users-created', ['App\Http\Controllers\PatientController', 'getAllRegisteredByUser']);
    Route::get('/profile', ['App\Http\Controllers\PatientController', 'profile']);
    Route::patch('/profile/update', ['App\Http\Controllers\PatientController', 'updateProfile']);
    Route::patch('/inner/password/update', ['App\Http\Controllers\Auth\AuthController', 'innerUpdatePassword']);
});
Route::group(['prefix' => 'v1'], function () {
    Route::get('/countries', ['App\Http\Controllers\GeneralController', 'countries']);
    Route::post('/donation', ['App\Http\Controllers\BookingController', 'donation']);
    Route::get('/states', ['App\Http\Controllers\GeneralController', 'states']);
    Route::post('/register', ['App\Http\Controllers\Auth\AuthController', 'registerUser']);
    Route::post('/login', ['App\Http\Controllers\Auth\AuthController', 'login']);
    Route::post('/verify-code', ['App\Http\Controllers\Auth\AuthController', 'verifyCode']);
    Route::post('/send-forgot-password-code', ['App\Http\Controllers\Auth\AuthController', 'forgotPassword']);
    Route::post('/verify-password-reset-code', ['App\Http\Controllers\Auth\AuthController', 'verifyPasswordCode']);
    Route::patch('/password/update', ['App\Http\Controllers\Auth\AuthController', 'updatePassword']);


    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('/services', ['App\Http\Controllers\GeneralController', 'services']);
        Route::get('/logout', ['App\Http\Controllers\Auth\AuthController', 'logout']);
        Route::get('/payments', ['App\Http\Controllers\PatientController', 'payments']);

    });
});

Route::group(['prefix' => 'v1/admin'], function () {
    Route::post('/login', ['App\Http\Controllers\Auth\AuthController', 'adminLogin']);
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/add-service', ['App\Http\Controllers\AdminController', 'addService']);
        Route::get('/get-dashboard-data', ['App\Http\Controllers\AdminController', 'dashboardData']);
        Route::get('/payments', ['App\Http\Controllers\AdminController', 'adminPayments']);
        Route::post('/add-service', ['App\Http\Controllers\AdminController', 'addService']);
        Route::get('/patients', ['App\Http\Controllers\AdminController', 'getPatients']);
        Route::get('/get-bookings', ['App\Http\Controllers\AdminController', 'index']);
        Route::post('/create-service', ['App\Http\Controllers\AdminController', 'createService']);
        Route::get('/services', ['App\Http\Controllers\GeneralController', 'services']);
        Route::get('/logout', ['App\Http\Controllers\Auth\AuthController', 'logout']);
        Route::get('/get-payments', ['App\Http\Controllers\PatientController', 'getPayments']);
    });
});
