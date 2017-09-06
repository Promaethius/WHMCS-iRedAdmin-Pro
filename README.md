# WHMCS Plugin for iRedAdmin Pro
Tested on WHMCS 7.2.3-release.1, PHP7, and iRedAdmin-Pro v2.8.0 (MySQL)

## Philosophy
Being incredibly difficult to break into self-hosting markets, the goal of this plugin is to enable developers, small business owners, and freelancers to take advantage of the powerhouses that are WHMCS and iRedAdmin-Pro.

## Design
Based on the iRedAdmin-Pro design, that an administrative account exists that is responsible for multiple domains, the plugin will use WHMCS module hooks to create/update/delete an iRedAdmin administrator on WHMCS account create/update/delete. Whenever the password is changed in WHMCS, it will push the password to iRedAdmin via API.
Purchase of a product in WHMCS will raise the iRedAdmin administrator's domain capacity by one. The domain can be billed as a separate object from users including total domain storage space.
The WHMCS server status cron function will ping the iRedAdmin-Pro domain to parse the number of users and then utilize the WHMCS api to invoice an amount per user per hour as well as total domain storage per hour.

## Action Flow
### Administrator
#### WHMCS Account Creation (handled through core module function) https://developers.whmcs.com/provisioning-modules/core-module-functions/ CreateAccount()
    
1. Check if the account exists. `iRedAdmin:GET /api/admin/<mail>`
    * If it does exist, update it with zero alotted domains and zero total storage. `iRedAdmin:PUT /api/admin/<mail>?password=$WHMCS_USER_PASS&maxDomains=0`
    * Get a list of domains assigned to administrator. `iRedAdmin:GET /api/admin/<mail> "_data"."managed_domains"[]`
    * Update WHMCS user allotted domains with product list through internal api. (This action will branch to the WHMCS Server Product Purchase function tree. The purpose of this flow is to allow for existing domains to be imported into WHMCS billing.)
        * Get a list of products assigned to the module. `WHMCS:https://developers.whmcs.com/api-reference/getproducts/ POST:api.php?action='GetProducts'&module='WHMCS-iRedAdminPro'`
        * If no products are created, initialize.
        ```
        WHMCS:https://developers.whmcs.com/api-reference/addproduct/
        POST:api.php?
        action='AddProduct'&
        name='Default iRedAdmin Domain Product'&
        type='server'&
        paytype='free'&
        description='Initialized product for the WHMCS-iRedAdmin-Pro addon. Users are charged through WHMCS server status crons at $5 a user.'&
        module='WHMCS-iRedAdminPro'
        ```
    * Select first returned product and grab the PID to pass to addOrder.
    * Use list of domains to determine the number of products to add.
    * Add products to the new user.
    ```
    WHMCS:https://developers.whmcs.com/api-reference/addorder/
    POST:api.php?
    action='AddOrder'&
    clientid=WHMCS_USER_ID&
    paymentmethod=mailin&
    pid[0]=WHMCS_MODULE_PRODUCTDEFAULT
    ```
    
2. If the admin account doesn't exist, create it with zero allotted domains and zero total storage.
```
iRedAdmin:POST /api/admin/<mail>?
name='WHMCS_USER_NAME'&
password='WHMCS_USER_PASS'&
maxDomains=0
```

#### WHMCS Account Update (handled through core module function) https://developers.whmcs.com/provisioning-modules/core-module-functions/ ChangePassword()
1. Through a WHMCS hook watching for accountUpdates, pass the new password through to iRedAdmin API using the same <mail> as the email of the WHMCS user.
2. WHMCS passes the details of the client through $param['clientsdetails']. 
3. Grab email of client.
`iRedAdmin:PUT /api/admin/<mail>?password=WHMCS_USER_PASS`

#### WHMCS Account Suspension (handled through core module function) https://developers.whmcs.com/provisioning-modules/core-module-functions/ SuspendAccount()/UnsuspendAccount()
1. For the sake of the end-users, only pass the API to disable/re-enable iRedAdmin admin.
2. WHMCS passes the details of the client through $param['clientsdetails']. 
3. Grab email of client.
UnuspendAccount: `iRedAdmin:PUT /api/admin/<mail>?accountStatus=active`
SuspendAccount: `iRedAdmin:PUT /api/admin/<mail>?accountStatus=disabled`

#### WHMCS Account Deletion (handled through core module function) https://developers.whmcs.com/provisioning-modules/core-module-functions/ TerminateAccount()
1. Get iRedAdmin-Pro assigned domains for the iRedAdmin administrator. `NOT YET IMPLEMENTED`
2. Delete each domain. `iRedAdmin:DELETE /api/domain/<domain>`
3. Delete iRedAdmin-Pro Administrator. `iRedAdmin:DELETE /api/admin/<mail>`


### Domain
#### WHMCS Server Product Purchase

#### WHMCS Server Product Update

#### WHMCS Server Product Deletion


### Cron
#### WHMCS Cron Run https://developers.whmcs.com/provisioning-modules/supported-functions/ UsageUpdate()
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
