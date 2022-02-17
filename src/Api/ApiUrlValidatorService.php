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

    protected function getConfigParam($key, $default="")
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
        $lowercase_name = strtolower($name);
        if (!in_array($lowercase_name, $this->order)) {
            return;
        }
        $this->values[$lowercase_name] = $value;
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
            $this->generateUrlHash();
        }
        return $this->url;
    }

    public function getHash()
    {
        if ($this->hash_valid == false) {
            $this->generateUrlHash();
        }
        return $this->hash;
    }

    public function generateUrlHash()
    {
        $http_args = array();
        $cleartext = "";
        foreach ($this->order as $item) {
            if (array_key_exists($item, $this->values)) {
                $http_args[$item] = $this->values[$item];
                $cleartext .= $this->values[$item];
            }
        }

        $hash = strtoupper(sha1($cleartext . $this->shared_secret));  //build url
        $http_args["hash"] = $hash;

        $this->url = $this->base_url. http_build_query($http_args);
        $this->hash = $hash;
        $this->hash_valid = true;
    }
}