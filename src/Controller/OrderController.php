<?php
declare(strict_types=1);

namespace Eimed\Modules\CommdooPayment\Controller;

use Eimed\Modules\CommdooPayment\Api\ApiFaildUrlValidator;
use Eimed\Modules\CommdooPayment\Api\ApiSuccessfulUrlValidator;
use Eimed\Modules\CommdooPayment\Api\ApiUrlValidatorService;
use Eimed\Modules\CommdooPayment\Constants;
use Eimed\Modules\CommdooPayment\Model\CommDooOrder;
use Eimed\Modules\CommdooPayment\Module;
use Eimed\Modules\CommdooPayment\Traits\LoggerTrait;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\Payment;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\Database\Adapter\Doctrine\Database;
use Weing\Modules\ProhandelConnector\Exception\CommdooPaymentException;

/**
 * CommDoo Payment OrderController
 * @mixin \OxidEsales\Eshop\Application\Controller\OrderController
 */
class OrderController extends OrderController_parent
{
    use LoggerTrait;

    /**
     * @return string
     */
    public function render()
    {
        $sSessChallenge = Registry::getSession()->getVariable('sess_challenge');
        $blCommdooIsRedirected = Registry::getSession()->getVariable('commdooIsRedirected');
        if (!empty($sSessChallenge) && $blCommdooIsRedirected === true) {
            $oOrder = oxNew(Order::class);
            if ($oOrder->load($sSessChallenge) === true) {
                if ($oOrder->oxorder__oxtransstatus->value !== Constants::TRANSACTION_STATUS_OK) {
                    $oOrder->cancelOrder();
                }
            }
        }
        Registry::getSession()->deleteVariable('commdooIsRedirected');
        return parent::render();
    }

    /**
     * Handles Commdoo Return Callback
     * @return false|string|null
     */
    public function handleCommDooReturn()
    {
        /** @var CommDooOrder $oOrder */
        $oOrder = null;

        try {
            $oOrder = $this->commdooGetOrder();
            if ($oOrder === null) {
                $this->getLogger()->debug([
                    'sess_challenge' => Registry::getSession()->getVariable('sess_challenge'),
                    'order_number' => Registry::getRequest()->getRequestEscapedParameter('onr')
                ]);
                throw new CommdooPaymentException(Registry::getLang()->translateString('COMMDOO_ERROR_ORDER_NOT_FOUND'));
            }

            if ($oOrder->getBasket() === null) {
                $oOrder->commdooRecreateBasket();
            }

            /** @var Payment $oPayment */
            $oPayment = $this->getPayment();
            if ($oPayment && Module::supportsPaymentType($oPayment->getId())) {

                /** @var ApiUrlValidatorService $response */
                $validator = $this->isRequestValid($_REQUEST);
                if ($validator === null) {
                    throw new CommdooPaymentException('Request validation failed');
                }

                Registry::getSession()->deleteVariable('commdooIsRedirected');

                $this->getLogger()->setOrderId($oOrder->oxorder__oxordernr->value);

                $aResult = $this->handleRequestValues($validator, $oOrder);
                if ($aResult['success'] === false) {
                    $status = Constants::PAYMENT_STATUS_CANCELED;

                    $sErrorIdent = 'COMMDOO_ERROR_SOMETHING_WENT_WRONG';
                    if ($aResult['status'] == 'canceled') {
                        $sErrorIdent = 'COMMDOO_ERROR_ORDER_CANCELED';
                    } elseif ($aResult['status'] == 'failed') {
                        $status = Constants::PAYMENT_STATUS_FAILED;
                        $sErrorIdent = 'COMMDOO_ERROR_ORDER_FAILED';
                    }

                    $oOrder->oxorder__cdpaymentstatus = new Field($status);
                    $oOrder->save();

                    throw new CommdooPaymentException(Registry::getLang()->translateString($sErrorIdent));
                } else {
                    $this->getLogger()->setTitle('handleCommDooReturn - SUCCESS');
                    $this->getLogger()->log($_REQUEST);
                    $oOrder->commdooPrepareFinalizeOrder();

                    $bReturn = parent::execute();

                    // Provide backward compatibility
                    if ($bReturn) {
                        if ($oOrder->oxorder__cdpaymentstatus->value === Constants::PAYMENT_STATUS_PENDING) {
                            $oOrder->oxorder__oxtransstatus->setValue(Constants::TRANSACTION_STATUS_PENDING);
                            $oOrder->save();
                        }
                    }

                    return $bReturn;
                }
            }
        } catch (\Throwable $exception) {
            $this->getLogger()->setTitle($exception->getMessage());
            $this->getLogger()->debug($exception->getTrace());
            $this->getLogger()->log($_REQUEST);
            Registry::getLogger()->error($exception->getMessage(), $exception->getTrace());

            if ($oOrder) {
                $oOrder->oxorder__oxtransstatus = new Field(Constants::TRANSACTION_STATUS_FAILED);
                $oOrder->oxorder__oxfolder = new Field('ORDERFOLDER_PROBLEMS');

                $cancelMode = Registry::getConfig()->getConfigParam('sCD_cancelMode');
                if (!empty($cancelMode) && $cancelMode === 'delete') {
                    $oOrder->delete();
                } else {
                    $oOrder->cancelOrder();
                }

                Registry::getSession()->deleteVariable('sess_challenge');

                if ($oOrder->oxorder__cdpaymentstatus->value === Constants::PAYMENT_STATUS_FAILED) {
                    Registry::getSession()->setVariable('payerror', 2);
                    $sPaymentUrl = Registry::getConfig()->getCurrentShopUrl() . 'index.php?cl=payment&payerror=2';
                } else {
                    $sPaymentUrl = Registry::getConfig()->getCurrentShopUrl() . 'index.php?cl=payment';
                }
                Registry::getUtils()->redirect($sPaymentUrl);
            }
        }
        return false;
    }

    private function handleRequestValues(ApiUrlValidatorService $request, Order $oOrder)
    {
        $transactionStatus = $request->get("transactionstatus");
        $internal_order_id = $request->get("referenceid");
        $providerpurpose   = $request->get("providerpurpose");
        $referenceid       = $request->get("referenceid");
        $transactionid     = $request->get("transactionid");
        $amount            = $request->get("amount");
        $errornumber       = $request->get("errornumber");
        $errortext         = $request->get("errortext");

        $oOrder->oxorder__providerpurpose = new Field($providerpurpose);

        // Check if is faild callback
        if (!empty($errornumber) || !empty($errortext)) {
            $oOrder->oxorder__providerpurpose = new Field($errortext);
            $oOrder->cancelOrder();
            return ['success' => false, 'status' => 'failed', 'errorId' => $errornumber, 'error' => $errortext];
        }

        // Check transaction status
        switch($transactionStatus)
        {
            case 'Reserved':
            case 'Charged':
                break;

            default:
                $oOrder->cancelOrder();
                return ['success' => false, 'status' => $transactionStatus, 'error' => 'Unsupported transaction status'];
        }

        // Order is canceled? Revert....
        if ($oOrder->oxorder__oxstorno->value == 1) {
            $oOrder->oxorder__oxstorno = new \OxidEsales\Eshop\Core\Field(0);
            if ($oOrder->save()) {
                // canceling ordered products
                foreach ($this->getOrderArticles() as $oOrderArticle) {
                    if ($oOrderArticle->oxorderarticles__oxstorno->value == 1) {
                        $oOrderArticle->oxorderarticles__oxstorno = new \OxidEsales\Eshop\Core\Field(0);
                        if ($oOrderArticle->save()) {
                            $oOrderArticle->updateArticleStock($oOrderArticle->oxorderarticles__oxamount->value * -1, $this->getConfig()->getConfigParam('blAllowNegativeStock'));
                        }
                    }
                }
            }
        }

        switch($transactionStatus)
        {
            case 'Reserved':
                $oOrder->oxorder__oxtransid = new Field("{$referenceid}:{$transactionid}");
                $oOrder->oxorder__cdpaymentstatus = new Field(Constants::PAYMENT_STATUS_PENDING);
                break;

            case 'Charged':
                $iAmound = intval($amount) / 100;
                if (abs($iAmound - $oOrder->oxorder__oxtotalordersum->value) < 0.01) {
                    $timestamp = date("Y-m-d H:i:s");
                    $this->debug("Saving date as $timestamp");
                    $oOrder->oxorder__oxpaid = new Field ($timestamp);
                    $oOrder->oxorder__cdpaymentstatus = new Field(Constants::PAYMENT_STATUS_OK);
                }
                break;
        }

        $oOrder->save();

        return ['success' => true, 'status' => $transactionStatus];
    }

    /**
     * @param $request
     * @return ApiUrlValidatorService|null
     */
    private function isRequestValid($request): ?ApiUrlValidatorService
    {
        if (!isset($request['clientid'])) {
            $this->getLogger()->error("ClientID was not set!");
            return null;
        }

        $clientID = Registry::getConfig()->getConfigParam('sCD_clientid');

        if ($request['clientid'] != $clientID) {
            $this->getLogger()->error("ClientID mismatch!");
            $this->getLogger()->error("Received clientID was '" . $request['clientid'] . "', but internal ID is '$clientID'");
            return null;
        }

        $response = new ApiSuccessfulUrlValidator();
        if (!empty($request['errortext'])) {
            $response = new ApiFaildUrlValidator();
        }

        foreach ($request as $key => $value) {
            $response->set($key, $value);
        }

        if (empty($request['errortext'])) {
            $hash = $response->getHash();

            if (strtolower($hash) != strtolower($request['hash'])) {
                $this->getLogger()->error("Hash mismatch!");
                $this->getLogger()->error("Received hash was '" . $request['hash'] . "', but internal hash is '$hash'");
                return null;
            }
        }

        return $response;
    }

    /**
     * @return CommDooOrder|null
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    protected function commdooGetOrder(): ?CommDooOrder
    {
        $sOrderId = Registry::getSession()->getVariable('sess_challenge');
        $order = $this->loadOrder($sOrderId);
        if ($order === null) {
            $sOrderNumber = Registry::getRequest()->getRequestEscapedParameter('onr');
            $db = DatabaseProvider::getDb();
            $sOrderId = $db->getOne("SELECT oxid FROM oxorder where OXORDERNR = '$sOrderNumber'");
            return $this->loadOrder($sOrderId);
        }
        return $order;
    }

    private function loadOrder($sOrderId)
    {
        if (!empty($sOrderId)) {
            $oOrder = oxNew(Order::class);
            $oOrder->load($sOrderId);
            if ($oOrder->isLoaded() === true) {
                $this->order = $oOrder;
                return $oOrder;
            }
        }
        return null;
    }
}
