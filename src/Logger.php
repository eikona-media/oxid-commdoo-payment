<?php

namespace Eimed\Modules\CommdooPayment;

/**
 * Base logger class
 */
class Logger
{
    /**
     * Logger session id.
     *
     * @var string
     */
    protected $loggerSessionId;

    /**
     * Order Id.
     *
     * @var string
     */
    protected $orderId;

    /**
     * Log title
     */
    protected $logTitle = '';

    /**
     * @var array
     */
    protected $dataArray = [];

    /**
     * Sets logger session id.
     *
     * @param string $id session id
     */
    public function setLoggerSessionId($id)
    {
        $this->loggerSessionId = $id;
    }

    /**
     * Returns loggers session id.
     *
     * @return string
     */
    public function getLoggerSessionId()
    {
        return $this->loggerSessionId;
    }

    /**
     * Sets order id.
     *
     * @param string $id session id
     */
    public function setOrderId($id)
    {
        $this->orderId = $id;
    }

    /**
     * Returns order id.
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Returns full log file path.
     *
     * @return string
     */
    protected function getLogFilePath()
    {
        $logDirectoryPath = \OxidEsales\Eshop\Core\Registry::getConfig()->getLogsDir() . 'commdoo-payment/';
        if (!is_dir($logDirectoryPath)) {
            mkdir($logDirectoryPath, 0777, true);
        }
        return $logDirectoryPath . date('y-m-d') . '.log';
    }

    /**
     * Set log title.
     *
     * @param string $title Log title
     */
    public function setTitle($title)
    {
        $this->logTitle = $title;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->logTitle;
    }

    /**
     * Writes log message.
     *
     * @param mixed $logData logger data
     */
    public function log($logData)
    {
        $handle = fopen($this->getLogFilePath(), "a+");
        if ($handle !== false) {
            if (is_string($logData)) {
                parse_str($logData, $result);
            } else {
                $result = $logData;
            }

            if (is_array($result)) {
                foreach ($result as $key => $value) {
                    if (is_string($value)) {
                        $result[$key] = urldecode($value);
                    }
                }
            }

            fwrite($handle, "======================= " . $this->getTitle() . " [" . date("Y-m-d H:i:s") . "] ======================= #\n\n");
            fwrite($handle, "SESS ID: " . $this->getLoggerSessionId() . "\n");
            fwrite($handle, "ORDER ID: " . $this->getOrderId() . "\n");
            fwrite($handle, trim(var_export($result, true)) . "\n\n");
            if (!empty($this->dataArray)) {
                fwrite($handle, trim(json_encode($this->dataArray, JSON_PRETTY_PRINT)) . "\n\n");
            }
            fclose($handle);
        }

        //resetting log
        $this->setTitle('');
        $this->dataArray = [];
    }

    public function debug($message, $context = [])
    {
        $this->dataArray[] = ['DEBUG', $message, $context];
    }

    public function error($message, $context = [])
    {
        $this->dataArray[] = ['ERROR', $message, $context];
    }
}
