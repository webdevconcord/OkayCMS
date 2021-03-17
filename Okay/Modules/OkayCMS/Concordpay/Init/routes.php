<?php

namespace Okay\Modules\OkayCMS\Concordpay;

return [
    'OkayCMS_Concordpay_callback' => [
        'slug' => 'payment/OkayCMS/Concordpay/callback',
        'params' => [
            'controller' => __NAMESPACE__ . '\Controllers\CallbackController',
            'method' => 'payOrder',
        ],
    ],
];
