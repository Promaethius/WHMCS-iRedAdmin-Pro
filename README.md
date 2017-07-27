# WHMCS Plugin for iRedMail Pro
Tested on WHMCS 7.2.3-release.1, PHP7, and iRedMail-Pro v2.7.0 (MySQL)

## Philosophy
Being incredibly difficult to break into self-hosting markets, the goal of this plugin is to enable developers, small business owners, and freelancers to take advantage of the powerhouses that are WHMCS and iRedMail-Pro.

## Design
Based on the iRedMail-Pro design, that an administrative account exists that is responsible for multiple domains, the plugin will use WHMCS module hooks to create/update/delete an iRedMail administrator on WHMCS account create/update/delete. Whenever the password is changed in WHMCS, it will push the password to iRedMail via API.
Purchase of a product in WHMCS will raise the iRedMail administrator's domain capacity by one. The domain can be billed as a separate object from users including total domain storage space.
The WHMCS server status cron function will ping the iRedMail-Pro domain to parse the number of users and then utilize the WHMCS api to invoice an amount per user per hour as well as total domain storage per hour.

## Action Flow
### Administrator
#### WHMCS Account Creation (handled through core module function) https://developers.whmcs.com/provisioning-modules/core-module-functions/ CreateAccount()
    
1. Check if the account exists. `iRedMail:GET /api/admin/<mail>`
    * If it does exist, update it with zero alotted domains and zero total storage. `iRedMail:PUT /api/admin/<mail>?password=$WHMCS_USER_PASS&maxDomains=0`
    * Get a list of domains assigned to administrator. `NOT YET IMPLEMENTED`
    * Update WHMCS user allotted domains with product list through internal api. (This action will branch to the WHMCS Server Product Purchase function tree. The purpose of this flow is to allow for existing domains to be imported into WHMCS billing.)
        * Get a list of products assigned to the module. `WHMCS:https://developers.whmcs.com/api-reference/getproducts/ POST:api.php?action='GetProducts'&module='WHMCS-iRedMailPro'`
        * If no products are created, initialize.
        ```
        WHMCS:https://developers.whmcs.com/api-reference/addproduct/
        POST:api.php?
        action='AddProduct'&
        name='Default iRedMail Domain Product'&
        type='server'&
        paytype='free'&
        description='Initialized product for the WHMCS-iRedMail-Pro addon. Users are charged through WHMCS server status crons at $5 a user.'&
        module='WHMCS-iRedMailPro'
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
iRedMail:POST /api/admin/<mail>?
name='WHMCS_USER_NAME'&
password='WHMCS_USER_PASS'&
maxDomains=0
```

#### WHMCS Account Update (handled through core module function) https://developers.whmcs.com/provisioning-modules/core-module-functions/ ChangePassword()
1. Through a WHMCS hook watching for accountUpdates, pass the new password through to iRedMail API using the same <mail> as the email of the WHMCS user.
`iRedMail:PUT /api/admin/<mail>?password=WHMCS_USER_PASS`

#### WHMCS Account Suspension (handled through core module function) https://developers.whmcs.com/provisioning-modules/core-module-functions/ SuspendAccount()/UnsuspendAccount()
1. For the sake of the end-users, only pass the API to disable/re-enable iRedMail admin.
UnuspendAccount: `iRedMail:PUT /api/admin/<mail>?accountStatus=active`
SuspendAccount: `iRedMail:PUT /api/admin/<mail>?accountStatus=disabled`

#### WHMCS Account Deletion (handled through core module function) https://developers.whmcs.com/provisioning-modules/core-module-functions/ TerminateAccount()
1. Get iRedMail-Pro assigned domains for the iRedMail administrator. `NOT YET IMPLEMENTED`
2. Delete each domain. `iRedAdmin:DELETE /api/domain/<domain>`
3. Delete iRedMail-Pro Administrator. `iRedAdmin:DELETE /api/admin/<mail>`


### Domain
#### WHMCS Server Product Purchase

#### WHMCS Server Product Update

#### WHMCS Server Product Deletion


### Cron
#### WHMCS Cron Run
