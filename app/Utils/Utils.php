<?php

namespace App\Utils;


use App\Models\User;
use http\Env\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Util\Json;
#########################
class Utils
{


    /**
     * @param $l
     * @param string $c
     * @return string
     */
    public static function code_ref ($l, string $c = '1234567890') : string {
        for ($s = '', $cl = strlen($c)-1, $i = 0; $i < $l; $s .= $c[mt_rand(0, $cl)], ++$i);
        return $s;
    }

    public static function generateCramp($type) :string
    {
        $mt = explode(' ', microtime());
        $rand = time() . rand(10, 99);
        $time = ((int)$mt[1]) * 1000000 + ((int)round($mt[0] * 1000000));
        $generated = $rand . $time;

        switch ($type) {
            case "service_payments" :
                return "3060" . $generated;
                break;
            case "service" :
                return "3061" . $generated;
                break;
            case "user" :
                return "3062" . $generated;
                break;
            default:
                return "3069" . $generated;
                break;
        }
    }

    public  function generateCode($type)
    {
        $mt = explode(' ', microtime());
        $rand = time() . rand(10, 99);
        $time = ((int)$mt[1]) * 1000000 + ((int)round($mt[0] * 1000000));
        $generated = $rand . $time;

        switch ($type) {
            case "bookings" :
                return "3060" . $generated;
                break;
            case "post" :
                return "3061" . $generated;
                break;
            case "user" :
                return "3062" . $generated;
                break;
            default:
                return "3069" . $generated;
                break;
        }
    }
    public function sendOTPToSMS($phone, $verifyCode)
    {
        $user = User::where("phone", $phone)->firstOrFail();
        $user->vCode = $verifyCode;
        $user->save();
        $data = [
            "code" => $verifyCode
        ];
        // Define the URL and data you want to send
        $url = 'https://portal.nigeriabulksms.com/api/?username='. env("SMS_USERNAME").'&password=' . env("SMS_PASSWORD"). '&message=verification code is ' .  $verifyCode . '&sender=' . env("SMS_SENDER") . '&mobiles=' .$phone;

        // Send the POST request
      return  $response = Http::get($url);

    }
    public function generateKey($keyLength = 6) {
        // Set a blank variable to store the key in
        $key = "";
        for ($x = 1; $x <= $keyLength; $x++) {
            // Set each digit
            $key .= random_int(0, 9);
        }
        return $key;
    }


    public function validatePayment($transaction_id)
    {
        $token = env("FLWSECKEY");
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
                "Authorization: Bearer " . $token
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
