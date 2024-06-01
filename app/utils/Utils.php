<?php

namespace App\Utils;


use http\Env\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Util\Json;

class Utils
{


    public function validatePayment($transaction_id)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.flutterwave.com/v3/transactions/". $transaction_id."/verify",
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
        return $response = json_decode($response, true);
    }
    public function validateUser($sink_account): JsonResponse
    {

        // execute the request
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://staging.mybankone.com/thirdpartyapiservice/apiservice/Account/AccountEnquiry', [
            'form_params' => [
                "AccountNo" => $sink_account,
                "AuthenticationCode" => env("BANK_ONE_AUTH_TOKEN")
            ],
            'headers' => [
                'Accept'     => 'application/json',
            ]
        ]);
       return $user = json_decode($response->getBody()->getContents());

    }
    public function message($msg = "Success", $data, $code): JsonResponse
    {
        return response()->json(["msg" => $msg, "data" => $data], $code);
    }
    public function convertImageToBase64($request, $image): array
    {
        preg_match("/data:image\/(.*?);/",$image,$image_extension); // extract the image extension
        $image = preg_replace('/data:image\/(.*?);base64,/','',$image); // remove the type part
        $image = str_replace(' ', '+', $image);
        $imageName = 'images/image_' . time() . '.' . $image_extension[1]; //generating unique file name;
        return [
                "image" =>  $image,
                "imageName" => $imageName
            ];
    }

    public function uploadImage($imageName, $image)
    {
        $storageSuccess  =  Storage::disk('public')->put($imageName,base64_decode($image));
        if($storageSuccess) {
            return Storage::disk('public')->url($imageName);
        } else {
            return response('Failed to store the image', 500);
        }
    }
}
