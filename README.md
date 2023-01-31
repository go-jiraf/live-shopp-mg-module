# Live Shopping Magento Module

## Purpose

 This is a module meant to integrate GoJiraf Live Shopping with Magento. 

### API

It constructs and API that provides products data, and very basic information about store configurations.
  
### Checkout redirect

It includes functions that automatically process guests-carts (previously generated from Live Shopping) and redirects the user to the store checkout view.

## How to install

- Require the project through composer:

``` bash
composer require gojiraf/live-shopp-mg-module
```

- Install the module in Magento:

``` bash
php bin/magento setup:upgrade
```

- Compile dependencies:

``` bash
php bin/magento setup:di:compile
```

## How to use

### Integration

The integration process with Gojiraf Live Shopping is about creating a store for your company, and associate it with your Magento store from some access grants and keys that lets our application automatically comunicate with the Magento's system.

Depending on your agreement with Gojiraf, you can either generate an integration manually (recommended option), or do it automatically.

#### Manual integration

It implies to generate an access token and give it to our support area, who will create a store for your company and set the integration.

**Steps:**

1. Go to your Magento admin panel, then to System > Integrations
2. Add a new integration. You can name it "Gojiraf" (there is probably yet another one named "Gojiraf Live Shopping", just ignore it)
3. You must complete the password field with your admin password
4. On the 'API' section, search for "Gojiraf", and check it
5. Click on "Save"
6. On the integrations list, on the just created one, click on "Activate", and then "Allow"
7. Copy the content of the "Access token" field, and send it to our support staff to continue with the integration

The bearer access tokens must be enabled con the system in order to admit it on REST queries. If they are not, you can do it with the CLI:

```bash
bin/magento config:set oauth/consumer/enable_integration_as_bearer 1
```

There is also a config on the admin panel: Stores > Configuration > Services > OAuth > Consumer settings, there you must to "Yes" the option "Allow OAuth Access Tokens to be used as standalone Bearer tokens".

#### Automatic integration

Our integration service can automatically create your Live Shopping account and request the access token to Magento. However, it is not the most recommended option right now, due to some unexpected behaviors, and because it is suitable only to one-site systems.
If you still want to try it, the steps are very simple. After install the Gojiraf's module, you will find a "ready-to-go" integration in your panel.

**Steps:**

1. Go to your Magento admin panel, an then to Stores > Configuration, and in the top input "scope" select your main website.
2. Open the "Store email address" section.
3. In "sales representative", input the email that will be used as user on the Live Shopping account.
4. Go to System > Integrations
5. In the integrations list, you should find "Gojiraf Live Shopping". Click on "Activate", an then on "Allow".

It can take a while to process the whole integration. If it was successfull, you should recieve further instructions on the email address that you set on the step 2. If you don't, you can try again throught the "Reauthorize"/"Activate" option in the Integrations list.
