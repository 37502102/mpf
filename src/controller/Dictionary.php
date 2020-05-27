<?php
namespace mpf\controller;

use mpf\MpfController;

class Dictionary extends MpfController
{

    protected function mpfInit()
    {
        $this->setMpfModel('mpf_model_Dictionary')
            ->setPageTitle('字典')
            ->setPageExplain(['保存所有表的枚举字段的key-value。', '字典会保存在缓存中，添加删除或修改后需要更新缓存才会生效。'])
            ->setPageAutoQuery()
            ->setOperatAdd()
            ->setOperatDeletes()
            ->setOperatEdit()
            ->setOperatImport()
            ->setOperatSaves();
    }
}