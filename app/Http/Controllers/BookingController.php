<?php

namespace App\Http\Controllers;

use App\Models\Bookings;
use App\Models\Donations;
use App\Models\r;
use App\Utils\Utils;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function donation(Request $request, Utils $utils)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/". $request->get("transaction_id")."/verify",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json",
                "Authorization: Bearer FLWSECK_TEST-940432bfbcd581506e354f8597ca89ab-X"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
return        $response = json_decode($response, true);
        return $response["data"]["card"]["country"];


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
            $booking = Bookings::where("user_id", $user_id)->get();
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
     *     path="/api/v1/patient/add-a-session",
     *      tags={"Booking"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *     @OA\Parameter(
     *         name="booking_start",
     *         in="query",
     *         description="booking_start",
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
     *         description="booking_for_self",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="booked_by_id",
     *         in="query",
     *         description="booked_by_id",
     *         @OA\Schema(type="integer")
     *     ),
     *      @OA\Parameter(
     *          name="price",
     *          in="query",
     *          description="price",
     *          @OA\Schema(type="string")
     *      ),
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
            "price" => "required"
        ]);

        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $user_id =  auth('sanctum')->user()->id;
        try {
            $booking_start = Carbon::parse($request->get("booking_start"));
            $booking_start_formatted =  $booking_start->format("Y-m-d H:i:s");
            $booking_end =  $booking_start->copy()->addMinute(45)->format("Y-m-d H:i:s");

            if(Bookings::whereBetween("session_start", [$booking_start_formatted, $booking_end])->exists())
                return $utils->message("error","The session is already booked." , 400);

            $recipient_id = $request->get("booked_by_id");
            $booking = new Bookings();
            $booking->session_start = $booking_start_formatted;
            $booking->service_id = $request->get("service_id");
            $booking->price = $request->get("price");
            $booking->session_end = $booking_end;
            $booking->user_id =  $user_id;
            $booking->booking_for_self = $request->get("booking_for_self");
            $booking->recipient_id = $recipient_id;
            $booking->save();
            return $utils->message("success", $booking , 400);

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
