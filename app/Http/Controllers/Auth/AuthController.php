<?php

namespace App\Http\Controllers\Auth;

use App\Execs\Execs;
use App\Models\User;
use App\Utils\Utils;
use App\Utils\CurlGet;
use App\Models\Account;
use App\Utils\CurlPost;
use App\Models\Patients;
use App\Enums\TokenAbility;
use App\Models\NewCustomer;
use Illuminate\Support\Str;
use App\Mail\VerifyCodeMail;
use Illuminate\Http\Request;
use mysql_xdevapi\Exception;
use Illuminate\Http\Response;
use App\Mail\VerificationMail;
use App\Models\PatientDontUse;
use Illuminate\Support\Carbon;
use App\Mail\PasswordCodeEmail;
use App\Mail\PasswordResetMail;
use App\Http\Resources\Customer;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Models\OfflineOnlinePatientsSync;
use Illuminate\Testing\Fluent\Concerns\Has;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AuthController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/v1/resend-email",
     *      tags={"Auth"},
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="email",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Verification successful"),
     *     @OA\Response(response="404", description="Code Not Found")
     * )
     */
    public function resendEmail(Request $request, Utils $utils)
    {

        $request->validate([
            "email" => "required",
        ]);

        try{
            $verifyCode = $utils->generateKey();
            $email = $request->get('email');

            $user = User::where("email", $email)->firstOrFail();
            $user->vCode = $verifyCode;
            $user->save();
            $data = [
                "code" => $verifyCode
            ];
            Mail::to($email)->send(new VerificationMail($data));
            return $utils->message("success","Verification sent Successfully", 200);

        }catch (Exception $e){
            return $utils->message("error", $e->getMessage() , 200);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/v1/resend-sms",
     *      tags={"Auth"},
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="phone",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="send sms"),
     *     @OA\Response(response="404", description="Code Not Found")
     * )
     */
    public function sendSMS(Request $request, Utils $utils)
    {
        $request->validate([
            "phone" => "required"
        ]);

        $response = $utils->sendOTPToSMS($request->get("phone"));
        return $utils->message("success",["msg" => "Verification code sent Successfully", "response" => $response] , 200);

    }

    /**
     * @OA\Post(
     *     path="/api/v1/verify-password-reset-code",
     *      tags={"Auth"},
     *     @OA\Parameter(
     *         name="options",
     *         in="query",
     *         description="email or phone",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Verification successful"),
     *     @OA\Response(response="404", description="Code Not Found")
     * )
     */
    public function verifyPasswordCode(Request $request, Utils $utils)
    {

        $request->validate([
            "code" => "required|string",
            "options" => "required|string"
        ]);

        $options = $request->get("options");
        if(!User::where(function ($query) use ($options){
            $query->where("email", $options);
            $query->orWhere("phone", $options);
        })->where("password_reset_code", $request->get("code"))->exists())
            return $utils->message("error", "Code Does Not Exist", 404);

        return $utils->message("success","Verification Successful.", 200);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/recover-reg-id",
     *      tags={"Auth"},
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="phone",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Verification successful"),
     *     @OA\Response(response="404", description="Code Not Found")
     * )
     */
    public function recoverRegId(Request $request, Utils $utils)
    {

        $request->validate([
            "phone" => "required|string"
        ]);

        $phone = $request->get("phone");
        $phone_with_carrier = "+234". $phone;
        if (strpos($phone, '0') === 0) {
            $phone_without_zero = preg_replace('/^0/', '', $phone);
        }else{
            $phone_without_zero = $phone;
        }

//        if (Patients::where("phone", $phone)->exists()){

//            $patients = Patients::where("phone", $phone)->get();
//            return $utils->message("success",$patients, 200);
//            $patient = Patients::where("phone", $phone)->first();
//            $url = 'https://portal.nigeriabulksms.com/api/?username='. env("SMS_USERNAME").'&password=' . env("SMS_PASSWORD"). '&message=' .  $patient->reg_id .' your Regina Ceali Reg ID. &sender=' . env("SMS_SENDER"). '&mobiles=' .$phone;

            // Send the POST request
//            $response = Http::get($url);
//        }else
            if (PatientDontUse::whereIn('phone_no', [
                $phone,
                $phone_with_carrier,
                $phone_without_zero
            ])->exists()
            ){

            $patients = PatientDontUse::where('phone_no', $phone)->get();

            if ($patients->isEmpty()) {

                // Try phone without zero
                $patients = PatientDontUse::where('phone_no', $phone_without_zero)->get();

                if ($patients->isEmpty()) {

                    // Try phone with carrier
                    $patients = PatientDontUse::where('phone_no', $phone_with_carrier)->get();
                }
            }


            return $utils->message("success",$patients, 200);

//            $url = 'https://portal.nigeriabulksms.com/api/?username='. env("SMS_USERNAME").'&password=' . env("SMS_PASSWORD"). '&message=' .  $patient->patient_id .' is your Regina Ceali Reg ID. &sender=' . env("SMS_SENDER"). '&mobiles=' .$phone;
//            $response = Http::get($url);
        }else{
            return $utils->message("error","Patient Not Found.", 404);

        }

        return $utils->message("success","You will receive a text message if your phone is on our database.", 200);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/send-forgot-password-code",
     *      tags={"Auth"},
     *     @OA\Parameter(
     *         name="options",
     *         in="query",
     *         description="email or phone",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="auth_type",
     *         in="query",
     *         description="EMAIL or SMS",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Registration successful"),
     *     @OA\Response(response="401", description="Invalid credentials")
     * )
     */
    public function forgotPassword(Request $request, Utils $utils)
    {

        $request->validate([
            "options" => "required|string",
            "auth_type" => "required|string"
        ]);

        $auth_type = $request->get("auth_type");
        $options = $request->get("options");

        if (!User::where(function ($query) use ($options){
            $query->where("email", $options);
            $query->orWhere("phone", $options);
        })->exists())
            return $utils->message("error", "User Not Found", 404);


        $password_reset_code = random_int(100000, 999999);
        User::where(function ($query) use ($options){
            $query->where("email", $options);
            $query->orWhere("phone", $options);
        })->update(["password_reset_code" => $password_reset_code]);

        $mailData = [
            'title' => 'Reset your password',
            'code' => $password_reset_code
        ];


        if ($auth_type == "EMAIL"){
            Mail::to($options)->send(new PasswordResetMail($mailData));
            return $utils->message("success", "OTP Sent. Check your mailbox or phone", 200);
        }else{
            $utils->sendOTPToSMS($options, $password_reset_code);
            return $utils->message("success", "OTP Sent. Check your mailbox or phone", 200);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/verify-code",
     *      tags={"Auth"},
     *     @OA\Parameter(
     *         name="options",
     *         in="query",
     *         description="email or phone",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Verification successful"),
     *     @OA\Response(response="404", description="Code Not Found")
     * )
     */
    public function verifyCode(Request $request, Utils $utils)
    {
        $request->validate([
            "code" => "required",
            "options" => "required|string"
        ]);
//
        if(!User::where(function ($query) use ($request){
            $query->where("email", $request->get("options"));
            $query->orWhere("phone", $request->get("options"));
        })->where("vCode", $request->get("code"))->exists())
            return $utils->message("error", "Code Does Not Exist", 404);

        $user = User::where(function ($query) use ($request){
            $query->where("email", $request->get("options"));
            $query->orWhere("phone", $request->get("options"));
        })->firstOrFail();
        $user->verified = 1;
        $user->update();
        return $utils->message("success","Verification Successful.", 200);
    }


    /**
     * @OA\Patch(
     *     path="/api/v1/password/update",
     *      tags={"Auth"},
     *     @OA\Parameter(
     *         name="options",
     *         in="query",
     *         description="options",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="password",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="confirm_password",
     *         in="query",
     *         description="confirm_password",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Registration successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Invalid credentials", @OA\JsonContent()),
     *     @OA\Response(response="422", description="validation Error", @OA\JsonContent())
     *
     * )
     */

    public function updatePassword(Request $request, Utils $utils)
    {

        $request->validate([
            'password' => "required|string|required_with:confirm_password|same:confirm_password",
            'confirm_password' => "required|string",
            'options' => "required|string"
        ]);

        $options = $request->get("options");
        $password = $request->get("password");
        if(!User::where(function ($query) use ($options){
            $query->where("email", $options);
            $query->orWhere("phone", $options);
        })->exists())
        return $utils->message("error", "User Not Found", 404);

        User::where(function ($query) use ($options){
            $query->where("email", $options);
            $query->orWhere("phone", $options);
        })->update(["password" => Hash::make($password)]);


        return $utils->message("success", "Password Updated Successfully.", 200);


    }

    /**
     * @OA\Patch(
     *     path="/api/v1/patient/inner/password/update",
     *      tags={"Auth"},
     *       security={
     *            {"sanctum": {}},
     *        },
     *     @OA\Parameter(
     *         name="old_password",
     *         in="query",
     *         description="password",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="password",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="confirm_password",
     *         in="query",
     *         description="confirm_password",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Registration successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Invalid credentials", @OA\JsonContent()),
     *     @OA\Response(response="422", description="validation Error", @OA\JsonContent())
     *
     * )
     */

    public function innerUpdatePassword(Request $request, Utils $utils)
    {

        $request->validate([
            "old_password" => "required|string",
            'password' => "required|string|required_with:confirm_password|same:confirm_password",
            'confirm_password' => "required|string"
        ]);

        if(!Hash::check($request->get("old_password"), auth('sanctum')->user()->password))
            return $utils->message("error", "Invalid Password", 400);


        User::where("id", auth('sanctum')->user()->id)->update(["password" => Hash::make($request->get("new_password"))]);
        return $utils->message("success", "Password Updated Successfully.", 200);


    }
    public function verifyPin(Request $request, Utils $utils)
    {
        $request->validate([
            "pin" => "required|string|digits:4"
        ]);
        if(!Hash::check($request->get("pin"), auth('sanctum')->user()->pin))
            return $utils->message("error", "Invalid Pin", 400);

        return $utils->message("error", "Pin Verified", 200);

    }
    public function createPin(Request $request, $username, Utils $utils)
    {
        $request->validate([
            "pin" => "required|string|min:6|max:6",
        ]);
       if(!User::where("username", $username)->exists())
           return $utils->message("success", "User Not Found", 404);
        try {
            return  DB::transaction(function () use ($request, $username, $utils) {
                $user = User::where("username", "=", $username)->firstOrFail();
                $user->pin = Hash::make($request->get("pin"));
                $user->save();
                return $utils->message("success", $user, 200);
            });
        } catch (\Throwable $e) {
            return $utils->message("error",$e->getMessage() , 404);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     tags={"Auth"},
     *     summary="User Registration",
     *     description="Registers a new user with the provided information.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\JsonContent(
     *             required={
     *                 "username", "password", "first_name", "last_name", "phone",
     *                 "gender", "marital_status", "religion",
     *                 "nationality", "state", "state_of_residence",
     *                 "address_of_residence", "address_of_next_of_kin",
     *                 "register_for_self", "auth_type"
     *             },
     *             @OA\Property(property="username", type="string", description="Username", example="johndoe"),
     *             @OA\Property(property="password", type="string", description="Password", example="strongpassword123"),
     *             @OA\Property(property="first_name", type="string", description="First Name", example="John"),
     *             @OA\Property(property="middle_name", type="string", description="Middle Name", example="Victor"),
     *             @OA\Property(property="last_name", type="string", description="Last Name", example="Doe"),
     *             @OA\Property(property="phone", type="string", description="Phone number", example="+1234567890"),
     *             @OA\Property(property="email", type="string", description="Email", example="johndoe@example.com"),
     *             @OA\Property(property="gender", type="string", description="Gender", example="Male"),
     *             @OA\Property(property="marital_status", type="string", description="Marital Status", example="Single"),
     *             @OA\Property(property="religion", type="string", description="Religion", example="Christianity"),
     *             @OA\Property(property="nationality", type="string", description="Nationality", example="161"),
     *             @OA\Property(property="state", type="string", description="State of Origin", example="Lagos"),
     *             @OA\Property(property="next_of_kin", type="string", description="Next of Kin", example="Jane Doe", nullable=true),
     *             @OA\Property(property="next_of_kin_phone", type="string", description="Next of Kin Phone", example="+2348012345678", nullable=true),
     *             @OA\Property(property="nature_of_relationship", type="string", description="Nature of Relationship", example="Sister", nullable=true),
     *             @OA\Property(property="date_of_birth", type="string", format="date", description="Date of Birth", example="1990-01-01", nullable=true),
     *             @OA\Property(property="state_of_residence", type="string", description="State of Residence", example="Lagos"),
     *             @OA\Property(property="address_of_residence", type="string", description="Address of Residence", example="123 Main Street, Lagos"),
     *             @OA\Property(property="address_of_next_of_kin", type="string", description="Address of Next of Kin", example="456 Another Street, Lagos"),
     *             @OA\Property(property="register_for_self", type="boolean", description="1 = register for self, 0 = register for another person", example=true),
     *             @OA\Property(property="auth_type", type="string", description="Auth type, either EMAIL or SMS", example="EMAIL")
     *         )
     *     ),
     *     @OA\Response(response="200", description="Registration successful", @OA\JsonContent(example={"message": "Registration successful"})),
     *     @OA\Response(response="401", description="Invalid credentials", @OA\JsonContent(example={"error": "Invalid credentials"})),
     *     @OA\Response(response="422", description="Validation Error", @OA\JsonContent(example={"error": "Validation failed", "details": {"username": "This field is required"}}))
     * )
     */

    public function registerUser(UserRequest $userRequest, Utils $utils, Execs $execs)
    {

        try {
            $newPatientId = DB::transaction(function () use ($userRequest, $utils) { // Use the $request and $utils in the closure

                $password =   Hash::make($userRequest->get("password"));
                $verifyCode = mt_rand(100000,999999);
                $latestId = DB::table('users')->max('id');

                $parts = explode("/", $latestId);
                $system_id = (int)$parts[0] + 1;
                $currentMonth = date('m');
                $currentYear = date('y');


                $latestPatient = DB::table('users')->orderBy('id', 'desc')->first();

                if ($latestPatient) {
                    $lastNumber = (int)$latestPatient->id;
                } else {
                    $lastNumber = 0; // start from 0 if no record exists
                }

                $newNumber = $lastNumber + 1;
                $currentMonth = date('m');
                $currentYear = date('y');

                $newPatientId = "RO{$newNumber}/{$currentMonth}/{$currentYear}";

                $phone = $userRequest->get("phone");
                $user = new User();
                $user->password = $password;
                $user->email = $userRequest->get("email");
                $user->username = $userRequest->get("username");
                $user->phone = $phone;
                $user->authentication_type = $userRequest->get("auth_type");
                $user->register_for_self = $userRequest->get("register_for_self");
                $user->register_for_self = $userRequest->get("register_for_self");
                $user->vCode = $verifyCode;
                $user->reg_id = $newPatientId;
                $user->save();


                $patient = new Patients();
                $patient->firstName = $userRequest->get("first_name");
                $patient->lastName = $userRequest->get("last_name");
                $patient->user_id = $user->id;
                $patient->reg_id = $newPatientId;
                $patient->phone = $phone;
                $patient->date_of_birth = $userRequest->get("date_of_birth");
                $patient->gender = $userRequest->get("gender");
                $patient->marital_status = $userRequest->get("marital_status");
                $patient->religion = $userRequest->get("religion");
                $patient->nationality = $userRequest->get("nationality");
                $patient->next_of_kin = $userRequest->get("next_of_kin");
                $patient->next_of_kin_phone = $userRequest->get("next_of_kin_phone");
                $patient->nature_of_relationship = $userRequest->get("nature_of_relationship");
                $patient->state_of_residence = $userRequest->get("state_of_residence");
                $patient->address_of_residence = $userRequest->get("address_of_residence");
                $patient->user_id = $user->id;
                $patient->save();

                $offlineOnlinePatientSync = new OfflineOnlinePatientsSync();
                $offlineOnlinePatientSync->firstName = $userRequest->get("first_name");
                $offlineOnlinePatientSync->lastName = $userRequest->get("last_name");
                $offlineOnlinePatientSync->user_id = $user->id;
                $offlineOnlinePatientSync->reg_id = $newPatientId;
                $offlineOnlinePatientSync->phone = $phone;
                $offlineOnlinePatientSync->date_of_birth = $userRequest->get("date_of_birth");
                $offlineOnlinePatientSync->gender = $userRequest->get("gender");
                $offlineOnlinePatientSync->nature_of_relationship = $userRequest->get("gender");
                $offlineOnlinePatientSync->marital_status = $userRequest->get("marital_status");
                $offlineOnlinePatientSync->religion = $userRequest->get("religion");
                $offlineOnlinePatientSync->nationality = $userRequest->get("nationality");
                $offlineOnlinePatientSync->next_of_kin = $userRequest->get("next_of_kin");
                $offlineOnlinePatientSync->next_of_kin_phone = $userRequest->get("next_of_kin_phone");
                $offlineOnlinePatientSync->nature_of_relationship = $userRequest->get("nature_of_relationship");
                $offlineOnlinePatientSync->state_of_residence = $userRequest->get("state_of_residence");
                $offlineOnlinePatientSync->address_of_residence = $userRequest->get("address_of_residence");
                $offlineOnlinePatientSync->user_id = $user->id;
                $offlineOnlinePatientSync->patient_id = $patient->id;
                $offlineOnlinePatientSync->place = "online";
                $offlineOnlinePatientSync->save();


                if ($userRequest->get("auth_type") == "EMAIL") {
                    $email = $userRequest->get("email");
                    $data = [
                        "code" => $verifyCode
                    ];
                    if (!empty($email))
                        Mail::to($email)->send(new VerificationMail($data));
                } else {


                    // Define the URL and data you want to send
                    $url = 'https://portal.nigeriabulksms.com/api/?username=' . env("SMS_USERNAME") . '&password=' . env("SMS_PASSWORD") . '&message=' . $verifyCode . ' your Regina Ceali Hospital verification code. Expires in 5 minutes. &sender=' . env("SMS_SENDER") . '&mobiles=' . $phone;

                    // Send the POST request
                    $response = Http::get($url);

                }
                return $offlineOnlinePatientSync->reg_id;
            });
            return $utils->message("success", ["reg_id" => $newPatientId], 200);

            } catch (\Throwable $e) {
                return $utils->message("error",$e->getMessage() , 400);
            }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/customer/verify-otp",
     *     summary="Authenticate user and generate Sactum token",
     *     tags={"General"},
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="code",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Verification successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Invalid credentials", @OA\JsonContent()),
     *     @OA\Response(response="422", description="Validation Error", @OA\JsonContent())
     *
     * )
     */
    public function verifyOTP(Request $request, Utils $utils): JsonResponse
    {

        $request->validate([
            'code' => "required",
        ]);
        if(User::where('email',$request->get("email"))->value("password_reset") !== $request->get("code"))
            return $utils->message("error", "Code is incorrect", 401);

        return $utils->message("success", "Code Verified Successfully.", 200);


    }
    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     summary="Authenticate user",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="username",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="password",
     *         in="query",
     *         description="password",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Login successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized", @OA\JsonContent()),
     *     @OA\Response(response="422", description="Validation Error", @OA\JsonContent())
     * )
     */
    public function login(LoginRequest $loginRequest, Utils $utils, Execs $execs)
    {

        if (auth()->attempt($loginRequest->only(['username', 'password'])) ){
            $authUser = Auth::user();


            if (\App\Models\User::where("id", $authUser->id)->where("paid", "!=", 1)->exists())
                return $utils->message("error","Payment of Service Charge Required" , 400);

            $success['token']  = $authUser->createToken('access_token')->plainTextToken;
//            $success['token']  = $authUser->createToken('access_token', [TokenAbility::ACCESS_API->value], \Carbon\Carbon::now()->addMinute(2))->plainTextToken;
            $success['refreshToken']  = $authUser->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value],\Carbon\Carbon::now()->addDays(7))->plainTextToken;
            $success['username'] =  $authUser->username;
            $success['reg_id'] =  $authUser->reg_id;
            $success['email'] =  $authUser->email;
            $success['phone'] =  $authUser->phone;
            $success['first_name'] =  Patients::where("user_id", $authUser->id)->value("firstName");
            $success['last_name'] =  Patients::where("user_id", $authUser->id)->value("lastName");
            return $utils->message("success", $success, 200);


        }else{
            return $utils->message( "error", "Invalid Username/Password", 401);
        }

    }


    /**
     * @OA\Get (
     *     path="/api/v1/refresh-token",
     *      tags={"Auth"},
     *        security={
     *             {"sanctum": {}},
     *         },
     *     @OA\Response(response="200", description="Verification successful"),
     *     @OA\Response(response="404", description="Code Not Found")
     * )
     */
    public function refreshToken(Request $request, Utils $utils)
    {

        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $accessToken = $request->user()->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(2))->plainTextToken;
        return $utils->message("success",  ['token' => $accessToken ], 200);

    }

    public function adminLogin(Request $loginRequest, Utils $utils, Execs $execs)
    {
        $loginRequest->validate([
            "username" => "required",
            "password" => "required"
        ]);
        if (!auth()->attempt($loginRequest->only(['username', 'password']))){
            return $utils->message( "error", "Invalid Username/Password", 401);

        }else{

            $authUser = Auth::user();
            $success['token']  = $authUser->createToken('access_token', [TokenAbility::ACCESS_API->value], \Carbon\Carbon::now()->addMinutes(15))->plainTextToken;
            $success['refreshToken']  = $authUser->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value],\Carbon\Carbon::now()->addDays(7))->plainTextToken;
            $success['username'] =  $authUser->username;
            $success['email'] =  $authUser->email;
            return $utils->message("success", $success, 200);
        }

    }


    /**
     * @OA\Get(
     *     path="/api/v1/logout",
     *     summary="Authenticate user and generate Sactum token",
     *     tags={"Auth"},
     *       security={
     *            {"sanctum": {}},
     *        },
     *     @OA\Response(response="200", description="logout successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized", @OA\JsonContent()),
     *     @OA\Response(response="422", description="Validation Error", @OA\JsonContent())
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'User successfully signed out']);
    }
}
