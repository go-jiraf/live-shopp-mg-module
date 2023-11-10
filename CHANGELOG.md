# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.3.1] 2023-11-10

### Fix

This version fixes an unsatisfactory behavior in the redirection to the cart.
Now, it requires the configurable products to be previously added to the guest cart, not by its variant sku, but with the parent sku and an options configurations as specified in https://developer.adobe.com/commerce/webapi/rest/tutorials/orders/order-add-items/#add-a-configurable-product-to-a-cart
The Gojiraf's module's is products-type-agnostic.

## [1.3.0] 2023-09-29

### Feature

This new minor version presents the GoJiraf webhook feature, that sends a request to GoJiraf Services each time a order is paid,
in order to update the Gojiraf order with the proper status, improving the business analytics.

- Create a Webhooks Configuration table in database
- Add a REST API and custom resource to get, create and delete webhook configurations for Gojiraf
- Add a Observer for the sales_order_payment_pay event

## [1.2.4] 2023-10-18

### Fix

- Remove commas not supported by PHP7

## [1.2.3] 2023-09-27

### Fix

- Downgrade the PHP Version requirement (7.3 or higher, instead of 8.0/8.1)
## [1.2.2] 2023-09-26

### Refactor

- Improve Redirect class for a more standar behavior when adding configurable products to the cart
- General style refactor in Redirect class

## [1.2.0] 2023-03-23

### Feature

- Add handler to return errors by API when a flag is set on the request

### Refactor

- Replace SQL query (low level code) for a Magento method in products stock filtering by stock
## [1.1.6-beta] 2023-01-30

### Refactor

- Now on multisource stores the products are filtered using Magento native class

## [1.1.5] 2023-01-9

### Fixed

- Add logic to set default image url (base product image) when a variant has not a selected image