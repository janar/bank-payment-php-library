<?php

require_once realpath(dirname(__FILE__)) . '/IPizza.php';

class BankPayment_Gateway_Seb extends BankPayment_Gateway_IPizza
{
    /**
     * $config is an array of key/value pairs
     * containing configuration options for swedbank.
     *
     * @param array $config An array containing configuration options
     */
    public function __construct($config)
    {
        parent::__construct($config);
    }
}
