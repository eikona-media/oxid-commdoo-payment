<?php


namespace Eimed\Modules\CommdooPayment;

use OxidEsales\Eshop\Core\DbMetaDataHandler;
use OxidEsales\Eshop\Core\Field;
use OxidEsales\Eshop\Core\Registry;

class Module
{
    public static function onActivate()
    {
        $fields = [
            'oxorder' => [
                [
                    'sql' => 'VARCHAR(50) NULL',
                    'column' => 'providerpurpose'
                ],
                [
                    'sql' => 'VARCHAR(50) NULL',
                    'column' => 'cdpaymentstatus'
                ],
            ],
        ];

        foreach($fields as $table => $values) {
            foreach($values as $value){
                if(!static::columnExists($table, $value['column'])) {
                    static::createColumn($table, $value['column'], $value['sql']);
                }
            }
        }

        self::addPaymentMethods();
        self::enablePaymentMethods();

    }

    public static function onDeactivate()
    {
        // self::disablePaymentMethods();
    }

    /**
     * @param $type
     * @return bool
     */
    public static function supportsPaymentType($type): bool
    {
        if (strpos($type, 'commdoo_payment_') !== false) {
            $key = substr($type, 16);
            return key_exists($key, self::getCommdooPayments());
        }

        return false;
    }

    public static function getLogger(): \Psr\Log\LoggerInterface
    {

    }

    private static function getCommdooPayments()
    {
        return array(
            "santander" => array(
                "display_name" => "Kauf auf Rechnung",
                "descriptions" => array(
                    "en" => "",
                    "de" => "",
                ),
            ),
            "zinia" => array(
                "display_name" => "Kauf auf Rechnung",
                "descriptions" => array(
                    "en" => "",
                    "de" => "",
                ),
            ),
        );
    }

    protected static function tableExists($sTableName)
    {
        $oDbMetaDataHandler = oxNew(DbMetaDataHandler::class);
        return $oDbMetaDataHandler->tableExists($sTableName);
    }

    protected static function createColumn($table, $column, $sql)
    {
        $db = \OxidEsales\EshopCommunity\Core\DatabaseProvider::getDb();
        $sSql = "ALTER TABLE `" . $table . "`
                  ADD `" . $column . "` " . $sql . ";";

        $db->execute($sSql);
    }

    protected static function updateColumn($table, $column, $sql)
    {
        $db = \OxidEsales\EshopCommunity\Core\DatabaseProvider::getDb();

        $comment = self::getColumnComment($table, $column);
        if (!empty($comment)) {
            $comment = "COMMENT '{$comment}'";
        }

        $sSql = "ALTER TABLE `{$table}` CHANGE `{$column}` `{$column}` {$sql} {$comment};";

        $db->execute($sSql);
    }

    protected static function columnExists($table, $column)
    {
        $oDbHandler = oxNew(DbMetaDataHandler::class);
        return $oDbHandler->tableExists($table) && $oDbHandler->fieldExists($column, $table);
    }

    private static function addPaymentMethods()
    {
        $payment_names = self::getCommdooPayments();

        $payment = oxNew(\OxidEsales\Eshop\Application\Model\Payment::class);
        foreach ($payment_names as $payment_name => $data) {
            $internal_name = "commdoo_payment_" . $payment_name;
            if (!$payment->load($internal_name)) {
                $payment->setId($internal_name);
                $payment->oxpayments__oxactive = new Field(1);
                $payment->oxpayments__oxdesc = new Field($data["display_name"]);
                $payment->oxpayments__oxaddsum = new Field(0);
                $payment->oxpayments__oxaddsumtype = new Field('abs');
                $payment->oxpayments__oxfromboni = new Field(0);
                $payment->oxpayments__oxfromamount = new Field(0);
                $payment->oxpayments__oxtoamount = new Field(1000000);

                $language = Registry::getLang();
                $languages = $language->getLanguageIds();
                if (array_key_exists("descriptions", $data)) {
                    foreach ($data["descriptions"] as $languageAbbreviation => $description) {
                        $languageId = array_search($languageAbbreviation, $languages);
                        if ($languageId !== false) {
                            $payment->setLanguage($languageId);
                            $payment->oxpayments__oxlongdesc = new Field($description);
                            $payment->save();
                        }
                    }
                }
            }
        }
    }

    /**
     * Activates CommDoo payment methods
     */
    private static function enablePaymentMethods()
    {
        $payment = oxNew(\OxidEsales\Eshop\Application\Model\Payment::class);
        foreach (self::getCommdooPayments() as $commdoo_payment_key => $value) {
            if ($payment->load($commdoo_payment_key)) {
                $payment->oxpayments__oxactive = new Field(1);
                $payment->save();
            }
        }
    }

    /**
     * Disables CommDoo payment methods
     */
    private static function disablePaymentMethods()
    {
        $payment = oxNew(\OxidEsales\Eshop\Application\Model\Payment::class);
        foreach (self::getCommdooPayments() as $commdoo_payment_key => $value) {
            if ($payment->load($commdoo_payment_key)) {
                $payment->oxpayments__oxactive = new Field(0);
                $payment->save();
            }
        }
    }
}