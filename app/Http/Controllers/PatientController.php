<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Http\Resources\PaymentsResource;
use App\Models\Bookings;
use App\Models\FlutterwavePayment;
use App\Models\Patients;
use App\Models\Payments;
use App\Models\User;
use App\Utils\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\Exception;

class PatientController extends Controller
{
    public function getPayments(Request $request, Utils $utils)
    {
        try {

            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

           $patient = FlutterwavePayment::with(["services", "patients"])->where("status", "successful")->orderBy("created_at", "DESC")->get();
             $data = [
                 "payments" => PaymentResource::collection($patient),
                 "total" => number_format(FlutterwavePayment::sum("amount_settled"), 2)
             ];
            return $utils->message("success",$data , 200);

        }catch (\Exception $exception){
            Log::error($exception->getMessage());
        }
    }

    /**
     * @OA\Get (
     *     path="/api/v1/patient/profile",
     *      tags={"Auth"},
     *       security={
     *            {"sanctum": {}},
     *        },
     *     @OA\Response(response="200", description="Registration successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Invalid credentials", @OA\JsonContent()),
     *     @OA\Response(response="422", description="validation Error", @OA\JsonContent())
     *
     * )
     */
    public function profile(Request $request, Utils $utils)
    {
        try {

            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

            $user_id = auth('sanctum')->id();
            $patient = Patients::with(["user"])->where("user_id", $user_id)->get();
            return $utils->message("success", $patient , 200);

        }catch (\Exception $exception){
            Log::error($exception->getMessage());
        }
    }
    /**
     * @OA\Patch(
     *     path="/api/v1/patient/profile/update",
     *      tags={"Auth"},
     *       security={
     *            {"sanctum": {}},
     *        },
     *     @OA\Parameter(
     *         name="first_name",
     *         in="query",
     *         description="first_name",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="last_name",
     *         in="query",
     *         description="last_name",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="phone",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="email",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="gender",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="marital_status",
     *         in="query",
     *         description="marital_status",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="religion",
     *         in="query",
     *         description="religion",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="nationality",
     *         in="query",
     *         description="nationality",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="next_of_kin",
     *         in="query",
     *         description="next_of_kin",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="next_of_kin_phone",
     *         in="query",
     *         description="next_of_kin_phone",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="nature_of_relationship",
     *         in="query",
     *         description="nature_of_relationship",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date_of_birth",
     *         in="query",
     *         description="date_of_birth",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="state_of_residence",
     *         in="query",
     *         required=true,
     *         description="state_of_residence",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="address_of_residence",
     *         in="query",
     *         required=true,
     *         description="address_of_residence",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="address_of_next_of_kin",
     *         in="query",
     *         required=true,
     *         description="address_of_next_of_kin",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Registration successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Invalid credentials", @OA\JsonContent()),
     *     @OA\Response(response="422", description="validation Error", @OA\JsonContent())
     *
     * )
     */
    public function updateProfile(Request $request, Utils $utils)
    {
        try {

            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

               $user =  Patients::where("user_id", auth('sanctum')->id())->firstOrFail();
               if($user){
                    $user->first_name = $request->get("first_name");
                    $user->last_name = $request->get("last_name");
                    $user->phone = $request->get("phone");
                    $user->gender = $request->get("gender");
                    $user->marital_status = $request->get("marital_status");
                    $user->religion = $request->get("religion");
                    $user->nationality = $request->get("nationality");
                    $user->next_of_kin = $request->get("next_of_kin");
                    $user->next_of_kin_phone = $request->get("next_of_kin_phone");
                    $user->address_of_next_of_kin = $request->get("address_of_next_of_kin");
                    $user->nature_of_relationship = $request->get("nature_of_relationship");
                    $user->date_of_birth = $request->get("date_of_birth");
                    $user->state_of_residence = $request->get("state_of_residence");
                    $user->address_of_residence = $request->get("address_of_residence");
                    $user->save();
               }
//               $user =  Patients::where("user_id", auth('sanctum')->id())->update([
//                            "first_name" => $request->get("first_name"),
//                            "last_name" => $request->get("last_name"),
//                            "phone" => $request->get("phone"),
//                            "gender" => $request->get("gender"),
//                            "marital_status" => $request->get("marital_status"),
//                            "religion" => $request->get("religion"),
//                            "preferred_language" => $request->get("preferred_language"),
//                            "nationality" => $request->get("nationality"),
//                            "state" => $request->get("state"),
//                            "lga" => $request->get("lga"),
//                            "town" => $request->get("town"),
//                            "card_number" => $request->get("card_number"),
//                            "next_of_kin" => $request->get("next_of_kin"),
//                            "next_of_kin_phone" => $request->get("next_of_kin_phone"),
//                            "nature_of_relationship" => $request->get("nature_of_relationship"),
//                            "date_of_birth" => $request->get("date_of_birth"),
//                            "insurance_number" => $request->get("insurance_number"),
//                            "ward" => $request->get("ward"),
//                            "state_of_residence" => $request->get("state_of_residence"),
//                            "address_of_residence" => $request->get("address_of_residence")
//                    ]);

            return $utils->message("success", $user , 200);

        }catch (\Exception $exception){
            Log::error($exception->getMessage());
        }
    }
    /**
     * @OA\Get (
     *     path="/api/v1/patient/get-users-created",
     *      tags={"Patients"},
     *      security={
     *           {"sanctum": {}},
     *       },
     *     @OA\Response(response="200", description="Booking successful", @OA\JsonContent()),
     *     @OA\Response(response="404", description="Code Not Found", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized Access", @OA\JsonContent()),
     *     @OA\Response(response="400", description="Booking already exists", @OA\JsonContent())
     * )
     */
    public function getAllRegisteredByUser(Request $request, Utils $utils)
    {
        try {

            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

            $user_id =  auth('sanctum')->user()->id;

            $users = User::with(["patient" => function ($query) {
                $query->get();
            }])->where("registerer_user_id", $user_id)->get(["username"]);

            return $utils->message("success", $users , 200);

        }catch (Exception $exception){
            Log::error($exception->getMessage());
        }
    }
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
                return $utils->message("success", $payments , 200);

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

}
