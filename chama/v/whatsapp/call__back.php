<?php
$handle = curl_init('http://206.189.207.206/test/call__back.php');

$data = [];

$encodedData = json_encode($data);

curl_setopt($handle, CURLOPT_POST, 1);
curl_setopt($handle, CURLOPT_POSTFIELDS, $encodedData);
curl_setopt($handle, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$result = curl_exec($handle);
//
var_dump($result);
echo $result;
