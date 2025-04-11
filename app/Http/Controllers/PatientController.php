<?php

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PaymentsResource;
use App\Http\Resources\SearchBookingResource;
use App\Http\Resources\SearchPatientResource;
use App\Models\Bookings;
use App\Models\FlutterwavePayment;
use App\Models\Patients;
use App\Models\Payments;
use App\Models\Services;
use App\Models\User;
use App\Utils\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery\Exception;

class PatientController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/v1/create-password",
     *     summary="Create Password",
     *     tags={"Patients"},
     *     @OA\Parameter(
     *         name="patient_id",
     *         in="query",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Password",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="password_confirmation",
     *         in="query",
     *         description="password confirmation",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Create Password", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized", @OA\JsonContent()),
     *     @OA\Response(response="422", description="Validation Error", @OA\JsonContent())
     * )
     */
    public function createPassword(Request $request, Utils $utils)
    {
        $request->validate([
            "patient_id" => "required|string",
            "password" => "required|string|min:8|confirmed"
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        if (!Patients::where("patient_id", $request->get("patient_id"))->exists())
            return $utils->message("error", "Patient Not Found" , 404);

        $patient =  User::where("patient_id", $request->get("patient_id"))
                    ->update([
                        "password" => Hash::make($request->get("password"))
                    ]);

        return $utils->message("success", $patient , 200);

    }

    /**
     * @OA\Post(
     *     path="/api/v1/get-details",
     *     summary="Get Patient Details",
     *     tags={"Patients"},
     *     @OA\Parameter(
     *         name="patient_id",
     *         in="query",
     *         description="Patient ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Patient Details", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized", @OA\JsonContent()),
     *     @OA\Response(response="422", description="Validation Error", @OA\JsonContent())
     * )
     */
    public function getDetails(Request $request, Utils $utils)
    {
        $request->validate([
            "patient_id" => "required|string"
        ]);

        if (!Patients::where("patient_id", $request->get("patient_id"))->exists())
            return $utils->message("error", "Patient Not Found" , 404);

        $patient = Patients::where("patient_id", $request->get("patient_id"))->first();

        return $utils->message("success", $patient , 200);


    }

    public function updatePatient(Request $request, Utils $utils)
    {

        $reg_id = $request->get("reg_id");
        $phone = $request->get("phone_no");
        $user = User::where("reg_id", $reg_id)->first();

        if ($user) {
            $patient = Patients::where("user_id", $user->id)->first();

            if ($patient) {
                $patient->firstName = $request->get("firstName");
                $patient->lastName = $request->get("lastName");
                $patient->middleName = $request->get("middleName");
                $patient->phone_no = $phone;
                $patient->dateOfBirth = $request->get("dateOfBirth");
                $patient->next_of_kin_relationship = $request->get("next_of_kin_relationship");
                $patient->marital_status = $request->get("marital_status");
                $patient->next_of_kin = $request->get("next_of_kin");
                $patient->state_of_origin = $request->get("state_of_Origin");
                $patient->next_of_kin_phoneno = $request->get("next_of_kin_phoneno");
                $patient->next_of_kin_address = $request->get("next_of_kin_address");
                $patient->state_of_residence = $request->get("state_of_Residence");
                $patient->permanent_address = $request->get("address");
                $patient->title = $request->get("title");

                $patient->update();

                return $utils->message("error", $patient , 200);
            } else {
                return $utils->message("error", "Patient Not Found" , 400);
            }
        } else {
            return $utils->message("error", "User Not Found" , 404);
        }


    }
    public function getPatient(Request $request, Utils $utils)
    {
        $user =  User::where("reg_id", $request->input('reg_id'))->first();
        $patient = Patients::where("user_id", $user->id)->first();
        return $utils->message("success",$patient , 200);

    }
    public function updateService(Request $request, Utils $utils)
    {
        $identity = $request->get("identity");
        $name = $request->get("name");
        $amount = $request->get("amount");

        $services = Services::where("identity", $identity)->firstOrFail();
        $services->service_name = $name;
        $services->service_fee = $amount;
        $services->update();
        return $utils->message("success",$services , 200);
    }
    public function getService($identity, Request $request, Utils $utils)
    {
        try {
            $services = Services::where("identity", $identity)->firstOrFail();

            return $utils->message("success", $services  , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
    public function searchPatient(Request $request, Utils $utils)
    {

        $search_item = $request->get("search_item");
        try {
            $patients = Patients::where(function ($query) use ($search_item) {
                $query->where("phone_no", 'like', "%{$search_item}%");
                $query->orWhere("firstName", 'like', "%{$search_item}%");
                $query->orWhere("lastName", 'like', "%{$search_item}%");
                $query->orWhere("middleName", 'like', "%{$search_item}%");
                $query->orWhere("system_id", 'like', "%{$search_item}%");
                $query->orWhere("patient_id", 'like', "%{$search_item}%");
            })->get();
            $patients = SearchPatientResource::collection($patients);
            return $utils->message("success", $patients  , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
    public function searchBooking(Request $request, Utils $utils)
    {

        $search_item = $request->get("search_item");
        try {
            $patients = Bookings::with("patient")->orWhereHas('patient', function ($query) use ($search_item) {
                $query->where("phone_no", 'like', "%{$search_item}%");
                $query->orWhere("firstName", 'like', "%{$search_item}%");
                $query->orWhere("lastName", 'like', "%{$search_item}%");
                $query->orWhere("middleName", 'like', "%{$search_item}%");
                $query->orWhere("system_id", 'like', "%{$search_item}%");
                $query->orWhere("patient_id", 'like', "%{$search_item}%");
            })->get();
            $patients = SearchBookingResource::collection($patients);
            return $utils->message("success", $patients  , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
    public function getPayments(Request $request, Utils $utils)
    {
        try {

            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);
            $patient = FlutterwavePayment::with(["patients", "services"])->get();
             PaymentResource::collection($patient);
             $data = [
                 "payments" => PaymentResource::collection($patient),
                 "total" => number_format(Payments::sum("amount"), 2)
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
     *         name="email",
     *         in="query",
     *         description="email",
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

                    $user->firstName = $request->get("first_name");
                    $user->lastName = $request->get("last_name");
                    $user->ethnic = $request->get("religion");
                    $user->next_of_kin = $request->get("next_of_kin");
                    $user->next_of_kin_phoneno = $request->get("next_of_kin_phone");
//                    $user->address_of_next_of_kin = $request->get("address_of_next_of_kin");
//                    $user->state_of_residence = $request->get("state_of_residence");
//                    $user->address = $request->get("address_of_residence");
                    $user->update();
               }
            return $utils->message("success", "User updated successfully.." , 200);

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
