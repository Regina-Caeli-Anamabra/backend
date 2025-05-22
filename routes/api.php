<?php

use App\Http\Resources\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controller\CustomerStakeController;
use App\Models\Category;
use App\Models\Categories;
use App\Models\Services;
use Illuminate\Support\Facades\DB;
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



Route::get('/check-proxy', function () {
    return request()->ip();  // This will show the IP Laravel detects
});
Route::get('/update-services-now', function(){

    $categories = Services::all();
    $util = new App\Utils\Utils();
    foreach($categories as $category){
        echo $category->id;
        $service = Services::find($category->id);
        $service->identity = $util->generateCramp("service");
        $service->update();
    }
});


Route::get('/re-arrange/category', function(){
    return 1;
    $categories = Category::all();
    foreach($categories as $category){
        if (Categories::where("name", $category->name)->value("name") == $category->name) {
            echo Categories::where("name", $category->name)->value("id");
            $cat = Category::findOrFail($category->id);
            $cat->category_id = Categories::where("name", $category->name)->value("id");
            $cat->update();
        }
    }
});


Route::get('/move-patient-to-users', function(){
return 1;
    echo "Starting Job Processing";
    \App\Jobs\MovePatientsToUsers::dispatch()->delay(\Carbon\Carbon::now()->addSecond(5));;
    return response()->json(['message' => 'Patients are being processed']);


});


//Route::get('retrieve', [CustomerStakeController::class, 'index']);
Route::group(['prefix' => 'v1/patient', 'middleware' => ['auth:sanctum']], function () {
    Route::get('/generate-url', ['App\Http\Controllers\BookingController', 'generateUrl'])::get('/cancelled', ['App\Http\Controllers\BookingController', 'cancelAppointment']);
    Route::get('/get-payment', ['App\Http\Controllers\BookingController', 'getPayment']);
    Route::post('/check-availability', ['App\Http\Controllers\BookingController', 'checkAvailability']);
    Route::get('/next-appointment', ['App\Http\Controllers\BookingController', 'nextAppointment']);
    Route::post('/cancel-payment', ['App\Http\Controllers\BookingController', 'cancelPayment']);
    Route::post('/add-a-session', ['App\Http\Controllers\BookingController', 'store'])->name('flutterwave.callback');
    Route::post('/reschedule', ['App\Http\Controllers\BookingController', 'reschedule']);
    Route::get('/all-sessions', ['App\Http\Controllers\BookingController', 'index']);
    Route::get('/get-session', ['App\Http\Controllers\BookingController', 'getSession']);
    Route::post('/add-payment', ['App\Http\Controllers\PatientController', 'addPayment']);
    Route::get('/get-users-created', ['App\Http\Controllers\PatientController', 'getAllRegisteredByUser']);
    Route::get('/profile', ['App\Http\Controllers\PatientController', 'profile']);
    Route::patch('/profile/update', ['App\Http\Controllers\PatientController', 'updateProfile']);
    Route::patch('/inner/password/update', ['App\Http\Controllers\Auth\AuthController', 'innerUpdatePassword']);
    Route::get('/get-categories', ['App\Http\Controllers\BookingController', 'getCategories']);
    Route::get('/bookings', ['App\Http\Controllers\BookingController', 'bookings']);
    Route::get('/receipt', ['App\Http\Controllers\BookingController', 'receipt']);

});
Route::group(['prefix' => 'v1'], function () {
    ;
    Route::get('/check-email', ['App\Http\Controllers\BookingController', 'checkEmail']);
    Route::get('/check-phone', ['App\Http\Controllers\BookingController', 'checkPhone']);
    Route::get('/countries', ['App\Http\Controllers\GeneralController', 'countries']);
    Route::get('/states', ['App\Http\Controllers\GeneralController', 'states']);
    Route::post('/register', ['App\Http\Controllers\Auth\AuthController', 'registerUser']);
    Route::post('/login', ['App\Http\Controllers\Auth\AuthController', 'login']);
    Route::post('/verify-code', ['App\Http\Controllers\Auth\AuthController', 'verifyCode']);
    Route::post('/send-forgot-password-code', ['App\Http\Controllers\Auth\AuthController', 'forgotPassword']);
    Route::post('/verify-password-reset-code', ['App\Http\Controllers\Auth\AuthController', 'verifyPasswordCode']);
    Route::patch('/password/update', ['App\Http\Controllers\Auth\AuthController', 'updatePassword']);
    Route::get('/resend-email', ['App\Http\Controllers\Auth\AuthController', 'resendEmail']);
    Route::get('/resend-sms', ['App\Http\Controllers\Auth\AuthController', 'sendSMS']);
    Route::get('/dashboard-services', ['App\Http\Controllers\GeneralController', 'dashboardServices']);

    Route::post('/get-details', ['App\Http\Controllers\PatientController', 'getDetails']);
    Route::post('/create-password', ['App\Http\Controllers\PatientController', 'createPassword']);
    Route::post('/service-charge-payment', ['App\Http\Controllers\PatientController', 'serviceChargePayment']);
    Route::post('/initiate-service-charge-payments', ['App\Http\Controllers\PatientController', 'initiateServiceChargePayment']);
    Route::post('/cancel-service-charge-payments', ['App\Http\Controllers\PatientController', 'cancelServiceChargePayment']);
    Route::post('/complete-service-charge-payments', ['App\Http\Controllers\PatientController', 'CompleteServiceChargePayment']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('/get-live-encryption-key', ['App\Http\Controllers\GeneralController', 'getLiveEncryptionKey']);
        Route::get('/get-live-secret-key', ['App\Http\Controllers\GeneralController', 'getLiveSecretKey']);
        Route::get('/get-live-public-key', ['App\Http\Controllers\GeneralController', 'getLivePublicKey']);
        Route::get('/get-test-public-key', ['App\Http\Controllers\GeneralController', 'getTestPublicKey']);
        Route::get('/get-test-secret-key', ['App\Http\Controllers\GeneralController', 'getTestSecretKey']);
        Route::get('/get-test-encryption-key', ['App\Http\Controllers\GeneralController', 'getTestEncryptionKey']);
        Route::get('/category', ['App\Http\Controllers\GeneralController', 'category']);
        Route::get('/services', ['App\Http\Controllers\GeneralController', 'services']);
        Route::get('/logout', ['App\Http\Controllers\Auth\AuthController', 'logout']);
    });

    Route::middleware('auth:sanctum', 'ability:' . \App\Enums\TokenAbility::ISSUE_ACCESS_TOKEN->value)->group(function () {
        Route::get('/refresh-token',['App\Http\Controllers\Auth\AuthController', 'refreshToken']);
    });
});

Route::group(['prefix' => 'v1/admin'], function () {
    Route::post('/login', ['App\Http\Controllers\Auth\AuthController', 'adminLogin']);
    Route::group(['middleware' => ['auth:sanctum', 'check.role']], function () {
        Route::get('/services', ['App\Http\Controllers\GeneralController', 'adminServices']);
        Route::get('/service-charge', ['App\Http\Controllers\AdminController', 'serviceCharges']);
        Route::get('/category', ['App\Http\Controllers\GeneralController', 'category']);
        Route::get('/get-categories', ['App\Http\Controllers\AdminController', 'getCategories']);
        Route::post('/add-service', ['App\Http\Controllers\AdminController', 'addService']);
        Route::get('/get-dashboard-data', ['App\Http\Controllers\AdminController', 'dashboardData']);
        Route::get('/payments', ['App\Http\Controllers\AdminController', 'adminPayments']);
        Route::post('/add-service', ['App\Http\Controllers\AdminController', 'addService']);
        Route::get('/patients', ['App\Http\Controllers\AdminController', 'getPatients']);
        Route::get('/get-bookings', ['App\Http\Controllers\AdminController', 'index']);
        Route::post('/create-service', ['App\Http\Controllers\AdminController', 'createService']);
        Route::get('/logout', ['App\Http\Controllers\Auth\AuthController', 'logout']);
        Route::get('/get-payments', ['App\Http\Controllers\PatientController', 'getPayments']);
        Route::post('/search-patient', ['App\Http\Controllers\PatientController', 'searchPatient']);
        Route::post('/search-booking', ['App\Http\Controllers\PatientController', 'searchBooking']);
        Route::get('/get-service/{identity}', ['App\Http\Controllers\PatientController', 'getService']);
        Route::get('/get-patient', ['App\Http\Controllers\PatientController', 'getPatient']);
        Route::post('/update-service', ['App\Http\Controllers\PatientController', 'updateService']);
        Route::post('/update-patient', ['App\Http\Controllers\PatientController', 'updatePatient']);
    });
});
