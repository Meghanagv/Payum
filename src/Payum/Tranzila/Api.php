<?php
namespace Payum\Tranzila;

use Payum\Core\Bridge\Guzzle\HttpClientFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\HttpClientInterface;

class Api
{
    const OPERATION_PAYMENT = 'payment';
    protected $options = array(
        'seller_payme_id' => null,
        'test_mode' => null,
    );

    public function __construct(array $options, HttpClientInterface $client = null)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty(array(
            'seller_payme_id',
            'test_mode',
        ));

        $this->options = $options;
        $this->client = $client ?: HttpClientFactory::create();
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function payment(array $params)
    {
        $params['OPERATIONTYPE'] = static::OPERATION_PAYMENT;
        return $this->doRequest(array(
            'method' => 'payment',
            'params' => $params
        ));
    }

    protected function doRequest(array $fields)
    {
        $param = $fields['params'];
        if($param['refund'] == 'false') {
            $saleResponse = $this->generateSaleID($param);
        } else {
            $saleResponse = json_encode(array('status_code' => 0));
        }
        $response = $this->makePayment($saleResponse, $param);
        return $response;
    }

    private function generateSaleID(array $data)
    {
        if ($this->options['test_mode'] == 1) {
            $url = 'https://preprod.paymeservice.com/api/generate-sale';
        } else {
            $url = 'https://ng.paymeservice.com/api/generate-sale';
        }

        $postvalues = array(
            'seller_payme_id' => $this->options['seller_payme_id'],
            "sale_price" => abs($data['amount'] * 100),
            "currency" => $data['currency'],
            "transaction_id" => $data['orderno'],
            "installments" => 1,
            "product_name" => $data['eventname'],
            "language" => "en",
        );

        $data_string = json_encode($postvalues);
        $request = curl_init($url);
        curl_setopt($request, CURLOPT_HEADER, 0);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $response = curl_exec($request);
        if(curl_error($request))
        {
            return json_encode(array('status_code' => 1));
        }
        curl_close($request);
        return $response;
    }

    private function makePayment($data, $param)
    {
        if ($this->options['test_mode'] == 1) {
            if($param['refund'] == 'false')
                $url = 'https://preprod.paymeservice.com/api/pay-sale';
            else
                $url = 'https://preprod.paymeservice.com/api/refund-sale';
        } else {
            if($param['refund'] == 'false')
                $url = 'https://ng.paymeservice.com/api/pay-sale';
            else
                $url = 'https://ng.paymeservice.com/api/refund-sale';
        }

        $data = json_decode($data, true);
        if($data['status_code'] == 0) {
            if($param['refund'] == 'false') {
                $postvalues = array(
                    'seller_payme_id' => $this->options['seller_payme_id'],
                    "payme_sale_id" => $data['payme_sale_id'],
                    "credit_card_number" => $param['number'],
                    "credit_card_cvv" => $param['cvc'],
                    "credit_card_exp" => $param['expmonth'] . substr($param['expyear'], 2, 2),
                    "buyer_phone" => $param['wphone'],
                    "buyer_email" => $param['email'],
                    "buyer_name" => $param['fname'] . ' ' . $param['lname'],
                    "language" => "en",
                );
            } else {
                $transactionID = explode("~", $param['id']);
                $postvalues = array(
                    'seller_payme_id' => $this->options['seller_payme_id'],
                    "payme_sale_id" => $transactionID[0],
                    "language" => "en",
                );
            }

            $data_string = json_encode($postvalues);
            $request = curl_init($url);
            curl_setopt($request, CURLOPT_HEADER, 0);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($request, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($request, CURLOPT_POST, true);
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($request, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
            );
            $response = curl_exec($request);

            if(curl_error($request)) {
                return array('status_code' => 1);
            } else {
                curl_close($request);
                $response = json_decode($response, true);
                if($param['refund'] == 'false') {
                    $response['reference'] = $response['id'] =  $response['payme_sale_id'] . '~' . $response['payme_transaction_id'];
                }
                $response['result'] = $response['status_code'];
            }

        } else {
            $response = $data;
        }

        return $response;
    }
}
