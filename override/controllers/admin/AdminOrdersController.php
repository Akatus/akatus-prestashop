<?php

class AdminOrdersController extends AdminOrdersControllerCore
{
	public $toolbar_title;

	public function __construct()
	{
		parent::__construct();
	}

    public function setMedia()                                                                                                                                                                                
    {
        parent::setMedia();
        $this->addJS(_PS_JS_DIR_.'akatus.js');
    }


	public function postProcess()
	{
		// If id_order is sent, we instanciate a new Order object
		if (Tools::isSubmit('id_order') && Tools::getValue('id_order') > 0)
		{
			$order = new Order(Tools::getValue('id_order'));

            /* Change order state, add a new entry in order history and send an e-mail to the customer if needed */
            if (Tools::isSubmit('submitState') && isset($order))
            {
                if ($this->tabAccess['edit'] === '1')
                {
                    $order_state = new OrderState(Tools::getValue('id_order_state'));

                    $current_order_state = $order->getCurrentOrderState();
                    if ($current_order_state->id != $order_state->id)
                    {
                        $statesEstornaveis = array(Configuration::get('AKATUS_STATUS_2'), Configuration::get('AKATUS_STATUS_0'));

                        if (in_array($order->current_state, $statesEstornaveis)) {

                            $json = $this->getJSON($order->id);
                            $response = $this->akatusRefund($json);

                            if ($response['resposta']['codigo-retorno'] == 0) {
                                // Create new OrderHistory
                                $history = new OrderHistory();
                                $history->id_order = $order->id;
                                $history->id_employee = (int)$this->context->employee->id;

                                $use_existings_payment = false;
                                if (!$order->hasInvoice())
                                    $use_existings_payment = true;
                                $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);

                                $carrier = new Carrier($order->id_carrier, $order->id_lang);
                                $templateVars = array();
                                if ($history->id_order_state == Configuration::get('PS_OS_SHIPPING') && $order->shipping_number)
                                    $templateVars = array('{followup}' => str_replace('@', $order->shipping_number, $carrier->url));
                                // Save all changes
                                if ($history->addWithemail(true, $templateVars))
                                {
                                    // synchronizes quantities if needed..
                                    if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'))
                                    {
                                        foreach ($order->getProducts() as $product)
                                        {
                                            if (StockAvailable::dependsOnStock($product['product_id']))
                                                StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
                                        }
                                    }

                                }

                                Tools::redirectAdmin(self::$currentIndex.'&id_order='.(int)$order->id.'&vieworder&token='.$this->token);

                            } else {

                                $this->errors[] = Tools::displayError('Não foi possível estornar a transação na Akatus: ' . $response['resposta']['mensagem']);
                            }

                        } else if ($order_state->id == Configuration::get('AKATUS_STATUS_7')) {
                        
                            $this->errors[] = Tools::displayError('Não é possível estornar o pedido devido ao status atual na loja.');
                        } else {
                        
                            return parent::postProcess();
                        }
                    }
                }
            }
        }
        else
            parent::postProcess();
    }

    private function getJSON($orderId)
    {
        $sql = 'select id_transacao FROM transacoes_akatus where referencia = '.$orderId;
        $transaction = Db::getInstance()->getValue($sql);
        
        $apiKey = Configuration::get('AKATUS_API_KEY');
        $email = Configuration::get('AKATUS_EMAIL_CONTA');

        $jsonObject = new stdClass();
        $jsonObject->estorno = new stdClass();
        $jsonObject->estorno->transacao = $transaction;
        $jsonObject->estorno->api_key = $apiKey;
        $jsonObject->estorno->email = $email;

        return json_encode($jsonObject);
    }

    private function akatusRefund($json)
    {
        $url = "https://www.akatus.com/api/v1/estornar-transacao.json";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, $assoc = true);
    }
}
