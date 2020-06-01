<?php
namespace mpf\model;

use mpf\MpfModel;
use mpf\MpfField;

/**
 * 模块操作日志
 *
 * @author chenliang
 */
class Uselog extends MpfModel
{

    protected function mpfInit()
    {
        $this->setMpfTable('mpf_uselogs');
        $this->setMpfField(MpfField::new('f1', '编号', 'id')->setQueryText())
            ->setMpfField(
            MpfField::new('f2', '用户名', 'user_id')->setOption((new User())->getList())
                ->setResultWidth(100)
                ->setQuerySelect())
            ->setMpfField(
            MpfField::new('f3', '模块', 'module_id')->setOption(Module::getList())
                ->setResultWidth(100)
                ->setQuerySelect())
            ->setMpfField(MpfField::new('f4', '操作', 'action')->setQueryText()
            ->setResultWidth(100))
            ->setMpfField(MpfField::new('f5', '数据', 'data')->setQueryText()
            ->setResultMore(30))
            ->setMpfField(MpfField::new('f6', 'IP', 'ip')->setQueryText()
            ->setResultWidth(120))
            ->setMpfField(
            MpfField::new('f8', '操作日期', 'create_date')->setQueryDate()
                ->setResultWidth(100)
                ->setQueryDefault(date('Y-m-d', strtotime('-1 day'))))
            ->setMpfField(MpfField::new('f9', '操作时间', 'create_time')->setResultWidth(100));
    }
}