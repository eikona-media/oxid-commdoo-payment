<?php


namespace Eimed\Modules\CommdooPayment\Model;


use Eimed\Modules\CommdooPayment\Api\ApiFrontendUrlValidator;
use Eimed\Modules\CommdooPayment\Constants;
use Eimed\Modules\CommdooPayment\Traits\LoggerTrait;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Application\Model\OrderArticle;
use OxidEsales\Eshop\Application\Model\PaymentGateway;
use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class CommDooPaymentGateway
 * @mixin PaymentGateway
 */
class CommDooPaymentGateway extends CommDooPaymentGateway_parent
{
    use LoggerTrait;

    /**
     * @param double $dAmount
     * @param CommDooOrder $oOrder
     * @return bool
     */
    public function executePayment($dAmount, &$oOrder): bool
    {
        if(!$oOrder->isCommDooOrder()) {
            Registry::getSession()->deleteVariable('commdooIsRedirected');
            return parent::executePayment($dAmount, $oOrder);
        }

        if (!$this->_isActive()) {
            // return false;
        }

        $oOrder->commdooSetOrderNumber();

        $this->getLogger()->setOrderId($oOrder->oxorder__oxordernr->value);

        $config = Registry::getConfig();
        $session = Registry::getSession();
        $db = DatabaseProvider::getDb();

        $internal_order_id = $session->getVariable('sess_challenge');
        $request = new ApiFrontendUrlValidator();

        // independent params
        $request->set("timestamp", date("dmYHis"));
        $payment_key = substr($oOrder->oxorder__oxpaymenttype->value, 16);
        $request->set("payment", $payment_key);

        // config params
        $request->set("clientid", $config->getConfigParam('sCD_clientid'));
        $lang = Registry::getLang()->getLanguageAbbr();

        $callbackUrl = Registry::getConfig()->getCurrentShopUrl().'index.php?cl=order&fnc=handleCommDooReturn';
        $callbackUrl .= $this->getAdditionalParameters($oOrder);
        $request->set("successurl", $callbackUrl);
        $request->set("failurl", $callbackUrl);

        // order params
        switch (strtolower($oOrder->oxorder__oxbillsal->value)) {
            case 'mr':
                $request->set("salutation", "Herr");
                break;
            case 'mrs':
                $request->set("salutation", "Frau");
                break;
        }

        $paymentMode = $config->getConfigParam('sCD_paymentmode');
        if (!empty($paymentMode)) {
            $request->set("paymentmode", $paymentMode);
        }
        $request->set("amount", $this->getPrice($dAmount));
        $request->set("currency", $oOrder->oxorder__oxcurrency->value);
        $request->set("firstName", $oOrder->oxorder__oxbillfname->value);
        $request->set("lastName", $oOrder->oxorder__oxbilllname->value);
        $request->set("street", $oOrder->oxorder__oxbillstreet->value);
        $request->set("houseNumber", $oOrder->oxorder__oxbillstreetnr->value);
        $request->set("postalCode", $oOrder->oxorder__oxbillzip->value);
        $request->set("city", $oOrder->oxorder__oxbillcity->value);
        $request->set("emailAddress", $oOrder->oxorder__oxbillemail->value);
        $sCountryId = $oOrder->oxorder__oxbillcountryid->value;
        $request->set("country", $db->getOne(sprintf("SELECT oxisoalpha3 FROM oxcountry WHERE oxid = '%s'", $sCountryId)));
        $request->set("referenceid", $internal_order_id);

        /** @var OrderArticle $orderArticle */
        $pos = 1;
        foreach ($oOrder->getOrderArticles() as $orderArticle) {
            $orderPrefix = "item$pos";
            $request->set("$orderPrefix-id", $orderArticle->oxorderarticles__oxartid->value);
            $request->set("$orderPrefix-name", $orderArticle->oxorderarticles__oxtitle->value);
            $request->set("$orderPrefix-description", $orderArticle->oxorderarticles__oxshortdesc->value);
            $request->set("$orderPrefix-quantity", $orderArticle->oxorderarticles__oxamount->value);
            $request->set("$orderPrefix-totalprice", $this->getPrice($orderArticle->oxorderarticles__oxprice->value));
            $request->set("$orderPrefix-currency", $oOrder->oxorder__oxcurrency->value);
            //$request->set("$orderPrefix-taxpercentage", $orderArticle->oxorderarticles__oxvat->value);
            //$request->set("$orderPrefix-taxamount", $this->getPrice($orderArticle->oxorderarticles__oxvatprice->value));
            $pos++;
        }

        $deliveryCost = $oOrder->oxorder__oxdelcost->value;
        if ($deliveryCost > 0) {
            $orderPrefix = "item$pos";
            $request->set("$orderPrefix-id", 'DELIVERY');
            $request->set("$orderPrefix-name", 'Versandkosten');
            $request->set("$orderPrefix-description", '');
            $request->set("$orderPrefix-quantity", 1);
            $request->set("$orderPrefix-totalprice", $this->getPrice($deliveryCost));
            $request->set("$orderPrefix-currency", $oOrder->oxorder__oxcurrency->value);
        }

        //$request->check();

        $sPaymentUrl = $request->getUrl();

        $logData = array_replace([], $request->getValues());
        $logData['hash'] = $request->getHash();
        $logData['redirectUrl'] = $sPaymentUrl;

        $this->getLogger()->setTitle('Commdoo Redirect');
        $this->getLogger()->log($logData);

        // change order status
        $oOrder->oxorder__oxtransstatus = new Field(Constants::TRANSACTION_STATUS_PENDING);
        $oOrder->oxorder__cdpaymentstatus = new Field(Constants::PAYMENT_STATUS_STARTED);
        $oOrder->oxorder__oxfolder = new Field('ORDERFOLDER_NEW');
        $oOrder->save();

        // redirect to CommDoo
        Registry::getSession()->setVariable('commdooIsRedirected', true);
        Registry::getUtils()->redirect($sPaymentUrl);

        return false;
    }

    /**
     * @param Order $oOrder
     * @return string
     */
    private function getAdditionalParameters(Order $oOrder)
    {
        $oRequest = Registry::getRequest();
        $oSession = Registry::getSession();

        $sAddParams = '';

        $copyParameters = [
            'stoken',
            'sDeliveryAddressMD5',
            'oxdownloadableproductsagreement',
            'oxserviceproductsagreement',
        ];

        foreach ($copyParameters as $sParamName) {
            $sValue = $oRequest->getRequestEscapedParameter($sParamName);
            if (!empty($sValue)) {
                $sAddParams .= '&'.$sParamName.'='.$sValue;
            }
        }

        $sSid = $oSession->sid(true);
        if ($sSid != '') {
            $sAddParams .= '&'.$sSid;
        }

        if (!$oRequest->getRequestEscapedParameter('stoken')) {
            $sAddParams .= '&stoken='.$oSession->getSessionChallengeToken();
        }
        $sAddParams .= '&ord_agb=1';
        $sAddParams .= '&rtoken='.$oSession->getRemoteAccessToken();
        $sAddParams .= '&onr='.$oOrder->oxorder__oxordernr->value;

        return $sAddParams;
    }

    private function getPrice(float $price): int
    {
        $price = Registry::getUtils()->fRound($price);
        return intval($price * 100);
    }
}