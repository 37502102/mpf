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
            ->setMpfField(
            MpfField::new('f2', '登陆名', 'username')->setEditRequire()
                ->setEditNotice('登陆名必须唯一')
                ->setQueryText())
            ->setMpfField(
            MpfField::new('f3', '密码', 'passwd')->setResultDisplay()
                ->setEditInput('password')
                ->setEditNotice('添加时必须填写，修改时不填写则不会修改密码')
                ->setEditNoValue())
            ->setMpfField(MpfField::new('f4', '真实姓名', 'realname')->setQueryText())
            ->setMpfField(
            MpfField::new('f5', '部门', 'department')->setOption(Dictionary::getDics('部门'))
                ->setResultWidth(100)
                ->setEditRequire()
                ->setQuerySelect())
            ->setMpfField(
            MpfField::new('f6', '状态', 'status')->setOption(Dictionary::getDics('状态'))
                ->setResultWidth(80)
                ->setEditRequire()
                ->setQuerySelect())
            ->setMpfField(
            MpfField::new('f7', '所属岗位', 'groups')->setOption((new Group())->getList(), ',')
                ->setQuerySelect()
                ->setEditNotice('只有设置了岗位后，用户才会有该岗位的权限，可多选'))
            ->setMpfField(MpfField::new('f8', '创建时间', 'create_time')->setEditHidden())
            ->setMpfField(MpfField::new('f9', '更新时间', 'update_time')->setEditHidden()
            ->setResultDisplay());
    }

    protected function mpfCreateBefore(&$row): void
    {
        if (! isset($row['passwd'])) {
            throw new \Exception('添加用户时密码不能为空');
        }
        $row['passwd'] = self::getEncrypPassword($row['passwd']);
    }

    protected function mpfUpdateBefore(&$oldRow, &$update): void
    {
        if (isset($update['passwd'])) {
            $update['passwd'] = self::getEncrypPassword($update['passwd']);
        }
    }

    /**
     * 获取加密后的密码，可修改成自己使用的加密方式
     *
     * @param string $password
     * @return string
     */
    public static function getEncrypPassword(string $password)
    {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 10]);
    }

    /**
     * 验证密码，可修改成自己使用的加密方式进行验证
     *
     * @param string $password
     *            待验证的密码
     * @param string $encrypPassword
     *            已加密的密码
     * @return boolean
     */
    public static function verifyPassword(string $password, string $encrypPassword)
    {
        return password_verify($password, $encrypPassword);
    }

    public function getList()
    {
        $return = [];
        $rows = $this->pdoSelect("SELECT id, username FROM mpf_users");
        foreach ($rows as $row) {
            $return[$row['id']] = $row['username'];
        }
        return $return;
    }
}