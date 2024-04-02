<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentsResource;
use App\Models\Bookings;
use App\Models\Patients;
use App\Models\Payments;
use App\Utils\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function addPayment(Request $request, Utils $utils)
    {
        $request->validate([
            "user_id" => "required|int",
            "trx_id" => "required|string",
            "booking_id" => "required|int",
            "service_id" => "required|int",
            "amount" => "required"
        ]);

        try {
            $trx_id =  Str::random(20);
            if (!Payments::where("trx_id", $trx_id)->exists()){
                $user_id = $request->get("user_id");
                $payments = new Payments();
                $payments->user_id = $user_id;
                $payments->merchant_trx_id = $request->get("trx_id");
                $payments->booking_id = $request->get("booking_id");
                $payments->amount = $request->get("amount");
                $payments->trx_id = $trx_id;
                $payments->service_id = $request->get("service_id");
                $payments->patient_id = Patients::where("user_id", $user_id)->value("id");
                $payments->save();
                return $utils->message("success", $payments , 400);

            }else{
                return $utils->message("error", "Network Error. Please Try Again." , 400);

            }

        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    public function payments(Request $request, Utils $utils)
    {
        $request->validate([
            "user_id" => "required|int"
        ]);
        try {
            $payments = Payments::with(["user", "paymentBookings", "patients", "services"])->where("user_id", $request->get("user_id"))->get();
            return $utils->message("success", PaymentsResource::collection($payments) , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    public function adminPayments(Request $request, Utils $utils)
    {
        try {
            $payments = Payments::with(["user", "paymentBookings", "patients", "services"])->get();
            return $utils->message("success", PaymentsResource::collection($payments) , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
}
