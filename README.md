# Cardinity Payment Gateway for Magento 2
This module will enable Cardinity payments in your Magento e-shop.
If you are using older version of Magento refer to  [1.9 branch](https://github.com/cardinity/cardinity-magento/tree/1.9.x).

## Requirements
* [Cardinity account](https://cardinity.com/sign-up)
* Magento Community Edition v2.0.0 or above
* PHP >= 7.2

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
put this inside app/code/Cardinity/Magento

add
```
"require": {
    "cardinity/cardinity-sdk-php": "~3.0",
    ***
```
in magento composer.json and composer update

## Downloads
[https://github.com/cardinity/cardinity-magento/releases](https://github.com/cardinity/cardinity-magento/releases)

## Screenshots
![Admin Page](https://github.com/cardinity/cardinity-magento/raw/master/screen.png)

## About
Cardinity is a safe and cost-effective online payment solution for e-commerce businesses selling various products or providing services.

Cardinity is available for EU merchants of different types: from low to high risk, from businesses to sole proprietors, from retail products to digital goods.

We operate not only as a Payment Gateway but also as an Acquiring Bank. With over 10 years of experience in providing reliable online payment services, we continue to grow and improve as a perfect solution for your businesses.

## How to Apply
Register directly at our wesbsite [https://cardinity.com/sign-up](https://cardinity.com/sign-up).

## Features
* Fast application and boarding procedure.
* Global payments. Accept payments in major currencies with all main credit and debit cards from customers all around the world.
* Recurring billing. Offer subscriptions or memberships, and your customers will be charged automatically.
* One-click payments. Let your customers purchase with a single click.
* Mobile payments. Purchases made anywhere on any mobile device.
* A payment gateway and a free merchant account.
* Ensured security with our enhanced protection measures.
* Simple and transparent pricing model. Pay only per transaction and get all the features for free.

## Keywords
payment gateway, credit card payment, online payment, credit card processing, online payment gateway.

## Change Log
* Added External Payment
* 3dsv2 Secured
