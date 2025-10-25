<?php

namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PaymentsResource;
use App\Http\Resources\SearchBookingResource;
use App\Http\Resources\SearchPatientResource;
use App\Models\Bookings;
use App\Models\FlutterwavePayment;
use App\Models\OfflineOnlinePatientsSync;
use App\Models\PatientDontUse;
use App\Models\PatientFromHospital;
use App\Models\Patients;
use App\Models\Payments;
use App\Models\ServiceChargeFlutterwavePayments;
use App\Models\Services;
use App\Models\User;
use App\Utils\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\Concerns\Has;
use Mockery\Exception;

class PatientController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/complete-service-charge-payments",
     *     summary="Complete service charge payment",
     *     tags={"Patients"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id", "trx_id", "payment_identity"},
     *             @OA\Property(
     *                 property="payment_identity",
     *                 type="string",
     *                 example="30601745065990641745065990189454",
     *                 description="Patient ID"
     *             ),
     *             @OA\Property(
     *                 property="trx_id",
     *                 type="string",
     *                 example="1739976680226",
     *                 description="TRX ID"
     *             ),
     *             @OA\Property(
     *                 property="patient_id",
     *                 type="string",
     *                 example="94901/03/24",
     *                 description="Payment ID"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment completed successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent()
     *     )
     * )
     */

    public function CompleteServiceChargePayment(Request $request, Utils $utils)
    {
        $request->validate([
            "payment_identity" => "required",
            "trx_id" => "required",
            "patient_id" => "required",
        ]);


        if (!ServiceChargeFlutterwavePayments::where("identity", $request->get("payment_identity"))->exists())
            return $utils->message("error", "Payment Not Found" , 404);

        if (!User::where("reg_id", $request->input("patient_id"))->exists())
            return $utils->message("Error", "User Not Found." , 404);


        $patient = $request->get("patient_id");
        $trx_id = $request->get("trx_id");
        $payment_id = $request->get("payment_identity");

         $user = User::where("reg_id", $patient)->first();
        $paymentData = $utils->validatePayment($trx_id);

        if ($paymentData["data"]["status"] == "successful") {
            try {
                $patients = Patients::where("user_id", $user->id)->first();
                $dbSave =  DB::transaction(function () use ($utils, $paymentData, $payment_id, $trx_id, $user, $patients) {
                    $flutter = ServiceChargeFlutterwavePayments::where("identity", $payment_id)->firstOrFail();
                    $flutter->user_id = $user->id;
                    $flutter->patient_id = $patients->id;
                    $flutter->trx_id = $trx_id;
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
                    $flutter->update();


                    $user = User::where("id", $user->id)->first();
                    $user->paid = 1;
                    $user->save();

                    return true;

                });

                if ($dbSave)
                    return $utils->message("Success", "Payment Completed Successfully." , 200);

                return $utils->message("error", "Server Error" , 400);


            }catch (Exception $e){
                return $utils->message("error", $e->getMessage() , 400);
            }
        }


    }


    /**
     * @OA\Post(
     *     path="/api/v1/cancel-service-charge-payments",
     *     summary="Cancel service charge payment",
     *     tags={"Patients"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id"},
     *             @OA\Property(
     *                 property="patient_id",
     *                 type="string",
     *                 example="94901/03/24",
     *                 description="Patient ID"
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Create Password",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent()
     *     )
     * )
     */

    public function cancelServiceChargePayment(Request $request, Utils $utils)
    {
        $request->validate([
            "patient_id" => "required"
        ]);

        if (!User::where("reg_id", $request->input("patient_id"))->exists())
            return $utils->message("Error", "Patient Not Found." , 404);

        $user = User::where("reg_id", $request->input("patient_id"))->first();
        $transaction_id = $request->get('trx_id');

        $trx_id =  $utils->generateCode(20);

        $payment = ServiceChargeFlutterwavePayments::where("identity", $transaction_id)->firstOrFail();
        $payment->status = "Cancelled";
        $payment->user_id = $user->id;
        $payment->amount = 1500;
        $payment->identity = $utils->generateCramp("service_payments");
        $payment->patient_id =  Patients::where('user_id', $user->id)->first()->id;
        $payment->status = "pending";
        $payment->save();

        return $utils->message("Success", $payment , 200);

    }

    /**
     * @OA\Post(
     *     path="/api/v1/initiate-service-charge-payments",
     *     summary="Initiate service charge payment",
     *     tags={"Patients"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"patient_id"},
     *             @OA\Property(
     *                 property="patient_id",
     *                 type="string",
     *                 example="94901/03/24",
     *                 description="Patient ID"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Create Password",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent()
     *     )
     * )
     */

    public function initiateServiceChargePayment(Request $request, Utils $utils)
    {
        $request->validate([
            "patient_id" => "required"
        ]);

        $patient_id =  $request->input("patient_id");
        return 4848;

        if (!PatientDontUse::where("patient_id", $patient_id)->exists())
            return $utils->message("Error", "Patient Not Found." , 404);


        if (!empty($patient) > 0){
            if(PatientDontUse::where("patient_id",$patient_id)->exists()){
            return    $oldPatients = PatientDontUse::where("patient_id",$patient_id)->firstOrFail();

                $user = new User();
                $user->reg_id = $patient_id;
                $user->phone = $oldPatients->phone_no;
                $user->email = $oldPatients->email;
                $user->save();

                $offlineOnlinePatientSync = new OfflineOnlinePatientsSync();
                $offlineOnlinePatientSync->firstName = $oldPatients->firstName;
                $offlineOnlinePatientSync->lastName = $oldPatients->glastName;
                $offlineOnlinePatientSync->user_id = $user->id;
                $offlineOnlinePatientSync->phone =  $oldPatients->phone_no;
                $offlineOnlinePatientSync->date_of_birth = $oldPatients->dateOfBirth;
                $offlineOnlinePatientSync->gender = $oldPatients->gender;
                $offlineOnlinePatientSync->nature_of_relationship = $oldPatients->next_of_kin_relationship;
                $offlineOnlinePatientSync->marital_status = $oldPatients->marital_status;
                $offlineOnlinePatientSync->religion = $oldPatients->ethnic;
                $offlineOnlinePatientSync->nationality = $oldPatients->nationality;
                $offlineOnlinePatientSync->next_of_kin = $oldPatients->next_of_kin;
                $offlineOnlinePatientSync->next_of_kin_phone = $oldPatients->next_of_kin_phoneno;
                $offlineOnlinePatientSync->state_of_residence = $oldPatients->state_of_residence;
                $offlineOnlinePatientSync->address_of_residence = $oldPatients->permanent_address;
                $offlineOnlinePatientSync->patient_id = $oldPatients->id;
                $offlineOnlinePatientSync->place = "offline";
                $offlineOnlinePatientSync->save();
            }

        }else{
            $patient = Patients::where("patient_id", $patient_id)->first();
            $user = User::findOrFail($patient->user_id);
        }

        $trx_id =  $utils->generateCode(20);
        $payment = new ServiceChargeFlutterwavePayments();
        $payment->status = "Pending";
        $payment->amount = 1500;
        $payment->identity = $utils->generateCramp("service_payments");
        $payment->user_id =  $user->id;
        $payment->save();

        return $utils->message("Success", $payment , 200);

    }


    /**
     * @OA\Post(
     *     path="/api/v1/create-password",
     *     summary="Create Password",
     *     tags={"Patients"},
     *     @OA\Parameter(
     *         name="patient_id",
     *         in="query",
     *         description="Patient ID",
     *         example="94901/03/24",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="Password",
     *         example="sam12345",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="password_confirmation",
     *         in="query",
     *         description="Password confirmation",
     *         example="sam12345",
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

        if (!PatientDontUse::where("patient_id", $request->get("patient_id"))->exists())
            return $utils->message("error", "Patient Not Found" , 404);

        $patientDontUse = PatientDontUse::where("patient_id", $request->get("patient_id"))->first();

        $users = new User();
        $users->reg_id = $patientDontUse->patient_id;
        $users->phone  = $patientDontUse->phone_no;
        $users->email  = $patientDontUse->email;
        $users->password  = Hash::make($request->get("password"));
        $users->save();

        $patient =  User::where("reg_id", $request->get("patient_id"))
                    ->update([
                        "password" => Hash::make($request->get("password"))
                    ]);

        return $utils->message("success", "Password Updated Successfully." , 200);

    }


//    public function createPassword(Request $request, Utils $utils)
//    {
//        $request->validate([
//            "patient_id" => "required|string",
//            "password" => "required|string|min:8|confirmed"
//        ], [
//            'password.confirmed' => 'The password confirmation does not match.',
//        ]);
//
//        if (!User::where("reg_id", $request->get("patient_id"))->exists())
//            return $utils->message("error", "Patient Not Found" , 404);
//
//        $patient =  User::where("reg_id", $request->get("patient_id"))
//                    ->update([
//                        "password" => Hash::make($request->get("password"))
//                    ]);
//
//        return $utils->message("success", "Password Updated Successfully." , 200);
//
//    }

    /**
     * @OA\Post(
     *     path="/api/v1/get-details",
     *     summary="Get Patient Details",
     *     tags={"Patients"},
     *     @OA\Parameter(
     *         name="patient_id",
     *         in="query",
     *         description="patient_id",
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

        $patient_id = $request->get("patient_id");

        if (!PatientDontUse::where("patient_id", $patient_id )->exists())
            return $utils->message("error", "Patient does not exist" , 400);

        $patient = PatientDontUse::where("patient_id", $patient_id)->get();

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
                $patient->phone = $phone;
                $patient->date_of_birth = $request->get("dateOfBirth");
                $patient->nature_of_relationship = $request->get("next_of_kin_relationship");
                $patient->marital_status = $request->get("marital_status");
                $patient->next_of_kin = $request->get("next_of_kin");
//                $patient->state_of_origin = $request->get("state_of_Origin");
                $patient->next_of_kin_phone = $request->get("next_of_kin_phoneno");
                $patient->address_of_next_of_kin = $request->get("next_of_kin_address");
                $patient->state_of_residence = $request->get("state_of_Residence");
//                $patient->address_of_residence = $request->get("address");

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
                $query->where("phone_no", 'like', "%{$search_item}%")
                    ->orWhere("firstName", 'like', "%{$search_item}%")
                    ->orWhere("lastName", 'like', "%{$search_item}%")
                    ->orWhere("middleName", 'like', "%{$search_item}%")
                    ->orWhere("system_id", 'like', "%{$search_item}%")
                    ->orWhere("patient_id", 'like', "%{$search_item}%")
                    ->orWhereRaw("CONCAT(firstName, ' ', lastName) LIKE ?", ["%{$search_item}%"]);
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

            $patient = FlutterwavePayment::with(["patients", "services"])->whereHas("patients")->get();
             PaymentResource::collection($patient);
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
     *      @OA\Parameter(
     *          name="phone",
     *          in="query",
     *          required=true,
     *          description="phone",
     *          @OA\Schema(type="string")
     *      ),
     *     @OA\Response(response="200", description="Registration successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Invalid credentials", @OA\JsonContent()),
     *     @OA\Response(response="422", description="validation Error", @OA\JsonContent())
     *
     * )
     */
    public function updateProfile(Request $request, Utils $utils)
    {
        $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'religion' => 'nullable|string|max:50',
            'next_of_kin' => 'required|string|max:100',
            'address_of_next_of_kin' => 'required|string|max:255',
            'state_of_residence' => 'required|string|max:100',
            'address_of_residence' => 'required|string|max:255',
        ]);

        try {

            if(!auth('sanctum')->check())
                return $utils->message("error","Unauthorized Access." , 401);

               $user =  Patients::where("user_id", auth('sanctum')->id())->firstOrFail();
               if($user){

                    $user->firstName = $request->get("first_name");
                    $user->lastName = $request->get("last_name");
                    $user->phone = $request->get("phone");
                    $user->religion = $request->get("religion");
                    $user->next_of_kin = $request->get("next_of_kin");
                    $user->next_of_kin_phone = $request->get("next_of_kin_phone");
                    $user->address_of_next_of_kin = $request->get("address_of_next_of_kin");
                    $user->state_of_residence = $request->get("state_of_residence");
                    $user->address_of_residence = $request->get("address_of_residence");
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
