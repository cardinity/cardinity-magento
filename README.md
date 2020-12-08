# Cardinity Payment Gateway for Magento

This module will enable Cardinity payments in your Magento e-shop.
development module for cardinikty payment gateway

## Requirements
* [Cardinity account](https://cardinity.com/sign-up)
* Magento Community Edition v2.0.0 or above

## How to install
Recommended to install via marketplace. After install you can set configurations from Store -> Configuration -> Payment Methods

### Using marketplace
Navigate to https://marketplace.magento.com/cardinity-magento.html
Select your store version and add extension to cart
Obtain the extension via Magento Marketplace Platform
Once the order is complete, click install extension
Once you redirected to Keys page copy private and public access key
Login to your store admin panel

### Using app/code
put this inside app/code/Cardinity/Payment

add 
"require": {
	"cardinity/cardinity-sdk-php": "~3.0",
      ***

in magento composer.json and composer update

## Downloads
[https://github.com/cardinity/cardinity-magento/releases](https://github.com/cardinity/cardinity-magento/releases)

## Screenshots
![Admin Page](https://github.com/cardinity/cardinity-magento/raw/master/screen.png)

## About
## How to Apply
## Features
## Keywords

## Change Log

* Added External Payment 
* 3dsv2 Secured
