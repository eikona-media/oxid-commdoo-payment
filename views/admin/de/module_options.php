<?php
$sLangName = "Deutsch";

$aLang = [
    //<editor-fold desc="API">
    // Group
    'SHOP_MODULE_GROUP_api' => 'API Einstellungen',

    // sCD_clientid
    'SHOP_MODULE_sCD_clientid' => 'Client ID',
    'HELP_SHOP_MODULE_sCD_clientid' => 'Ihre CommDoo Client ID',

    //sCD_sharedsecret
    'SHOP_MODULE_sCD_sharedsecret' => 'Shared Secret',
    'HELP_SHOP_MODULE_sCD_sharedsecret' => 'Mit dem Shared Secret wird sichergestellt, dass übertragene Daten nicht durch Fremdeingriffe manipuliert wurden.<br><br>
    Das "Shared Secret" ist nur Ihnen und der CommDoo bekannt. Geben sie es auf keinen Fall an Dritte weiter!',

    // sCD_baseurl
    'SHOP_MODULE_sCD_baseurl' => 'Base URL',
    'HELP_SHOP_MODULE_sCD_baseurl' => 'Die CommDoo Api URL',

    // sCD_successurl
    'SHOP_MODULE_sCD_successurl' => 'Success URL',
    'HELP_SHOP_MODULE_sCD_successurl' => 'Die URL, die bei einer erfolgreichen Zahlungsinitiierung aufgerufen wird',

    // sCD_failurl
    'SHOP_MODULE_sCD_failurl' => 'Fail URL',
    'HELP_SHOP_MODULE_sCD_failurl' => 'Die URL, die bei einer erfolglosen Zahlungsinitiierung aufgerufen wird',

    // sCD_notificationurl
    'SHOP_MODULE_sCD_notificationurl' => 'Notification URL',
    'HELP_SHOP_MODULE_sCD_notificationurl' => 'Die URL, die nach einer Zahlungsbestätigung aufgerufen wird',
    //</editor-fold>

    'SHOP_MODULE_GROUP_transaction' => 'Transaktionseinstellungen',

    'SHOP_MODULE_sCD_paymentmode' => 'Bezahlverfahren (direkt, oder erst nur reservieren)',
    'SHOP_MODULE_sCD_paymentmode_0' => 'Direkt',
    'SHOP_MODULE_sCD_paymentmode_reservation' => 'Reservieren',

    'SHOP_MODULE_sCD_cancelMode' => 'Behandlung für Bestellungen bei Bezahlabbruch',
    'SHOP_MODULE_sCD_cancelMode_delete' => 'Löschen',
    'SHOP_MODULE_sCD_cancelMode_cancel' => 'Stornieren',

    //<editor-fold desc="Internal Settings">
    // Group
    'SHOP_MODULE_GROUP_internal' => 'Interne Einstellungen (Änderungen können systemkritisch sein)',

    // aCD_PaymentTypes
    "SHOP_MODULE_aCD_PaymentTypes" => "Zahlungsarten",
    "HELP_SHOP_MODULE_aCD_PaymentTypes" => "Die verfügbaren CommDoo Zahlungsarten.<p>
    Ändern Sie diese Einstellung nicht, solange sie nicht explizit dazu aufgefordert werden. Fehlerhafte Änderungen könnten sämtliche Zahlungarten deaktivieren.",

    // aCD_frontend_request_params
    "SHOP_MODULE_aCD_frontend_request_params" => "Request Parameter",
    "HELP_SHOP_MODULE_aCD_frontend_request_params" => "Available request parameters for CommDoo frontend requests.<p>
    Ändern Sie diese Einstellung nicht, solange sie nicht explizit dazu aufgefordert werden. Fehlerhafte Änderungen könnten dazu führen, dass das CommDoo Frontend alle Anfragen ablehnt.",

    // aCD_frontend_response_params
    "SHOP_MODULE_aCD_frontend_successful_response_params" => "Successful Response Parameter",
    "HELP_SHOP_MODULE_aCD_frontend_successful_response_params" => "Available response parameters for CommDoo frontend requests.<p> 
    Ändern Sie diese Einstellung nicht, solange sie nicht explizit dazu aufgefordert werden. Fehlerhafte Änderungen könnten dazu führen, dass keine CommDoo Antworten mehr verifiziert werden können.",

    // aCD_frontend_response_params
    "SHOP_MODULE_aCD_frontend_failed_response_params" => "Failed Response Parameter",
    "HELP_SHOP_MODULE_aCD_frontend_failed_response_params" => "Available response parameters for CommDoo frontend requests.<p> 
    Ändern Sie diese Einstellung nicht, solange sie nicht explizit dazu aufgefordert werden. Fehlerhafte Änderungen könnten dazu führen, dass keine CommDoo Antworten mehr verifiziert werden können.",

    // aCD_frontend_postback_params
    "SHOP_MODULE_aCD_frontend_postback_params" => "Notification Parameter",
    "HELP_SHOP_MODULE_aCD_frontend_postback_params" => "Available notification parameters for CommDoo frontend requests.<p> 
    Ändern Sie diese Einstellung nicht, solange sie nicht explizit dazu aufgefordert werden. Fehlerhafte Änderungen könnten dazu führen, dass keine CommDoo Zahlungsbestätigungen mehr verifiziert werden können.",

    //</editor-fold>
];
