<?php
namespace Payum\MerchantESolution;

use GuzzleHttp\Psr7\Request;
use Payum\Core\Bridge\Guzzle\HttpClientFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;
use Payum\Core\Model\CreditCardInterface;
use EConfiguration;



class Api
{

    const OPERATION_PAYMENT = 'payment';
    protected $options = array(
       // 'profileId' => null,
        //'profileKey' => null,
    );

    public function __construct(array $options, HttpClientInterface $client = null)
    {
        //error_log(print_R($options, TRUE), 3, "error.log");
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty(array(
           // 'profileId',
            //'profileKey',

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

    function doRequest(array $fields)

    {
        include('MeSsdk.php');
        $Param = $fields["params"];
        $host = 'https://cert.merchante-solutions.com/mes-api/tridentApi';
        //(profileid,profilekey)
        if ($Param['refund']  == 'true') {
            $tran = new TpgSale();
            $tran->TpgSale('94100012476500000125', 'GcAWPVfRNxqXSPkmuoVUzseJRqEZckPd');
            $tran->setAvsRequest('123 Main Street', 55555);
            $tran->setRequestField('cvv2', 123);
            $tran->setRequestField('invoice_number', 123456);
            $tran->setTransactionData(4012888812348882, 1212, 1.0);
            $tran->setHost($host);
            $tran->execute();
        }else{
            $tran = new TpgRefund();
            $tran->TpgRefund($this->options['profileId'], $this->options['profileKey'],$Param['transaction_id']);
            $tran->setRequestField('transaction_id', $Param['transaction_id']);
           // $tran->setRequestField('invoice_number',$Param['invoice_number']);
           // $tran->setStoredData($Param['number'],$Param['amount']);
           // $tran->RequestFields($Param['number']);
          //  $tran->setStoredData($Param['number'],$Param['amount']);
            //$tran->setPost("false");
            $tran->setHost($host);
            $tran->execute();
        }

    }

    public function status()
    {
        return count($this->errors);
    }
}
