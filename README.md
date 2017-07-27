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
#### WHMCS Account Creation
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
    * Select first returned product.
    * Use list of domains to determine the number of products to add.
    * Add products to an order assigned to the new user.
2. If it doesn't exist, create it with zero allotted domains and zero total storage.

#### WHMCS Account Update
1. Through a WHMCS hook watching for accountUpdates, pass the new password through to iRedMail API using the same <mail> as the email of the WHMCS user.

#### WHMCS Account Suspension
1. For the sake of the end-users, only pass the API to disable/re-enable iRedMail admin.

#### WHMCS Account Deletion
1. Get iRedMail-Pro assigned domains for the iRedMail administrator.
2. Delete each domain.
3. Delete iRedMail-Pro Administrator.


### Domain
#### WHMCS Server Product Purchase

#### WHMCS Server Product Update

#### WHMCS Server Product Deletion


### Cron
#### WHMCS Cron Run
