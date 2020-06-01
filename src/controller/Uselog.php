<?php
namespace mpf\controller;

use mpf\MpfController;

class Uselog extends MpfController
{

    protected function mpfInit()
    {
        $this->setMpfModel('mpf_model_Uselog')
            ->setPageTitle('使用日志')
            ->setPageAutoQuery();
    }
}
