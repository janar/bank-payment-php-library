<?php

require_once realpath(dirname(__FILE__)) . '/Abstract.php';

class BankPayment_Gateway_Nordea extends BankLink_Gateway_Abstract {

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

        $nordeaConfig = array(
            'key' => '',
            'nordeaLanguage' => '4'
        );

        if(is_array($config)){
            //add some Nordea special config keys
            $config = array_merge($nordeaConfig, $config);

            $this->config = array_merge($this->config, $config);
        } else {
            throw new Exception('Gateway parameters must be in an array');
        }
    }

    /**
     * Is there currently response from bank or not
     *
     * @return boolean True if there is response
     */
    public function hasReponse()
    {
        //check random variable from possible response fields
        if(isset($_POST['SOLOPMT_RETURN_REF']) || isset($_GET['SOLOPMT_RETURN_REF'])){
            return true;
        }

        return false;
    }

    /**
     * Return response params from bank
     *
     * @return array
     */
    public function getResponse()
    {
        $response = new stdClass();
        $params = $this->getPaymentReturnFields();

        //TODO: SOLOPMT_RETURN_PAID

        if($this->checkSignature($params)){
            $response->result = 'success';
            $response->amount = $params['SOLOPMT_AMOUNT'];
        } else {

            $response->result = 'canceled';
            $response->amount = 0;

        }

        $response->stamp = $params['SOLOPMT_STAMP'];
        $response->referenceNr = $params['SOLOPMT_RETURN_REF'];
        $response->message = $params['SOLOPMT_MSG'];
        $response->isAutomaticResponse= false;

        return $response;
    }

    /**
     * Generate key/value pairs for currently set payment
     * and store them for various usages
     *
     * @throws Exception
     * @return void()
     */
    protected function preparePaymentFields()
    {
        if(!$this->paymentSet){
            throw new Exception('Payment parameters must be set before generating field values');
        }

        $this->preparedFields['SOLOPMT_VERSION'] = '0003';
        $this->preparedFields['SOLOPMT_STAMP'] = $this->payment['stamp'];
        $this->preparedFields['SOLOPMT_RCV_ID'] = $this->config['clientId'];
        $this->preparedFields['SOLOPMT_RCV_ACCOUNT'] = $this->config['clientAccount'];
        $this->preparedFields['SOLOPMT_RCV_NAME'] = $this->config['clientName'];
        $this->preparedFields['SOLOPMT_AMOUNT'] = $this->payment['amount'];
        $this->preparedFields['SOLOPMT_REF'] = $this->payment['referenceNr'];
        $this->preparedFields['SOLOPMT_DATE'] = 'EXPRESS';
        $this->preparedFields['SOLOPMT_CUR'] = $this->payment['currency'];
        $this->preparedFields['SOLOPMT_MSG'] = substr((string)$this->payment['message'], 0, 70);
        $this->preparedFields['SOLOPMT_CONFIRM'] = 'YES';
        $this->preparedFields['SOLOPMT_KEYVERS'] = '0001';
        $this->preparedFields['SOLOPMT_LANGUAGE'] = $this->config['nordeaLanguage'];

        $this->preparedFields['SOLOPMT_RETURN'] = $this->config['returnUri'];
        $this->preparedFields['SOLOPMT_CANCEL'] = $this->config['returnUri'];
        $this->preparedFields['SOLOPMT_REJECT'] = $this->config['returnUri'];

        if(isset($this->config['cancelUri'])){
            $this->preparedFields['SOLOPMT_CANCEL'] = $this->config['cancelUri'];
        }

        if(isset($this->config['cancelUri'])){
            $this->preparedFields['SOLOPMT_REJECT'] = $this->config['rejectUri'];
        }

        $this->preparedFields['SOLOPMT_MAC'] = $this->generateMacString();
    }

    /**
     * Generate MAC field value(signature) from
     * currently set payment and config fields
     *
     * @throws Exception
     * @return string Mac value
     */
    protected function generateMacString()
    {
        $params = $this->preparedFields;

        $mac = '' .
            $params['SOLOPMT_VERSION'] . '&' .
            $params['SOLOPMT_STAMP'] . '&' .
            $params['SOLOPMT_RCV_ID'] . '&' .
            $params['SOLOPMT_AMOUNT'] . '&' .
            $params['SOLOPMT_REF'] . '&' .
            $params['SOLOPMT_DATE'] . '&' .
            $params['SOLOPMT_CUR'] . '&' .
            $this->config['key'] . '&';

        return strtoupper(md5($mac));
    }

    /**
     * Check signature (MAC) value returned from bank
     *
     * @throws Exception
     * @return boolean True if correct
     */
    protected function checkSignature($params)
    {
        $mac = '' .
            $params['SOLOPMT_RETURN_VERSION'] . '&' .
            $params['SOLOPMT_RETURN_STAMP'] . '&' .
            $params['SOLOPMT_RETURN_REF'] .'&' .
            $params['SOLOPMT_RETURN_PAID'] . '&' .
            $this->config['key'] . '&';

        $mac = strtoupper(md5($mac));

        if($mac != $params['SOLOPMT_RETURN_MAC']){
            return false;
        }

        return true;
    }

    /**
     * Get all 'SOLOPMT_' prefixed fields returned from bank
     *
     * @return array Of returned values
     */
    protected function getPaymentReturnFields()
    {
        $returnedFields = array();

        foreach ((array)$_GET as $f => $v) {
            if (substr ($f, 0, 8) == 'SOLOPMT_') {
                $returnedFields[$f] = $v;
            }
        }

        return $returnedFields;
    }
}
