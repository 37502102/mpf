<?php
namespace mpf\controller;

use mpf\MpfController;

class Group extends MpfController
{

    protected function mpfInit()
    {
        $this->setMpfModel('mpf_model_Group')
            ->setPageTitle('岗位')
            ->setPageAutoQuery()
            ->setOperatAdd()
            ->setOperatDeletes()
            ->setOperatEdit()
            ->setOperatUpdates('禁用', ['status' => 0])
            ->setOperatUpdates('启用', ['status' => 1]);
    }
}