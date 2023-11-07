<?php


namespace Eimed\Modules\CommdooPayment\Api;

use Eimed\Modules\CommdooPayment\Traits\LoggerTrait;
use OxidEsales\Eshop\Core\Registry;

abstract class ApiUrlValidatorService
{
    use LoggerTrait;

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
        $checkName = $this->getCheckupKey($lowercaseName);
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
        $this->url = $this->generateHashUrl($this->values, $this->shared_secret, $hash);
        $this->hash = $hash;
        $this->hash_valid = true;
    }

    private function getCheckupKey(string $key): string
    {
        $ret = preg_replace('/item\d-/', 'item[x]-', $key);
        if ($ret !== null) {
            $key = $ret;
        }
        return $key;
    }

    private function generateHashUrl(array $values, string $secret, string &$hash): string
    {
        function startsWith($haystack, $needle)
        {
            $length = strlen($needle);
            return substr($haystack, 0, $length) === $needle;
        }

        $params = array();
        $items = array();
        $cleartext = "";
        $handledItems = true;
        foreach ($this->order as $orderKey) {
            // Sobald 'items' in der Reihenfolge kommen, behalten wir die Reihenfolge aus den values...
            if (startsWith($orderKey, 'item')) {
                foreach ($values as $key => $value) {
                    if (startsWith($key, 'item') && !array_key_exists($key, $items)) {
                        $items[$key] = $value;
                        $cleartext .= $value;
                    }
                }
                $handledItems = true;
            } else {
                if ($handledItems) {
                    $params = array_merge($params, $items);
                    $items = [];
                }

                foreach ($values as $key => $value) {
                    if ($this->getCheckupKey($key) === $orderKey) {
                        $params[$key] = $value;
                        $cleartext .= $value;
                    }
                }
            }
        }
        $params = array_merge($params, $items);

        $cleartext .= $secret;
        $hash = strtoupper(sha1($cleartext));

        $this->getLogger()->setTitle('Generate Hash');
        $this->getLogger()->log([
            'params' => $params,
            'string' => $cleartext,
            'hash' => $hash,
        ]);

        $params["hash"] = $hash;
        return $this->base_url . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
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
            'timestamp' => 29032012142524,
            'language' => 'DEU',
            'additionaldata' => 'testdata',
        ];

        $hash = '';
        $url = $this->generateHashUrl($values, 'test', $hash);

        if ($hash === '2E24D72D09B8F3DAE91DB2DEA43A081727C8864F') {
            return;
        }

        if ($hash === '271B7B62089C76E0AED23B82872C9ECA393AD5B8') {
            throw new \Exception('the data are not UTF-8 encoded, but iso-8859');
        }
    }
}