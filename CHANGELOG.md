# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.3.2] 2024-07-03

### Fix

- In configurable products, use the base product "price" field for promotional price (lowest price), and "originalPrice" for original price (highest price)

- The prices formatting is fixed to take in account decimal values

- The configurable products variants (children) that are out of stock are being now added to the product state representation anyway, with stock zero.

### Refactor

- Refactor code on ConfigurableProductBuilder.php

## [1.3.0] 2024-03-12

### Feature

- Add a response header to the /productlist endpoint ('X-Total-Count') with the count of filtered and built products

## [1.2.4] 2023-10-18

### Fix

- Remove commas not supported by PHP7

## [1.2.3] 2023-09-27

### Fix

- Downgrade the PHP Version requirement (7.3 or higher, instead of 8.0/8.1)

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