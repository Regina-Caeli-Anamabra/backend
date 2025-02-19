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
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class BookingController extends Controller
{


    /**
     * @OA\Get (
     *     path="/api/v1/patient/reschedule",
     *     summary="Reschedule",
     *      @OA\Parameter(
     *          name="identity",
     *          in="query",
     *          description="identity",
     *          required=true,
     *          example="83383738383",
     *          @OA\Schema(type="string")
     *      ),
     *
     *      @OA\Parameter(
     *          name="start_time",
     *          in="query",
     *          description="start Time",
     *          required=true,
     *          example="2024-12-02 10:02",
     *          @OA\Schema(type="string")
     *      ),
     *     @OA\Response(response="201", description="Reschedule Booking", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Booking not Found", @OA\JsonContent()),
     *     @OA\Response(response="500", description="Server Error", @OA\JsonContent()),
     *     @OA\Response(response="422", description="Validation Error", @OA\JsonContent()),
     *
     * )
     * **/
    public function reScheduleBooking(Request $request, Utils $utils)
    {

        $request->validate([
            "identity" => "required|string",
            "start_time" => "required|string"
        ]);

        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $user_id =  auth('sanctum')->user()->id;


        try {
            $payment_id = $request->get("payment_id");
            $booking_start = Carbon::parse($request->get("booking_start"));
            $booking_start_formatted =  $booking_start->format("Y-m-d H:i");
            $booking_end =  $booking_start->copy()->addMinute(45)->format("Y-m-d H:i");

//          if(Bookings::whereBetween("session_start", [$booking_start_formatted, $booking_end])->exists())
//                return $utils->message("error","The session is already booked." , 400);

            $identity = $request->get("identity");
            $data = [
                "transaction_id" => $request->get("transaction_id"),
                "user_id" => $user_id,
                "first_name" => Patients::where("user_id", $user_id)->value("firstName"),
                "last_name" => Patients::where("user_id", $user_id)->value("lastName")
            ]; #######
            Log::info("Rescheduling...", $data);

            $name = Services::where("id", $request->get("service_id"))->value("name");
            $former_booking = Bookings::where("identity", $identity)->firstOrFail();
            Bookings::where("identity", $identity)->update(["status", 1]);

            $booking = new Bookings();
            $booking->flutterwave_id = $former_booking->flutterwave_id;
            $booking->session_start = $former_booking->session_start;
            $booking->service_id = $former_booking->service_id;
            $booking->identity = Utils::generateCode("bookings");
            $booking->price = $former_booking->price;
            $booking->session_end = $booking_end;
            $booking->user_id = $user_id;
            $booking->booking_for_self = 0;
            $booking->recipient_id = $user_id;
            $booking->reschedule_for = $former_booking->reschedule_for;
            $booking->status = 2;
            $booking->save();

            return $utils->message("success", $booking, 200);

        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }


    /**
     * @OA\Get (
     *     path="/api/v1/patient/cancel-booking",
     *     summary="Get States",
     *      @OA\Parameter(
     *          name="identity",
     *          in="query",
     *          description="identity",
     *          required=true,
     *          example="83383738383",
     *          @OA\Schema(type="string")
     *      ),
     *     @OA\Response(response="201", description="Cancel Booking [ 0 = Active, 1 = Cancelled]", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Booking not Found", @OA\JsonContent()),
     *     @OA\Response(response="500", description="Server Error", @OA\JsonContent()),
     *     @OA\Response(response="422", description="Validation Error", @OA\JsonContent()),
     *
     * )
     * **/
    public function cancelAppointment(Request $request, Utils $utils)
    {

        try {
            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

            $user_id =  auth('sanctum')->user()->id;
            $identity = $request->get("identity");

            $booking = Bookings::where("identity", $identity)->first();
            $booking->status = 1;
            $booking->update();

            return $utils->message("success", "Cancelled Successfully...", 200);
        }catch (\Exception $exception){
            return $utils->message("error",$exception->getMessage(), 401);
        }
    }


    /**
     * @OA\Get (
     *     path="/api/v1/patient/get-payment",
     *     summary="Get States",
     *      @OA\Parameter(
     *          name="identity",
     *          in="query",
     *          description="identity",
     *          required=true,
     *          example="83383738383",
     *          @OA\Schema(type="string")
     *      ),
     *     @OA\Response(response="201", description="Get Payment", @OA\JsonContent()),
     *     @OA\Response(response="404", description="States not Found", @OA\JsonContent()),
     *     @OA\Response(response="500", description="Server Error", @OA\JsonContent()),
     *     @OA\Response(response="422", description="Validation Error", @OA\JsonContent()),
     *
     * )
     * **/
    public function getPayment(Request $request, Utils $utils)
    {

        try {
            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

            $user_id =  auth('sanctum')->user()->id;
            $identity = $request->get("identity");

            $payments = Payments::where("identity", $identity)->get();

            return $utils->message("success", $payments, 200);
        }catch (\Exception $exception){
            return $utils->message("error",$exception->getMessage(), 401);
        }


    }
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
            $nextAppointment = Bookings::orderBy("id","DESC")->where("user_id",$user_id)->limit(1)->get();

            return $utils->message("success", $nextAppointment, 200);

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
     * @OA\Get (
     *     path="/api/v1/patient/generate-url",
     *      tags={"Booking"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *      @OA\Parameter(
     *          name="service_id",
     *          in="query",
     *          description="integer",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="amount",
     *          in="query",
     *          description="amount",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="interval",
     *          in="query",
     *          description="interval",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *     @OA\Response(response="200", description="Booking successful", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Code Not Found", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized Access", @OA\JsonContent()),
     *     @OA\Response(response="400", description="Booking already exists", @OA\JsonContent())
     * )
     */
    public function generateUrl(Request $request, Utils $utils)
    {
        try {

            $request->validate([
                "service_id" => "required|int",
                "amount" => "required|int"
            ]);

            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

             $user_id =  auth('sanctum')->user()->id;
             $service_id = $request->get("service_id");
             $amount = $request->get("amount");

//            $trx_id = 5804669; ###########################
            $trx_id =  $utils->generateCode(20);
            $logged_data = [
                "trx_id" => $trx_id,
                "service_id" => $service_id,
                "user_id" => $user_id,
                "service" => Services::where("id", $service_id)->value("service_name"),
                "first_name" => Patients::where("user_id", $user_id)->value("firstName"),
                "last_name" => Patients::where("user_id", $user_id)->value("lastName")
            ];
            Log::info("transaction Started", $logged_data);
             Patients::where('user_id', $user_id)->first();
            $payment = new FlutterwavePayment();
            $payment->status = "pending";
            $payment->service_id = $service_id;
            $payment->user_id = $user_id;
            $payment->amount = $amount;
            $payment->trx_id = $trx_id;
            $payment->patient_id =  Patients::where('user_id', $user_id)->first()->id;
            $payment->status = "pending";
            $payment->save();

            // Generate a signed URL
            $signedUrl = URL::signedRoute('flutterwave.callback', [
                'trx_id' => $trx_id,
                'user_id' => $user_id,
                "service_id" => $service_id
            ]);

            return $utils->message("success", ["url"  => $signedUrl, "payment_id" => $payment->id,  'trx_id' => $trx_id] , 200);

        }catch (\Throwable $e) {
        // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/patient/reschedule",
     *      tags={"Booking"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *     @OA\Parameter(
     *         name="identity",
     *         in="query",
     *         description="identity",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="booking_start",
     *         in="query",
     *         description="2024-04-29 18:00:00",
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
     *         name="interval",
     *         in="query",
     *         description="interval",
     *         @OA\Schema(type="integer")
     *     ),
     *      @OA\Parameter(
     *          name="booking_type",
     *          in="query",
     *          description="New or Reschedule",
     *          @OA\Schema(type="string")
     *      ),
     *     @OA\Response(response="200", description="Booking successful", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Code Not Found", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized Access", @OA\JsonContent()),
     *     @OA\Response(response="400", description="Booking already exists", @OA\JsonContent())
     * )
     */
    public function reschedule(Request $request, Utils $utils)
    {

        $request->validate([
            "booking_start" => "required",
            "service_id" => "required|int",
            "interval" => "required|int",
            "identity" => "required|int"
        ]);


        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $user_id =  auth('sanctum')->user()->id;
        try {
            $interval = $request->get("interval");
            $payment_id = $request->get("payment_id");
            $booking_start = Carbon::parse($request->get("booking_start"));
            $booking_start_formatted =  $booking_start->format("Y-m-d H:i");
            $booking_end =  $booking_start->copy()->addMinute($interval)->format("Y-m-d H:i");

//                if(Bookings::whereBetween("session_start", [$booking_start_formatted, $booking_end])->exists())
//                    return $utils->message("error","The session is already booked." , 400);

            $amount = Services::where("id", $request->get("service_id"))->value("amount");
            $name = Services::where("id", $request->get("service_id"))->value("name");
            $service_id = $request->get("service_id");
            $recipient_id = $request->get("booked_by_id");
            $appointment_type = $request->get("booking_type");


            $booking = new Bookings();
            $booking->flutterwave_id = $payment_id;
            $booking->session_start = $booking_start_formatted;
            $booking->service_id = $service_id;
            $booking->identity = Utils::generateCode("bookings");
            $booking->price = $amount;
            $booking->session_end = $booking_end;
            $booking->user_id = $user_id;
            $booking->booking_for_self = $request->get("booking_for_self");
            $booking->recipient_id = $recipient_id;
            $booking->appointment_type = $appointment_type;
            $booking->save();

            return $utils->message("success", $booking, 200);

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
     *         name="payment_id",
     *         in="query",
     *         description="Payment id",
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
     *         name="booking_type",
     *         in="query",
     *         description="New or Reschedule",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="trx_id",
     *         in="query",
     *         description="trx_id",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="interval",
     *         in="query",
     *         description="interval",
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
            "booking_type" => "required",
            "service_id" => "required|int",
            "booking_for_self" => "required|int",
            "interval" => "required|int"
        ]);


        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $user_id =  auth('sanctum')->user()->id;
        try {
            $interval = $request->get("interval");
            $payment_id = $request->get("payment_id");
            $booking_start = Carbon::parse($request->get("booking_start"));
            $booking_start_formatted =  $booking_start->format("Y-m-d H:i");
            $booking_end =  $booking_start->copy()->addMinute($interval)->format("Y-m-d H:i");

//                if(Bookings::whereBetween("session_start", [$booking_start_formatted, $booking_end])->exists())
//                    return $utils->message("error","The session is already booked." , 400);

            $amount = Services::where("id", $request->get("service_id"))->value("amount");
            $name = Services::where("id", $request->get("service_id"))->value("name");
            $service_id = $request->get("service_id");
            $recipient_id = $request->get("booked_by_id");
            $appointment_type = $request->get("booking_type");


            if ($appointment_type== "New") {
                $transaction_id = $request->get("trx_id");
                $paymentData = $utils->validatePayment($transaction_id);
                $data = [
                    "transaction_id" => $request->get("transaction_id"),
                    "user_id" => $user_id,
                    "first_name" => Patients::where("user_id", $user_id)->value("firstName"),
                    "last_name" => Patients::where("user_id", $user_id)->value("lastName"),
                    "payment_info" => $paymentData,
                ]; #######
                Log::info("Payment Completed", $data);
                if (empty($paymentData["data"]))
                    return $utils->message("error", "Invalid Transaction ID.", 400);

                if ($paymentData["data"]["status"] == "successful") {

                    $flutter = FlutterwavePayment::where("id", $payment_id)->firstOrFail();
                    $flutter->user_id = $user_id;
                    $flutter->patient_id = Patients::where("user_id", $user_id)->first()->id;
                    $flutter->trx_id = $transaction_id;
                    $flutter->patient_id = Patients::where("user_id", $user_id)->value("id");
                    $flutter->account_id = $paymentData["data"]["account_id"];
                    $flutter->amount = $paymentData["data"]["amount"];
                    $flutter->amount_settled = $paymentData["data"]["amount_settled"];
                    $flutter->app_fee = $paymentData["data"]["app_fee"];
                    $flutter->charged_amount = $paymentData["data"]["charged_amount"];
                    $flutter->country = $paymentData["data"]["card"]["country"];
                    $flutter->expiry = $paymentData["data"]["card"]["expiry"];
                    $flutter->first_6digits = $paymentData["data"]["card"]["first_6digits"];
                    $flutter->issuer = $paymentData["data"]["card"]["issuer"];
                    $flutter->last_4digits = $paymentData["data"]["card"]["last_4digits"];
                    $flutter->card_token = $paymentData["data"]["card"]["token"];
                    $flutter->card_type = $paymentData["data"]["card"]["type"];
                    $flutter->email = $paymentData["data"]["customer"]["email"];
                    $flutter->name = $paymentData["data"]["customer"]["name"];
                    $flutter->phone_number = $paymentData["data"]["customer"]["phone_number"];
                    $flutter->flw_ref = $paymentData["data"]["flw_ref"];
                    $flutter->ip = $paymentData["data"]["ip"];
                    $flutter->processor_response = $paymentData["data"]["processor_response"];
                    $flutter->status = $paymentData["data"]["status"];
                    $flutter->narration = $paymentData["data"]["status"];
                    $flutter->merchant_fee = $paymentData["data"]["merchant_fee"];
                    $flutter->tx_ref = $paymentData["data"]["tx_ref"];
                    $flutter->service_id = $request->get("service_id");
                    $flutter->update();

                    Log::info("Flutterwave Completed", $paymentData);


                    $booking = new Bookings();
                    $booking->flutterwave_id = $payment_id;
                    $booking->session_start = $booking_start_formatted;
                    $booking->service_id = $service_id;
                    $booking->identity = Utils::generateCode("bookings");
                    $booking->price = $amount;
                    $booking->session_end = $booking_end;
                    $booking->user_id = $user_id;
                    $booking->booking_for_self = $request->get("booking_for_self");
                    $booking->recipient_id = $recipient_id;
                    $booking->appointment_type = $appointment_type;
                    $booking->save();
                    $id_from_payment = $this->addPayment($utils, $user_id, $payment_id, $booking->id, $amount, $service_id, $name);


                    return $utils->message("success", $booking, 200);
                }
            }else{

                $booking = new Bookings();
                $booking->flutterwave_id = $payment_id;
                $booking->session_start = $booking_start_formatted;
                $booking->service_id = $service_id;
                $booking->identity = Utils::generateCode("bookings");
                $booking->price = $amount;
                $booking->session_end = $booking_end;
                $booking->user_id = $user_id;
                $booking->booking_for_self = $request->get("booking_for_self");
                $booking->recipient_id = $recipient_id;
                $booking->appointment_type = $appointment_type;
                $booking->save();
            }
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }

    public function addPayment(Utils $utils, $user_id, $payment_id, $booking_id, $amount, $service_id, $name)
    {

        try {
                $identity =  Utils::generateCode("user");
                $payments = new Payments();
                $payments->user_id = $user_id;
                $payments->flutterwave_id = $payment_id;
                $payments->booking_id = $booking_id;
                $payments->amount = $amount;
                $payments->name = $name;
                $payments->identiy = $identity;
                $payments->service_id = $service_id;
                $payments->patient_id = Patients::where("user_id", $user_id)->value("id");
                $payments->save();
                return $payments->id;


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
