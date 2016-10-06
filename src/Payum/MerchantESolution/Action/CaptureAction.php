<?php
/**
 * Created by PhpStorm.
 * User: meghana.gv
 * Date: 18-04-2016
 * Time: 17:47
 */

namespace Payum\MerchantESolution\Action;

use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Request\Capture;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\ObtainCreditCard;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\MerchantESolution\Api;
use Payum\Core\Security\SensitiveValue;

class CaptureAction  extends GatewayAwareAction implements ApiAwareInterface
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (false == $api instanceof Api) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {

        RequestNotSupportedException::assertSupports($this, $request);

        $model = new ArrayObject($request->getModel());


        $cardFields = array('CARDCODE', 'CARDCVV', 'CARDVALIDITYDATE', 'CARDFULLNAME');
        if (false == $model->validateNotEmpty(array('card_num', 'exp_date'), false) && false == $model['ALIAS']) {
            try {
//                $obtainCreditCard = new ObtainCreditCard($request->getToken());
//                $obtainCreditCard->setModel($request->getFirstModel());
//                $obtainCreditCard->setModel($request->getModel());
//                $this->gateway->execute($obtainCreditCard);
//                $card = $obtainCreditCard->obtain();
////
////                if ($card->getToken()) {
////                    $model['ALIAS'] = $card->getToken();
////                } else {
//                    $model['CARDVALIDITYDATE'] = new SensitiveValue($card->getExpireAt()->format('m-y'));
//                    $model['CARDCODE'] = new SensitiveValue($card->getNumber());
//                    $model['CARDFULLNAME'] = new SensitiveValue($card->getHolder());
//                    $model['CARDCVV'] = new SensitiveValue($card->getSecurityCode());
//                }
                $model['exp_date'] = new SensitiveValue($model['expire_at']);
                $model['card_num'] = new SensitiveValue($model['card_number']);
            } catch (RequestNotSupportedException $e) {
                throw new LogicException('Credit card details has to be set explicitly or there has to be an action that supports ObtainCreditCard request.');
            }
        }

        //instruction must have an alias set (e.g oneclick payment) or credit card info.
        if (false == ($model['exp_date'] || $model->validateNotEmpty($cardFields, false))) {
            throw new LogicException('Either credit card fields or its alias has to be set.');
        }

        $result = $this->api->payment($model->toUnsafeArray());

        $model->replace((array) $result);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess
            ;
    }
}