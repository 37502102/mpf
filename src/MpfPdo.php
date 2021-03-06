<?php
namespace mpf;

trait MpfPdo
{

    /**
     * 数据库连接
     *
     * @var \PDO
     */
    private static $mpfPdo = null;

    /**
     * 操作实例
     *
     * @var \PDOStatement
     */
    private $mpfPdoStat = null;

    /**
     * 表名
     *
     * @var string
     */
    protected $mpfTable = '';

    /**
     * 主键名
     *
     * @var string
     */
    protected $mpfPk = 'id';

    /**
     * 创建
     *
     * @param array $row
     * @param string $table
     * @return int
     */
    public function pdoCreate(array &$row, string $table = ''): int
    {
        $sql = "INSERT INTO `" . ($table ?: $this->mpfTable) . "` (`" . implode('`,`', array_keys($row)) . "`) values(:" .
            implode(',:', array_keys($row)) . ")";

        $this->mpfPdoStat = self::$mpfPdo->prepare($sql);
        foreach ($row as $k => $v) {
            $this->mpfPdoStat->bindValue(':' . $k, $v);
        }
        if ($this->mpfPdoStat->execute()) {
            return self::$mpfPdo->lastInsertId();
        } else {
            throw new \Exception($this->parseError());
        }
        return 0;
    }

    /**
     * 更新
     *
     * @param int $pkv
     * @param array $row
     * @param string $table
     * @return bool
     */
    public function pdoUpdate(int $pkv, array &$row, string $table = ''): bool
    {
        $sql = "UPDATE `" . ($table ?: $this->mpfTable) . "` SET ";
        foreach ($row as $k => $v) {
            $sql .= "`{$k}` = :{$k},";
        }
        $sql = substr($sql, 0, - 1) . " WHERE `{$this->mpfPk}` = :{$this->mpfPk}";

        $this->mpfPdoStat = self::$mpfPdo->prepare($sql);
        foreach ($row as $k => $v) {
            $this->mpfPdoStat->bindValue(':' . $k, $v);
        }
        $this->mpfPdoStat->bindValue(':' . $this->mpfPk, $pkv);

        return $this->mpfPdoStat->execute();
    }

    /**
     * 根据主键查询
     *
     * @param int $pkv
     * @param string $table
     * @return &array 未找到时返回空数组
     */
    public function &pdoFind(int $pkv, string $table = ''): array
    {
        $sql = "SELECT * FROM `" . ($table ?: $this->mpfTable) . "` WHERE `{$this->mpfPk}` = ?";
        $this->mpfPdoStat = self::$mpfPdo->prepare($sql);
        $this->mpfPdoStat->bindValue(1, $pkv);
        $this->mpfPdoStat->execute();
        $row = $this->mpfPdoStat->fetch(\PDO::FETCH_ASSOC);
        $row = $row ?: [];
        return $row;
    }

    /**
     * 查询
     *
     * @param string $sql
     * @param array $binds
     *            SQL中使用'?'则不用key,SQL中使用:key则用key=>value,只能使用其中一种方式
     * @param string $type
     *            one-返回一条数据,all-返回全部数据
     * @return &array 未查到则返回空数组
     */
    public function &pdoSelect(string $sql, array $binds = [], string $type = 'all'): array
    {
        $this->mpfPdoStat = self::$mpfPdo->prepare($sql);
        foreach ($binds as $k => $v) {
            if (is_int($k)) {
                $this->mpfPdoStat->bindValue($k + 1, $v);
            } else {
                $this->mpfPdoStat->bindValue(':' . $k, $v);
            }
        }
        $this->mpfPdoStat->execute();
        if ($type == 'all') {
            $row = $this->mpfPdoStat->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $row = $this->mpfPdoStat->fetch(\PDO::FETCH_ASSOC);
        }

        $row = $row ?: [];
        return $row;
    }

    /**
     * 查询返回生成器
     *
     * @param string $sql
     * @param array $binds
     *            SQL中使用'?'则不用key,SQL中使用:key则用key=>value,只能使用其中一种方式
     * @return &\Generator
     */
    public function &pdoSelectGen(string $sql, array $binds = []): \Generator
    {
        $this->mpfPdoStat = self::$mpfPdo->prepare($sql);
        foreach ($binds as $k => $v) {
            if (is_int($k)) {
                $this->mpfPdoStat->bindValue($k + 1, $v);
            } else {
                $this->mpfPdoStat->bindValue(':' . $k, $v);
            }
        }
        $this->mpfPdoStat->execute();
        while ($row = $this->mpfPdoStat->fetch(\PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    private function parseError()
    {
        $error = $this->mpfPdoStat->errorInfo();
        return str_ireplace(['Duplicate entry'], ['该名称已存在:'], array_pop($error));
    }
}