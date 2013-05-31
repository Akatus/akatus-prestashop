<?php

class AdminOrdersController extends AdminOrdersControllerCore
{
	public $toolbar_title;

	public function __construct()
	{
		parent::__construct();
	}

    public function initContent()
    {
        if (isset($_GET['refund'])) {

            $orderId = $_GET['id_order'];
            $xml = $this->getXML($orderId);
            $response = $this->requestEstorno($xml);
            $responseArray = $this->xml2array($response);

            if ($responseArray['resposta']['codigo-retorno']['value'] === '0') {
                $this->confirmations[] = 'Transação estornada com sucesso. Aguarde o NIP para a atualização do pedido na sua loja.';
            } else {
                $this->errors[] = Tools::displayError('Não foi possível solicitar o estorno.');
            }
        }

        return parent::initContent(); 
    }

	public function initToolbar()
    {
        if (isset($_GET['id_order'])) {
        
            $orderId = $_GET['id_order'];
            $token = $_GET['token'];
            $order = new Order(intval($orderId));

            $completeState = Configuration::get('AKATUS_STATUS_0');

            if ($order->current_state == $completeState) {
                $this->toolbar_btn['refund'] = array(
                    'short' => 'Refund',
                    'href' => "index.php?controller=AdminOrders&token=$token&id_order=$orderId&refund=1",
                    'desc' => $this->l('Estornar'),
                    'class' => 'process-icon-partialRefund'
                );
            }
        }

		return parent::initToolbar();
	}

    private function getXML($orderId)
    {
        $sql = 'select id_transacao FROM transacoes_akatus where referencia = '.$orderId;
        $transaction = Db::getInstance()->getValue($sql);
        
        $apiKey = Configuration::get('AKATUS_API_KEY');
        $email = Configuration::get('AKATUS_EMAIL_CONTA');

        return "<estorno><transacao>" . $transaction . "</transacao><api_key>" . $apiKey . "</api_key><email>" . $email . "</email></estorno>"; 
    }

    private function requestEstorno($xml)
    {
        $url = "https://www.akatus.com/api/v1/estornar-transacao.xml";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    private function xml2array($contents, $get_attributes = 1) {
        if (!$contents)
                return array();

        if (!function_exists('xml_parser_create')) {
                return array();
        }

        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $contents, $xml_values);
        xml_parser_free($parser);

        if (!$xml_values)
                return; //Hmm...
                
        //Initializations

        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array;

        //Go through the tags.

        foreach ($xml_values as $data) {
                unset($attributes, $value); //Remove existing values, or there will be trouble
                extract($data); //We could use the array by itself, but this cooler.

                $result = '';

                if ($get_attributes) {//The second argument of the function decides this.
                        $result = array();
                        if (isset($value))
                                $result['value'] = $value;

                        //Set the attributes too.
                        if (isset($attributes)) {
                                foreach ($attributes as $attr => $val) {
                                        if ($get_attributes == 1)
                                                $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                                }
                        }
                } elseif (isset($value)) {
                        $result = $value;
                }

                //See tag status and do the needed.

                if ($type == "open") {//The starting of the tag '<tag>'
                        $parent[$level - 1] = &$current;

                        if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                                $current[$tag] = $result;
                                $current = &$current[$tag];
                        } else { //There was another element with the same tag name
                                if (isset($current[$tag][0])) {
                                        array_push($current[$tag], $result);
                                } else {
                                        $current[$tag] = array($current[$tag], $result);
                                }
                                $last = count($current[$tag]) - 1;
                                $current = &$current[$tag][$last];
                        }
                } elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'
                        //See if the key is already taken.
                        if (!isset($current[$tag])) { //New Key
                                $result = str_replace('|', '&', $result);
                                $current[$tag] = $result;
                        } else { //If taken, put all things inside a list(array)
                                if ((is_array($current[$tag]) and $get_attributes == 0)//If it is already an array...
                                        or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {
                                        array_push($current[$tag], $result); // ...push the new element into that array.
                                } else { //If it is not an array...
                                        $current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value
                                }
                        }
                } elseif ($type == 'close') { //End of tag '</tag>'
                        $current = &$parent[$level - 1];
                }
        }

        if (!empty($xml_array['root']['node']['id'])) {
                $return['root']['node'][0] = $xml_array['root']['node'];
        } else {
                $return = $xml_array;
        }
        return($return);
    }
}
