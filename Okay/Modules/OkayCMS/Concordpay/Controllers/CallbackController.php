<?php


namespace Okay\Modules\OkayCMS\Concordpay\Controllers;

use DateTime;
use DateTimeZone;
use Exception;
use Okay\Core\Money;
use Okay\Core\Notify;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\OrdersEntity;
use Okay\Entities\PaymentsEntity;
use Okay\Controllers\AbstractController;
use Psr\Log\LoggerInterface;

/**
 * Class CallbackController
 * @package Okay\Modules\OkayCMS\Concordpay\Controllers
 */
class CallbackController extends AbstractController
{
    /**
     * @param Money $money
     * @param Notify $notify
     * @param OrdersEntity $ordersEntity
     * @param CurrenciesEntity $currenciesEntity
     * @param PaymentsEntity $paymentsEntity
     * @param LoggerInterface $logger
     * @throws Exception
     */
    public function payOrder(
        Money $money,
        Notify $notify,
        OrdersEntity $ordersEntity,
        CurrenciesEntity $currenciesEntity,
        PaymentsEntity $paymentsEntity,
        LoggerInterface $logger
    ) {
        $keysForSignature = [
            'merchantAccount',
            'orderReference',
            'amount',
            'currency'
        ];

        $this->response->setContentType(RESPONSE_TEXT);

        $data = json_decode(file_get_contents("php://input"), false);
        if (empty($data->orderReference)) {
            $this->response->setContent("Wrong data")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        $order_parse = !empty($data->orderReference) ? explode('#', $data->orderReference) : null;
        if (is_array($order_parse)) {
            $orderId = $order_parse[0];
        } else {
            $orderId = $order_parse;
        }

        $order = $ordersEntity->get((int)$orderId);
        if (empty($order)) {
            $logger->warning("Concordpay notice: 'Order not found'. Order №{$orderId}");
            $this->response->setContent("Order not found")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        $method = $paymentsEntity->get((int)$order->payment_method_id);
        if (empty($method)) {
            $logger->warning("Concordpay notice: 'Invalid payment method'. Order №{$orderId}");
            $this->response->setContent("Invalid payment method")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        $currency = $currenciesEntity->get((int) $method->currency_id);
        if ($data->currency !== $currency->code) {
            $logger->warning("Concordpay notice: 'Invalid currency'. Order №{$orderId}");
            $this->response->setContent("Invalid currency")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        $amount = !empty($data->amount) ? $data->amount : null;
        $concordpayAmount = round($amount, 2);
        $orderAmount = round($money->convert($order->total_price, $method->currency_id, false), 2);
        if ($orderAmount != $concordpayAmount) {
            $logger->warning("Concordpay notice: 'Invalid total order amount'. Order №{$orderId}");
            $this->response->setContent("Invalid total order amount")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        $settings = unserialize($method->settings, [true]);

        $sign = array();
        foreach ($keysForSignature as $dataKey) {
            if (array_key_exists($dataKey, $data)) {
                $sign [] = $data->$dataKey;
            }
        }

        $sign = implode(';', $sign);
        $sign = hash_hmac('md5', $sign, $settings['concordpay_secretkey']);
        if (!empty($data->merchantSignature) && $data->merchantSignature !== $sign) {
            $logger->warning("Concordpay notice: 'Invalid merchant signature'. Order №{$orderId}");
            $this->response->setContent("Invalid merchant signature")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        if ($order->paid) {
            $logger->warning("Concordpay notice: 'Order already paid'. Order №{$orderId}");
            $this->response->setContent("Order already paid")->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        if (!empty($data->transactionStatus) && $data->transactionStatus === 'Declined') {
            $reasonCode = $data->reasonCode ?? '';
            $reason = $data->reason ?? '';
            $logger->warning("Concordpay notice: 'Payment declined'. Order №{$orderId}");
            $this->response->setContent("Payment declined. Error: " . $reasonCode . '. ' . $reason)->setStatusCode(400);
            $this->response->sendContent();
            exit;
        }

        if (!empty($data->transactionStatus) && $data->transactionStatus === 'Approved') {
            $ordersEntity->update((int)$order->id, ['paid' => 1]);
            $ordersEntity->close((int)$order->id);
            $notify->emailOrderUser((int)$order->id);
            $notify->emailOrderAdmin((int)$order->id);
        }
    }
}
