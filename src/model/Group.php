<?php
namespace mpf\model;

use mpf\MpfModel;
use mpf\MpfField;
use mpf\MpfController;

/**
 * 岗位
 *
 * @author chenliang
 */
class Group extends MpfModel
{

    protected function mpfInit()
    {
        $this->setMpfTable('mpf_groups');

        $this->setMpfField(MpfField::new('f1', '编号', 'id')->setPk()
            ->setQueryText())
            ->setMpfField(
            MpfField::new('f2', '名称', 'name')->setQueryText()
                ->setEditRequire()
                ->setEditNotice('同一部门下的岗位名称必须唯一'))
            ->setMpfField(
            MpfField::new('f3', '部门', 'department')->setOption(Dictionary::getDics('部门'))
                ->setResultWidth(100)
                ->setQuerySelect()
                ->setEditRequire())
            ->setMpfField(
            MpfField::new('f4', '访问权限', 'modules')->setOption($rows = Module::getList(), ',')
                ->setQuerySelect()
                ->setEditNotice('当选择了下级模块时，一定要选择其上级模块，否则不会在菜单中显示')
                ->setEditCallback(
                function ($v, $row) {
                    return ['html' => 'tree', 'name' => MpfController::$queryNames['row'] . '[f4][]',
                        'value' => explode(',', $v),
                        'data' => array_merge([['id' => '', 'name' => '无'], ['id' => 'all', 'name' => '全部']],
                            Module::getMpfCache(true)[1]['menu'])];
                }))
            ->setMpfField(
            MpfField::new('f5', '编辑权限', 'edits')->setOption($rows, ',')
                ->setQuerySelect()
                ->setEditCallback(
                function ($v, $row) {
                    return ['html' => 'tree', 'name' => MpfController::$queryNames['row'] . '[f4][]',
                        'value' => explode(',', $v),
                        'data' => array_merge([['id' => '', 'name' => '无'], ['id' => 'all', 'name' => '全部']],
                            Module::getMpfCache(true)[1]['menu'])];
                }))
            ->setMpfField(
            MpfField::new('f6', '状态', 'status')->setOption(Dictionary::getDics('状态'))
                ->setResultWidth(80)
                ->setQuerySelect()
                ->setEditRequire());
    }

    public function getList(): array
    {
        $return = ['' => '无'];
        $rows = $this->pdoSelect("SELECT id, name FROM mpf_groups WHERE status = 1");
        foreach ($rows as $row) {
            $return[$row['id']] = $row['name'];
        }
        return $return;
    }
}