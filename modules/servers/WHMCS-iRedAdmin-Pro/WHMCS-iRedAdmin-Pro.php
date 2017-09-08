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

function Login(string $url, string $admin, string $pass) {
   $postfields = array(
       'username' => $admin,
       'password' => $pass,
   );

   // Call the API
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $url . '/api/login');
   curl_setopt($ch, CURLOPT_POST, 1);
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
 
   preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
   $cookies = array();
   foreach($matches[1] as $item) {
       parse_str($item, $cookie);
       $cookies = array_merge($cookies, $cookie);
       // TODO: select only cookie matching regex iRedAdmin-Pro-mysql or ldap
   }
 
   return($cookies);
}
