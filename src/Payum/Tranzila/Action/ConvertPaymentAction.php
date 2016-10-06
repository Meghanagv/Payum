<?php

namespace Payum\Tranzila\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Storage\IdentityInterface;

class ConvertPaymentAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $payment = $request->getSource();
        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        $details['description'] = $payment->getDescription();
        $details['amount'] = $payment->getTotalAmount();        
        $details["currency"] = $payment->getCurrencyCode();
        $details["cardName"] = $payment->getCardName();
        $details["fname"] = $payment->getFirstName();
        $details["lname"] = $payment->getLastName();
        $details["email"] = $payment->getClientEmail();
        $details["wphone"] = $payment->getWorkPhone();
        $details["addressLine1"] = $payment->getAddressLine1();
        $details["addressZip"] = $payment->getAddressZip();
        $details['addressState'] = $payment->getAddressState();
        $details["orderno"] = str_pad($payment->getEventID(), 6, "0", STR_PAD_LEFT) . "-" . str_pad($payment->getAttendeeID(), 8, "0", STR_PAD_LEFT);
        $details["eventstatus"] = $payment->getEventStatus();
        $details["env"] = $payment->getEnvironment();
        $details["eventname"] = $payment->getEventName();
        $details["refund"] = $payment->getRefund();
        $details["id"] = $payment->getTransactionID();
        $details["attendeeid"] = $payment->getAttendeeID();
        if ($card = $payment->getCreditCard()) {            
            $details['number'] = $card->getNumber();
            $details['expmonth'] = $payment->getExpireMonth();
            $details['expyear'] = $payment->getExpireYear();
            $details['cvc'] = $card->getSecurityCode();      
        }

        $request->setResult((array) $details);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
            ;
    }
}