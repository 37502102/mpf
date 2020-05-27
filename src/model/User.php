<?php
namespace mpf\model;

use mpf\MpfModel;
use mpf\MpfField;

/**
 * 用户
 *
 * @author chenliang
 */
class User extends MpfModel
{

    protected function mpfInit()
    {
        $this->setMpfTable('mpf_users')->setMpfAutoTime();
        $this->setMpfField(MpfField::new('f1', '编号', 'id')->setPk()
            ->setQueryText())
            ->setMpfField(MpfField::new('f2', '登陆名', 'username')->setQueryText())
            ->setMpfField(MpfField::new('f3', '密码', 'passwd')->setResultDisplay()
            ->setEditNoValue())
            ->setMpfField(MpfField::new('f4', '真实姓名', 'realname')->setQueryText())
            ->setMpfField(
            MpfField::new('f5', '部门', 'department')->setOption([Dictionary::getDics('部门')])
                ->setQuerySelect())
            ->setMpfField(
            MpfField::new('f6', '状态', 'status')->setOption([Dictionary::getDics('用户状态')])
                ->setQuerySelect())
            ->setMpfField(MpfField::new('f7', '所属岗位', 'groups')->setOption([], ',')
            ->setQuerySelect())
            ->setMpfField(MpfField::new('f8', '创建时间', 'create_time')->setEditHidden())
            ->setMpfField(MpfField::new('f9', '更新时间', 'update_time')->setEditHidden());
    }

    protected function mpfCreateBefore($row)
    {
        if ($row['passwd']) {
            $row['passwd'] = $this->getEncrypPassword($row['passwd']);
        } else {
            unset($row['passwd']);
        }
    }

    protected function mpfUpdateBefore($oldRow, $update)
    {
        $this->mpfCreateBefore($update);
    }

    /**
     * 获取加密后的密码
     *
     * @todo 需要实现
     * @param string $password
     * @return string
     */
    protected function getEncrypPassword(string $password)
    {
        return $password;
    }
}