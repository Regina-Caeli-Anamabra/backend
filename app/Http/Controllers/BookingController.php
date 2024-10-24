<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Bookings;
use App\Models\Categories;
use App\Models\DonationPayment;
use App\Models\Donations;
use App\Models\FlutterwavePayment;
use App\Models\Patients;
use App\Models\r;
use App\Models\Services;
use App\Utils\Utils;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function donation(Request $request, Utils $utils)
    {

        $paymentData =  $utils->validatePayment($request->get("transaction_id"));

        $donation = new DonationPayment();
        $donation->name = $request->get("name");
        $donation->account_id = $paymentData["data"]["account_id"];
        $donation->amount =  $paymentData["data"]["amount"];
        $donation->amount_settled =  $paymentData["data"]["amount_settled"];
        $donation->app_fee =  $paymentData["data"]["app_fee"];
        $donation->charged_amount =  $paymentData["data"]["charged_amount"];
        $donation->country =  $paymentData["data"]["card"]["country"];
        $donation->expiry = $paymentData["data"]["card"]["expiry"];
        $donation->first_6digits =  $paymentData["data"]["card"]["first_6digits"];
        $donation->issuer = $paymentData["data"]["card"]["issuer"];
        $donation->last_4digits = $paymentData["data"]["card"]["last_4digits"];
        $donation->card_token =  $paymentData["data"]["card"]["token"];
        $donation->card_type =   $paymentData["data"]["card"]["type"];
        $donation->email =  $paymentData["data"]["customer"]["email"];
        $donation->name =  $paymentData["data"]["customer"]["name"];
        $donation->phone_number =  $paymentData["data"]["customer"]["phone_number"];
        $donation->flw_ref =  $paymentData["data"]["flw_ref"];
        $donation->ip =  $paymentData["data"]["ip"];
        $donation->processor_response =  $paymentData["data"]["processor_response"];
        $donation->status =  $paymentData["data"]["status"];
        $donation->narration =  $paymentData["data"]["status"];
        $donation->merchant_fee =  $paymentData["data"]["merchant_fee"];
        $donation->tx_ref =  $paymentData["data"]["tx_ref"];
        $donation_saved =  $donation->save();



        $request->validate([
            "name" => "required",
            "amount" => "required",
        ]);
        try {
            $donation = new Donations();
            $donation->name = $request->get("name");
            $donation->amount = $request->get("amount");
            $donation->save();

            return $utils->message("success", $donation , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    /**
     * @OA\Get (
     *     path="/api/v1/patient/all-sessions",
     *      tags={"Booking"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *     @OA\Response(response="200", description="Booking successful", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Code Not Found", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized Access", @OA\JsonContent()),
     *     @OA\Response(response="400", description="Booking already exists", @OA\JsonContent())
     * )
     */
    public function index(Request $request, Utils $utils)
    {

        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $user_id =  auth('sanctum')->user()->id;

        try {
            $booking = Bookings::with(["services"])->where("user_id", $user_id)->get();
            return $utils->message("success", $booking , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }



    /**
     * @OA\Post(
     *     path="/api/v1/patient/cancel-payment",
     *      tags={"Booking"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *     @OA\Parameter(
     *         name="payment_id                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          ",
     *         in="query",
     *         description="2024-04-29 18:00:00",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Booking successful", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Code Not Found", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized Access", @OA\JsonContent()),
     *     @OA\Response(response="400", description="Booking already exists", @OA\JsonContent())
     * )
     */
    public function cancelPayment(Request $request, Utils $utils)
    {
        try {

            $request->validate([
                "payment_id" => "required|int",
            ]);

            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

             $user_id =  auth('sanctum')->user()->id;
             $data = [
                 "payment_id" => $request->get("payment_id"),
                 "user_id" => $user_id,
                 "first_name" => Patients::where("user_id", $user_id)->value("first_name"),
                 "last_name" => Patients::where("user_id", $user_id)->value("last_name")
             ];
             Log::info("Transaction Cancelled", $data);

            $payment =  FlutterwavePayment::where("id", $request->get("payment_id"))->update([
                "status" => "cancelled"
            ]);
            return $utils->message("success", $payment , 200);

        }catch (\Throwable $e) {
        // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
    /**
     * @OA\Get (
     *     path="/api/v1/patient/get-categories",
     *      tags={"Booking"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *     @OA\Parameter(
     *         name="service_id",
     *         in="query",
     *         description="",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Booking successful", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Code Not Found", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized Access", @OA\JsonContent()),
     *     @OA\Response(response="400", description="Booking already exists", @OA\JsonContent())
     * )
     */
    public function getCategories(Request $request, Utils $utils)
    {
        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $categories = Categories::all();

        return $utils->message("success", $categories , 200);

    }


    /**
     * @OA\Post(
     *     path="/api/v1/patient/send-payment",
     *      tags={"Booking"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *     @OA\Parameter(
     *         name="service_id",
     *         in="query",
     *         description="2024-04-29 18:00:00",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Booking successful", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Code Not Found", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized Access", @OA\JsonContent()),
     *     @OA\Response(response="400", description="Booking already exists", @OA\JsonContent())
     * )
     */
    public function sendPayment(Request $request, Utils $utils)
    {
        try {

            $request->validate([
                "service_id" => "required|int",
            ]);

            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

             $user_id =  auth('sanctum')->user()->id;


            $data = [
                "user_id" => $user_id,
                "service" => Services::where("id", $request->get("service_id"))->value("name"),
                "first_name" => Patients::where("user_id", $user_id)->value("first_name"),
                "last_name" => Patients::where("user_id", $user_id)->value("last_name")
            ];
            Log::info("transaction Started", $data);

            $payment = new FlutterwavePayment();
            $payment->status = "pending";
            $payment->service_id = $request->get("service_id");
            $payment->user_id = $user_id;
            $payment->patient_id =  Patients::where('user_id', $user_id)->first()->id;
            $payment->status = "pending";
            $payment->save();
            return $utils->message("success", $payment , 200);

        }catch (\Throwable $e) {
        // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/patient/add-a-session",
     *      tags={"Booking"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *     @OA\Parameter(
     *         name="booking_start",
     *         in="query",
     *         description="2024-04-29 18:00:00",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="transaction_id",
     *         in="query",
     *         description="Transaction id",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="service_id",
     *         in="query",
     *         description="service_id",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="booking_for_self",
     *         in="query",
     *         description="1 for self, 0 for someone else",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="payment_id",
     *         in="query",
     *         description="id of the payment",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Booking successful", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Code Not Found", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized Access", @OA\JsonContent()),
     *     @OA\Response(response="400", description="Booking already exists", @OA\JsonContent())
     * )
     */
    public function store(Request $request, Utils $utils)
    {
        $request->validate([
            "booking_start" => "required",
            "service_id" => "required|int",
            "booking_for_self" => "required|int",
            "transaction_id" => "required",
            "payment_id" => "required"
        ]);


        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $user_id =  auth('sanctum')->user()->id;
        try {
            $paymentData =  $utils->validatePayment($request->get("transaction_id"));
            $data = [
                "transaction_id" => $request->get("transactiono_id"),
                "user_id" => $user_id,
                "first_name" => Patients::where("user_id", $user_id)->value("first_name"),
                "last_name" => Patients::where("user_id", $user_id)->value("last_name"),
                "payment_info" => $paymentData,
            ];
            Log::info("Payment Completed", $data);


            if(empty($paymentData["data"]))
                return $utils->message("error","Invalid Transaction ID." , 401);


            FlutterwavePayment::where("id", $request->get("payment_id"))->update([
            "user_id" => $user_id ,
            "patient_id" => Patients::where("user_id", $user_id)->value("id"),
            "account_id" => $paymentData["data"]["account_id"],
            "amount" =>  $paymentData["data"]["amount"],
            "amount_settled" =>  $paymentData["data"]["amount_settled"],
            "app_fee" =>  $paymentData["data"]["app_fee"],
            "charged_amount" =>  $paymentData["data"]["charged_amount"],
            "country" =>  $paymentData["data"]["card"]["country"],
            "expiry" => $paymentData["data"]["card"]["expiry"],
            "first_6digits" =>  $paymentData["data"]["card"]["first_6digits"],
            "issuer" => $paymentData["data"]["card"]["issuer"],
            "last_4digits" => $paymentData["data"]["card"]["last_4digits"],
            "card_token" =>  $paymentData["data"]["card"]["token"],
            "card_type" =>   $paymentData["data"]["card"]["type"],
            "email" =>  $paymentData["data"]["customer"]["email"],
            "name" =>  $paymentData["data"]["customer"]["name"],
            "phone_number" =>  $paymentData["data"]["customer"]["phone_number"],
            "flw_ref" =>  $paymentData["data"]["flw_ref"],
            "ip" =>  $paymentData["data"]["ip"],
            "processor_response" =>  $paymentData["data"]["processor_response"],
            "status" => $paymentData["data"]["status"],
            "narration" =>  $paymentData["data"]["status"],
            "merchant_fee" =>  $paymentData["data"]["merchant_fee"],
            "tx_ref" =>  $paymentData["data"]["tx_ref"],
            "service_id" => $request->get("service_id"),
            ]);



            $booking_start = Carbon::parse($request->get("booking_start"));
            $booking_start_formatted =  $booking_start->format("Y-m-d H:i:s");
            $booking_end =  $booking_start->copy()->addMinute(45)->format("Y-m-d H:i:s");

            if(Bookings::whereBetween("session_start", [$booking_start_formatted, $booking_end])->exists())
                return $utils->message("error","The session is already booked." , 400);



            $recipient_id = $request->get("booked_by_id");
            $booking = new Bookings();
            $booking->session_start = $booking_start_formatted;
            $booking->service_id = $request->get("service_id");
            $booking->price = Services::where("id", $request->get("service_id"))->value("amount");
            $booking->session_end = $booking_end;
            $booking->user_id =  $user_id;
            $booking->booking_for_self = $request->get("booking_for_self");
            $booking->recipient_id = $recipient_id;
            $booking->save();
            return $utils->message("success", $booking , 200);

        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(r $r)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(r $r)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, r $r)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(r $r)
    {
        //
    }
}
