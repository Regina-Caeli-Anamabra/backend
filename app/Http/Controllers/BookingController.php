<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Bookings;
use App\Models\Categories;
use App\Models\DonationPayment;
use App\Models\Donations;
use App\Models\FlutterwavePayment;
use App\Models\Patients;
use App\Models\Payments;
use App\Models\r;
use App\Models\Services;
use App\Utils\Utils;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingController extends Controller
{

    /**
     * @OA\Get (
     *     path="/api/v1/patient/next-appointment",
     *      tags={"Booking"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *     @OA\Response(response="200", description="Next Appoint", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Appointment Not Found", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized Access", @OA\JsonContent()),
     *     @OA\Response(response="400", description="Booking already exists", @OA\JsonContent())
     * )
     */
    public function nextAppointment(Utils $utils)
    {
        try {
            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

            $user_id =  auth('sanctum')->user()->id;
            $nextAppoitment = Bookings::orderBy("id","DESC")->where("user_id",$user_id)->limit(1)->get();

            return $utils->message("success", $nextAppoitment, 200);

        }catch (\Exception $exception){
            return $utils->message("error",$exception->getMessage(), 401);
        }
    }
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
     * @OA\Get (
     *     path="/api/v1/patient/transaction-completed",
     *      tags={"Booking"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *      @OA\Parameter(
     *          name="trx_id                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          ",
     *          in="query",
     *          description="trx_id",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *     @OA\Response(response="200", description="Booking successful", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Code Not Found", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized Access", @OA\JsonContent()),
     *     @OA\Response(response="400", description="Booking already exists", @OA\JsonContent())
     * )
     */
    public function transactionCompleted(Request $request, Utils $utils)
    {

        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $user_id =  auth('sanctum')->user()->id;

        try {
            return $utils->message("success", "Transaction Completed..." , 200);
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
     *     @OA\Parameter(
     *         name="unique_id",
     *         in="query",
     *         description="15 random unique string",
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
                "unique_id" => "required|string",
            ]);

            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

             $user_id =  auth('sanctum')->user()->id;


            $data = [
                "user_id" => $user_id,
                "service" => Services::where("id", $request->get("service_id"))->value("name"),
                "first_name" => Patients::where("user_id", $user_id)->value("firstName"),
                "last_name" => Patients::where("user_id", $user_id)->value("lastName")
            ];
            Log::info("transaction Started", $data);

            $payment = new FlutterwavePayment();
            $payment->status = "pending";
            $payment->service_id = $request->get("service_id");
            $payment->user_id = $user_id;
            $payment->unique_id = $request->get("unique_id");
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
        ]);


        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $user_id =  auth('sanctum')->user()->id;
        $transaction_id = $request->get("transaction_id");
        try {
            $paymentData =  $utils->validatePayment($transaction_id);
            $data = [
                "transaction_id" => $request->get("transaction_id"),
                "user_id" => $user_id,
                "first_name" => Patients::where("user_id", $user_id)->value("firstName"),
                "last_name" => Patients::where("user_id", $user_id)->value("lastName"),
                "payment_info" => $paymentData,
            ];
            Log::info("Payment Completed", $data);


            if(empty($paymentData["data"]))
                return $utils->message("error","Invalid Transaction ID." , 401);


            $flutter = new FlutterwavePayment();
            $flutter->user_id = $user_id ;
            $flutter->patient_id = Patients::where("user_id", $user_id)->value("id");
            $flutter->account_id = $paymentData["data"]["account_id"];
            $flutter->amount =  $paymentData["data"]["amount"];
            $flutter->amount_settled =  $paymentData["data"]["amount_settled"];
            $flutter->app_fee =  $paymentData["data"]["app_fee"];
            $flutter->charged_amount =  $paymentData["data"]["charged_amount"];
            $flutter->country =  $paymentData["data"]["card"]["country"];
            $flutter->expiry = $paymentData["data"]["card"]["expiry"];
            $flutter->first_6digits =  $paymentData["data"]["card"]["first_6digits"];
            $flutter->issuer = $paymentData["data"]["card"]["issuer"];
            $flutter->last_4digits = $paymentData["data"]["card"]["last_4digits"];
            $flutter->card_token =  $paymentData["data"]["card"]["token"];
            $flutter->card_type =   $paymentData["data"]["card"]["type"];
            $flutter->email =  $paymentData["data"]["customer"]["email"];
           $flutter->name =  $paymentData["data"]["customer"]["name"];
            $flutter->phone_number =  $paymentData["data"]["customer"]["phone_number"];
            $flutter->flw_ref =  $paymentData["data"]["flw_ref"];
            $flutter->ip =  $paymentData["data"]["ip"];
            $flutter->processor_response =  $paymentData["data"]["processor_response"];
            $flutter->status = $paymentData["data"]["status"];
            $flutter->narration =  $paymentData["data"]["status"];
            $flutter->merchant_fee =  $paymentData["data"]["merchant_fee"];
            $flutter->tx_ref =  $paymentData["data"]["tx_ref"];
            $flutter->service_id = $request->get("service_id");
            $flutter->save();
            Log::info("Flutterwave Completed", $paymentData);


            $booking_start = Carbon::parse($request->get("booking_start"));
            $booking_start_formatted =  $booking_start->format("Y-m-d H:i");
            $booking_end =  $booking_start->copy()->addMinute(45)->format("Y-m-d H:i");

            if(Bookings::whereBetween("session_start", [$booking_start_formatted, $booking_end])->exists())
                return $utils->message("error","The session is already booked." , 400);

            $amount = Services::where("id", $request->get("service_id"))->value("amount");
            $name = Services::where("id", $request->get("service_id"))->value("name");
            $service_id = $request->get("service_id");
            $recipient_id = $request->get("booked_by_id");
            $booking = new Bookings();
            $booking->session_start = $booking_start_formatted;
            $booking->service_id = $service_id;
            $booking->price = $amount;
            $booking->session_end = $booking_end;
            $booking->user_id =  $user_id;
            $booking->booking_for_self = $request->get("booking_for_self");
            $booking->recipient_id = $recipient_id;
            $booking->save();

            $flutterwave_id = FlutterwavePayment::where("unique_id", $request->get("unique_id"))->value("id");
            $this->addPayment($utils, $user_id, $flutterwave_id , $booking->id, $amount, $service_id, $name);
            return $utils->message("success", $booking , 200);

        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    public function addPayment(Utils $utils, $user_id, $trx_id, $booking_id, $amount, $service_id, $name)
    {

        try {
            $trx_id =  Str::random(20);
            if (!Payments::where("trx_id", $trx_id)->exists()){
                $payments = new Payments();
                $payments->user_id = $user_id;
                $payments->merchant_trx_id = $trx_id;
                $payments->booking_id = $booking_id;
                $payments->amount = $amount;
                $payments->name = $name;
                $payments->trx_id = $trx_id;
                $payments->service_id = $service_id;
                $payments->patient_id = Patients::where("user_id", $user_id)->value("id");
                $payments->save();
                return $utils->message("success", $payments , 200);

            }else{
                return $utils->message("error", "Network Error. Please Try Again." , 400);

            }

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
