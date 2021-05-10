# Cardinity Payment Gateway for Magento
This module will enable Cardinity payments system in your Magento e-shop. If you are using older version of Magento refer to <a href="https://github.com/cardinity/cardinity-magento/tree/1.9.x">1.9 branch</a>.

### Table of Contents  
[<b>How to install? →</b>](#how-to-install)<br>
      [Using marketplace (recommended)](#using-marketplace-recommended)  
       [Using composer](#using-composer)   
      [Using app/code](#using-appcode)   
 [<b>Downloads →</b>](#downloads)<br>
 [<b>Having problems? →</b>](#having-problems)<br>
 [<b>About us →</b>](#-aboutus)<br>     
<a name="headers"/>  

## How to install?

### Requirements
• Cardinity account  
• Magento Community Edition v2.0.0 or above  
• PHP ≥ 7.2
<br>

### Installation
Recommended to install via marketplace. You can click on each step for screenshot to appear with clearer instructions. 
#### Using marketplace (recommended)
1) Go to Magento marketplace and search for "Cardinity payment gateway" or navigate directly to → https://marketplace.magento.com/cardinity-magento.html
2) Select your store version and click ```Add to cart```.
3) Proceed to checkout, fill in the billing form (don't worry - its free) and obtain the extension via Magento Marketplace Platform.
4) Once the order is complete, click ```Install```.
5) Once you are redirected to "My Access Keys" page, copy private and public access keys.
6) Next, login to your store admin panel. Go to ```System" → "Web setup wizard```, then choose ```Component manager```.
7) Click on ```Sign in``` and paste Public and Private access keys.
8) After successfully putting in the keys, click ```Sync```. Then, click ```Install``` under ```New purchases```.
9) Among new purchases, find Cardinity payment module and click ```Install```. Wait while readiness check is finished. Click ```Next```. During step 2, create a backup if needed (highly recommended) and click next again. Finally, click ```Install``` during "step 3: Component install".
10) If everything is done correctly, you should be prompted that Cardinity extension has been installed.
11) Go to ```Stores > Configuration > Sales > Payment Methods > Cardinity```
12) Finally, enter your API credentials which can be found in your Cardinity account and set the payment method as active

After installation you can setup configurations in ```Store -> Configuration -> Payment Methods```

#### Using composer
1) Run the following command in Magento 2 root folder (require the module via ```composer.json```)
```
$ composer require cardinity/magento
```
2) Then, enable the module with
```
$ bin/magento module:enable Cardinity_Magento
$ bin/magento setup:upgrade
```
3) Login to the Magento admin panel.
4) Go to ```Stores > Configuration > Sales > Payment Methods > Cardinity```.
5) Enter your API credentials which can be found in your Cardinity account and set the payment method as active.


#### Using app/code
0) Download the latest Cardinity Payment Module for Magento 2 here: https://github.com/cardinity/cardinity-magento/releases

1) Go to Magento base directory and extract files to ```app/code/Cardinity/Magento```. 

2) Add the following line in Magento 2 ```composer.json``` file under ```require```.
```
    "cardinity/cardinity-sdk-php": "~3.0",
```
3) Next, update dependencies with
```
composer update
```
4) Then, use following command to compile the new codes
```
$ bin/magento setup:di:compile
$ bin/magento setup:upgrade
```
5) Enable the module by adding
```
$ bin/magento module:enable Cardinity_Magento
```
6) Then, login to Magento admin panel
7) Go to ```Stores > Configuration > Sales > Payment Methods > Cardinity```.
8) Finally, enter your API credentials which can be found in your Cardinity account and set the payment method as active.


### Downloads
Find the latest Cardinity Payment Module for Magento 1 and 2 here: https://github.com/cardinity/cardinity-magento/releases

<details show>
  <summary>For Magento 1</summary>
  
| Version          | Changes                                             | Link  |
| ------------- |-----------------------------------------------------|------------|
| v1.0.4    | <b>Recommended (latest)</b>.<br>Reduced Session dependencies.            | <a href="https://github.com/cardinity/cardinity-magento/releases/tag/magento-1.9">Download</a> |
| v1.0.3    | <b>Previous Version</b>.<br>Added payment by external hosted gateway            | <a href="https://github.com/cardinity/cardinity-magento/releases/tag/magento-1.9">Download</a> |
| v1.0.2    | Merge pull request #11 from ```cardinity/dev-1.9.x```; seperate projectID and secret possible in case of multi store| <a href="https://github.com/cardinity/cardinity-magento/releases/tag/magento-1.9">Download</a> |
| v1.0    | Added payment by external hosted gateway            | <a href="https://github.com/cardinity/cardinity-magento/releases/tag/magento-1.9">Download</a> |
</details>

<details show>
  <summary>For Magento 2</summary>
  
| Version          | Changes                                             |  Link   |
| ------------- |-----------------------------------------------------|------------|
| v2.0.2    | <b>Recommended (latest)</b>.<br>Merge pull request #10 from ```cardinity/improve_compatibility```;<br> renamed module to ```Cardinity_Magento``` | <a href="https://github.com/cardinity/cardinity-magento/releases/tag/v2.0.2.2">Download</a>     |
| v2.0.1    | bug fix, minor update related to refund issues.     | <a href="https://github.com/cardinity/cardinity-magento/releases/tag/v2.0.1">Download</a>     |
| v2.0.0    | • Updated cardinity sdk to version 3,<br>• 3D secured version 2 with fallback to version 1,<br>• External hosted payments                  | <a href="https://github.com/cardinity/cardinity-magento/releases/tag/v2.0.0.0">Download</a>       |
</details>

### Having problems?  

Feel free to contact us regarding any problems that occurred during integration via info@cardinity.com. We will be more than happy to help.

-----

### ► About us
Cardinity is a licensed payment institution, active in the European Union, registered on VISA Europe and MasterCard International associations to provide <b>e-commerce credit card processing services</b> for online merchants. We operate not only as a <u>payment gateway</u> but also as an <u>acquiring Bank</u>. With over 10 years of experience in providing reliable online payment services, we continue to grow and improve as a perfect payment service solution for your businesses. Cardinity is certified as PCI-DSS level 1 payment service provider and always assures a secure environment for transactions. We assure a safe and cost-effective, all-in-one online payment solution for e-commerce businesses and sole proprietorships.<br>
#### Our features
• Fast application and boarding procedure.   
• Global payments - accept payments in major currencies with credit and debit cards from customers all around the world.   
• Recurring billing for subscription or membership based sales.  
• One-click payments - let your customers purchase with a single click.   
• Mobile payments. Purchases made anywhere on any mobile device.   
• Payment gateway and free merchant account.   
• PCI DSS level 1 compliance and assured security with our enhanced protection measures.   
• Simple and transparent pricing model. Only pay per transaction and receive all the features for free.
### Get started
<a href="https://cardinity.com/sign-up">Click here</a> to sign-up and start accepting credit and debit card payments on your website or <a href="https://cardinity.com/company/contact-us">here</a> to contact us 
#### Keywords
payment gateway, credit card payment, online payment, credit card processing, online payment gateway, cardinity for magento.     

  
 [▲ back to top](#Cardinity-Payment-Gateway-for-PrestaShop)
<!--
**fjundzer/fjundzer** is a ✨ _special_ ✨ repository because its `README.md` (this file) appears on your GitHub profile.
