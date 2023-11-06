<?php


namespace Eimed\Modules\CommdooPayment\Api;

use OxidEsales\Eshop\Core\Registry;

abstract class ApiUrlValidatorService
{
    /**
     * @var array
     */
    private $values;

    private $hash_valid;
    private $url;
    private $hash;
    private $order;
    private $shared_secret;
    private $base_url;

    function __construct($order)
    {
        $this->order = $order;
        $this->values = array();
        $this->hash_valid = false;
        $this->shared_secret = $this->getConfigParam("sCD_sharedsecret");
        $this->base_url = $this->getConfigParam("sCD_baseurl");
    }

    protected function getConfigParam($key, $default = "")
    {
        if (!isset($this->config)) {
            $this->config = Registry::getConfig();
        }
        return $this->config->getConfigParam($key);
    }

    public function setSecret($secret)
    {
        $this->shared_secret = $secret;
    }

    public function set($name, $value)
    {
        $lowercaseName = strtolower($name);
        $checkName = $this->getCheckupKey($name);
        if (!in_array($checkName, $this->order)) {
            return;
        }
        $this->values[$lowercaseName] = $value;
        $this->hash_valid = false;
    }

    public function get($name, $default = "")
    {
        $lowercase_name = strtolower($name);
        if (array_key_exists($lowercase_name, $this->values)) {
            return $this->values[$lowercase_name];
        } else {
            return $default;
        }
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getUrl()
    {
        if ($this->hash_valid == false) {
            $this->setUrlHash();
        }
        return $this->url;
    }

    public function getHash()
    {
        if ($this->hash_valid == false) {
            $this->setUrlHash();
        }
        return $this->hash;
    }

    public function setUrlHash()
    {
        $hash = '';
        $this->url = $this->generateHashUrl($this->values, $hash);
        $this->hash = $hash;
        $this->hash_valid = true;
    }

    private function getCheckupKey(string $key): string
    {
        $ret = preg_replace('/item\[\d\]-/', 'item[x]-', $key);
        if ($ret !== null) {
            $key = $ret;
        }
        return $key;
    }

    private function generateHashUrl(array $values, string &$hash): string
    {
        $params = array();
        $cleartext = "";
        foreach ($this->order as $item) {
            foreach ($values as $key => $value) {
                $checkKey = $this->getCheckupKey($key);
                if ($checkKey === $item) {
                    $params[$key] = $value;
                    $cleartext .= $value;
                }
            }
        }

        $hash = strtoupper(sha1($cleartext . $this->shared_secret));
        $params["hash"] = $hash;

        return $this->base_url . http_build_query($params, '', null, PHP_QUERY_RFC3986);
    }

    public function check(): void
    {
        $values = [
            'clientid' => '99999999',
            'payment' => 'all',
            'referenceid' => '316458a1d8cf07815fc78dd0ff70b63d',
            'amount' => 1234,
            'currency' => 'EUR',
            'salutation' => 'Herr',
            'firstname' => 'Max',
            'lastname' => 'Musterman',
            'street' => 'Musterstraße',
            'housenumber' => 123,
            'postalcode' => '45678',
            'city' => 'Musterstadt',
            'emailaddress' => 'test@commdoo.de',
            'country' => 'DEU',
            'successurl' => 'http://localhost/sample.php?result=success',
            'failurl' => 'http://localhost/sample.php?result=fail',
            'hash' => '2e24d72d09b8f3dae91db2dea43a081727c8864f',
            'language' => 'DEU',
        ];

        $hash = '';
        $url = $this->generateHashUrl($values, $hash);

        if ($hash === '271b7b62089c76e0aed23b82872c9eca393ad5b8') {
            throw new \Exception('the data are not UTF-8 encoded, but iso-8859');
        }
    }
}