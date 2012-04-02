<?php
/**
 * 
 * IPizza base class
 * 
 * Some code may be copied from referenced files from
 * http://blog.zone.ee/2006/12/12/pangalink/
 *  
 **/
require_once realpath(dirname(__FILE__)) . '/Abstract.php';

abstract class BankPayment_Gateway_IPizza extends BankLink_Gateway_Abstract {

    /**
     * Variable orders for MAC string generation
     * 
     * @var array
     */
    protected $VKOrders = Array(
        1001 => Array(
            'VK_SERVICE','VK_VERSION','VK_SND_ID',
            'VK_STAMP','VK_AMOUNT','VK_CURR',
            'VK_ACC','VK_NAME','VK_REF','VK_MSG'
        ),
        1101 => Array(
            'VK_SERVICE','VK_VERSION','VK_SND_ID',
            'VK_REC_ID','VK_STAMP','VK_T_NO','VK_AMOUNT','VK_CURR',
            'VK_REC_ACC','VK_REC_NAME','VK_SND_ACC','VK_SND_NAME',
            'VK_REF','VK_MSG','VK_T_DATE'
        ),
        1901 => Array(
            'VK_SERVICE','VK_VERSION','VK_SND_ID',
            'VK_REC_ID','VK_STAMP','VK_REF','VK_MSG'
        )
    );

    /**
     * Is there currently response from bank or not
     *
     * @return boolean True if there is response
     */
    public function hasReponse()
    {
        if(isset($_POST['VK_SERVICE'])){
            return true;
        }

        return false;
    }

    /**
     * Return response params from bank
     *
     * @return object
     */
    public function getResponse()
    {
        $params = $this->getPaymentReturnFields();
        $response = new stdClass();

        if($this->checkSignature($params) && $params['VK_SERVICE'] == '1101'){
            $response->result = 'success';
            $response->amount = $params['VK_AMOUNT'];
        } else {
            $response->result = 'failed';
            $response->amount = 0; //VK_AMOUNT is not specified in response parameters anyway
        }

        $response->stamp = $params['VK_STAMP'];
        $response->referenceNr = $params['VK_REF'];
        $response->message = $params['VK_MSG'];
        $response->isAutomaticResponse = ($params['VK_AUTO'] == 'N' ? false : true);

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

        $this->preparedFields['VK_SERVICE'] = 1001;
        $this->preparedFields['VK_VERSION'] = '008';
        $this->preparedFields['VK_SND_ID'] = $this->config['clientId'];
        $this->preparedFields['VK_STAMP'] = $this->payment['stamp'];
        $this->preparedFields['VK_AMOUNT'] = $this->payment['amount'];
        $this->preparedFields['VK_CURR'] = $this->payment['currency'];
        $this->preparedFields['VK_ACC'] = $this->config['clientAccount'];
        $this->preparedFields['VK_NAME'] = $this->config['clientName'];
        $this->preparedFields['VK_REF'] = $this->payment['referenceNr'];
        $this->preparedFields['VK_MSG'] = substr((string)$this->payment['message'], 0, 70);
        $this->preparedFields['VK_RETURN'] = $this->config['returnUri'];
        $this->preparedFields['VK_CANCEL'] = $this->config['returnUri'];
        $this->preparedFields['VK_LANG'] = $this->config['language'];

        if(isset($this->config['encodingVKFieldKey'])){
            $this->preparedFields[  $this->config['encodingVKFieldKey'] ] = $this->config['encoding'];
        } else {
            $this->preparedFields['VK_CHARSET'] = $this->config['encoding'];
        }

        if(isset($this->config['cancelUri'])){
            $this->preparedFields['VK_CANCEL'] = $this->config['cancelUri'];
        }

        //finally add MAC string generated using previously set fields
        $this->preparedFields['VK_MAC'] = $this->generateMacString();
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
        $preparedParams = $this->preparedFields;
        $requestType = $preparedParams['VK_SERVICE'];
        $mac = '';

        //TODO: utf8 / iso encoding difference?
        
        foreach ((array)$this->VKOrders[$requestType] as $key) {
            $v = $preparedParams[$key];
            $mac .= str_pad (strlen ($v), 3, '0', STR_PAD_LEFT) . $v;
        }

        if(is_readable($this->config['privateKeyPath'])){
            $strKeyC = file_get_contents ($this->config['privateKeyPath']);
        } else {
            throw new Exception('Private key missing');
        }

        $key = openssl_pkey_get_private ($strKeyC, '');
        if (!openssl_sign ($mac, $signature, $key)) {
            throw new Exception('Unable to create signature');
        }

        openssl_free_key($key);

        return base64_encode($signature);
    }

    /**
     * Check signature (MAC) value returned from bank
     *
     * @throws Exception
     * @return boolean True if correct
     */
    protected function checkSignature($params)
    {
        $requestType = $params['VK_SERVICE'];
        $mac = '';

        /*
        TODO:
            if(utf8){
                $data .= mb_substr("000".mb_strlen(($element), 'utf-8'),	-3, mb_strlen("000".mb_strlen(($element), 'utf-8')), 'utf-8' ).$element;
                //$data .= substr("000".strlen(utf8_decode($element)),	-3).$element;
            } else {
                $data .= substr("000".strlen($element),	-3).$element;
            }
        */
        
        foreach ((array)$this->VKOrders[$requestType] as $key) {
            $v = $params[$key];
            $mac .= str_pad (strlen ($v), 3, '0', STR_PAD_LEFT) . $v;
        }

        if(is_readable($this->config['publicKeyPath'])){
            $strKeyC = file_get_contents ($this->config['publicKeyPath']);
        } else {
            throw new Exception('Public key missing');
        }
        
        $key = openssl_pkey_get_public ($strKeyC);
        if (!openssl_verify ($mac, base64_decode ($params['VK_MAC']), $key)) {
            return false;
        }

        return true;
    }
    
    /**
     * Get all 'VK_' prefixed fields returned from bank
     *
     * @return array Of returned values
     */
    protected function getPaymentReturnFields()
    {
        $returnedFields = array();

        foreach ((array)$_POST as $f => $v) {
            if (substr ($f, 0, 3) == 'VK_') {
                $returnedFields[$f] = $v;
            }
        }

        return $returnedFields;
    }
}

