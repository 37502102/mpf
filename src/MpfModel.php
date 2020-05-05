<?php
namespace mpf;

/**
 * MPF表模型,可以从框架中的模型基类继承
 * 内部的数据库操作使用PDO,以避免在不同框架中要进行大量修改
 *
 * @author chenliang
 */
abstract class MpfModel
{
    use MpfPdo;

    /**
     * 当字段对应为上传的文件时,保存文件的绝对路径,需要根据项目设置
     *
     * @var string
     * @todo 需要修改
     */
    private static $uploadPath = 'd:/xampp/htdocs/upload';

    /**
     * 上传文件的下载地址
     *
     * @var string
     */
    private static $uploadUrl = 'upload';

    /**
     * 缓存表的键名,前面应加上项目名,以避免不同项目之间的冲突
     *
     * @var string
     * @todo 需要把app_name替换
     */
    private static $cacheKey = 'mpf.mpfTableCache.';

    private static $cacheObj;

    /**
     * 查询条件类型对应的HTTP请求参数名(value可修改)
     *
     * @var array --text:文本搜索,act=准确值,blur=模糊搜索<br>
     *      --number:数字条件,min=最小值,max=最大值<br>
     *      --stat:统计条件,min=最小值,max=最大值<br>
     *      --date:日期条件,min=开始日期,max=结束日期<br>
     *      --select:多选条件<br>
     *      --selectone:单选条件<br>
     *      --display:显示字段
     */
    public static $condTypes = ['text' => 'tx', 'number' => 'nb', 'stat' => 'st', 'date' => 'da', 'select' => 'sl',
        'selectone' => 'so', 'display' => 'dp'];

    /**
     * 设置项
     *
     * @var array --autoTime array 自动时间createTime,updateTime,timeType<br>
     *      --alias string 表的别名<br>
     *      --fields MpfField[] 查询字段<br>
     *      --stats MpfField[] 统计字段<br>
     *      --joins array 关联SQL<br>
     *      --conds MpfField[] 查询条件<br>
     *      --orders array 排序<br>
     *      --initJoin string 初始关联SQL<br>
     *      --initCond string 初始条件SQL<br>
     *      --needCache boolean 是否缓存<br>
     *      --fieldKeys array field与key的对应<br>
     *      --fieldSelects array 当前要显示的字段<br>
     *      --fieldOrders array 当前排序<br>
     */
    protected $mpfSets = ['alias' => '', 'fields' => [], 'stats' => [],
        'autoTime' => ['createTime' => '', 'updateTime' => '', 'timeType' => 'datetime'], 'joins' => [],
        'conds' => [], 'orders' => [], 'initJoin' => '', 'initCond' => '', 'needCache' => false, 'fieldKeys' => [],
        'fieldSelects' => []];

    /**
     * 构造函数
     *
     * @param array $attributes
     *            由MpfController调用时会加上'mpf'=>true则会调用mpfinit()进行初始化
     */
    public function __construct($attributes = [])
    {
        $isMpf = false;
        if (isset($attributes['isMpf'])) {
            $isMpf = true;
            unset($attributes['isMpf']);
        }

        $notTable = false;
        if (isset($attributes['notTable'])) {
            $notTable = true;
            unset($attributes['notTable']);
        }

        foreach (self::$condTypes as $k => $v) {
            $this->mpfSets['conds'][$k] = [];
        }

        if ($isMpf) {
            if (! $notTable && ! self::$mpfPdo) {
                self::$mpfPdo = new \PDO($attributes['dsn'], $attributes['username'], $attributes['password']);
                unset($attributes['dsn'], $attributes['username'], $attributes['password']);
            }
            $this->mpfInit();
        }

        // 调用框架模型的构造函数
        // if (!$notTable) {
        // parent::__construct($attributes);
        // }
    }

    /**
     * MPF的初始化,添加表的各种设置
     */
    abstract protected function mpfInit();

    /**
     * 设置表
     *
     * @param string $table
     * @param string $pk
     * @return self
     */
    protected function setMpfTable(string $table, string $pk = 'id'): self
    {
        $this->mpfTable = $table;
        $this->mpfPk = $pk;
        return $this;
    }

    /**
     * 自动时间设置
     *
     * @param string $createTime
     *            创建时间字段名,为空时不设置自动时间
     * @param string $updateTime
     *            更新时间字段名,为空时不设置自动时间
     * @param string $timeType
     *            时间格式datetime-'Y-m-d H:i:s',int:timestamp
     * @return self
     */
    public function setMpfAutoTime(string $createTime = 'create_time', string $updateTime = 'update_time', string $timeType = 'datetime'): self
    {
        $this->mpfSets['autoTime']['createTime'] = $createTime;
        $this->mpfSets['autoTime']['updateTime'] = $updateTime;
        $this->mpfSets['autoTime']['timeType'] = in_array($timeType, ['datetime', 'int']) ? $timeType : 'datetime';
        return $this;
    }

    /**
     * 设置主表的别名,应在设置字段之前设置
     *
     * @param string $alias
     * @return self
     */
    protected function setMpfAlias(string $alias): self
    {
        $this->mpfSets['alias'] = $alias;
        return $this;
    }

    /**
     * 添加关联查询,只有要显示字段时才会加载对应的关联SQL
     *
     * @param string $key
     * @param string $joinSql
     */
    protected function setMpfJoin(string $key, string $joinSql): self
    {
        $this->mpfSets['joins'][$key] = $joinSql;
        return $this;
    }

    /**
     * 添加字段
     *
     * @param MpfField $field
     * @return self
     */
    public function setMpfField(MpfField $field): self
    {
        if ($this->mpfPk == $field->getFieldReal(true)) {
            $field->setPk();
        }

        if ($this->mpfSets['alias']) { // 主表有别名时，未加别名的字段默认为主表字段
            $field->addTableAlias($this->mpfSets['alias']);
        }

        $this->mpfSets['fields'][$field->getKey()] = $field;

        if ($field->isStat()) {
            $this->mpfSets['stats'][$field->getKey()] = $field;
        }

        $this->setMpfCond($field);

        return $this;
    }

    /**
     * 删除字段
     *
     * @param array $keys
     * @return self
     */
    public function unsetMpfFields(array $keys): self
    {
        foreach ($keys as $key) {
            if (isset($this->mpfSets['fields'][$key])) {
                unset($this->mpfSets['fields'][$key]);
            }
        }
        return $this;
    }

    /**
     * 设置初始要关联的SQL
     *
     * @param string $joinSql
     * @return self
     */
    protected function setMpfInitJoin(string $joinSql): self
    {
        $this->mpfSets['initJoin'] = $joinSql;
        return $this;
    }

    /**
     * 设置需要缓存
     *
     * @return self
     */
    protected function setMpfNeedCache(): self
    {
        $this->mpfSets['needCache'] = true;
        return $this;
    }

    /**
     * 设置或添加初始查询条件
     *
     * @param string $condSQL
     * @param string $operator
     *            为空时是设置初始查询条件,为AND或OR时为添加初始查询条件
     * @return self
     */
    public function setMpfInitCond(string $condSQL, string $operator = ''): self
    {
        $operator = strtoupper($operator);
        if (in_array($operator, ['AND', 'OR'])) {
            $this->mpfSets['initCond'] .= $this->mpfSets['initCond'] ? " $operator $condSQL" : $condSQL;
        } else {
            $this->mpfSets['initCond'] = $condSQL;
        }
        return $this;
    }

    /**
     * 添加不在字段设置中的搜索项
     *
     * @param MpfField $field
     *            一定要调用setQueryText(),setQueryDate(),setQueryNumber(),setQuerySelect(),setQuerySelectone()之一
     * @return self
     */
    public function setMpfCond(MpfField $field): self
    {
        if (($type = $field->getQueryType()) && isset(self::$condTypes[$type])) {
            $this->mpfSets['conds'][$type][$field->getKey()] = $field;
        }
        return $this;
    }

    /**
     * 添加默认排序
     *
     * @param string $key
     *            字段设置中的key,如果不在字段设置中则为sql
     * @param string $order
     *            a-ASC,d-DESC
     * @return self
     */
    public function setMpfOrder(string $key, string $order = 'd'): self
    {
        $this->mpfSets['orders'][$key] = strtolower($order) == 'd' ? 'DESC' : 'ASC';
        return $this;
    }

    /**
     * 获取字段设置
     *
     * @param string $key
     *            返回对应的字段设置
     * @return MpfField|null 未设置则返回null
     */
    public function getMpfField($key)
    {
        return $this->mpfSets['fields'][$key] ?? null;
    }

    /**
     * 获取统计字段
     *
     * @param string $key
     *            返回对应的统计字段设置
     * @return MpfField|null
     */
    private function getMpfStat(string $key)
    {
        return $this->mpfSets['stats'][$key] ?? null;
    }

    /**
     * 获取字段与key的对应数组
     *
     * @param string $field
     *            为空返回整个数组,否则返回field对应的key
     * @return array|string
     */
    protected function getMpfFieldKey(string $field)
    {
        if (! $this->mpfSets['fieldKeys']) {
            foreach ($this->mpfSets['fields'] as $k => $v) {
                $this->mpfSets['fieldKeys'][$v->getFieldAlias(true)] = $k;
            }
        }
        return $this->mpfSets['fieldKeys'][$field] ?? '';
    }

    /**
     * 获取是否要缓存表数据
     *
     * @return bool
     */
    public function getMpfNeedCache(): bool
    {
        return $this->mpfSets['needCache'];
    }

    /**
     * 获取缓存键名
     *
     * @return string
     */
    private static function getMpfCacheKey(): string
    {
        return self::$cacheKey . str_replace('\\', '.', static::class);
    }

    /**
     * 获取缓存对象
     *
     * @todo 需实现
     * @return \Redis
     */
    private static function getMpfCacheObj()
    {
        if (! self::$cacheObj) {
            self::$cacheObj = new \Redis();
            self::$cacheObj->connect('127.0.0.1', 6379, 5);
        }
        return self::$cacheObj;
    }

    /**
     * 获取缓存表数组
     *
     * @return &array
     */
    public static function &getMpfCache(): array
    {
        $key = self::getMpfCacheKey();
        $value = unserialize(self::getMpfCacheObj()->get($key));

        return $value;
    }

    /**
     * 缓存表数组
     *
     * @return self
     */
    public function mpfCache(): self
    {
        $key = self::getMpfCacheKey();
        $value = serialize($this->mpfCacheBefore());
        self::getMpfCacheObj()->set($key, $value);

        return $this;
    }

    /**
     * 获取表要缓存的内容,默认是['pk' => ['field' => 'value', ...]]的形式,要更改时请重载
     *
     * @return &array
     */
    protected function &mpfCacheBefore(): array
    {
        $rows = [];
        $sql = "SELECT * FROM `{$this->mpfTable}` ORDER BY `{$this->mpfPk}`";
        $recodes = $this->pdoSelectGen($sql, []);
        foreach ($recodes as $row) {
            $rows[$row[$this->mpfPk]] = $row;
        }
        return $rows;
    }

    /**
     * 递归的去除空白符
     *
     * @param array $ary
     */
    public static function requestTrim(array &$ary): void
    {
        foreach ($ary as $k => $v) {
            if (is_array($v)) {
                self::requestTrim($v);
            } else {
                $ary[$k] = trim($v);
            }
        }
    }

    /**
     * 数据是否为空
     *
     * @param mixed $v
     * @return bool
     */
    public static function valueIsEmpty($v): bool
    {
        if (is_array($v)) {
            if (! $v) {
                return true;
            }
        }
        if ($v === null || strlen($v) == 0) {
            return true;
        }
        return false;
    }

    /**
     * 如果有上传,将$_FILES中的数据放入$record中
     *
     * @param array $record
     */
    private function checkUpload(array &$record): void
    {
        if (isset($_FILES['rcd'])) {
            foreach ($_FILES['rcd']['name'] as $key => $name) {
                $record[$key]['name'] = $name;
            }
            foreach ($_FILES['rcd']['tmp_name'] as $key => $name) {
                $record[$key]['tmp_name'] = $name;
            }
        }
    }

    /**
     * 检查提交的表单必填项是否有值,去除不在设置中或设置为不可编辑的,返回key转化成field的数组
     *
     * @param array $record
     * @param bool $isImport
     *            是否为导入
     * @throws \Exception
     * @return array
     */
    private function &checkRecord(array &$record, bool $isImport = false): array
    {
        self::requestTrim($record);

        $msg = $new = [];
        foreach ($record as $key => $v) {
            $fieldSet = $this->getMpfField($key);
            if ($v === null) {
                $record[$key] = '';
            }
            if (! $fieldSet || $fieldSet->getEditHidden() || $fieldSet->getEditReadOnly() ||
                 ($fieldSet->getJoinKey() && ! $fieldSet->getJoinEdit())) {
                unset($record[$key]);
                continue;
            } elseif ($fieldSet->getEditRequire() && self::valueIsEmpty($v)) {
                $msg[] = "【{$fieldSet->getText()}】不能为空";
                continue;
            } elseif ($fieldSet->getOptionSeparator()) {
                if (is_array($v)) {
                    $record[$key] = implode($fieldSet->getOptionSeparator(), $v);
                } else {
                    $msg[] = "【{$fieldSet->getText()}】的值不是数组";
                    continue;
                }
            } elseif ($fieldSet->isEditUpload()) {
                if (isset($v['name']) && isset($v['tmp_name'])) {
                    $this->mpfSets['uploads'][$key] = $fieldSet->setEditUploadFile($v['name'], $v['tmp_name']);
                    $record[$key] = '-';
                } else {
                    $msg[] = "【{$fieldSet->getText()}】需要上传文件的name和tmp_name";
                    continue;
                }
            } elseif ($isImport && ($option = $fieldSet->getOption())) { // 导入时将枚举文本转化为对应的值
                $record[$key] = array_search($v, $option);
                if ($record[$key] === false) {
                    $msg[] = "【{$v}】未找到对应的值";
                    continue;
                }
            }
            $field = $fieldSet->getFieldAlias(true);
            $new[$field] = $v;
        }
        if ($msg) {
            throw new \Exception(implode('\n', $msg));
        }
        return $new;
    }

    /**
     * 检查查询的表单,必填项是否有值
     *
     * @param array $req
     * @throws \Exception
     */
    private function checkRequest(array &$req): void
    {
        self::requestTrim($req);

        $msg = [];
        foreach ($this->mpfSets['conds'] as $fields) {
            /**
             *
             * @var MpfField $fieldSet
             */
            foreach ($fields as $key => $fieldSet) {
                if ($fieldSet->getQueryRequire()) {
                    $type = $fieldSet->getQueryType();
                    $reqKey = self::$condTypes[$fieldSet->getQueryType()];
                    switch ($type) {
                        case 'text':
                            $value = $req[$reqKey][$key]['act'] ?? '';
                            break;
                        case 'date':
                        case 'number':
                            $value = $req[$reqKey][$key]['min'] ?? '';
                            break;
                        default:
                            $value = $req[$reqKey][$key];
                    }
                    if (self::valueIsEmpty($value)) {
                        $msg[] = "【" . $fieldSet->getText() . "】";
                    }
                }
            }
        }
        if ($msg) {
            throw new \Exception("查询项" . implode(',', $msg) . "不能为空");
        }
    }

    /**
     * 检查是否有自动时间设置,有则将当前时间填入$row
     *
     * @param array $row
     * @param bool $isAdd
     */
    private function checkAutoTime(array &$row, bool $isAdd = false)
    {
        if ($isAdd && isset($this->mpfSets['autoTime']['createTime']) && $this->mpfSets['autoTime']['createTime']) {
            $row[$this->mpfSets['autoTime']['createTime']] = $this->mpfSets['autoTime']['timeType'] == 'int' ? time() : date(
                'Y-m-d H:i:s');
        }
        if (isset($this->mpfSets['autoTime']['updateTime']) && $this->mpfSets['autoTime']['updateTime']) {
            $row[$this->mpfSets['autoTime']['updateTime']] = $this->mpfSets['autoTime']['timeType'] == 'int' ? time() : date(
                'Y-m-d H:i:s');
        }
    }

    /**
     * 获取默认查询
     *
     * @return array
     */
    private function getDefaultQuery()
    {
        $return = [];
        /**
         *
         * @var MpfField $fieldSet
         */
        foreach ($this->mpfSets['fields'] as $key => $fieldSet) {
            if ($ary = $fieldSet->getQueryDefault()) {
                $return = array_merge_recursive($return, $ary);
            }
            if ($fieldSet->getQueryDisplay()) {
                $return[self::$condTypes['display']][$key] = 1;
            }
        }
        return $return;
    }

    /**
     * 获取结果页
     *
     * @param array $req
     *            查询请求
     * @param int $pageSize
     * @param bool $isExcel
     *            是否返回生成器,用于导出excel
     * @return &\Generator|array
     * @throws \Exception 如果有设置查询项为必填而没有值时
     */
    public function &getPageResult(array $req, int $pageSize, bool $isExcel = false)
    {
        $this->checkRequest($req);

        if (! isset($req['subv'])) { // 进入页面的自动查询,使用默认查询
            $req = $this->getDefaultQuery();
        }

        $wheres = $groupBys = $havings = $selects = $orders = $joins = $bindings = [];

        $orderKey = ''; // 当前排序列
        if (isset($req['ord']) && strpos($req['ord'], '-') !== false) { // 有手动排序
            list ($orderKey, $order) = explode('-', $req['ord']);
            if ($ordField = $this->getMpfField($orderKey)) {
                $orders[] = $ordField->getFieldAlias() . " " . (strtolower($order) == 'd' ? 'DESC' : 'ASC');
            }
            $fOrderStr = $orderKey . '-' . (strtolower($order) == 'd' ? 'a' : 'd'); // 相反排序
        }

        $stats = $this->mpfSets['stats'];
        foreach ($req as $reqKey => $reqValue) {
            $keyCond = array_flip(self::$condTypes);

            if (! isset($keyCond[$reqKey]) || ! is_array($reqValue)) { // 不在指定的查询项中
                continue;
            }
            $condType = $keyCond[$reqKey];
            foreach ($reqValue as $key => $value) {
                if ($condType != 'display' && ! isset($this->mpfSets['conds'][$condType][$key])) { // 不在查询设置中
                    continue;
                }
                if (! ($fieldSet = $this->mpfSets['conds'][$condType][$key] ?? $this->getMpfField($key))) {
                    continue; // 如果是display会在fields中,没有对应的字段设置则跳过
                }
                $field = $fieldSet->getFieldReal();

                if ($join = $fieldSet->getJoinKey()) { // 有关联,添加关联SQL
                    $joins[$join] = $this->mpfSets['joins'][$join];
                }
                switch ($condType) {
                    case 'display':
                        $selects[] = $fieldSet->getSql();
                        if ($stats) { // 有统计项加入归类字段
                            $groupBys[] = $fieldSet->getFieldAlias();
                        }
                        $this->mpfSets['fieldSelects'][] = $key; // 显示的字段
                        $this->mpfSets['fieldOrders'][$key] = $orderKey == $key ? $fOrderStr : $key . "-d"; // 未排序字段默认点击倒序
                        break;
                    case 'text':
                        if (! isset($value['act']) || $value['act'] === '' || $value['act'] === null) {
                            break;
                        }
                        if (strpos($value['act'], ',') || strpos($value['act'], '，')) { // 支持有分隔符的多项搜索
                            $search = strpos($value['act'], ',') ? explode(',', $value['act']) : explode('，',
                                $value['act']);
                            $search = array_map('trim', $search);
                        } else {
                            $search = $value['act'];
                        }
                        if (isset($value['blur']) && $fieldSet->canQueryBlur()) { // 模糊搜索
                            if (is_array($search)) {
                                foreach ($search as &$v) {
                                    $bindings[] = $v;
                                    $v = "{$field} LIKE CONCAT('%',?,'%')";
                                }
                                $wheres[] = "(" . implode(' OR ', $search) . ")";
                            } else {
                                $wheres[] = "{$field} LIKE CONCAT('%',?,'%')";
                                $bindings[] = $search;
                            }
                        } else {
                            if (is_array($search)) {
                                $wheres[] = "{$field} IN (" . str_repeat('?, ', count($search) - 1) . "?)";
                                $bindings = array_merge($bindings, $search);
                            } else {
                                $wheres[] = "{$field} = ?";
                                $bindings[] = $search;
                            }
                        }
                        break;
                    case 'number':
                    case 'date':
                        if (isset($value['min']) && strlen($value['min'])) {
                            $wheres[] = "$field >= ?";
                            $bindings[] = $value['min'];
                        }
                        if (isset($value['max']) && strlen($value['max'])) {
                            $wheres[] = "$field <= ?";
                            $bindings[] = $value['max'];
                        }
                        break;
                    case 'select':
                        if (count($value) < 1) {
                            break;
                        }
                        if ($sp = $fieldSet->getOptionSeparator()) { // 有分隔符
                            foreach ($value as &$v) {
                                $bindings[] = $v;
                                if ($sp == ',') {
                                    $v = "FIND_IN_SET(?, {$field})";
                                } else {
                                    $v = "{$field} LIKE CONCAT('%',?,'%')";
                                }
                            }
                            $wheres[] = "(" . implode(' OR ', $value) . ")";
                        } else {
                            $wheres[] = "{$field} IN (" . str_repeat('?, ', count($value) - 1) . "?)";
                            $bindings = array_merge($bindings, $value);
                        }
                        break;
                    case 'selectone':
                        $wheres[] = "{$field} = ?";
                        $bindings[] = $value;
                        break;
                    case 'stat':
                        if (isset($value['min']) && is_numeric($value['min'])) {
                            $havings[] = "{$field} >= {$value['min']}";
                        }
                        if (isset($value['max']) && is_numeric($value['max'])) {
                            $havings[] = "{$field} <= {$value['max']}";
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        if ($stats) { // 统计字段都显示
            foreach ($stats as $k => $v) {
                $selects[] = $v->getSql();
                $this->mpfSets['fieldSelects'][] = $k;
                $this->mpfSets['fieldOrders'][$k] = $orderKey == $k ? $fOrderStr : $k . "-d";
                if ($join = $fieldSet->getJoinKey()) { // 有关联SQL
                    $joins[$join] = $this->mpfSets['joins'][$join];
                }
            }
        }
        if (! empty($this->mpfSets['initCond'])) { // 初始条件
            $wheres[] = $this->mpfSets['initCond'];
        }
        if (! empty($this->mpfSets['initJoin'])) { // 初始关联
            $joins['init'] = $this->mpfSets['initJoin'];
        }
        if ($this->mpfPk && ! $groupBys) { // 不显示主键时依然将主键放入查询字段中,否则没有主键值无法编辑
            $as = $this->mpfSets['alias'] ? $this->mpfSets['alias'] . "." : '';
            if (! in_array($as . $this->mpfPk, $selects)) {
                $selects[] = $as . $this->mpfPk;
            }
        }
        if (! $orders && ! empty($this->mpfSets['orders'])) { // 无手动排序,有默认排序
            foreach ($this->mpfSets['orders'] as $key => $order) {
                if ($fieldSet = $this->getMpfField($key)) {
                    if ($join = $fieldSet->getJoinKey() && ! isset($joins[$join]) || // 字段在关联查询中，该关联未使用
                         $stats && ! in_array($fieldSet->getSql(), $selects)) { // 统计查询，该字段未在归类项中
                        continue;
                    }
                    $orders[] = $fieldSet->getFieldAlias() . " $order";
                } else { // 不在字段设置中,key为sql
                    $orders[] = "$key $order";
                }
            }
        }

        $sql = "SELECT " . implode(", ", $selects) . " FROM {$this->mpfTable}" .
             ($this->mpfSets['alias'] ? " AS {$this->mpfSets['alias']}" : "") .
             ($joins ? " " . implode(' ', $joins) : "") . ($wheres ? " WHERE " . implode(" AND ", $wheres) : "") . ($groupBys ? " GROUP BY " .
             implode(", ", $groupBys) . ($havings ? " HAVING " . implode(" AND ", $havings) : "") : "") .
             ($orders ? " ORDER BY " . implode(", ", $orders) : "");

        if ($isExcel) { // excel返回生成器
            $return = $this->pdoSelectGen($sql, $bindings);

            return $return;
        }

        $startTime = microtime(true);
        if ($groupBys) {
            $temps = ["COUNT(1) AS total"];
            foreach ($stats as $fieldSet) { // 有设置总计项,否则用SUM汇总
                $temps[] = ($fieldSet->getStatAllSql() ?: "SUM({$fieldSet->getFieldAlias()})") .
                     " AS {$fieldSet->getFieldAlias()}";
            }
            $sql2 = "SELECT " . implode(',', $temps) . " FROM ($sql) AS a";
            $statResult = $this->pdoSelect($sql2, $bindings, 'one');
            $total = $statResult['total'];
            unset($statResult['total']);
        } else {
            $sql2 = "SELECT COUNT(1) AS total" . " FROM {$this->mpfTable}" .
                 ($this->mpfSets['alias'] ? " AS {$this->mpfSets['alias']}" : "") .
                 ($joins ? " " . implode(' ', $joins) : "") . ($wheres ? " WHERE " . implode(" AND ", $wheres) : "");
            $countResult = $this->pdoSelect($sql2, $bindings, 'one');
            $total = $countResult['total'];
        }
        $endTime = microtime(true);
        // var_dump($sql2, $bindings, $endTime - $startTime);

        $return = ['titles' => $this->getMpfResultTitles(), 'results' => [], 'pages' => [], 'stats' => []];

        if ($total) { // 分页
            $pages = ceil($total / $pageSize);
            $page = $req['page'] ?? 1;
            $page = $page > $pages ? $pages : ($page < 1 ? 1 : $page);
            $start = ($page - 1) * $pageSize;

            $startTime = microtime(true);
            $sql .= " LIMIT $start, $pageSize";
            $result = $this->pdoSelect($sql, $bindings);
            $endTime = microtime(true);
            // var_dump($sql, $bindings, $endTime - $startTime);

            foreach ($result as $k => $v) {
                $this->parseMpfResult($result[$k], $k);
            }
            $return['results'] = $result;
            $return['pages'] = ['count' => $total, 'total' => $pages, 'current' => $page, 'first' => 1,
                'prev' => max($page - 1, 1), 'next' => min($page + 1, $pages), 'last' => $pages];
            if ($groupBys) {
                $return['stats'] = $statResult;
            }
        }

        return $return;
    }

    /**
     * 获取结果页,非表模式,需要重载,
     *
     * @param array $req
     * @return array [titles,results]
     */
    public function &getPageResultNotTable(array $req): array
    {}

    /**
     * 获取查询页
     *
     * @return &array
     */
    public function &getPageQuery(): array
    {
        $return = [];
        foreach ($this->mpfSets['conds'] as $type => $fields) {
            if (! $fields) {
                continue;
            }
            switch ($type) {
                case 'text':
                    $return[$type]['title'] = ['text' => '文字条件', 'notice' => '勾上复选框可以进行模糊搜索，搜索多个时可以用逗号分隔'];
                    break;
                case 'date':
                    $return[$type]['title'] = ['text' => '日期条件', 'notice' => '指定日期范围，起始日期为大于等于起始日期，截止日期为小于等于截止日期'];
                    break;
                case 'select':
                    $return[$type]['title'] = ['text' => '多选条件', 'notice' => '可以同时选择多项'];
                    break;
                case 'selectone':
                    $return[$type]['title'] = ['text' => '单选条件', 'notice' => ''];
                    break;
                case 'number':
                    $return[$type]['title'] = ['text' => '数字条件', 'notice' => '可指定数值的范围，最小值为大于等于最小值，最大值为小于等于最大值'];
                    break;
                case 'stat':
                    $return[$type]['title'] = ['text' => '统计条件', 'notice' => '可指定统计值的范围，最小值为大于等于最小值，最大值为小于等于最大值'];
                    break;
                default:
                    break;
            }
            foreach ($fields as $fieldSet) {
                $return[$type]['data'][] = $fieldSet->getQuery();
            }
        }
        // 显示或归类
        $return['display']['title'] = ['text' => $this->mpfSets['stats'] ? '归类选项' : '显示选项',
            'notice' => $this->mpfSets['stats'] ? '只对选定的项进行归类统计' : '在结果页中只显示选定的项'];
        foreach ($this->mpfSets['fields'] as $fieldSet) {
            if ($data = $fieldSet->getQueryDisplay()) {
                $return['display']['data'][] = $data;
            }
        }

        return $return;
    }

    /**
     * 获取编辑页
     *
     * @throws \Exception
     * @return &array
     */
    public function &getPageEdit(int $pkv = 0): array
    {
        $return = [];
        if ($pkv) {
            $row = $this->pdoFind($pkv);
            if (! $row) {
                throw new \Exception("未找到主键值为【{$pkv}】的记录");
            }
        } else {
            $row = [];
        }
        $this->getPageEditBefore($row);

        /**
         *
         * @var MpfField $fieldSet
         */
        foreach ($this->mpfSets['fields'] as $fieldSet) {
            if ($edit = $fieldSet->getEdit($row)) {
                $return[] = $edit;
            }
        }
        return $return;
    }

    /**
     * 重载此方法,可以在获取编辑页面前进行设置
     *
     * @param array $row
     */
    protected function getPageEditBefore(array &$row): void
    {}

    /**
     * 根据提交的表单创建记录
     *
     * @param array $record
     *            如果有上传文件,并且框架注销了$_FILES时,应将name,tmp_name放入字段中
     * @throws \Exception 失败则抛出异常
     * @return &array 返回创建的记录数组
     */
    public function &mpfCreate(array &$record): array
    {
        $this->checkUpload($record);
        $new = $this->checkRecord($record);
        $this->checkAutoTime($new, true);

        $this->mpfCreateBefore($new);

        try {
            self::$mpfPdo->beginTransaction();

            $id = $this->pdoCreate($new);
            $new[$this->mpfPk] = $id;

            if (isset($this->mpfSets['uploads'])) {
                foreach ($this->mpfSets['uploads'] as $fieldSet) {
                    $update[$fieldSet->getFieldAlias(true)] = $fieldSet->getEditUploadFile($id,
                        self::$uploadPath . '/' . $this->mpfTable);
                }
                $this->pdoUpdate($id, $update);
            }

            $this->mpfCreateAfter($new);

            self::$mpfPdo->commit();

            return $new;
        } catch (\Exception $e) {
            self::$mpfPdo->rollBack();
            throw $e;
        }
    }

    /**
     * 重载此方法,可以在添加前进行检查或设置,如果检查失败可抛出异常
     *
     * @throws \Exception
     * @param array $row
     */
    protected function mpfCreateBefore(array &$row): void
    {}

    /**
     * 重载此方法,可以在添加后进行后续操作,此时仍在事务中,如果抛出异常将会回滚
     *
     * @throws \Exception
     * @param array $row
     */
    protected function mpfCreateAfter(array &$row): void
    {}

    /**
     * 根据提交的表单更新一条记录
     *
     * @param int $pkv
     * @param array $record
     * @throws \Exception 更新失败则抛出异常
     * @return &array|true 更新成功返回[old=>更新前数组,new=>更新后数组],如果没有需要更新的但是修改了上传的文件则返回true
     */
    public function &mpfUpdate(int $pkv, array &$record)
    {
        $this->checkUpload($record);
        $this->checkRecord($record);

        $row = $this->pdoFind($pkv);
        if (! $row) {
            throw new \Exception("未找到主键值为【{$pkv}】的记录");
        }

        $update = ['new' => [], 'old' => []];
        $isUpload = false;
        foreach ($record as $key => $v) {
            $fieldSet = $this->getMpfField($key);
            $field = $fieldSet->getFieldAlias(true);
            if ($fieldSet->isEditUpload() && isset($this->mpfSets['uploads'][$key])) {
                $v = $fieldSet->getEditUploadFile($pkv, self::$uploadPath . '/' . $this->mpfTable);
                $isUpload = true;
            }
            if ($v != $row[$field]) {
                $update['old'][$field] = $row[$field];
                $update['new'][$field] = $v;
            }
        }
        if ($update['new']) {
            $this->checkAutoTime($update['new']);

            $this->mpfUpdateBefore($row, $update['new']);

            try {
                self::$mpfPdo->beginTransaction();

                $return = $this->_mpfUpdate($pkv, $update['new']);

                $this->mpfUpdateAfter($row, $update['new']);

                self::$mpfPdo->commit();

                return $return ? $update : $return;
            } catch (\Exception $e) {
                self::$mpfPdo->rollBack();
                throw $e;
            }
        }
        if (! $isUpload) {
            throw new \Exception("没有要更新的字段");
        }
        return $isUpload;
    }

    /**
     * 重载此方法,可以在更新前进行检查或设置,如果检查失败可抛出异常
     *
     * @throws \Exception
     * @param array $oldRow
     *            原记录
     * @param array $update
     *            更新的字段
     */
    protected function mpfUpdateBefore(array &$oldRow, array &$update): void
    {}

    /**
     * 重载此方法,可以在更新后进行后续操作,此时仍在事务中,如果抛出异常将会回滚
     *
     * @param array $oldRow
     *            原记录
     * @param array $update
     *            更新的字段
     */
    protected function mpfUpdateAfter(array &$oldRow, array &$update): void
    {}

    /**
     * 批量更新
     *
     * @param array $pkvs
     * @param array $update
     *            应是程序定义的内容,而不是用户提交的内容<br>
     *            可以在值后加|raw表示保持原样,否则值会加上单引号
     * @throws Exception
     * @return &array
     */
    public function &mpfUpdates(array &$pkvs, array $update): array
    {
        $pkvs = array_map('intval', array_map('trim', $pkvs));
        $this->checkAutoTime($update);
        $this->mpfUpdatesBefore($pkvs, $update);
        try {
            self::$mpfPdo->beginTransaction();

            $set = [];
            foreach ($update as $field => $v) {
                if (stripos($v, '|raw')) {
                    $set[] = "$field = " . str_replace('|raw', '', $v);
                } else {
                    $set[] = "$field = '$v'";
                }
            }
            $set = implode(', ', $set);
            $chunk = array_chunk($pkvs, 100);
            foreach ($chunk as $pkvsChunk) {
                $sql = "UPDATE {$this->mpfTable} SET {$set} WHERE {$this->mpfPk} IN(" . implode(',', $pkvsChunk) . ")";
                $this->mpfPdoStat = self::$mpfPdo->prepare($sql);
                $this->mpfPdoStat->execute();
            }

            $this->mpfUpdatesAfter($pkvs, $update);

            self::$mpfPdo->commit();
            $return = ['pks' => $pkvs, 'update' => $update];
            return $return;
        } catch (\Exception $e) {
            self::$mpfPdo->rollBack();
            throw $e;
        }
    }

    /**
     * 重载此方法,可以在批量更新前进行检查或设置,如果检查失败可抛出异常
     *
     * @throws \Exception
     * @param array $pkvs
     * @param array $update
     */
    protected function mpfUpdatesBefore(array &$pkvs, array $update): void
    {}

    /**
     * 重载此方法,可以在批量更新后进行后续操作,此时仍在事务中,如果抛出异常将会回滚
     *
     * @throws \Exception
     * @param array $pkvs
     * @param array $update
     */
    protected function mpfUpdatesAfter(array &$pkvs, array $update): void
    {}

    /**
     * 批量删除
     *
     * @param array $pkvs
     * @return &array
     */
    public function &mpfDeletes(array &$pkvs): array
    {
        $pkvs = array_map('intval', array_map('trim', $pkvs));

        $this->mpfDeletesBefore($pkvs);

        try {
            self::$mpfPdo->beginTransaction();

            $chunk = array_chunk($pkvs, 100);
            foreach ($chunk as $pkvsChunk) {
                $sql = "DELETE FROM {$this->mpfTable} WHERE {$this->mpfPk} IN(" . implode(',', $pkvsChunk) . ")";
                $this->mpfPdoStat = self::$mpfPdo->prepare($sql);
                $this->mpfPdoStat->execute();
            }

            $this->mpfDeletesAfter($pkvs);

            self::$mpfPdo->commit();
            return $pkvs;
        } catch (\Exception $e) {
            self::$mpfPdo->rollBack();
            throw $e;
        }
    }

    /**
     * 重载此方法,可以在删除前进行检查或设置,如果检查失败可抛出异常
     *
     * @throws \Exception
     * @param array $pkvs
     */
    protected function mpfDeletesBefore(array &$pkvs): void
    {}

    /**
     * 重载此方法,可以在删除后进行后续操作,此时仍在事务中,如果抛出异常将会回滚
     *
     * @throws \Exception
     * @param array $pkvs
     */
    protected function mpfDeletesAfter(array &$pkvs): void
    {}

    /**
     * 批量保存
     *
     * @param array $pkvs
     * @param array $records
     * @throws Exception
     * @return &array
     */
    public function &mpfSaves(array &$pkvs, array &$records): array
    {
        $pkvs = array_map('intval', array_map('trim', $pkvs));
        self::requestTrim($records);

        $this->mpfSavesBefore($pkvs, $records);
        try {
            self::$mpfPdo->beginTransaction();

            $return = [];
            foreach ($pkvs as $i => $pkv) {
                $record[$i] = $this->checkRecord($record[$i]);
                $this->checkAutoTime($record[$i]);

                $this->pdoUpdate($pkv, $record[$i]);
                $return[] = $record[$i] + ['pk' => $pkv];
            }

            $this->mpfSavesAfter($pkvs, $records);

            self::$mpfPdo->commit();
            return $return;
        } catch (\Exception $e) {
            self::$mpfPdo->rollBack();
            throw $e;
        }
    }

    /**
     * 重载此方法,可以在批量保存前进行检查或设置,如果检查失败可抛出异常
     *
     * @throws \Exception
     * @param array $pkvs
     * @param array $records
     */
    protected function mpfSavesBefore(array &$pkvs, array &$records): void
    {}

    /**
     * 重载此方法,可以在批量保存后进行后续操作,此时仍在事务中,如果抛出异常将会回滚
     *
     * @throws \Exception
     * @param array $pkvs
     * @param array $records
     */
    protected function mpfSavesAfter(array &$pkvs, array &$records): void
    {}

    /**
     * 导出CSV格式文件
     *
     * @param array $req
     * @param string $title
     */
    public function mpfExportCsv(array $req, string $title)
    {
        set_time_limit(0);

        $fileName = mb_convert_encoding(str_replace([' ', '　'], '', $title), 'gbk', 'utf-8') . date('_YmdHi') .
             ".csv";
        header("Content-Disposition: attachment;filename=\"$fileName\"");
        header("Content-Type: text/csv");

        $rows = $this->getPageResult($req, 100, true);
        $titles = $this->getMpfResultTitles(false);

        echo implode(',', $titles) . "\n";
        foreach ($rows as $row) {
            $this->parseMpfExcel($row);
            echo implode(',', $row) . "\n";
        }
    }

    /**
     * 导出Excel格式文件
     *
     * @param array $req
     * @param string $title
     */
    public function mpfExportExcel(array $req, string $title)
    {
        set_time_limit(0);

        $fileName = mb_convert_encoding(str_replace([' ', '　'], '', $title), 'gbk', 'utf-8') . date('_YmdHi') .
             ".xlsx";
        header("Content-Disposition: attachment;filename=\"$fileName\"");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $rows = $this->getPageResult($req, 100, true);
        $titles = $this->getMpfResultTitles(false);

        $sheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet->getProperties()
            ->setCreator('ChenLiang')
            ->setTitle('MPF ' . $title)
            ->setCategory('MPF Export Excel')
            ->setCompany('MPF')
            ->setDescription('');

        $border = ['borders' => ['outline' => ['borderStyle' => 'thin']]];
        $wsheet = $sheet->setActiveSheetIndex(0)->setTitle($title);
        foreach ($titles as $key => $text) { // 表头
            $wsheet->setCellValueByColumnAndRow($key + 1, 1, $text);
            $style = $wsheet->getStyleByColumnAndRow($key + 1, 1);
            $style->getAlignment()->setHorizontal('center');
            $style->getFont()->setBold(true);
            $style->applyFromArray($border);
        }
        $i = 2; // 表行数，从第二行开始
        foreach ($rows as $row) {
            $k = 1; // 表列数
            foreach ($row as $field => $value) {
                $wsheet->setCellValueByColumnAndRow($k, $i, $value);
                $wsheet->getStyleByColumnAndRow($k, $i)->applyFromArray($border);
                $k ++;
            }
            $i ++;
        }
        $writer = PhpOffice\PhpSpreadsheet\IOFactory::createWriter($sheet, 'xlsx');
        $writer->save('php://output');
    }

    /**
     * 导入CSV格式数据
     *
     * @param string $file
     * @throws Exception
     * @return &array ['suc'=>成功数,'err'=>失败数,'msg'=>[错误信息],'data'=>[成功的数据]]
     */
    public function &mpfImportCsv(string $file)
    {
        set_time_limit(0);

        $str = file_get_contents($file);
        $charSet = mb_detect_encoding($str, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
        if ($charSet != 'UTF-8') {
            $str = mb_convert_encoding($str, 'UTF-8', $charSet);
        }
        $ary = explode("\n", $str);
        unset($str);

        $title = $msg = $data = [];
        $havePk = false;
        $suc = $err = 0;
        foreach ($ary as $i => $v) {
            $temp = array_map('trim', explode(',', $v));
            if ($i == 0) { // 标题
                if (! $this->parsImportTitle($temp, $title, $msg, $havePk)) {
                    break;
                }
            } else { // 数据
                $this->parsImportData($temp, $title, $msg, $havePk, $i, $suc, $err, $data);
            }
        }
        $return = compact('suc', 'err', 'msg', 'data');
        return $return;
    }

    /**
     * 导入Excel格式数据
     *
     * @param string $file
     * @return &array
     */
    public function &mpfImportExcel(string $file)
    {
        set_time_limit(0);

        $sheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $ary = $sheet->getActiveSheet()->toArray();

        $title = $msg = $data = [];
        $havePk = false;
        $suc = $err = 0;
        foreach ($ary as $i => $v) {
            $temp = array_map('trim', $v);
            if ($i == 0) { // 标题
                if (! $this->parsImportTitle($temp, $title, $msg, $havePk)) {
                    break;
                }
            } else { // 数据
                $this->parsImportData($temp, $title, $msg, $havePk, $i, $suc, $err, $data);
            }
        }
        $return = compact('suc', 'err', 'msg', 'data');
        return $return;
    }

    /**
     * 解析导入标题
     *
     * @param array $data
     * @param array $title
     * @param array $msg
     * @param bool $havePk
     * @return bool 成功返回true
     */
    private function parsImportTitle(array $data, array &$title, array &$msg, &$havePk): bool
    {
        $findField = false;
        foreach ($data as $k => $text) {
            $title[$k] = '';
            /**
             *
             * @var MpfField $fieldSet
             */
            foreach ($this->mpfSets['fields'] as $fieldSet) {
                if ($fieldSet->getText() == $text) {
                    $title[$k] = $fieldSet->getKey();
                    if ($fieldSet->isPk()) {
                        $havePk = $k;
                    }
                    $findField = true;
                    break;
                }
            }
            if (! $title[$k]) {
                $msg[] = $text;
            }
        }
        if (! $findField) {
            $msg[] = "未找到标题或全部标题未找到对应的字段";
            return false;
        }
        if ($msg) {
            $error = '标题【' . implode(',', $msg) . '】未找到对应的字段，这些列被忽略';
            $msg = [];
            $msg[] = $error;
        }
        return true;
    }

    /**
     * 解析导入数据
     *
     * @param array $data
     * @param array $title
     * @param array $msg
     * @param int|false $havePk
     * @param int $i
     * @param int $suc
     * @param int $err
     * @param array $data
     * @throws Exception
     */
    private function parsImportData(array $data, array $title, array &$msg, $havePk, int $i, int &$suc, int &$err,
        array &$data)
    {
        $row = [];
        foreach ($title as $k => $key) {
            if ($key && isset($data[$k])) {
                $row[$key] = $data[$k];
            }
        }
        if ($row) {
            try {
                $row = $this->checkRecord($row, true);
                if ($havePk !== false) { // 更新
                    $pkv = intval($data[$havePk]);
                    $oldRow = $this->pdoFind($pkv);
                    $this->mpfUpdateBefore($oldRow, $row);
                    $this->checkAutoTime($row);
                    try {
                        self::$mpfPdo->beginTransaction();
                        $this->pdoUpdate($pkv, $row);
                        $this->mpfUpdateAfter($oldRow, $row);
                        self::$mpfPdo->commit();
                        $suc ++;
                        $data[] = $row;
                    } catch (\Exception $e) {
                        self::$mpfPdo->rollBack();
                        throw $e;
                    }
                } else { // 添加
                    $this->mpfCreateBefore($row);
                    $this->checkAutoTime($row, true);
                    try {
                        self::$mpfPdo->beginTransaction();
                        $id = $this->pdoCreate($row);
                        $row[$this->mpfPk] = $id;
                        $this->mpfCreateAfter($row);
                        self::$mpfPdo->commit();
                        $suc ++;
                        $data[] = $row;
                    } catch (\Exception $e) {
                        self::$mpfPdo->rollBack();
                        throw $e;
                    }
                }
            } catch (\Exception $e) {
                $err ++;
                $msg[] = "第【{$i}】行,{$e->getMessage()}";
            }
        } else {
            $err ++;
            $msg[] = "第【{$i}】行没有数据";
        }
    }

    /**
     * 解析为Excel数据
     *
     * @param array $row
     */
    private function parseMpfExcel(array &$row)
    {
        foreach ($row as $field => $v) {
            $key = $this->getMpfFieldKey($field);
            $fieldSet = $this->getMpfField($key);
            if ($fieldSet->isPk() && ! in_array($key, $this->mpfSets['fieldSelects'])) { // 主键不在显示中
                unset($row[$field]);
            } else {
                $row[$field] = $fieldSet->getExcel($v, $row);
            }
        }
    }

    /**
     * 解析为结果页数据
     *
     * @param array $row
     * @param int $i
     */
    private function parseMpfResult(array &$row, int $i)
    {
        if ($this->mpfPk && ! $this->mpfSets['stats']) {
            $row['pk'] = $row[$this->mpfPk];
        }
        foreach ($row as $field => $v) {
            if ($field == 'pk') {
                continue;
            }
            $key = $this->getMpfFieldKey($field);
            $fieldSet = $this->getMpfField($key);

            if ($fieldSet->isPk() && ! in_array($key, $this->mpfSets['fieldSelects'])) { // 主键不在显示中
                unset($row[$field]);
            } else {
                $row[$field] = $fieldSet->getResult($v, $row, $i, self::$uploadUrl . '/' . $this->mpfTable);
            }
        }
    }

    /**
     * 获取结果页或Excel的表头
     *
     * @param string $isResult
     * @return array
     */
    private function getMpfResultTitles($isResult = true): array
    {
        $titles = [];
        foreach ($this->mpfSets['fieldSelects'] as $key) {
            $fieldSet = $this->getMpfField($key);
            $field = $fieldSet->getFieldAlias(true);
            if ($isResult) {
                $titles[$field]['text'] = $fieldSet->getText();
                $titles[$field]['html'] = 'a';
                $titles[$field]['link'] = '?ord=' . $this->mpfSets['fieldOrders'][$key];
            } else { // excel
                $titles[$field] = $fieldSet->getText();
            }
        }
        return $titles;
    }
}