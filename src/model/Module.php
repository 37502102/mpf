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
            ->setMpfField(MpfField::new('f3', '所属上级', 'fatherid')->setOption($this->getFatherList())
            ->setQuerySelect())
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
                ->setQuerySelect()
                ->setEditNotice('隐藏状态的模块不会显示在菜单上'))
            ->setMpfField(
            MpfField::new('f10', '图标', 'icon')->setEditNotice('一级模块设置图标，便于菜单收缩时只显示图标，使用图标的class名')
                ->setResultWidth(60)
                ->setResultCallback(function ($v) {
                return $v ? ['html' => 'icon', 'class' => $v] : '';
            }))
            ->setMpfField(
            MpfField::new('f9', '平台', 'platform')->setOption(Dictionary::getDics('模块平台'))
                ->setResultWidth(80)
                ->setQuerySelect())
            ->setMpfField(MpfField::new('f8', '说明', 'bewrite')->setResultDisplay());
        $this->setMpfOrder('platform', 'a')
            ->setMpfOrder('fatherid', 'a')
            ->setMpfOrder('mod_order', 'a');
        $this->setMpfNeedCache();
    }

    /**
     * 获取目录列表
     *
     * @return array
     */
    public function getFatherList(): array
    {
        $return = ['无'];
        $rows = $this->pdoSelect("SELECT id,name,fatherid FROM mpf_modules WHERE url='' ORDER BY fatherid");
        foreach ($rows as $row) {
            $return[$row['id']] = $row['fatherid'] ? $return[$row['fatherid']] . '->' . $row['name'] : $row['name'];
        }
        return $return;
    }

    /**
     * 获取模块列表
     *
     * @param number $platform
     *            平台编号
     * @return array
     */
    public static function getList($platform = 1): array
    {
        $return = ['' => '无', 'all' => '全部'];
        $rows = self::getMpfCache(true)[1];
        foreach ($rows as $k => $v) {
            if ($k == 'menu') {
                continue;
            }
            $return[$k] = $v['name'];
        }
        return $return;
    }

    protected function &mpfCacheBefore(): array
    {
        $return = [];
        $rows = $this->pdoSelect("SELECT * FROM {$this->mpfTable} WHERE status>0 ORDER BY platform,fatherid,mod_order");
        foreach ($rows as $row) {
            $return[$row['platform']][$row['id']] = $row;
        }
        foreach ($return as $platform => $rows) { // 添加菜单
            $return[$platform]['menu'] = $this->addMenu($rows, 0);
        }

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
        $i = 0;
        foreach ($rows as $id => $row) {
            if ($row['fatherid'] == $fatherid && $row['status'] == 1) {
                $return[$i] = $row;
                if (! $row['url']) { // 没有地址属于目录
                    $return[$i]['submenu'] = $this->addMenu($rows, $id);
                }
                $i ++;
            }
        }
        return $return;
    }
}