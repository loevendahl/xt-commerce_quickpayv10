README for Quickpay Payment module protocol 10 (version 2.0)


Please contact kl@blkom.dk for questions, comments, feature requests and professional support.


ACKNOWLEDGEMENT

Quickpay payment module is developed for Quickpay by Kim Løvendahl, kl@blkom.dk 2016.


IMPORTANT:
Using this module you acknowledge, that the Author can not be made responsible for any kind of damages, 
errors or problems caused by wrong or correct implementation of this module.


REQUIREMENTS:
You must have the following:
- XT:Commerce version 5 or 4.2 webshop
- QuickPay Payment manager account login (get it here: https://manage.quickpay.net).
- Credit card aquirer agreement. Could be Clearhaus, Nets and/or others. Must be set active in the Quickpay v10 manager.
- PHP version 5
- The php-extension Curl must be installed on your webserver and access to quickpay.net allowed by your web hosting provider (normally this is allowed) in order to use this module.

If you wish to use danish paymentmethods (marked with (DK) in the plugin admin ), your customers must be able to select DKK currency. Otherwise the danish payment methods will not be shown to the customer.
  
  
CONFIGURATION NOTES

You can access all necessary settings in the "integration" menu in your version 10 Quickpay manager (https://manage.quickpay.net)



INSTALL INSTRUCTIONS:


#####################################################################################
STEP 0
BACKUP BACKUP BACKUP BACKUP BACKUP and lastly BACKUP

#####################################################################################
STEP 1

copy the folder xt_quickpay and all containing folders and files to your webshop plugin-directory
	
  
#####################################################################################
STEP 2

Go into the Shop Administration->plugins->uninstalled plugins.

install the module "Quickpay".


#####################################################################################
STEP 3
Go into the Shop Administration->Configuration->Payment Methods and activate the plugin.

The plugin must be activated in order to select payment method(s) .

Open the plugin and fill in all necessary settings. These are Merchant ID, Merchant private key and API user key

Necessary setting values can be found in the Quickpay manager application at https://manage.quickpay.net in the section "integration".

You can capture partially, refund partially or cancel payments in the order transaction toolbox in your webshop Administration->Orders->(orderdetail)

please note:
You can configure your shop for either single payment or subscription payment, but Subscription transaction admin and subscription product handling is not implemented in this standard version. Please contact developer at kl@blkom.dk for implementation of these features. You can always use the Quickpay manager for subscription and recurring payments handling. 
