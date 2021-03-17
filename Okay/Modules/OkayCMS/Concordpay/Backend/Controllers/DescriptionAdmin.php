<?php


namespace Okay\Modules\OkayCMS\Concordpay\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;

/**
 * Class DescriptionAdmin
 * @package Okay\Modules\OkayCMS\Concordpay\Backend\Controllers
 */
class DescriptionAdmin extends IndexAdmin
{
    public function fetch()
    {
        $this->response->setContent($this->design->fetch('description.tpl'));
    }
}
