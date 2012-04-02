<?php

require_once realpath(dirname(__FILE__)) . '/IPizza.php';

class BankPayment_Gateway_Swedbank extends BankPayment_Gateway_IPizza
{
    /**
     * Required options for gateway, things such as merchant account id
     *
     * @var array
     */
    protected $_config = array(
        'encodingVKFieldKey' => 'VK_ENCODING',
    );
    
    /**
     * $config is an array of key/value pairs
     * containing configuration options for swedbank.
     *
     * @param array $config An array containing configuration options
     */
    public function __construct($config)
    {
        if(is_array($config)){
            $this->_config = array_merge($this->_config, $config);
        } else {
            $this->_config = $config;
        }
        
        parent::__construct($this->_config);
    }
}
