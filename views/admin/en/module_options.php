<?php
$sLangName = "English";

$aLang = [
    //<editor-fold desc="API Settings">
    // Group
    'SHOP_MODULE_GROUP_api' => 'API settings',

    // sCD_clientid
    'SHOP_MODULE_sCD_clientid' => 'Client ID',
    'HELP_SHOP_MODULE_sCD_clientid' => 'Your CommDoo Client ID',

    //sCD_sharedsecret
    'SHOP_MODULE_sCD_sharedsecret' => 'Shared secret',
    'HELP_SHOP_MODULE_sCD_sharedsecret' => 'The shared Secret verifies the integrity of the transmitted data.<p>
    The secret is only known to you and CommDoo. Do not share this string with any third party!',

    // sCD_baseurl
    'SHOP_MODULE_sCD_baseurl' => 'Base URL',
    'HELP_SHOP_MODULE_sCD_baseurl' => 'The CommDoo Api URL',

    // sCD_successurl
    'SHOP_MODULE_sCD_successurl' => 'Success URL',
    'HELP_SHOP_MODULE_sCD_successurl' => 'After a successful payment initialisation, the user gets redirected here',

    // sCD_failurl
    'SHOP_MODULE_sCD_failurl' => 'Fail URL',
    'HELP_SHOP_MODULE_sCD_failurl' => 'After a failed payment initialisation, the user gets redirected here',

    // sCD_notificationurl
    'SHOP_MODULE_sCD_notificationurl' => 'Notification URL',
    'HELP_SHOP_MODULE_sCD_notificationurl' => 'After a payment confirmation, ths url is called',
    //</editor-fold>

    'SHOP_MODULE_GROUP_transaction' => 'Transaction settings',

    'SHOP_MODULE_sCD_paymentmode' => 'payment method (directly or reservation)',
    'SHOP_MODULE_sCD_paymentmode_0' => 'directly',
    'SHOP_MODULE_sCD_paymentmode_reservation' => 'reservation',

    'SHOP_MODULE_sCD_cancelMode' => 'Handling for orders in case of payment cancellation',
    'SHOP_MODULE_sCD_cancelMode_delete' => 'Delete',
    'SHOP_MODULE_sCD_cancelMode_cancel' => 'Cancel',

    //<editor-fold desc="Internal Settings">
    // Group
    'SHOP_MODULE_GROUP_internal' => 'Internal settings (edit can break system)',

    // aCD_PaymentTypes
    "SHOP_MODULE_aCD_PaymentTypes" => "Payment types",
    "HELP_SHOP_MODULE_aCD_PaymentTypes" => "All available CommDoo Payment types.<p>
    Do not modify these unless explicitly told to. You may end up without any working payment types.",

    // aCD_frontend_request_params
    "SHOP_MODULE_aCD_frontend_request_params" => "Request parameters",
    "HELP_SHOP_MODULE_aCD_frontend_request_params" => "Available request parameters for CommDoo frontend requests.<p> 
    Do not modify these unless explicitly told to. You may end up with a system, that is unable to connect to the CommDoo frontend.",

    // aCD_frontend_response_params
    "SHOP_MODULE_aCD_frontend_successful_response_params" => "Successful response parameters",
    "HELP_SHOP_MODULE_aCD_frontend_successful_response_params" => "Available response parameters for CommDoo frontend requests.<p>
    Do not modify these unless explicitly told to. You may end up with a system, that is unable to verify any CommDoo response.",

    // aCD_frontend_response_params
    "SHOP_MODULE_aCD_frontend_failed_response_params" => "Failed response parameters",
    "HELP_SHOP_MODULE_aCD_frontend_failed_response_params" => "Available response parameters for CommDoo frontend requests.<p>
    Do not modify these unless explicitly told to. You may end up with a system, that is unable to verify any CommDoo response.",

    // aCD_frontend_postback_params
    "SHOP_MODULE_aCD_frontend_postback_params" => "Notification parameters",
    "HELP_SHOP_MODULE_aCD_frontend_postback_params" => "Available notification parameters for CommDoo frontend requests.<p> 
    Do not modify these unless explicitly told to. You may end up with a system, that is unable to verify any CommDoo payment notification.",

    //</editor-fold>


];
