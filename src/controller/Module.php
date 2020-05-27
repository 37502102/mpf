<?php
namespace mpf\controller;

use mpf\MpfController;

class Module extends MpfController
{

    protected function mpfInit()
    {
        $this->setMpfModel('mpf_model_Module')
            ->setPageTitle('模块')
            ->setPageExplain(['', ''])
            ->setPageAutoQuery()
            ->setOperatAdd()
            ->setOperatDeletes()
            ->setOperatEdit()
            ->setOperatImport()
            ->setOperatSaves()
            ->setOperatUpdates('禁用', ['status' => 0])
            ->setOperatUpdates('启用', ['status' => 1])
            ->setOperatUpdates('隐藏', ['status' => 2]);
    }
}