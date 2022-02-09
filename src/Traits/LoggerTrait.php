<?php
declare(strict_types=1);

namespace Eimed\Modules\CommdooPayment\Traits;

use Eimed\Modules\CommdooPayment\Logger;
use OxidEsales\Eshop\Core\Registry;

trait LoggerTrait
{
    /**
     * @var Logger
     */
    protected $logger = null;

    private function debug($message)
    {
        $this->getLogger()->debug($message, [__CLASS__]);
    }

    private function error($message)
    {
        $this->getLogger()->error($message, [__CLASS__, __FUNCTION__]);
    }

    /**
     * Return PayPal logger
     *
     * @return Logger
     */
    protected function getLogger(): Logger
    {
        if (is_null($this->logger)) {
            $session = Registry::getSession();
            $this->logger = new Logger();
            $this->logger->setLoggerSessionId($session->getId());
        }

        return $this->logger;
    }
}