<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TokenAbility;
use App\Execs\Execs;
use App\Http\Resources\Customer;
use App\Mail\PasswordCodeEmail;
use App\Mail\PasswordResetMail;
use App\Mail\VerificationMail;
use App\Mail\VerifyCodeMail;
use App\Models\Account;
use App\Models\NewCustomer;
use App\Models\Patients;
use App\Models\User;
use App\Utils\CurlGet;
use App\Utils\CurlPost;
use App\Utils\Utils;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\UserRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/verify-password-reset-code",
     *      tags={"Auth"},
     *     @OA\Parameter(
     *         name="options",
     *         in="query",
     *         description="username",
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


        if(!User::where(function ($query) use ($request){
            $query->where("email", $request->get("options"));
            $query->orWhere("username", $request->get("options"));
            $query->orWhere("phone", $request->get("options"));
        })->where("password_reset_code", $request->get("code"))->exists())
            return $utils->message("error", "Code Does Not Exist", 404);

        return $utils->message("success","Verification Successful.", 200);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/send-forgot-password-code",
     *      tags={"Auth"},
     *     @OA\Parameter(
     *         name="username",
     *         in="query",
     *         description="username",
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
            "username" => "required|string",
            "auth_type" => "required|string"
        ]);

        $auth_type = $request->get("auth_type");
        $options = $request->get("username");

        if (!User::where(function ($query) use ($options){
            $query->where("username", $options);
//            $query->orWhere("phone", $options);
        })->exists())
            return $utils->message("error", "User Not Found", 404);


        $password_reset_code = random_int(100000, 999999);
        User::where(function ($query) use ($options){
            $query->where("username", $options);
//            $query->orWhere("phone", $options);
        })->update(["password_reset_code" => $password_reset_code]);

        $mailData = [
            'title' => 'Reset your password',
            'code' => $password_reset_code
        ];


        if ($auth_type == "EMAIL")
            Mail::to(User::where("username", $options)->value("email"))->send(new PasswordResetMail($mailData));

        return $utils->message("success", "Email Sent. Check your mailbox", 200);

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
     *         name="email",
     *         in="query",
     *         description="email",
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
            'email' => "required|string"
        ]);

        User::where("email", $request->get("email"))->update(["password" => Hash::make($request->get("password"))]);
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
     *      tags={"Auth"},
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
     *         description="Password",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
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
     *         name="state",
     *         in="query",
     *         description="state",
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
     *     @OA\Parameter(
     *         name="register_for_self",
 *             required=true,
     *         in="query",
     *         description="1 = register for self, 0 = register for another person",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="auth_type",
 *             required=true,
     *         in="query",
     *         description="EMAIL OR SMS",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Registration successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Invalid credentials", @OA\JsonContent()),
     *     @OA\Response(response="422", description="validation Error", @OA\JsonContent())
     *
     * )
     */
    public function registerUser(UserRequest $userRequest, Utils $utils, Execs $execs)
    {
        $phone = $userRequest->get("phone");
        $phone = $userRequest->get("phone");
         $password =   Hash::make($userRequest->get("password"));
        $verifyCode = mt_rand(100000,999999);
        try {
                $user = New User();
                $user->username = $userRequest->get("username");
                $user->password = $password;
                $user->email = $userRequest->get("email");
                $user->phone = $userRequest->get("phone");
                $user->authentication_type = $userRequest->get("auth_type");
                $user->register_for_self = $userRequest->get("register_for_self");
                $user->vCode = $verifyCode;
                $user->save();

                $patient = new Patients();
                $patient->first_name = $userRequest->get("first_name");
                $patient->last_name = $userRequest->get("last_name");
                $patient->phone = $userRequest->get("phone");
                $patient->gender = $userRequest->get("gender");
                $patient->marital_status = $userRequest->get("marital_status");
                $patient->religion = $userRequest->get("religion");
                $patient->nationality = $userRequest->get("nationality");
                $patient->next_of_kin = $userRequest->get("next_of_kin");
                $patient->address_of_next_of_kin = $userRequest->get("address_of_next_of_kin");
                $patient->next_of_kin_phone = $userRequest->get("next_of_kin_phone");
                $patient->nature_of_relationship = $userRequest->get("nature_of_relationship");
                $patient->date_of_birth = $userRequest->get("date_of_birth");
                $patient->state_of_residence = $userRequest->get("state_of_residence");
                $patient->address_of_residence = $userRequest->get("address_of_residence");
                $patient->user_id = $user->id;
                $patient->save();


                if ($userRequest->get("auth_type") == "EMAIL") {

                    $data = [
                        "code" => $verifyCode
                    ];
                    Mail::to($userRequest->get("email"))->send(new VerificationMail($data));
                }else{

                }
                return $utils->message("success", $patient , 200);

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
     *     summary="Authenticate user and generate Sactum token",
     *     tags={"Auth"},
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="email",
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
     *     @OA\Response(response="200", description="Login successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Unauthorized", @OA\JsonContent()),
     *     @OA\Response(response="422", description="Validation Error", @OA\JsonContent())
     * )
     */
    public function login(LoginRequest $loginRequest, Utils $utils, Execs $execs)
    {

        if (auth()->attempt($loginRequest->only(['username', 'password'])) || auth()->attempt($loginRequest->only(['phone', 'password'])) ){

            $authUser = Auth::user();

            $success['token']  = $authUser->createToken('access_token', [TokenAbility::ACCESS_API->value], \Carbon\Carbon::now()->addMinutes(15))->plainTextToken;
            $success['refreshToken']  = $authUser->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value],\Carbon\Carbon::now()->addDays(7))->plainTextToken;
            $success['username'] =  $authUser->username;
            $success['email'] =  $authUser->email;
            $success['first_name'] =  Patients::where("user_id", $authUser->id)->value("first_name");
            $success['last_name'] =  Patients::where("user_id", $authUser->id)->value("last_name");
            return $utils->message("success", $success, 200);
        }else{
            return $utils->message( "error", "Invalid Email/Password", 401);

        }

    }


    /**
     * @OA\Get (
     *     path="/api/v1/regresh-token",
     *      tags={"Auth"},
     *     @OA\Parameter(
     *         name="refresh_token",
     *         in="query",
     *         description="refresh_token",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Verification successful"),
     *     @OA\Response(response="404", description="Code Not Found")
     * )
     */
    public function refreshToken(Request $request, Utils $utils)
    {
        if(!auth('sanctum')->check())
            return $utils->message("error","Unauthorized Access." , 401);

        $accessToken = $request->user()->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(10))->plainTextToken;
        return $utils->message("success",  ['token' => $accessToken ], 200);

    }

    public function adminLogin(Request $loginRequest, Utils $utils, Execs $execs)
    {
        $loginRequest->validate([
            "username" => "required",
            "password" => "required"
        ]);
        if (!auth()->attempt($loginRequest->only(['username', 'password'])))
            return $utils->message( "error", "Invalid Username/Password", 401);

        $authUser = Auth::user();
        $success['token']  = $authUser->createToken('access_token', [TokenAbility::ACCESS_API->value], \Carbon\Carbon::now()->addMinutes(15))->plainTextToken;
        $success['refreshToken']  = $authUser->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value],\Carbon\Carbon::now()->addDays(7))->plainTextToken;
        $success['username'] =  $authUser->username;
        $success['email'] =  $authUser->email;
        return $utils->message("success", $success, 200);
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
