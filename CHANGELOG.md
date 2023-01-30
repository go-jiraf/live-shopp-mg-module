# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

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