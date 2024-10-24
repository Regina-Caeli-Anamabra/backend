<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Http\Resources\Category;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PaymentsResource;
use App\Models\Bookings;
use App\Models\Categories;
use App\Models\Patients;
use App\Models\Payments;
use App\Models\Services;
use App\Models\User;
use App\Utils\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{

    public function getCategories(Request $request, Utils $utils)
    {
        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $categories = CategoryResource::collection(Categories::all());

        return $utils->message("success", $categories , 200);

    }

    public function dashboardData(Request $request, Utils $utils)
    {

        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $booking = Bookings::count();
        $services = Services::count();
        $patient = Patients::count();
        $recentBookings =  Bookings::with("patient")->get();

        $data = [
            "bookings" => $booking,
            "services" => $services,
            "patients" => $patient,
            "recentBookings" => BookingResource::collection($recentBookings)
        ];

        return $utils->message("success", $data , 200);

    }
    public function createService(Request $request, Utils $utils)
    {

        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        try {
            $service = new Services();
            $service->name = $request->get('name');
            $service->amount  = $request->get('amount');
            $service->category_id  = $request->get('category');
            $service->save();

            return $utils->message("success", $service , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            Log::error($e->getMessage());

        }

    }
    public function index(Request $request, Utils $utils)
    {

        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $user_id =  auth('sanctum')->user()->id;

        try {
             $booking = Bookings::with("users", "patient")->orderBy("created_at", "DESC")->get();
            $bookings = BookingResource::collection($booking);
            $data = [
                "bookings" => $bookings,
                "total" => number_format(Bookings::sum('price'), 2)
            ];
            return $utils->message("success", $data , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
    public function adminPayments(Request $request, Utils $utils)
    {
        try {
            $payments = Payments::with(["user", "paymentBookings", "patients", "services"])->orderBy("created_at", "DESC")->get();
            return $utils->message("success", PaymentsResource::collection($payments) , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
    public function addService(Request $request, Utils $utils): JsonResponse
    {

        $request->validate([
            "name" => "required|string",
            "amount" => "required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/",
        ]);
        try {
            $service = new Services();
            $service->name = $request->get("name");
            $service->amount = $request->get("amount");
            $service->save();
            return $utils->message("success", $service  , 200);

        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
    public function getPatients(Utils $utils): JsonResponse
    {

        try {

            $patients = User::with(['patient'])->orderBy("created_at", "DESC")->get();
            $patients = PatientResource::collection($patients);
            return $utils->message("success", $patients  , 200);

        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
}
