<?php


namespace Eimed\Modules\CommdooPayment\Api;


class ApiFaildUrlValidator extends ApiUrlValidatorService
{
    public function __construct()
    {
        $order = $this->getConfigParam("aCD_frontend_failed_response_params");
        parent::__construct($order);
    }
}