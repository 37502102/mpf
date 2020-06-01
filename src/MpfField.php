<?php
namespace mpf;

/**
 * MPF表字段
 *
 * @author chenliang
 */
class MpfField
{

    private $set;

    private static $tagColors = ['danger', 'primary', 'success', 'warning', 'info'];

    private function __construct(string $key, string $text, string $sql)
    {
        $this->set['key'] = $key;
        $this->set['text'] = $text;
        $this->set['sql'] = $sql;
    }

    /**
     * 创建本类的对象
     *
     * @param string $text
     *            字段对应的文本
     * @param string $sql
     *            字段对应的SQL语句
     * @return self
     */
    public static function new(string $key, string $text, string $sql): self
    {
        return new MpfField($key, $text, $sql);
    }

    /**
     * 设置是否在结果页中显示,不设置时默认为显示
     *
     * @param bool $display
     * @return self
     */
    public function setResultDisplay(bool $display = false): self
    {
        $this->set['resultDisplay'] = $display;
        return $this;
    }

    /**
     * 获取是否在结果页中显示
     *
     * @return bool 未设置返回true
     */
    public function getResultDisplay(): bool
    {
        return $this->set['resultDisplay'] ?? true;
    }

    public function setResultWidth(int $width): self
    {
        $this->set['resultWidth'] = $width;
        return $this;
    }

    public function getResultWidth(): int
    {
        return $this->set['resultWidth'] ?? 200;
    }

    /**
     * 设置结果页编辑框
     *
     * @param int $size
     *            编辑框尺寸
     * @return self
     */
    public function setResultInput(int $size = 10): self
    {
        $this->set['result'] = ['type' => 'input', 'size' => $size];
        $this->setResultWidth(max($size * 20, 75));
        return $this;
    }

    /**
     * 设置结果页文本框
     *
     * @param int $rows
     *            行数
     * @param int $cols
     *            列数
     * @return self
     */
    public function setResultTextarea(int $rows = 5, int $cols = 10): self
    {
        $this->set['result'] = ['type' => 'textarea', 'rows' => $rows, 'cols' => $cols];
        return $this;
    }

    /**
     * 设置结果页下拉框
     *
     * @return self
     */
    public function setResultSelect(): self
    {
        $this->set['result'] = ['type' => 'select'];
        return $this;
    }

    /**
     * 获取结果页编辑设置
     *
     * @param string $name
     *            表单中的name
     * @param mixed $value
     *            字段的值
     * @return array 返回设置后的结果,未设置返回空数组
     */
    public function getResultEdit(int $i, $value): array
    {
        if (! isset($this->set['result'])) {
            return [];
        }
        $name = MpfController::$queryNames['rows'] . "[$i][{$this->getKey()}]";
        switch ($this->set['result']['type']) {
            case 'input':
                return ['name' => $name, 'value' => $value, 'html' => 'input', 'type' => 'text',
                    'size' => $this->set['result']['size']];
            case 'textarea':
                return ['name' => $name, 'value' => $value, 'html' => 'textarea',
                    'rows' => $this->set['result']['rows'], 'cols' => $this->set['result']['cols']];
            case 'select':
                if (! ($option = $this->getOption())) {
                    return [];
                }
                if ($this->getOptionSeparator()) {
                    $value = explode($this->getOptionSeparator(), $value);
                }
                return ['name' => $name, 'value' => $value, 'html' => 'select', 'option' => $option,
                    'multiple' => ! empty($this->getOptionSeparator())];
            default:
                return [];
        }
    }

    /**
     * 获取结果页上传文件设置
     *
     * @param string $uploadUrl
     * @param mixed $v
     * @return array
     */
    public function getResultUpload(string $uploadUrl, $v): array
    {
        if (! $this->isEditUpload()) {
            return [];
        }
        if ($v) {
            $tmp = pathinfo($v);
            $url = $uploadUrl . '/' . $v;
            if (isset($tmp['extension']) &&
                in_array(strtolower($tmp['extension']), ['png', 'jpeg', 'jpg', 'bmp', 'gif', 'tif', 'psd', 'ico'])) {
                return ['text' => $v, 'html' => 'img', 'src' => $url];
            }
            return ['text' => $v, 'html' => 'a', 'href' => $url];
        }
        return ['text' => $v];
    }

    /**
     * 获取结果页枚举值
     *
     * @param mixed $value
     * @return array 未设置返回空数组
     */
    public function getResultOption($value): array
    {
        if (! $this->getOption()) {
            return [];
        }

        $return = []; // 值放入title,枚举值放入text,有颜色放入class
        if ($this->getOptionSeparator()) { // 多选
            $titles = explode($this->getOptionSeparator(), $value);
            $values = $this->getOptionValue($value);
            $colors = [];
            if ($this->getResultColor()) { // 颜色
                foreach ($titles as $k) {
                    $colors[] = $this->getResultColor($k);
                }
            }
            foreach ($titles as $k => $v) {
                $return[$k] = ['html' => 'span', 'title' => $v, 'text' => $values[$k]];
                if (isset($colors[$k])) {
                    $return[$k]['color'] = $colors[$k];
                }
            }
        } else { // 单选
            $return = ['html' => 'span', 'title' => $value, 'text' => $this->getOptionValue($value)];
            if ($this->getResultColor()) {
                $return['color'] = $this->getResultColor($value);
            }
        }
        return $return;
    }

    /**
     * 设置结果页显示更多
     *
     * @param true|int $more
     *            int-只显示该长度的内容,全部内容放入title中<br>
     *            true-链接到详情页,通常是查看关联表的内容,请求参数为field=value
     * @return self
     */
    public function setResultMore($more): self
    {
        $this->set['resultMore'] = $more;
        return $this;
    }

    /**
     * 获取结果页显示更多内容
     *
     * @param mixed $value
     * @return array 未设置返回空数组
     */
    public function getResultMore($value)
    {
        if (! isset($this->set['resultMore'])) {
            return false;
        }
        if (is_int($this->set['resultMore'])) {
            if (mb_strlen($value, 'utf-8') > $this->set['resultMore']) {
                return ['html' => 'span', 'title' => $value,
                    'text' => mb_substr($value, 0, $this->set['resultMore'], 'utf-8')];
            }
            return $value;
        }
        return ['html' => 'a', 'href' => "more?{$this->getFieldReal(true)}={$value}", 'title' => '点击查看详情',
            'text' => $value];
    }

    /**
     * 设置结果页对齐,不设置时为left,统计项为right
     *
     * @param string $align
     *            right,center
     * @return self
     */
    public function setResultAlign(string $align): self
    {
        $this->set['resultAlign'] = $align;
        return $this;
    }

    /**
     * 获取结果页对齐设置
     *
     * @return string 未设置时为空
     */
    public function getResultAlign($value): array
    {
        if (! isset($this->set['resultAlign'])) {
            return [];
        }
        return ['text' => $value, 'align' => $this->set['resultAlign']];
    }

    /**
     * 获取结果页设置后的内容
     *
     * @param mixed $value
     * @param array $row
     * @param int $i
     * @param string $uploadUrl
     * @return mixed
     */
    public function getResult($value, array &$row, int $i, string $uploadUrl)
    {
        if ($ary = $this->getResultCallback($value, $row)) { // 自定义函数
            return $ary;
        } elseif ($value && $ary = $this->getResultUpload($uploadUrl, $value)) { // 上传文件
            return $ary;
        } elseif ($ary = $this->getResultMore($value)) { // 显示更多
            return $ary;
        } elseif ($ary = $this->getResultEdit($i, $value)) { // 可编辑
            return $ary;
        } elseif ($ary = $this->getResultOption($value)) { // 设置了枚举值
            return $ary;
        } elseif ($ary = $this->getResultAlign($value)) { // 设置对齐
            return $ary;
        }
        return $value;
    }

    public function getExcel($value, array &$row)
    {
        if ($v = $this->getExcelCallback($value, $row)) {
            return $v;
        } elseif ($this->getOption()) {
            $v = $this->getOptionValue($value);
            return is_array($v) ? implode($this->getOptionSeparator(), $v) : $v;
        }
        return str_replace([',', '\n', '\r'], ['，', '。', ''], $value);
    }

    /**
     * 设置编辑页只读,不设置时默认为否
     *
     * @param bool $readOnly
     * @return self
     */
    public function setEditReadOnly(bool $readOnly = true): self
    {
        $this->set['editReadOnly'] = $readOnly;
        return $this;
    }

    /**
     * 获取是否在编辑页只读
     *
     * @return bool 未设置返回false
     */
    public function getEditReadOnly(): bool
    {
        return $this->set['editReadOnly'] ?? false;
    }

    /**
     * 设置编辑页不显示原值，不设置时默认为否，提交时如果未填写会过滤掉该项
     *
     * @param bool $noValue
     * @return self
     */
    public function setEditNoValue(bool $noValue = true): self
    {
        $this->set['editNoValue'] = $noValue;
        return $this;
    }

    /**
     * 获取是否在编辑页不显示值
     *
     * @return bool 未设置返回false
     */
    public function getEditNoValue(): bool
    {
        return $this->set['editNoValue'] ?? false;
    }

    /**
     * 设置是否出现在编辑页
     *
     * @param bool $display
     * @return self
     */
    public function setEditHidden(bool $hidden = true): self
    {
        $this->set['editHidden'] = $hidden;
        return $this;
    }

    /**
     * 获取是否出现在编辑页
     *
     * @return bool 未设置返回false
     */
    public function getEditHidden(): bool
    {
        return $this->set['editHidden'] ?? false;
    }

    /**
     * 设置编辑页提醒
     *
     * @param string $notice
     * @return self
     */
    public function setEditNotice(string $notice): self
    {
        $this->set['editNotice'] = $notice;
        return $this;
    }

    /**
     * 获取编辑页提醒
     *
     * @return string 未设置时返回空字符串
     */
    public function getEditNotice(): string
    {
        $return = $this->set['editNotice'] ?? '';
        if ($this->getEditRequire()) {
            $return .= ($return ? '，' : '') . '必填';
        }
        return $return;
    }

    /**
     * 设置编辑页必填,默认为否
     *
     * @param bool $require
     * @return self
     */
    public function setEditRequire(bool $require = true): self
    {
        $this->set['editRequire'] = $require;
        return $this;
    }

    /**
     * 获取编辑页必填设置
     *
     * @return bool 未设置时返回false
     */
    public function getEditRequire(): bool
    {
        return $this->set['editRequire'] ?? false;
    }

    /**
     * 设置编辑页该字段跟另一字段的值关联
     *
     * @param MpfField $joinField
     *            关联的字段
     * @param string $url
     *            ajax请求地址?paramName=
     * @return self
     */
    public function setEditJoin(MpfField $joinField, string $url): self
    {
        $this->set['editJoin'] = ['level1Name' => $joinField->getEditName(), 'url' => $url,
            'multiple' => $joinField->getOptionSeparator() ? true : false, 'level2Name' => $this->getEditName()];
        return $this;
    }

    /**
     * 设置编辑时为输入框,如果为input:text则不需要单独设置
     *
     * @param string $type
     *            date,number,password,file
     * @return self
     */
    public function setEditInput(string $type): self
    {
        $this->set['edit'] = 'input';
        $this->set['editType'] = $type;

        if ($type == 'password') {
            $this->setEditNoValue();
        }
        return $this;
    }

    /**
     * 设置编辑时为checkbox或radio<br>
     * 枚举字段默认为select编辑,如果要改变这个行为则需要设置此项<br>
     * 如果有分隔符会使用checkbox,否则使用radio
     *
     * @return self
     */
    public function setEditCheckbox(): self
    {
        $this->set['edit'] = 'checkbox';
        return $this;
    }

    /**
     * 设置编辑时为文本框
     *
     * @param int $rows
     *            行数
     * @param bool $isHtml
     *            是否为html内容
     * @return self
     */
    public function setEditTextarea(int $rows = 5, bool $isHtml = false): self
    {
        $this->set['edit'] = 'textarea';
        $this->set['editRows'] = $rows;
        $this->set['editType'] = $isHtml ? 'html' : '';
        return $this;
    }

    /**
     * 设置编辑时为上传文件
     *
     * @return self
     */
    public function setEditUpload(): self
    {
        $this->set['edit'] = 'input';
        $this->set['editType'] = 'file';
        return $this;
    }

    /**
     * 是否是上传文件
     *
     * @return bool
     */
    public function isEditUpload(): bool
    {
        return isset($this->set['editType']) && $this->set['editType'] == 'file';
    }

    /**
     * 设置上传文件信息
     *
     * @param string $name
     *            原文件名
     * @param string $tmpName
     *            临时文件路径
     * @throws \Exception
     * @return self
     */
    public function setEditUploadFile(string $name, string $tmpName): self
    {
        $temp = pathinfo($name);
        if (strtolower($temp['extension']) == 'php') {
            throw new \Exception('不能上传php文件');
        }
        $this->set['uploadNewFile'] = $this->getFieldReal(true) . '_{pk}.' . $temp['extension'];
        $this->set['uploadOldFile'] = $tmpName;
        return $this;
    }

    /**
     * 获取要保存的文件名
     *
     * @param int $pkv
     * @param string $path
     *            保存文件的路径
     * @return string
     */
    public function getEditUploadFile(int $pkv, string $path): string
    {
        $newFile = str_replace('{pk}', $pkv, $this->set['uploadNewFile']);
        if (! file_exists($path)) {
            mkdir($path);
        }
        move_uploaded_file($this->set['uploadOldFile'], $path . '/' . $newFile);

        return $newFile;
    }

    /**
     * 获取编辑页
     *
     * @param array $row
     * @return array
     */
    public function &getEdit(array &$row): array
    {
        $return = [];
        if ($this->getEditHidden() || ($this->getJoinKey() && ! $this->getJoinEdit())) { // 隐藏或关联且未设置关联可编辑
            return $return;
        }
        $return = ['title' => $this->getEditTitle()];
        $value = $row ? $row[$this->getFieldAlias(true)] : '';

        if ($this->getEditNoValue()) { // 不显示值
            $value = '';
        }

        if ($this->getEditReadOnly()) { // 只读
            $return['data'] = ['text' => $this->getOption() ? $this->getOptionValue($value) : $value];
            return $return;
        }

        $return['data'] = ['name' => $this->getEditName(), 'key' => $this->getKey(),
            'require' => $this->getEditRequire()];

        if ($ary = $this->getEditCallback($value, $row)) { // 回调
            $return['data'] += $ary;
        } elseif ($option = $this->getOption()) { // 枚举
            $return['data']['html'] = $this->set['edit'] ?? 'select';
            if ($str = $this->getOptionSeparator()) { // 多选
                $return['data']['value'] = explode($str, $value);
                $return['data']['multiple'] = true;
            } else {
                $return['data']['html'] = $return['data']['html'] == 'checkbox' ? 'radio' : $return['data']['html'];
                $return['data']['value'] = $value;
                $return['data']['multiple'] = false;
            }
            $return['data']['option'] = $option;

            if (isset($this->set['editJoin'])) { // 联动
                $return['data']['join'] = $this->set['editJoin'];
            }
        } else {
            $return['data']['value'] = $value;
            $return['data']['html'] = $this->set['edit'] ?? 'input';
            $return['data']['type'] = $this->set['editType'] ?? 'text';
            if ($return['data']['type'] == 'file' && $value) { // 上传文件
                $return['data']['text'] = "当前文件:$value";
            } elseif ($return['data']['html'] == 'textarea') { // 文本框
                $return['data']['rows'] = $this->set['editRows'];
            }
        }

        return $return;
    }

    /**
     * 获取编辑页字段名
     *
     * @return array
     */
    private function getEditTitle(): array
    {
        return ($this->isPk() || $this->getEditNoValue() || $this->getEditReadOnly()) ? ['text' => $this->getText(),
            'notice' => $this->getEditNotice()] : ['html' => 'label', 'text' => $this->getText(),
            'notice' => $this->getEditNotice(), 'for' => $this->getEditName()];
    }

    /**
     * 获取编辑项的name
     *
     * @return string
     */
    public function getEditName(): string
    {
        return MpfController::$queryNames['row'] . "[{$this->getKey()}]" . ($this->getOptionSeparator() ? '[]' : '');
    }

    /**
     * 设置日期查询条件
     *
     * @return self
     */
    public function setQueryDate(): self
    {
        $this->set['queryType'] = 'date';
        return $this;
    }

    /**
     * 设置文本查询条件
     *
     * @param bool $canBlur
     *            是否允许模糊搜索
     * @return self
     */
    public function setQueryText(bool $canBlur = true): self
    {
        $this->set['queryType'] = 'text';
        $this->set['queryTextBlur'] = $canBlur;
        return $this;
    }

    /**
     * 设置数字查询条件
     *
     * @return self
     */
    public function setQueryNumber(): self
    {
        $this->set['queryType'] = 'number';
        return $this;
    }

    /**
     * 设置多选查询条件
     *
     * @return self
     */
    public function setQuerySelect(): self
    {
        $this->set['queryType'] = 'select';
        return $this;
    }

    /**
     * 设置单选查询条件
     *
     * @return self
     */
    public function setQuerySelectone(): self
    {
        $this->set['queryType'] = 'selectone';
        return $this;
    }

    /**
     * 设置默认查询
     *
     * @param mixed $min
     *            Numeric,Stat为最小值,Date为开始日期,Select应为数组
     * @param mixed $max
     *            Numeric,Stat为最大值,Date为截止日期,其他类型不用设置
     * @return self
     */
    public function setQueryDefault($min, $max = ''): self
    {
        $this->set['queryDefaultMin'] = $min;
        $this->set['queryDefaultMax'] = $max;
        return $this;
    }

    /**
     * 设置查询项必须要有值
     *
     * @param bool $require
     * @return self
     */
    public function setQueryRequire(bool $require = true): self
    {
        $this->set['queryRequire'] = $require;
        return $this;
    }

    /**
     * 获取默认起始查询
     *
     * @return string|array 未设置返回空字符串或空数组
     */
    public function getQueryDefaultMin()
    {
        return $this->set['queryDefaultMin'] ?? ($this->set['queryType'] == 'select' ? [] : '');
    }

    /**
     * 获取默认截止查询
     *
     * @return string 未设置返回空字符串
     */
    public function getQueryDefaultMax(): string
    {
        return $this->set['queryDefaultMax'] ?? '';
    }

    /**
     * 获取默认请求
     *
     * @return array
     */
    public function getQueryDefault()
    {
        $return = [];
        if ($type = $this->getQueryType()) {
            if ($min = $this->getQueryDefaultMin()) {
                switch ($type) {
                    case 'text':
                        $return[$this->getKey()]['act'] = $min;
                        break;
                    case 'date':
                    case 'number':
                        $return[$this->getKey()]['min'] = $min;
                        break;
                    default:
                        $return[$this->getKey()] = $min;
                }
            }
            if ($max = $this->getQueryDefaultMax()) {
                $return[$this->getKey()]['max'] = $max;
            }
        }
        return $return;
    }

    /**
     * 获取查询页搜索项
     *
     * @param bool $isDefault
     *            是否为默认查询，否则为页面请求
     * @param array $values
     *            页面请求的值
     * @return array
     */
    public function getQuery(bool $isDefault = true, array $values = []): array
    {
        if ($this->isStat()) {
            $type = 'stat';
        } else {
            $type = $this->getQueryType();
        }
        if (! $type) {
            return [];
        }

        if ($isDefault) {
            $min = $this->getQueryDefaultMin();
            $max = $this->getQueryDefaultMax();
        } else {
            switch ($type) {
                case 'text':
                    $min = $values['act'] ?? '';
                    $max = isset($values['blur']) ? boolval($values['blur']) : false;
                    break;
                case 'date':
                case 'number':
                case 'stat':
                    $min = $values['min'] ?? '';
                    $max = $values['max'] ?? '';
                    break;
                case 'selectone':
                    $min = $values ?: '';
                    break;
                default:
                    $min = $values;
            }
        }

        $return = ['title' => $this->getText(), 'type' => $type, 'key' => $this->getKey()];
        switch ($type) {
            case 'text':
                $return['query'] = ['name' => self::getQueryName($this) . "[act]", 'value' => $min];

                if ($this->canQueryBlur()) { // 允许模糊搜索
                    $return['query2'] = ['name' => self::getQueryName($this) . "[blur]", 'value' => boolval($max)];
                }
                break;
            case 'date':
            case 'number':
            case 'stat':
                $return['query'] = ['name' => self::getQueryName($this) . "[min]", 'value' => $min];
                $return['query2'] = ['name' => self::getQueryName($this) . "[max]", 'value' => $max];
                break;
            case 'selectone':
            case 'select':
                $return['query'] = ['option' => $this->getOption(), 'value' => $min,
                    'name' => self::getQueryName($this), 'multiple' => $type == 'select' ? true : false];

                if (isset($this->set['queryJoin'])) { // 设置了联动
                    $return['query']['join'] = $this->set['queryJoin'];
                }
                break;
            default:
                return [];
        }
        if ($require = $this->getQueryRequire()) {
            $return['query']['require'] = true;
        }

        return $return;
    }

    /**
     * 获取查询页显示项
     *
     * @param bool $isDefault
     *            是否为默认查询，否则为页面请求
     * @param bool $isDisplay
     *            页面请求的值
     * @return array 如果没有设置返回空数组
     */
    public function getQueryDisplay(bool $isDefault = true, bool $isDisplay): array
    {
        if ($this->isStat()) { // 统计项不能选择
            return [];
        }
        return ['html' => 'checkbox',
            'name' => MpfController::$queryNames['query'] . '[' . MpfController::$queryNames['display'] . '][]',
            'value' => $this->getKey(), 'checked' => $isDefault ? $this->getResultDisplay() : $isDisplay,
            'text' => $this->getText()];
    }

    /**
     * 获取查询类型
     *
     * @return string 未设置返回空字符串
     * @see MpfModel::$condTypes
     */
    public function getQueryType(): string
    {
        return $this->set['queryType'] ?? '';
    }

    /**
     * 是否能模糊查询
     *
     * @return bool 未设置返回true
     */
    public function canQueryBlur(): bool
    {
        return $this->set['queryTextBlue'] ?? true;
    }

    /**
     * 获取查询name，查询参数都放入q数组中，所以不要占用
     *
     * @param MpfField $field
     * @return string
     */
    public static function getQueryName(MpfField $field): string
    {
        return MpfController::$queryNames['query'] . "[{$field->getKey()}]" .
            ($field->getQueryType() == 'select' ? "[]" : '');
    }

    /**
     * 获取查询是否必填
     *
     * @return bool
     */
    public function getQueryRequire(): bool
    {
        return $this->set['queryRequire'] ?? false;
    }

    /**
     * 设置联动查询
     *
     * @param MpfField $joinField
     *            关联的上级字段
     * @param string $url
     *            请求地址?paramName=
     * @return self
     */
    public function setQueryJoin(MpfField $joinField, string $url): self
    {
        $this->set['queryJoin'] = ['url' => $url, 'multiple' => $joinField->getQueryType() == 'select' ? true : false,
            'level1Name' => self::getQueryName($joinField), 'level2Name' => self::getQueryName($this)];
        return $this;
    }

    /**
     * 设置对应的关联语句,关联字段默认不能编辑,如果要可以编辑则需要设置setJoinEdit()
     *
     * @param string $joinKey
     *            关联语句的KEY
     * @return self
     */
    public function setJoinKey(string $joinKey): self
    {
        $this->set['joinKey'] = $joinKey;
        return $this;
    }

    /**
     * 获取关联key
     *
     * @return string 未设置时返回空字符串
     */
    public function getJoinKey(): string
    {
        return $this->set['joinKey'] ?? '';
    }

    /**
     * 设置关联字段是否可编辑,如果可编辑需要在保存时自行处理
     *
     * @param bool $joinEdit
     * @return self
     */
    public function setJoinEdit(bool $joinEdit): self
    {
        $this->set['joinEdit'] = $joinEdit;
        return $this;
    }

    /**
     * 获取是否关联可编辑
     *
     * @return bool 未设置时返回false
     */
    public function getJoinEdit(): bool
    {
        return $this->set['joinEdit'] ?? false;
    }

    /**
     * 设置结果页回调函数
     *
     * @param callable $callback
     *            传递的参数为(该字段的值,该条记录结果集,该记录在结果集中的位置)
     * @return self
     */
    public function setResultCallback(callable $callback): self
    {
        $this->set['callback']['result'] = $callback;
        return $this;
    }

    /**
     * 获取结果页回调设置
     *
     * @param mixed $value
     *            字段的值
     * @param array $row
     *            整条记录
     * @return mixed 未设置返回false
     */
    public function getResultCallback($value, array &$row)
    {
        return isset($this->set['callback']['result']) ? call_user_func($this->set['callback']['result'], $value, $row) : false;
    }

    /**
     * 设置编辑页回调函数
     *
     * @param callable $callback
     *            传递的参数为(该字段的值,该条记录数组)
     * @return self
     */
    public function setEditCallback(callable $callback): self
    {
        $this->set['callback']['edit'] = $callback;
        return $this;
    }

    /**
     * 获取编辑页回调设置
     *
     * @param mixed $value
     *            字段的值
     * @param array $row
     *            整条记录
     * @return mixed 未设置返回false
     */
    public function getEditCallback($value, array &$row)
    {
        return isset($this->set['callback']['edit']) ? call_user_func($this->set['callback']['edit'], $value, $row) : false;
    }

    /**
     * 设置导出Excel回调函数
     *
     * @param callable $callback
     *            传递的参数为(该字段的值,该条记录结果集,该记录在结果集中的位置)
     * @return self
     */
    public function setExcelCallback(callable $callback): self
    {
        $this->set['callback']['excel'] = $callback;
        return $this;
    }

    /**
     * 获取导出Excel回调设置
     *
     * @param mixed $value
     *            字段的值
     * @param array $row
     *            整条记录
     * @return mixed 未设置返回false
     */
    public function getExcelCallback($value, array &$row)
    {
        return isset($this->set['callback']['excel']) ? call_user_func($this->set['callback']['excel'], $value, $row) : false;
    }

    /**
     * 设置枚举数组
     *
     * @param array $option
     * @param string $separator
     *            分隔符,为多选项时设置,为空则为单选项
     * @return self
     */
    public function setOption(array $option, string $separator = ''): self
    {
        $this->set['option'] = $option;
        $this->set['optionSeparator'] = $separator;
        $this->setResultColor();
        return $this;
    }

    /**
     * 获取设置的枚举数组
     *
     * @return array 未设置返回空数组
     */
    public function getOption(): array
    {
        return $this->set['option'] ?? [];
    }

    /**
     * 获取枚举键对应的枚举值
     *
     * @param mixed $key
     *            枚举键,可以是用分隔符拼接后的多个枚举键
     * @return mixed 没有对应的枚举值则返回$key,多个枚举键返回数组
     */
    public function getOptionValue($key)
    {
        if ($this->getOptionSeparator()) {
            $keys = explode($this->getOptionSeparator(), $key);
            $return = [];
            foreach ($keys as $key) {
                $return[] = $this->set['option'][$key] ?? $key;
            }
            return $return;
        }
        return $this->set['option'][$key] ?? $key;
    }

    /**
     * 获取分隔符
     *
     * @return string 未设置返回空字符串
     */
    public function getOptionSeparator(): string
    {
        return $this->set['optionSeparator'] ?? '';
    }

    /**
     * 设置标签颜色,不设置默认为false
     *
     * @param bool $color
     * @return self
     */
    public function setResultColor(): self
    {
        $this->set['resultColor'] = true;
        return $this;
    }

    /**
     * 获取是否设置了标签颜色或者值对应的颜色
     *
     * @param mixed $value
     *            值,为空字符串时为获取是否设置了标签颜色
     * @return bool|string 返回值对应的颜色,未设置返回false
     */
    public function getResultColor($value = null)
    {
        if (! isset($this->set['resultColor'])) {
            return false;
        } elseif ($value === null) {
            return $this->set['resultColor'];
        }

        $count = count(self::$tagColors);
        $k = 0;
        if (is_numeric($value)) {
            $k = intval($value);
        } elseif (is_string($value)) {
            $temp = 0;
            for ($i = 0; $i < strlen($value); $i ++) {
                $temp += ord($value{$i});
            }
            $k = $temp;
        }
        return self::$tagColors[$k % $count];
    }

    /**
     * 获取字段别名after
     *
     * @param boolean $trim
     *            是否去掉表别名和'`'字符,取决于是否用于结果集,如果是则应去掉
     * @return string
     */
    public function getFieldAlias(bool $trim = false): string
    {
        $field = $this->set['sql'];
        if ($trim) {
            $field = stripos($field, '.') === false ? $field : trim(substr($field, stripos($field, '.') + 1));
            $field = str_replace('`', '', $field);
        }
        return stripos($field, ' as ') === false ? $field : trim(substr($field, stripos($field, ' as ') + 4));
    }

    /**
     * 获取真实字段名before
     *
     * @param boolean $trim
     *            是否去掉表别名和'`'字符,取决于是否用于结果集,如果是则应去掉
     * @return string
     */
    public function getFieldReal(bool $trim = false): string
    {
        $field = $this->set['sql'];
        if ($trim) {
            $field = stripos($field, '.') === false ? $field : trim(substr($field, stripos($field, '.') + 1));
            $field = str_replace('`', '', $field);
        }
        return stripos($field, ' as ') === false ? $field : trim(substr($field, 0, stripos($field, ' as ')));
    }

    /**
     * 设置为主键，默认编辑只读
     *
     * @return self
     */
    public function setPk(): self
    {
        $this->set['isPk'] = true;
        $this->setEditReadOnly();
        $this->setResultWidth(70);
        return $this;
    }

    /**
     * 是否为主键
     *
     * @return bool 未设置为false
     */
    public function isPk(): bool
    {
        return $this->set['isPk'] ?? false;
    }

    /**
     * 设置为统计项,会自动设置别名为key
     *
     * @param string $statAllSql
     *            默认的汇总为合计,可设置为其他方式的SQL<br>
     *            如:为百分比 ROUND(key1 / key2 * 100, 1)
     * @return self
     */
    public function setStat(string $statAllSql = ''): self
    {
        $this->set['isStat'] = true;
        $this->set['sql'] .= ' AS ' . $this->getKey(); // 自动加上key的别名
        $this->setResultWidth(100);

        if ($statAllSql) {
            $this->set['statAllSql'] = $statAllSql;
        }
        return $this;
    }

    /**
     * 是否为统计项
     *
     * @return bool 未设置为false
     */
    public function isStat(): bool
    {
        return $this->set['isStat'] ?? false;
    }

    /**
     * 获取统计汇总的设置
     *
     * @return string
     */
    public function getStatAllSql(): string
    {
        return $this->set['statAllSql'] ?? '';
    }

    /**
     * 获取键名
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->set['key'];
    }

    /**
     * 获取文字
     *
     * @return string
     */
    public function getText(): string
    {
        return $this->set['text'];
    }

    /**
     * 获取SQL
     *
     * @return string
     */
    public function getSql(): string
    {
        return $this->set['sql'];
    }

    /**
     * 添加表别名
     *
     * @param string $alias
     * @return self
     */
    public function addTableAlias(string $alias): self
    {
        if (strpos($this->set['sql'], '.') === false && stripos($this->set['sql'], ' as ') === false) {
            $this->set['sql'] = $alias . '.' . $this->set['sql'];
        }
        return $this;
    }
}
