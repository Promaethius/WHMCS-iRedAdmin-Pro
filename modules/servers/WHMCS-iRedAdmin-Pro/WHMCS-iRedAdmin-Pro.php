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

// logModuleCall($param['moduletype'], $action, $requestString, $responseData, $processedData, $replaceVars);

// Load GUZZLE and SQLite
require __DIR__ . 'vendor/autoload.php';

// Exception handlers
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

// Init a new Guzzle Client set to store and use cookies
$client = new GuzzleHttp\Client(['cookies' => true]);

// Database Class for non-blocking SQLite database
class DB extends SQLite3 {
 function __construct() {
  $this->open(__DIR__ . 'users.db');
 }
}

// Init Database Connnection
$ClientBase = new DB();
if(!$db) {
 die($db->lastErrorMsg());
}

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
 // Using the client ID param, check the SQLite database for the corresponding iRedAdmin admin
 try {
  $response = $client->put($url . '/api/admin/' . $admin, [
   'query' => [
    'accountStatus' => 'disabled',
   ]
  ]);
 } catch (RequestException $e) {
  die(Psr7\str($e->getRequest()));
  if ($e->hasResponse()) {
   die(Psr7\str($e->getResponse()));
  }
 }
}

function WHMCS-iRedAdmin-Pro_UnsuspendAccount($params) {
 
 try {
  $response = $client->put($url . '/api/admin/' . $admin, [
   'query' => [
    'accountStatus' => 'enabled',
   ]
  ]);
 } catch (RequestException $e) {
  die(Psr7\str($e->getRequest()));
  if ($e->hasResponse()) {
   die(Psr7\str($e->getResponse()));
  }
 }
}

function WHMCS-iRedAdmin-Pro_TerminateAccount($params) {
 
}

function WHMCS-iRedAdmin-Pro_UsageUpdate($params) {
 
}
  
function GetAdmin{int $clientid) {
 
}

function TryConnection(string $admin, string $pass, string $url) {
 // Try the connection at least 5 times before erroring out.
 for ($i = 0; $i <= 5; $i++) {
  // If validation fails, try to Log back in.
  if (Validate($admin, $pass, $url) == false) {
   Login($admin, $pass, $url);
  } else {
   // If the validation succeeds, return true.
   return(true);
  }
 }
 // If within 5 attempts the login is still failing, return false.
 return(false);
}
  

function Validate(string $admin, string $pass, string $url) {
 // It's easier to try a validation by pinging a single endpoint with low resource consumption.
 try {
  $response = $client->get($url . '/api/admin/' . $admin, [
   'query' => [
    'username' => $admin,
    'password' => $pass,
   ]
  ]);
 } catch (RequestException $e) {
  die(Psr7\str($e->getRequest()));
  if ($e->hasResponse()) {
   die(Psr7\str($e->getResponse()));
  }
 }
 
 $body = json_decode($response->getBody());
 // If we get the hoped for response, return that the login is still valid.
 if ($body['_success'] == 'true') {
  return(true);
 // However, if we get the expected error message for an expired cookie, return false. If the validation function runs the login function to re-validate again, we might get stuck in a loop.
 } else if ($body['_success'] == 'false') && ($body['_msg'] == 'LOGIN REQUIRED') {
  return(false);
 } else {
  die('Incorrect Response Format');
 }
}

function Login(string $admin, string $pass, string $url) {
 // Try Catch function for client login. Passes to the login interface and stores the cookie.
 try {
  $response = $client->post($url . '/api/login', [
   'query' => [
    'username' => $admin,
    'password' => $pass,
   ]
  ]);
 } catch (RequestException $e) {
  die(Psr7\str($e->getRequest()));
  if ($e->hasResponse()) {
   die(Psr7\str($e->getResponse()));
  }
 }
 // Decode json response string into array format
 $body = json_decode($response->getBody());
 // Makes exception for the fringe cases where no information is returned
 if ($body['_success'] == 'true')
 {
  return(true);
 } else if ($body['_success'] == 'false') {
  return(false);
 } else {
  die('Incorrect Response Format');
 }
}
