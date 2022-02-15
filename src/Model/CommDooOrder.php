<?php


namespace Eimed\Modules\CommdooPayment\Model;

use Eimed\Modules\CommdooPayment\Module;
use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Basket;
use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Application\Model\Order;
use OxidEsales\Eshop\Core\Counter;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class Order
 * @mixin Order
 */
class CommDooOrder extends CommDooOrder_parent
{
    /**
     * Toggles certain behaviours in finalizeOrder for when the customer returns after the payment
     *
     * @var bool
     */
    protected $commdooFinalizeReturnMode = false;

    /**
     * Toggles certain behaviours in finalizeOrder for when order is being finished automatically
     * because customer did not come back to shop
     *
     * @var bool
     */
    protected $commdooFinishOrderReturnMode = false;

    /**
     * Toggles certain behaviours in finalizeOrder for when the the payment is being reinitialized at a later point in time
     *
     * @var bool
     */
    protected $commdooReinitializePaymentMode = false;

    /**
     * State is saved to prevent order being set to transstatus OK during recalculation
     *
     * @var bool|null
     */
    protected $commdooRecalculateOrder = null;

    /**
     * Temporary field for saving the order nr
     *
     * @var int|null
     */
    protected $commdooTmpOrderNr = null;

    private $isCommdooInit = false;

    /**
     * CommDoo Order Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->addFieldName('providerpurpose');
        $this->addFieldName('cdpaymentstatus');
    }

    /**
     * @param Basket $oBasket
     * @return void
     */
    private function commdooInit(Basket $oBasket)
    {
        if (!$this->isCommdooInit) {
            $returnAfterPayment = (Registry::getRequest()->getRequestEscapedParameter('fnc') == 'handleCommDooReturn');
            if (Module::supportsPaymentType($oBasket->getPaymentId()) === true && $returnAfterPayment) {
                $this->commdooFinalizeReturnMode = true;
            }
            if (Registry::getSession()->getVariable('commdooReinitializePaymentMode')) {
                $this->commdooReinitializePaymentMode = true;
            }
            $this->isCommdooInit = true;
        }
    }

    public function isCommDooOrder()
    {
        return Module::supportsPaymentType($this->oxorder__oxpaymenttype->value);
    }

    /**
     * Used to trigger the _setNumber() method before the payment-process during finalizeOrder to have the order-number there already
     *
     * @return void
     */
    public function commdooSetOrderNumber()
    {
        if (!$this->oxorder__oxordernr->value) {
            $this->_setNumber();
        }
    }

    /**
     * Returns if the order is marked as paid, since OXID doesnt have a proper flag
     *
     * @return bool
     */
    public function commdooIsPaid()
    {
        if (!empty($this->oxorder__oxpaid->value) && $this->oxorder__oxpaid->value != "0000-00-00 00:00:00") {
            return true;
        }
        return false;
    }

    /**
     * This overloaded method sets the return mode flag so that the behaviour of some methods is changed when the customer
     * returns after successful payment from commdoo
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket              Basket object
     * @param object                                     $oUser                Current User object
     * @param bool                                       $blRecalculatingOrder Order recalculation
     * @return integer
     */
    public function finalizeOrder(\OxidEsales\Eshop\Application\Model\Basket $oBasket, $oUser, $blRecalculatingOrder = false)
    {
        $this->commdooInit($oBasket);
        $this->commdooRecalculateOrder = $blRecalculatingOrder;
        return parent::finalizeOrder($oBasket, $oUser, $blRecalculatingOrder);
    }

    /**
     * Checks if payment used for current order is available and active.
     * Throws exception if not available
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket    $oBasket basket object
     * @param \OxidEsales\Eshop\Application\Model\User|null $oUser   user object
     *
     * @return null
     */
    public function validatePayment($oBasket, $oUser = null)
    {
        $this->commdooInit($oBasket);
        if ($this->commdooReinitializePaymentMode === false) {
            $oReflection = new \ReflectionMethod(\OxidEsales\Eshop\Application\Model\Order::class, 'validatePayment');
            $aParams = $oReflection->getParameters();
            if (count($aParams) == 1) {
                return parent::validatePayment($oBasket); // Oxid 6.1 didnt have the $oUser parameter yet
            }
            return parent::validatePayment($oBasket, $oUser);
        }
    }

    /**
     * Extension: Order already existing because order was created before the user was redirected to commdoo,
     * therefore no stock validation needed. Otherwise an exception would be thrown on return when last product in stock was bought
     *
     * @param object $oBasket basket object
     */
    public function validateStock($oBasket)
    {
        $this->commdooInit($oBasket);
        if ($this->commdooFinalizeReturnMode === false) {
            return parent::validateStock($oBasket);
        }
    }

    /**
     * Validates order parameters like stock, delivery and payment
     * parameters
     *
     * @param \OxidEsales\Eshop\Application\Model\Basket $oBasket basket object
     * @param \OxidEsales\Eshop\Application\Model\User   $oUser   order user
     *
     * @return null
     */
    public function validateOrder($oBasket, $oUser)
    {
        $this->commdooInit($oBasket);
        if ($this->commdooFinishOrderReturnMode === false) {
            return parent::validateOrder($oBasket, $oUser);
        }
    }

    /**
     * Extension: Return false in return mode
     *
     * @param string $sOxId order ID
     * @return bool
     */
    protected function _checkOrderExist($sOxId = null)
    {
        if ($this->commdooFinalizeReturnMode === false && $this->commdooReinitializePaymentMode === false) {
            return parent::_checkOrderExist($sOxId);
        }
        return false; // In finalize return situation the order will already exist, but thats ok
    }

    public function _executePayment(Basket $oBasket, $oUserpayment)
    {
        if ($this->commdooFinalizeReturnMode === false) {
            return parent::_executePayment($oBasket, $oUserpayment);
        }
        return true;
    }

    /**
     * Tries to fetch and set next record number in DB. Returns true on success
     *
     * @return bool
     */
    protected function _setNumber()
    {
        if (!empty($this->oxorder__oxordernr->value) && $this->commdooReinitializePaymentMode === true) {
            return true;
        }
        return parent::_setNumber();
    }

    /**
     * Extension: In return mode load order from DB instead of generation from basket because it already exists
     *
     * @param \OxidEsales\EshopCommunity\Application\Model\Basket $oBasket Shopping basket object
     */
    protected function _loadFromBasket(\OxidEsales\Eshop\Application\Model\Basket $oBasket)
    {
        $this->commdooInit($oBasket);
        if ($this->commdooFinalizeReturnMode === false) {
            return parent::_loadFromBasket($oBasket);
        }
        $this->load(Registry::getSession()->getVariable('sess_challenge'));
    }

    /**
     * Extension: In return mode load existing userpayment instead of creating a new one
     *
     * @param string $sPaymentid used payment id
     * @return \OxidEsales\Eshop\Application\Model\UserPayment
     */
    protected function _setPayment($sPaymentid)
    {
        if ($this->commdooFinalizeReturnMode === false) {
            $mParentReturn = parent::_setPayment($sPaymentid);
            return $mParentReturn;
        }
        $oUserpayment = oxNew(\OxidEsales\Eshop\Application\Model\UserPayment::class);
        $oUserpayment->load($this->oxorder__oxpaymentid->value);
        return $oUserpayment;
    }

    /**
     * Recreates basket from order information
     *
     * @return object
     */
    public function commdooRecreateBasket()
    {
        Registry::getSession()->setVariable('commdoo_ignoreStockCheck', true);

        $oBasket = $this->_getOrderBasket();

        // add this order articles to virtual basket and recalculates basket
        $aItems = $this->getOrderArticles(true);
        /** @var BasketItem $item */
        foreach($aItems as $item) {
            $item->getArticle(false, null, true);
        }

        $this->_addOrderArticlesToBasket($oBasket, $aItems);

        // recalculating basket
        $oBasket->calculateBasket(true);

        Registry::getSession()->setVariable('sess_challenge', $this->getId());
        Registry::getSession()->setVariable('paymentid', $this->oxorder__oxpaymenttype->value);
        Registry::getSession()->setBasket($oBasket);

        Registry::getSession()->deleteVariable('commdoo_ignoreStockCheck');

        return $oBasket;
    }

    public function commdooPrepareFinalizeOrder()
    {
        $session = Registry::getSession();
        $oBasket = $session->getBasket();
        if ($oBasket) {
            $oBasket = $this->commdooRecreateBasket();
            $session->setBasket($oBasket);
        }

        $this->_oBasket = $oBasket;

        $this->commdooFinalizeReturnMode = true;
        $this->commdooFinishOrderReturnMode = true;
    }
}