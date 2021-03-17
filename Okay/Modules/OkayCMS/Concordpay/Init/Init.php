<?php


namespace Okay\Modules\OkayCMS\Concordpay\Init;

use Okay\Core\Modules\AbstractInit;

/**
 * Class Init
 * @package Okay\Modules\OkayCMS\Concordpay\Init
 */
class Init extends AbstractInit
{
    /**
     * @throws \Exception
     */
    public function install()
    {
        $this->setModuleType(MODULE_TYPE_PAYMENT);
        $this->setBackendMainController('DescriptionAdmin');
    }

    /**
     * @throws \Exception
     */
    public function init()
    {
        $this->addPermission('okaycms__concordpay');

        $this->registerBackendController('DescriptionAdmin');
        $this->addBackendControllerPermission('DescriptionAdmin', 'okaycms__concordpay');
    }
}
