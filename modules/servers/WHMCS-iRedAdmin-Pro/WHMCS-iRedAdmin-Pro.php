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
        "username" => [
            "FriendlyName" => "Per-User Prorate 24H",
            "Type" => "text", # Text Box
            "Size" => "5", # Defines the Field Width
            "Description" => "userprorate",
            "Default" => "0.0",
        ],
        "password" => [
            "FriendlyName" => "Per-MB Prorate 24H",
            "Type" => "text", # Password Field
            "Size" => "5", # Defines the Field Width
            "Description" => "mbprorate",
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
