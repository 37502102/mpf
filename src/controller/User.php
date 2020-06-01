<?php
namespace mpf\controller;

use mpf\MpfController;

class User extends MpfController
{

    protected function mpfInit()
    {
        $this->setMpfModel('mpf_model_User')
            ->setPageTitle('用户')
            ->setPageAutoQuery()
            ->setOperatAdd()
            ->setOperatDeletes()
            ->setOperatEdit()
            ->setOperatUpdates('禁用', ['status' => 0])
            ->setOperatUpdates('启用', ['status' => 1]);
    }
}
