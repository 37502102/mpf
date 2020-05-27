<?php
namespace mpf\model;

use mpf\MpfModel;
use mpf\MpfField;

/**
 * 模块
 *
 * @author chenliang
 */
class Module extends MpfModel
{

    protected function mpfInit()
    {
        $this->setMpfTable('mpf_modules');
        $this->setMpfField(MpfField::new('f1', '编号', 'id')->setPk()
            ->setQueryText())
            ->setMpfField(MpfField::new('f2', '名称', 'name')->setResultInput()
            ->setEditRequire()
            ->setQueryText())
            ->setMpfField(
            MpfField::new('f3', '所属上线', 'fatherid')->setOption($this->getFatherList())
                ->setQuerySelect()
                ->setTagColor())
            ->setMpfField(
            MpfField::new('f4', '地址', 'url')->setResultInput()
                ->setQueryText()
                ->setEditNotice('可以是绝对地址，也可以是模块名，为空时则是目录'))
            ->setMpfField(
            MpfField::new('f5', '自定义操作', 'actions')->setResultInput()
                ->setQueryText()
                ->setEditNotice('多个用英文逗号分隔'))
            ->setMpfField(
            MpfField::new('f7', '排序', 'mod_order')->setResultInput(3)
                ->setEditInput('number')
                ->setEditNotice('从低到高排序'))
            ->setMpfField(
            MpfField::new('f6', '状态', 'status')->setOption(Dictionary::getDics('模块状态'))
                ->setResultWidth(80)
                ->setTagColor()
                ->setQuerySelect())
            ->setMpfField(
            MpfField::new('f9', '平台', 'platform')->setOption(Dictionary::getDics('模块平台'))
                ->setResultWidth(80)
                ->setTagColor()
                ->setQuerySelect())
            ->setMpfField(MpfField::new('f8', '说明', 'bewrite'));
        $this->setMpfOrder('platform', 'a')
            ->setMpfOrder('fatherid', 'a')
            ->setMpfOrder('mod_order', 'a');
        $this->setMpfNeedCache();
    }

    public function getFatherList()
    {
        $return = ['无'];
        $rows = $this->pdoSelect("SELECT id,name,fatherid FROM {$this->mpfTable} WHERE url='' ORDER BY fatherid");
        foreach ($rows as $row) {
            $return[$row['id']] = $row['fatherid'] ? $return[$row['fatherid']] . '->' . $row['name'] : $row['name'];
        }
        return $return;
    }

    protected function &mpfCacheBefore(): array
    {
        $return = [];
        $rows = $this->pdoSelect("SELECT * FROM {$this->mpfTable} WHERE status>0 ORDER BY fatherid,mod_order");
        foreach ($rows as $row) {
            $return[$row['id']] = $row;
        }
        $return['menu'] = $this->addMenu($return, 0);

        return $return;
    }

    /**
     * 递归的添加菜单
     *
     * @param array $rows
     *            排序好的模块数组
     * @param int $fatherid
     *            上级模块编号
     * @return array
     */
    private function &addMenu(&$rows, $fatherid)
    {
        $return = [];
        foreach ($rows as $id => $row) {
            if ($row['fatherid'] == $fatherid && $row['status'] == 1) {
                $return[$id] = $row;
                if (! $row['url']) { // 没有地址属于目录
                    $return[$id]['submenu'] = $this->addMenu($rows, $id);
                }
            }
        }
        return $return;
    }
}