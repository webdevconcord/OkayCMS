<?php


namespace Okay\Modules\OkayCMS\Concordpay;

use Okay\Core\EntityFactory;
use Okay\Core\Modules\AbstractModule;
use Okay\Core\Modules\Interfaces\PaymentFormInterface;
use Okay\Core\Money;
use Okay\Core\Router;
use Okay\Core\Languages;
use Okay\Entities\CurrenciesEntity;
use Okay\Entities\OrdersEntity;
use Okay\Entities\PaymentsEntity;
use Okay\Entities\PurchasesEntity;
use Okay\Entities\LanguagesEntity;

/**
 * Class PaymentForm
 * @package Okay\Modules\OkayCMS\Concordpay
 */
class PaymentForm extends AbstractModule implements PaymentFormInterface
{

    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * @var Languages
     */
    private $languages;

    /**
     * @var Money
     */
    private $money;

    /**
     * PaymentForm constructor.
     * @param EntityFactory $entityFactory
     * @param Languages $languages
     * @param Money $money
     */
    public function __construct(EntityFactory $entityFactory, Languages $languages, Money $money)
    {
        parent::__construct();
        $this->entityFactory = $entityFactory;
        $this->languages = $languages;
        $this->money = $money;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function checkoutForm($orderId)
    {
        /** @var OrdersEntity $ordersEntity */
        $ordersEntity = $this->entityFactory->get(OrdersEntity::class);

        /** @var PurchasesEntity $purchasesEntity */
        $purchasesEntity = $this->entityFactory->get(PurchasesEntity::class);

        /** @var PaymentsEntity $paymentsEntity */
        $paymentsEntity = $this->entityFactory->get(PaymentsEntity::class);

        /** @var CurrenciesEntity $currenciesEntity */
        $currenciesEntity = $this->entityFactory->get(CurrenciesEntity::class);

        /** @var LanguagesEntity $languagesEntity */
        $languagesEntity = $this->entityFactory->get(LanguagesEntity::class);

        $order = $ordersEntity->get((int)$orderId);
        $paymentMethod = $paymentsEntity->get($order->payment_method_id);
        $settings = $paymentsEntity->getPaymentSettings($paymentMethod->id);

        $this->design->assign('operation', 'Purchase');
        $this->design->assign('merchant_id', $settings['concordpay_merchant']);

        $price = round($this->money->convert($order->total_price, $paymentMethod->currency_id, false), 2);
        $this->design->assign('amount', $price);

        $this->design->assign('order_id', $order->id . '#' . time());

        $paymentCurrency = $currenciesEntity->get((int)$paymentMethod->currency_id);
        $this->design->assign('currency_iso', $paymentCurrency->code);

        $description = $this->getDescriptionText($languagesEntity) . ' '. htmlspecialchars($_SERVER["HTTP_HOST"]) .
            ', ' . $order->name . ' ' . $order->last_name . ', ' . $order->phone;
        $this->design->assign('description', $description);

        $this->design->assign('signature', $this->generateHash($settings));

        $this->design->assign('add_params', []);

        $this->design->assign('approve_url', Router::generateUrl('order', ['url' => $order->url], true));
        $this->design->assign('decline_url', Router::generateUrl('order', ['url' => $order->url], true));
        $this->design->assign('cancel_url', Router::generateUrl('order', ['url' => $order->url], true));
        $this->design->assign('callback_url', Router::generateUrl('OkayCMS_Concordpay_callback', [], true));

        $this->design->assign('language', $languagesEntity->getMainLanguage()->label);

        // Statistics.
        $this->design->assign('client_first_name', ($order->name ?? ''));
        $this->design->assign('client_last_name', ($order->last_name ?? ''));
        $this->design->assign('email', ($order->email ?? ''));
        $this->design->assign('phone', ($order->phone ?? ''));

        return $this->design->fetch('form.tpl');
    }

    /**
     * @param $fullName
     * @return array
     */
    private function separateFullNameOnFirstNameAndLastName($fullName)
    {
        $parts = explode(' ', $fullName);
        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? '';

        return [$firstName, $lastName];
    }

    /**
     * @param $purchases
     * @return array
     */
    private function getPurchaseNames($purchases)
    {
        $purchasesNames = [];
        foreach ($purchases as $purchase) {
            $purchasesNames[] = $purchase->product_name . ' ' . $purchase->variant_name;
        }

        return $purchasesNames;
    }

    /**
     * @param $purchases
     * @param $currencyId
     * @return array
     */
    private function getPurchasePrices($purchases, $currencyId)
    {
        $purchasesPrices = [];

        foreach ($purchases as $purchase) {
            $purchasesPrices[] = round($this->money->convert($purchase->price, $currencyId, false), 2);
        }

        return $purchasesPrices;
    }

    /**
     * @param $purchases
     * @return array
     */
    private function getPurchaseCount($purchases)
    {
        $purchasesCount = [];

        foreach ($purchases as $purchase) {
            $purchasesCount[] = $purchase->amount;
        }

        return $purchasesCount;
    }

    /**
     * @param $settings
     * @return string
     */
    private function generateHash($settings)
    {
        $keysForSignature = [
            'merchant_id',
            'order_id',
            'amount',
            'currency_iso',
            'description'
        ];

        $hash = [];
        foreach ($keysForSignature as $dataKey) {
            $variableDataKey = $this->design->getVar($dataKey);
            if (empty($variableDataKey)) {
                continue;
            }

            $hash[] = $variableDataKey;
        }
        $hash = implode(';', $hash);

        return hash_hmac('md5', $hash, $settings['concordpay_secretkey']);
    }

    /**
     * @param $phone
     * @return string|string[]
     */
    private function formatPhone($phone)
    {
        $phone = str_replace(['+', ' ', '(', ')'], ['', '', '', ''], $phone);

        if (strlen($phone) == 10) {
            return '38' . $phone;
        }

        if (strlen($phone) == 11) {
            return '3' . $phone;
        }

        return $phone;
    }

    /**
     * @param $languagesEntity
     * @return string
     */
    private function getDescriptionText($languagesEntity)
    {
        $langCode = $languagesEntity->getMainLanguage()->label ?
            mb_strtolower($languagesEntity->getMainLanguage()->label) :
            '';
        switch ($langCode) {
            case 'ru':
                $text = 'Оплата картой на сайте';
                break;
            case 'ua':
                $text = 'Оплата карткою на сайті';
                break;
            default:
                $text = 'Payment by card on the site';
        }

        return $text;
    }
}
