# WHMCS Plugin for iRedAdmin Pro
Tested on WHMCS 7.2.3-release.1, PHP7, and iRedAdmin-Pro v2.8.0 (MySQL)

## Philosophy
Being incredibly difficult to break into self-hosting markets, the goal of this plugin is to enable developers, small business owners, and freelancers to take advantage of the powerhouses that are WHMCS and iRedAdmin-Pro.

## Design
Based on the iRedAdmin-Pro design, that an administrative account exists that is responsible for multiple domains, the plugin will use WHMCS module hooks to create/update/delete an iRedAdmin administrator on WHMCS account create/update/delete. Whenever the password is changed in WHMCS, it will push the password to iRedAdmin via API.
Purchase of a product in WHMCS will raise the iRedAdmin administrator's domain capacity by one. The domain can be billed as a separate object from users including total domain storage space.
The WHMCS server status cron function will ping the iRedAdmin-Pro domain to parse the number of users and then utilize the WHMCS api to invoice an amount per user per hour as well as total domain storage per hour.

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
Runs for each active product which is a process headache.
1. Get a list of products based off WHMCS module. `WHMCS:https://developers.whmcs.com/api-reference/getproducts/ POST:api.php?action='GetProducts'&module='WHMCS-iRedAdminPro'`
2. Get a list of active orders based off product list. `WHMCS:https://developers.whmcs.com/api-reference/getorders/ POST:api.php?action='GetOrders'&limitnum=0&status='Active'`
    * The returned JSON document needs to be sorted by the lineitems array returned.
    * `"orders[order][0][lineitems][lineitem][0][product]": WHMCS_MODULE_PRODUCT`
3. Get a list of users based off list of active orders.
    * Parsed and sorted JSON document that includes active module products.
    * User ID contained in `"orders[order][0][userid]"`
    * `WHMCS:https://developers.whmcs.com/api-reference/getclientsdetails/ POST:api.php?action='GetClientsDetails'&clientid=CLIENT_ID`
    * Use returned `"client[email]"` field to compile a list of active iRedAdmin-Pro users.
4. Get a list of iRedAdmin admins based off list of users. `iRedAdmin:GET /api/admin/<mail>`
    * Only looking for existing iRedAdmin-Pro administrators with '_result:success'
5. Get a list of domains based off list of iRedAdmin admins. `iRedAdmin:GET /api/admin/<mail> "_data"."managed_domains"[]`
6. Assign list of domains to respective WHMCS users.
7. Make an API call for each domain and create billable objects for each domain based off the number of users and quota used from the API response per cron cycle. `iRedAdmin:GET /api/domain/<domain>`
8. For each user, compile the billable objects from their respective domains and use internal WHMCS API AddBillableItem. 
    * `iRedAdmin:GET /api/domain/<domain> "_data":"mailboxcount":USER_COUNT`
    * `WHMCS:https://developers.whmcs.com/api-reference/addbillableitem/ POST:api.php?action='AddBillableItem'&clientid=CLIENT_ID&description='User cost for 'DOMAIN' for 'USER_COUNT&amount=USER_COUNT*COST&invoiceaction='nextinvoice'`
    * The logic behind this cron cycle is that it is performed once per 24 hours, that each user will have an ondemand cost for 24 hours, and that the composite will be charged the next time the user is invoiced for the initial domain order.
9. Similar in philosophy to the user billing section, charge an ondemand price for the amount that the domain administrator in iRedAdmin-Pro has assigned to each domain.
    * `iRedAdmin:GET /api/domain/<domain> "_data":"quota":USER_QUOTA`
    * `WHMCS:https://developers.whmcs.com/api-reference/addbillableitem/ POST:api.php?action='AddBillableItem'&clientid=CLIENT_ID&description='Quota cost for 'DOMAIN&amount=USER_QUOTA*COST&invoiceaction='nextinvoice'`
