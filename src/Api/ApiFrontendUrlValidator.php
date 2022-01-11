<?php


namespace Eimed\Modules\CommdooPayment\Api;


class ApiFrontendUrlValidator extends ApiUrlValidatorService
{
    public function __construct()
    {
        $order = $this->getConfigParam("aCD_frontend_request_params");
        parent::__construct($order);
    }
}