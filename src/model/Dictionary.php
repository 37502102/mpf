<?php
namespace mpf\model;

use mpf\MpfModel;
use mpf\MpfField;

/**
 * 字典
 *
 * @author chenliang
 */
class Dictionary extends MpfModel
{

    private static $dics;

    protected function mpfInit()
    {
        $this->setMpfTable('mpf_dictionaries');
        $this->setMpfField(MpfField::new('f1', '编号', 'id')->setPk()
            ->setQueryText())
            ->setMpfField(
            MpfField::new('f2', '名称', 'name')->setResultInput()
                ->setQueryText()
                ->setEditRequire()
                ->setEditNotice('按该名称来获取字典'))
            ->setMpfField(
            MpfField::new('f4', '文本', 'dic_value')->setResultInput()
                ->setQueryText()
                ->setEditRequire()
                ->setEditNotice('键值对应的显示文本'))
            ->setMpfField(
            MpfField::new('f3', '键值', 'dic_key')->setResultInput(4)
                ->setQueryText()
                ->setEditRequire()
                ->setEditNotice('数据库中保存的键值'))
            ->setMpfField(
            MpfField::new('f5', '排序', 'dic_order')->setResultInput(2)
                ->setEditInput('number')
                ->setEditNotice('按数值从低到高排序，仅适用于该名称的字典'));
        $this->setMpfNeedCache()->setMpfCond(
            MpfField::new('s1', '名称', 'name')->setOption($this->getNameList())
                ->setQuerySelect());
        $this->setMpfOrder('name', 'a')->setMpfOrder('dic_order', 'a');
    }

    public function getNameList(): array
    {
        $rows = $this->pdoSelect("SELECT DISTINCT name FROM mpf_dictionaries ORDER BY `name`");
        $return = [];
        foreach ($rows as $row) {
            $return[$row['name']] = $row['name'];
        }
        return $return;
    }

    protected function &mpfCacheBefore(): array
    {
        $rows = $this->pdoSelect(
            "SELECT `name`,`dic_key`,`dic_value` FROM {$this->mpfTable} ORDER BY `name`,`dic_order`,`dic_key`");
        $return = [];
        foreach ($rows as $row) {
            $return[$row['name']][$row['dic_key']] = $row['dic_value'];
        }
        return $return;
    }

    /**
     * 获取字典
     *
     * @param string $name
     *            名称
     * @return array
     */
    public static function getDics(string $name): array
    {
        if (! self::$dics) {
            self::$dics = self::getMpfCache();
        }

        return self::$dics[$name] ?? [];
    }

    /**
     * 获取字典中键对应的文本
     *
     * @param string $name
     *            名称
     * @param int|string $key
     *            键
     * @return string
     */
    public static function getDicText(string $name, $key): string
    {
        if (! self::$dics) {
            self::$dics = self::getMpfCache();
        }

        return self::$dics[$name][$key] ?? $key;
    }

    protected function mpfCreateBefore(&$record): void
    {
        $row = $this->pdoSelect("SELECT * FROM {$this->mpfTable} WHERE name=? AND dic_key=?",
            [$record['name'], $record['dic_key']], 'one');
        if ($row) {
            throw new \Exception("名称【{$record['name']}】下的键值【{$record['dic_key']}】已经存在，请勿重复添加");
        }
    }
}