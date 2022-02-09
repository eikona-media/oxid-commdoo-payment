<?php

namespace Eimed\Modules\CommdooPayment\Model;

use OxidEsales\Eshop\Application\Model\BasketItem;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class Order
 * @mixin BasketItem
 */
class CommDooBasketItem extends CommDooBasketItem_parent
{
    /**
     * Returns stock control mode
     *
     * @return bool
     */
    public function getStockCheckStatus()
    {
        if (Registry::getSession()->getVariable('commdoo_ignoreStockCheck') === true) {
            return false;
        }
        return $this->_blCheckArticleStock;
    }
}