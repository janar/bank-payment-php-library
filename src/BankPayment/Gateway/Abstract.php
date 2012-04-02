<?php

abstract class BankLink_Gateway_Abstract
{
    /**
     * Banklink default configuration data. Probably most of it must
     * be overwritten by passing config array to constructor
     *
     * Every child class should deal with config data how ever they need
     *
     * @var array
     */
    protected $config = array(
        'privateKeyPath' => '',
        'publicKeyPath' => '',
        'requestUri' => '',
        'clientId' => '',
        'clientName' => '',
        'clientAccount' => '',
        'returnUri' => '',
        'language' => 'EST',
        'encoding' => 'UTF-8',
    );
    
    /**
     * Is payment information currently loaded to create banklink/form
     *
     * @var boolean
     */
    protected $paymentSet = false;

    /**
     * Payment information
     *
     * @var array
     */
    protected $payment = array(
        'referenceNr' => 0,
        'message' => '',
        'amount' => 0,
        'currency' => 'EUR',
        'stamp' => '',
    );

    /**
     * key/value pairs generated based on config and payment data
     *
     * @var array
     */
    protected $preparedFields = array();

    /**
     * Set config parameters for nordea payment gateway
     *
     * @param  array $config an array with parameters.
     * @return void()
     * @throws Exception
     */
    public function __construct($config = null)
    {
        if($config === null){
            return;
        }
        
        if(is_array($config)){
            $this->config = array_merge($this->config, $config);
        } else {
            throw new Exception('Gateway parameters must be in an array');
        }
    }

    /**
     * Set payment data. Data set here is used to create payment form/url
     * or just getting fields. So this method must be called before.
     * Otherwise those methods will throw Exception
     *
     * @param  array $config an array with parameters.
     * @return void()
     */
    public function setPaymentFields($payment)
    {
        if(is_array($payment)){
            $this->payment = array_merge($this->payment, $payment);
            $this->paymentSet = true;
        } else {
            throw new Exception('Gateway payment parameters must be in an array');
        }
    }
    
    /**
     * -
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->config['requestUri'];
    }

    /**
     * Return prepared payment form/url key/value pairs
     * All other ations what are using these values must
     * request them from this method
     *
     * @return array
     */
    public function getFields()
    {
        $this->preparePaymentFields();
        return $this->preparedFields;
    }
    
    /**
     * Creates simple HTML string form from
     * currently set payment and config data
     *
     * @param  string $formName
     * @param  string $formId
     * @param  string $formId
     * @param  string $formId
     * @return string
     */
    public function renderSimpleHtmlForm($formName = '', $formId = '', $showSubmit = true, $submitValue = '')
    {
        $fields = $this->getFields();
        $eol = "\n";

        $form = '<form action="' . $this->getRequestUri() . '" method="post" id="' . $formId . '" name="' . $formName . '">' . $eol;

        foreach($fields as $k => $i){
            $form .= '<input type="hidden" name="' . $k . '" value="' . $i . '" />' . $eol;
        }

        if($showSubmit){
            $form .= '<input type="submit" name="" value="' . $submitValue . '" />' . $eol;
        }

        $form .= '</form>' . $eol;

        return $form;
    }

    /**
     * Was payment successfully processed
     *
     * @return boolean True if payment successfully processed
     */
    public function success()
    {
        if($this->hasReponse()){
            $response = $this->getResponse();
            if($response->result == "success"){
                return true;
            }
        }

        return false;
    }
    
    /**
     * Is there currently response from bank or not
     * must be overwritten by child class
     *
     * @return boolean True if there is response
     */
    public abstract function hasReponse();

    /**
     * Return response params from bank
     *
     * @return object
     */
    public abstract function getResponse();
    
}
