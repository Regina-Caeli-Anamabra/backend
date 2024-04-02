<?php

namespace App\Utils;


use http\Env\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Util\Json;

class Utils
{


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
