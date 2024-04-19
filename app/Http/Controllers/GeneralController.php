<?php

namespace App\Http\Controllers;

use App\Http\Resources\Bank;
use App\Http\Resources\BankResource;
use App\Models\Services;
use App\Models\User;
use App\Utils\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeneralController extends Controller
{

    /**
     * @OA\Get (
     *     path="/api/v1/services",
     *      tags={"General"},
     *       security={
     *            {"sanctum": {}},
     *        },
     *     @OA\Response(response="200", description="Registration successful", @OA\JsonContent()),
     *     @OA\Response(response="401", description="Invalid credentials", @OA\JsonContent()),
     *     @OA\Response(response="422", description="validation Error", @OA\JsonContent())
     *
     * )
     */
    public function services(Utils $utils): JsonResponse
    {
        try {
            return $utils->message("success", Services::all()  , 200);
        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }



    public function addService(Request $request, Utils $utils): JsonResponse
    {
        $request->validate([
            "name" => "required|string",
            "amount" => "required|regex:/^[0-9]+(\.[0-9][0-9]?)?$/",
        ]);
        try {
            $service = new Services();
            $service->name = $request->get("name");
            $service->amount = $request->get("amount");
            $service->save();
            return $utils->message("success", $service  , 200);

        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
    public function verifyAccount(Request $request, Utils $utils)
    {

        $request->validate([
            "account_number" => "required|int|digits:10",
        ]);
        $body = $request->all();
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . env("PAYSTACK_SEC_KEY") ,
        ];
        try {
//            $client = new \GuzzleHttp\Client();
//            $response = $client->request('POST', 'https://api.flutterwave.com/v3/accounts/resolve' , [
//                'json' => [
//                    'account_number' => $request->get("account_number"),
//                    'account_bank' => $request->get("account_bank")
//                ],
//                'verify' => false,
//                'headers' => $headers
//                ]);
//            $account = json_decode($response->getBody());
            $account = $utils->validateUser($request->get("account_number"));
            return $utils->message("success", $account  , 200);

        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error", $e->getMessage() , 400);
        }
    }
    public function getBanks(Utils $utils)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . env("PAYSTACK_SEC_KEY") ,
        ];
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', 'https://api.flutterwave.com/v3/banks/NG' , [
                'verify' => false,
                'headers' => $headers
                ]);
            $banks = json_decode($response->getBody(), true);
            return $utils->message("success", BankResource::collection(json_decode(json_encode($banks["data"])))  , 200);

        }catch (\Throwable $e) {
            // Do something with your exception
            return $utils->message("error",$e->getMessage() , 400);
        }
    }
    public function getAccountDetails($phone, Utils $utils)
    {
        try {
            // execute the request
            $data = "phoneNumber=" . $phone . "&authToken=" . env("BANK_ONE_AUTH_TOKEN") ;
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', 'https://staging.mybankone.com/BankOneWebAPI/api/Customer/GetByCustomerPhoneNumber/2?' . $data);
            $user = json_decode($response->getBody()->getContents());
            if (isset($user->Message) && !empty($user))
                return $utils->message("success", $user->Message, 404);

                $data = [
                    "name" => $user[0]->Accounts[0]->CustomerName
                ];
                return $utils->message("success", $data , 200);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            return $utils->message("error", "Network Error. Please Try Again" , 404);
//            $response = $e->getResponse();
//            return $utils->message("error", $response->getBody()->getContents() , 404);

        }
    }
}
