<?php

class BankPayment {

    /**
     * Factory for gateway classes.
     *
     * The first argument is a string that will represent the name
     * of the gateway class to use, For example: 'swedbank' corresponds to the class
     * BankPayment_Gateway_Swedbank. This is case-insensitive.
     *
     * Second argument is optional and may be an associative array of key-value
     * pairs.  This is used as the argument to the gateway constructor.
     *
     * @param  mixed $gateway String name of gateway class.
     * @param  mixed $config  OPTIONAL; an array with parameters.
     * @return object Gateway
     * @throws Exception
     */
    public static function factory($gateway, $config = array())
    {
        /**
         * Verify that gateway parameters are in an array.
         */
        if (!is_array($config)) {
            throw new Exception('Gateway parameters must be in an array');
        }

        /**
         * Verify that an adapter name has been specified.
         */
        if (!is_string($gateway) || empty($gateway)) {
            throw new Exception('Gateway name must be specified in a string');
        }

        /**
         * Construct gateway class name and filepath to create corresponding gateway instance
         */
        $gatewayClassName = 'BankPayment_Gateway_' . ucfirst($gateway);
        require_once realpath(dirname(__FILE__)).'/Gateway/' . ucfirst($gateway) . '.php';


        /**
         * Load the adapter class.  This throws an exception
         * if the specified class cannot be loaded.
         */
        Zend_Loader::loadClass($gatewayClassName);

        /**
         * Create an instance of the adapter class.
         * Pass the config to the adapter class constructor.
         */
        $paymentGateway = new $gatewayClassName($config);
        
        return $paymentGateway;
    }
}
