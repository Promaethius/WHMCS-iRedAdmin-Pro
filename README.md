# WHMCS Plugin for iRedAdmin Pro
Tested on WHMCS 7.2.3-release.1, PHP7, and iRedAdmin-Pro v2.8.0 (MySQL)

## Philosophy
Being incredibly difficult to break into self-hosting markets, the goal of this plugin is to enable developers, small business owners, and freelancers to take advantage of the powerhouses that are WHMCS and iRedAdmin-Pro.

## Design
Based on the iRedAdmin-Pro design, that an administrative account exists that is responsible for multiple domains, the plugin will use WHMCS module hooks to create/update/delete an iRedAdmin administrator on WHMCS account create/update/delete. Whenever the password is changed in WHMCS, it will push the password to iRedAdmin via API.
Purchase of a product in WHMCS will raise the iRedAdmin administrator's domain capacity by one. The domain can be billed as a separate object from users including total domain storage space.
The WHMCS server status cron function will ping the iRedAdmin-Pro domain to parse the number of users and then utilize the WHMCS api to invoice an amount per user per hour as well as total domain storage per hour.

## WHMCS Configuration Options
```php
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
```

## Action Flow
### Domain and Admin Relationship
In this case, WHMCS Module Accounts are increases and decreases in the allotted number of domains to an iRedAdmin-Pro administrator's domain count.

#### WHMCS Account Suspension (handled through core module function) https://developers.whmcs.com/provisioning-modules/core-module-functions/ SuspendAccount()/UnsuspendAccount()
1. For the sake of the end-users, only pass the API to disable/re-enable iRedAdmin admin.
2. WHMCS passes the details of the client through $param['clientsdetails']. 
3. Grab email of client.
UnuspendAccount: `iRedAdmin:PUT /api/admin/<mail>?accountStatus=active`
SuspendAccount: `iRedAdmin:PUT /api/admin/<mail>?accountStatus=disabled`

#### WHMCS Server Product Purchase (handled through core module function) https://developers.whmcs.com/provisioning-modules/core-module-functions/ CreateAccount()
`iRedAdmin:GET /api/admin/<mail> "_success"`
1. If the account already exists, increment the max_domains of the administrator. `"_success":"true"`
  * `iRedAdmin:GET /api/admin/<mail> "_data"."settings"."create_max_domains" = QUOTA`
  * `iRedAdmin:PUT /api/admin/<mail>?maxDomains=QUOTA+1`
2. If the account doesn't exist, create the iRedAdmin-Pro administrator with 1 max_domains. This should increment for every product past the first. Create a secure hashed 1st time password and pass it to the user. `"_success":"false"`
  * `iRedAdmin:POST /api/admin/<mail>?name=NAME&password=PASS&accountStatus=active&language=en_US&isGlobalAdmin=no&maxDomains=1&maxQuota=0&maxUsers=0&maxAliases=0&maxLists=0`

#### WHMCS Server Product Update
**Nothing to Update**
**TENTATIVE:** iRedAdmin-Pro account password changes are handled by WHMCS.

#### WHMCS Server Product Deletion 
1. The problem faced here is how to handle deleting a product without deleting a specific domain from iRedMail. Therefore, the user is prompted to remove the domain from iRedMail before they go through the Product Deletion function. This also allows for data accountability. If the user owns more domains in iRedMail than the post-deletion from WHMCS, WHMCS will return an error and the product will not be removed. **NOTE: WHMCS system will automatically try to terminate accounts past due. In these cases, emails are sent to the administrator and should be handled manually.**
2. Ping iRedAdmin-Pro for the number of accounts to an admin. `iRedAdmin:GET /api/admin/<mail> "_data"."managed_domains"[]`
3. If the number is less than the quota, approve the product deletion and remove one from the quota. `iRedAdmin:GET /api/admin/<mail> "_data"."settings"."create_max_domains"`
  * `iRedAdmin:PUT /api/admin/<mail>?maxDomains=QUOTA-1`
4. If the number is greater than or equal to the quota, return an error. `iRedAdmin:GET /api/admin/<mail> "_data"."managed_domains"[]`
  * http://developers.whmcs.com/provisioning-modules/core-module-functions/ return();
    return("Please remove the domain from your mail admin account first.");
    Module errors are accessable through the admin panel.


### Cron
#### WHMCS Cron Run https://developers.whmcs.com/provisioning-modules/supported-functions/ UsageUpdate()
**NOTE: Runs per server.**
This process is easier if it was on the WHMCS side since it has knowledge of existing admins but, because this is run per server and not per order or client, we have to create a list and a comparison.
1. Start by comparing WHMCS users with iRedAdmin <admin> and drop if <admin> has no corresponding active client in WHMCS.
  * For each `iRedAdmin:GET /api/admins NOT YET IMPLEMENTED`
  * Do associate a ClientID with each <admin> in an array. `WHMCS:INTERNAL_API $results = localAPI('GetClientsDetails', array('email' => $admin, 'stats' => true));`
  * A problem with this process is if the WHMCS user has changed their email.
    **TENTATIVE:** Possibly use a datastore for this?
2. Drop the <admin> from the list if the <admin> is a superadmin. `iRedAdmin:GET /api/admin/<admin> "_data"."isglobaladmin":1`
3. Drop the <admin> from the list if the <admin> has no managed domains. `iRedAdmin:GET /api/admin/<admin> "_data"."managed_domains"[]`
4. For each <admin> left `iRedAdmin:GET /api/admin/<admin> "_data"."managed_domains"[]`
  * For each managed domain, grab both the number of users and the amount of data in use for that domain. 
    USERS: `iRedAdmin:GET /api/domain/<domain> "_data"."mailboxes":`
    QUOTA: `iRedAdmin:GET /api/domain/<domain> "_data"."data_usage": NOT YET IMPLEMENTED`
5. Grab per user, mb costs from the module. `WHMCS:INTERNAL_API $results = localAPI('GetModuleConfiguration', array('moduleName' => 'WHMCS-iRedAdmin-Pro', 'moduleType' => 'provisioning'));`
   * The results come back in arrayed format. Grab the first dimensional array item.
     $userprorate = results[0][userprorate];
     $mbprorate = results[0][mbprorate];
     **NOTE:** Double check values are a float or drop the process with an error.
   * $domainprorate = $userprorate + $mbprorate;
6. Add all these numbers as billable items per domain per <admin> to each WHMCS user. 
```
WHMCS:INTERNAL_API
$postData = array(
    'clientid' => CLIENT,
    'description' => 'Prorate billing for ' + DOMAIN,
    'amount' => DOMAINPRORATE,
    'invoiceaction' => 'nextcron',
    'hours' => 24,
);
$results = localAPI('AddBillableItem', $postData);
```
