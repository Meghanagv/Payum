<?php

namespace Payum\MerchantESolution;

use Payum\MerchantESolution\Action\ConvertPaymentAction;
use Payum\MerchantESolution\Action\CaptureAction;
use Payum\MerchantESolution\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Payum\Core\Model\GatewayMetaData;

class MerchantESolutionGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)


    {
        $config->defaults(array(
            'payum.factory_name' => 'MerchantESolution',
            'payum.factory_title' => 'MerchantESolution',

            'payum.action.capture' => new CaptureAction(),
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
        ));

        if (false == $config['payum.api']) {
            $metaData = new GatewayMetaData();
            $config['payum.default_options'] = $metaData->getMerchantESolutionMetaData();
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = array('profileId','profileKey');

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    array(
                        'profileId'=>$config['profileId'],
                        'profileKey'=>$config['profileKey'],
                    ),
                    $config['payum.http_client']
                );
            };
        }
    }
}