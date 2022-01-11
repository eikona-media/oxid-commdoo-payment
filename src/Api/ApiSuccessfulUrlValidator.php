<?php


namespace Eimed\Modules\CommdooPayment\Api;


class ApiSuccessfulUrlValidator extends ApiUrlValidatorService
{
    public function __construct()
    {
        $order = $this->getConfigParam("aCD_frontend_successful_response_params");
        parent::__construct($order);
    }
}