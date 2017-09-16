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
 
}

function WHMCS-iRedAdmin-Pro_UnsuspendAccount($params) {
 
}

function WHMCS-iRedAdmin-Pro_TerminateAccount($params) {
 
}

function WHMCS-iRedAdmin-Pro_UsageUpdate($params) {
 
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
 } else {
  return(false);
 }
}
