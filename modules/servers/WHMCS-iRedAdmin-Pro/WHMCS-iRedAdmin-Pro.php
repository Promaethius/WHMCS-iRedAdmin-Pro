<?php
 /*Copyright 2017 Jonathan Bryant bryant.jonathan.42@gmail.com

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

     http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.*/

function WHMCS-iRedAdmin-Pro_ConfigOptions() {
    return [
        "userprorate" => [
            "FriendlyName" => "Per-User Prorate 24H",
            "Type" => "text", # Text Box
            "Size" => "5", # Defines the Field Width
            "Description" => "Cost per User charged to a WHMCS user at the end of every 24H period.",
            "Default" => "0.0",
        ],
        "mbprorate" => [
            "FriendlyName" => "Per-MB Prorate 24H",
            "Type" => "text", # Password Field
            "Size" => "5", # Defines the Field Width
            "Description" => "Cost per MB charged to a WHMCS user at the end of every 24H period.",
            "Default" => "0.0",
        ],
    ];
}

function WHMCS-iRedAdmin-Pro_CreateAccount($params) {
    
}

function WHMCS-iRedAdmin-Pro_SuspendAccount($params) {
    
}

function WHMCS-iRedAdmin-Pro_UnsuspendAccount($params) {
    
}

function WHMCS-iRedAdmin-Pro_TerminateAccount($params) {
    
}

function WHMCS-iRedAdmin-Pro_ChangePassword($params) {
    
}

function WHMCS-iRedAdmin-Pro_UsageUpdate($params) {
    
}

function Login(string $admin, string $pass, string $url) {
// Prep array for POST login.
   $postfields = array(
       'username' => $admin,
       'password' => $pass,
   );
// Take response and regex it for cookies
   preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', ConnectionHandler($postfields,$url,'/api/login','POST'), $matches);
// For each cookie returned, look for the one starting with iRedAdmin-Pro and assign it to a variable.
   foreach($matches[1] as $item) {
       parse_str($item, $cookie);
    
       if (substr( $cookie, 0, 4 ) === "iRedAdmin-Pro") {
           $login_cookie = $cookie;
       }
   }
// Return the login cookie in format iRedAdmin-Pro-*type*=...
   return($login_cookie);
}

function ConnectionHandler(array $postfields, string $url, string $uri, string $method, string $login_cookie) {

   // Call the API
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $url . $uri);
// Switch for API request types
   switch ($method) {
    case 'POST':
     curl_setopt($ch, CURLOPT_POST, 1);
     break;
    case 'GET':
     curl_setopt($ch, CURLOPT_GET, 1);
     break;
    case 'PUT':
     curl_setopt($ch, CURLOPT_PUT, 1);
     break;
    case 'DELETE':
     curl_setopt($ch, CURLOPT_DELETE, 1);
     break;
   }
// Check for a login cookie first. If it exists, then send it along with the request.
   if ($login_cookie != '') {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: " + $login_cookie));
   }
   curl_setopt($ch, CURLOPT_TIMEOUT, 30);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($ch, CURLOPT_HEADER, 1);
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
   curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
 
   $response = curl_exec($ch);
   if (curl_error($ch)) {
       die('Unable to connect: ' . curl_errno($ch) . ' - ' . curl_error($ch));
   }
   curl_close($ch);
 
   return($response);
}
