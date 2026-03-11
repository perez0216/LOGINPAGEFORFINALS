<?php

function send_sms($phone,$otp){

$apiKey = "YOUR_API_KEY";

$data = [
'apikey'=>$apiKey,
'number'=>$phone,
'message'=>"Your OTP code is: ".$otp
];

$ch = curl_init("https://semaphore.co/api/v4/messages");

curl_setopt($ch,CURLOPT_POST,true);
curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($data));
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

$result = curl_exec($ch);

curl_close($ch);

return $result;

}