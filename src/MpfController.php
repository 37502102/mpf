<?php
namespace mpf;

use mpf\model\Module;

abstract class MpfController
{

    /**
     * 请求参数名的设置
     * --query 查询
     * --row 编辑或添加
     * --rows 批量编辑
     * --pkvs 批量选择
     * --display 显示的字段
     *
     * @var array
     */
    public static $queryNames = ['query' => 'q', 'row' => 'rcd', 'rows' => 'rows', 'pkvs' => 'ckb', 'display' => 'dp'];

    /**
     * 操作的模型
     *
     * @var MpfModel
     */
    protected $mpfModel;

    /**
     * 设置
     * --operats 不需要权限的操作
     * --recordOperats 对单条记录的操作
     * --resultsOperats 对结果集的批量操作
     * --tabOperats 属于tab的操作
     * --needCheckbox 结果集是否需要选择框
     *
     * @var array
     */
    protected $mpfSets = ['operats' => ['export' => '导出'], 'recordOperats' => [], 'resultsOperats' => [],
        'tabOperats' => [], 'needCheckbox' => false, 'pageTitle' => '', 'pageExplain' => [], 'pageAutoQuery' => false,
        'pageSize' => 0];

    public function __construct()
    {
        $this->mpfInit();
    }

    /**
     * MPF的初始化,添加模块的各种设置
     */
    abstract protected function mpfInit();

    /**
     * 检查用户是否登陆
     *
     * @todo 需要实现
     * @return boolean
     */
    private function checkLogin()
    {
        return true;
    }

    /**
     * 检查用户是否有访问权限
     *
     * @todo 需要实现
     * @return bool
     */
    private function checkUserVisit(int $ctr): bool
    {
        return true;
    }

    /**
     * 检查用户是否有操作权限
     *
     * @todo 需要实现
     * @return bool
     */
    private function checkUserOperat(): bool
    {
        return true;
    }

    /**
     * 获取用户自定义的页面显示记录数
     *
     * @todo 需要实现
     * @return int
     */
    private function getUserPageSize(): int
    {
        return 20;
    }

    /**
     * 记录用户操作日志
     *
     * @todo
     * @param array $data
     */
    protected function addOperatLog(array &$data): void
    {
        $row = ['user_id' => 1, 'module_id' => mt_rand(1, 10), 'action' => $_GET['act'], 'create_date' => date('Y-m-d'),
            'ip' => $_SERVER['REMOTE_ADDR'], 'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'create_time' => date('H:i:s')];
        $this->mpfModel->pdoCreate($row, 'mpf_uselogs');
    }

    protected function check(string $action = '')
    {
        if (! $this->checkLogin()) {
            $this->mpfReturnJump('/login');
        }
        if (! $this->checkUserVisit(0)) {
            $this->mpfReturnErr('你没有此模块的访问权限');
        }
        if ($action && $action != 'export' && ! $this->checkUserOperat()) {
            $this->mpfReturnErr('你没有此模块的操作权限');
        }
        if ($action && $action != 'cache' && ! isset($this->mpfSets['operats'][$action]) &&
            ! isset($this->mpfSets['recordOperats'][$action]) && ! isset($this->mpfSets['resultsOperats'][$action]) &&
            ! isset($this->mpfSets['tabOperats'][$action])) {
            $this->mpfReturnErr('没有此功能');
        }
    }

    /**
     * 首页
     */
    public function actionIndex()
    {
        $this->check();

        $data['title'] = $this->mpfSets['pageTitle'];

        $data['menu'] = Module::getMpfCache()[1]['menu']; // 取后台菜单

        if ($this->mpfSets['pageAutoQuery']) {
            $data['tabs']['result'] = $this->getResult();
        } else {
            $data['tabs']['result'] = [];
        }

        $data['tabs']['query'] = $this->mpfModel->getPageQuery($_POST[self::$queryNames['query']] ?? []);

        $msg = ! isset($_POST[self::$queryNames['query']]) && $this->mpfModel->hasDefaultQuery() ? '请注意本模块设置了默认搜索条件' : '';

        if ($this->mpfSets['pageExplain']) {
            $data['tabs']['explain'] = $this->mpfSets['pageExplain'];
        }

        $haveOperat = $this->checkUserOperat();

        if ($haveOperat && isset($this->mpfSets['tabOperats']['import'])) {
            $data['tabs']['import'] = ['html' => 'input', 'type' => 'file', 'name' => 'import',
                'template' => $this->mpfSets['tabOperats']['import']];
        }

        if ($haveOperat && isset($this->mpfSets['tabOperats']['add'])) {
            $data['tabs']['add'] = $this->mpfModel->getPageEdit();
        }

        $this->mpfReturnPage('index', $data, $msg);
    }

    /**
     * 结果页
     */
    public function actionResult()
    {
        $this->check();

        try {
            $this->mpfReturnPage('result', $this->getResult());
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    protected function &getResult()
    {
        $haveOperat = $this->checkUserOperat();
        $pageSize = $this->mpfSets['pageSize'] ? $this->mpfSets['pageSize'] : $this->getUserPageSize();

        $data = $this->mpfModel->getPageResult(
            $_POST[self::$queryNames['query']] ?? ($_GET[self::$queryNames['query']] ?? []), $pageSize);

        if ($haveOperat && $data['results']) {
            if ($this->mpfSets['needCheckbox'] || $this->mpfSets['recordOperats']) {
                if ($this->mpfSets['needCheckbox']) {
                    $data['titles'] = ['choose' => '选择'] + $data['titles'];
                }
                if ($this->mpfSets['recordOperats']) {
                    $data['titles']['operat'] = '操作';
                }
                foreach ($data['results'] as $i => $result) {
                    if ($this->mpfSets['needCheckbox']) {
                        $data['results'][$i] = [
                            'choose' => ['html' => 'input', 'type' => 'checkbox', 'name' => "ckb[$i]", 'id' => "ckb$i",
                                'value' => "{$result['pk']}", 'checked' => false]] + $result;
                    }
                    if ($this->mpfSets['recordOperats']) {
                        foreach ($this->mpfSets['recordOperats'] as $action => $text) {
                            $data['results'][$i]['operat'][$action] = ['html' => 'a', 'text' => $text,
                                'link' => "?act={$action}&pk={$result['pk']}"];
                        }
                    }
                }
            }
            foreach ($this->mpfSets['resultsOperats'] as $action => $text) {
                if (is_array($text)) { // updates
                    foreach ($text as $i => $update) {
                        $data['operats'][] = ['html' => 'input', 'type' => 'submit', 'text' => $update['text'],
                            'action' => "$action&upid={$i}"];
                    }
                } else {
                    $data['operats'][] = ['html' => 'input', 'type' => 'submit', 'text' => $text, 'action' => "$action"];
                }
            }
            if ($this->mpfModel->getMpfNeedCache()) {
                $data['operats'][] = ['html' => 'input', 'type' => 'submit', 'text' => '更新缓存', 'action' => "cache"];
            }
        }
        if ($data['results'] && $this->mpfSets['operats']) {
            $data['operats'][] = ['html' => 'input', 'type' => 'submit', 'text' => $this->mpfSets['operats']['export'],
                'action' => 'export'];
        }
        return $data;
    }

    /**
     * 编辑
     */
    public function actionEdit()
    {
        $this->check('edit');

        if (! isset($_POST['pk']) || ! $_POST['pk']) {
            $this->mpfReturnErr('缺少请求参数或参数值为空');
        }
        try {
            $this->mpfReturnPage('edit', $this->mpfModel->getPageEdit($_POST['pk'], false));
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    /**
     * 复制
     */
    public function actionCopy()
    {
        $this->check('add');

        if (! isset($_POST['pk']) || ! $_POST['pk']) {
            $this->mpfReturnErr('缺少请求参数或参数值为空');
        }
        try {
            $this->mpfReturnPage('add', $this->mpfModel->getPageEdit($_POST['pk']));
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    /**
     * 添加或编辑
     */
    public function actionSave()
    {
        try {
            if (isset($_POST[self::$queryNames['row']]['pk'])) {
                $this->check('edit');

                $data = $this->mpfModel->mpfUpdate($_POST[self::$queryNames['row']]['pk'],
                    $_POST[self::$queryNames['row']]);

                if ($data === true) {
                    $data = ['更新了文件'];
                }
                $this->addOperatLog($data);

                $this->mpfReturnFlush('保存成功');
            } else {
                $this->check('add');

                $state = $_POST[self::$queryNames['row']]['state'] ?? 0;

                $data = $this->mpfModel->mpfCreate($_POST[self::$queryNames['row']]);

                $this->addOperatLog($data);

                if ($state) {
                    $this->mpfReturnSuc('保存成功');
                } else {
                    $this->mpfReturnFlush('保存成功');
                }
            }
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    /**
     * 批量保存
     */
    public function actionSaves()
    {
        $this->check('saves');

        $pkvs = $_POST[self::$queryNames['pkvs']];
        $records = $_POST[self::$queryNames['rows']];
        if (! $pkvs || ! $records) {
            $this->mpfReturnErr('没有选择要保存的数据');
        }
        try {
            $data = $this->mpfModel->mpfSaves($pkvs, $records);

            $this->addOperatLog($data);

            $this->mpfReturnFlush("保存成功，共更新" . count($data) . '条记录');
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    /**
     * 批量更新
     */
    public function actionUpdates()
    {
        $this->check('updates');

        $pkvs = $_POST[self::$queryNames['pkvs']];
        $upid = $_GET['upid'];
        if (! $pkvs) {
            $this->mpfReturnErr("没有选择要设置{$this->mpfSets['resultsOperats']['updates'][$upid]['text']}的数据");
        }
        try {
            $data = $this->mpfModel->mpfUpdates($pkvs, $this->mpfSets['resultsOperats']['updates'][$upid]['update']);

            $this->addOperatLog($data);

            $this->mpfReturnFlush(
                "设置{$this->mpfSets['resultsOperats']['updates'][$upid]['text']}成功，共设置" . count($data['pks']) . '条记录');
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    /**
     * 批量删除
     */
    public function actionDeletes()
    {
        $this->check('deletes');

        $pkvs = $_POST[self::$queryNames['pkvs']];
        if (! $pkvs) {
            $this->mpfReturnErr('没有选择要删除的数据');
        }
        try {
            $data = $this->mpfModel->mpfDeletes($pkvs);

            $this->addOperatLog($data);

            $this->mpfReturnFlush('删除成功，共删除' . count($data) . '条记录');
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    /**
     * 缓存
     */
    public function actionCache()
    {
        $this->check('cache');

        $this->mpfModel->mpfCache();

        $data = [];
        $this->addOperatLog($data);

        $this->mpfReturnFlush('缓存成功');
    }

    /**
     * 导出，注意这里用的GET，如果是POST需要修改
     */
    public function actionExport()
    {
        $this->check('export');

        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $this->mpfModel->mpfExportExcel($_GET['q'], $this->mpfSets['pageTitle']);
        } else {
            $this->mpfModel->mpfExportCsv($_GET['q'], $this->mpfSets['pageTitle']);
        }
    }

    /**
     * 导入
     */
    public function actionImport()
    {
        $this->check('import');

        if (! isset($_FILES['import']['tmp_name'])) {
            $this->mpfReturnErr('没有导入的文件');
        }

        $info = pathinfo($_FILES['import']['name']);
        $ext = strtolower($info['extension']);

        if ($ext == 'csv') {
            $return = $this->mpfModel->mpfImportCsv($_FILES['import']['tmp_name']);
        } elseif (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $return = $this->mpfModel->mpfImportExcel($_FILES['import']['tmp_name']);
        } else {
            $this->mpfReturnErr('不支持的文件格式');
        }
        $data = $return['data'];
        $this->addOperatLog($data);

        $this->mpfReturnFlush(
            "成功导入{$return['suc']}条数据，失败{$return['err']}条" . ($return['msg'] ? '，' . implode("\n", $return['msg']) : ''));
    }

    /**
     * 返回页面需要的数据
     *
     * @param string $pageName
     * @param array $data
     * @param string $msg
     */
    protected function mpfReturnPage(string $target, array $data, string $msg = ''): void
    {
        self::mpfReturn(1, $data, $msg, $target);
    }

    protected function mpfReturnSuc(string $msg): void
    {
        self::mpfReturn(5, [], $msg);
    }

    protected function mpfReturnErr(string $msg): void
    {
        self::mpfReturn(0, [], $msg);
    }

    protected function mpfReturnJump(string $url): void
    {
        self::mpfReturn(2, [], $url);
    }

    protected function mpfReturnFlush(string $msg): void
    {
        self::mpfReturn(3, [], $msg);
    }

    public static function mpfReturnDebug(array $data): void
    {
        self::mpfReturn(4, $data, '');
    }

    public static function mpfReturn(int $code, array $data, string $msg, string $target = ''): void
    {
        header('Content-Type:application/json');
        echo json_encode(compact('code', 'data', 'msg', 'target'), JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * 设置非表模式的MPF模型,如查看日志,管理缓存,管理数据库进程
     *
     * @param string $mpfModelName
     * @return self
     */
    protected function setMpfNotTable(string $mpfModelName): self
    {
        $name = str_replace('_', '\\', $mpfModelName);
        $this->mpfModel = new $name(true, true);
        return $this;
    }

    /**
     * 设置MPF模型
     *
     * @todo 需要修改,从配置中读取数据库配置
     * @param string $mpfModelName
     * @return self
     */
    protected function setMpfModel(string $mpfModelName): self
    {
        $name = str_replace('_', '\\', $mpfModelName);
        $this->mpfModel = new $name(true);
        return $this;
    }

    /**
     * 设置操作-添加
     *
     * @param string $text
     * @return self
     */
    protected function setOperatAdd(string $text = '添加'): self
    {
        $this->mpfSets['tabOperats']['add'] = $text;
        $this->mpfSets['recordOperats']['copy'] = '复制';
        return $this;
    }

    /**
     * 设置操作-编辑
     *
     * @param string $text='编辑'
     * @return self
     */
    protected function setOperatEdit(string $text = '编辑'): self
    {
        $this->mpfSets['recordOperats']['edit'] = $text;
        return $this;
    }

    /**
     * 设置操作-删除
     *
     * @param string $text
     * @return self
     */
    protected function setOperatDeletes(string $text = '删除'): self
    {
        $this->mpfSets['resultsOperats']['deletes'] = $text;
        $this->mpfSets['needCheckbox'] = true;
        return $this;
    }

    /**
     * 设置操作-保存
     *
     * @param string $text
     * @return self
     */
    protected function setOperatSaves(string $text = '保存'): self
    {
        $this->mpfSets['resultsOperats']['saves'] = $text;
        $this->mpfSets['needCheckbox'] = true;
        return $this;
    }

    /**
     * 设置操作-导入
     *
     * @param string $text
     * @param string $template
     *            模版文件
     * @return self
     */
    protected function setOperatImport(string $template = ''): self
    {
        $this->mpfSets['tabOperats']['import'] = $template;
        return $this;
    }

    /**
     * 设置操作-批量更新,可设置多个
     *
     * @param string $text
     * @param array $update
     *            要更新的数据
     * @return self
     */
    protected function setOperatUpdates(string $text, array $update): self
    {
        $this->mpfSets['resultsOperats']['updates'][] = ['text' => $text, 'update' => $update];
        $this->mpfSets['needCheckbox'] = true;
        return $this;
    }

    /**
     * 取消导出操作
     *
     * @return self
     */
    protected function setOperatUnExport(): self
    {
        unset($this->mpfSets['operats']['export']);
        return $this;
    }

    /**
     * 设置自定义操作
     *
     * @param string $action
     * @param string $text
     * @return self
     */
    protected function setOperat(string $action, string $text, bool $needCheckbox = true): self
    {
        $this->mpfSets['resultsOperats'][$action] = $text;
        if ($needCheckbox) {
            $this->mpfSets['needCheckbox'] = true;
        }
        return $this;
    }

    /**
     * 设置页面标题
     *
     * @param string $title
     * @return self
     */
    protected function setPageTitle(string $title): self
    {
        $this->mpfSets['pageTitle'] = $title;
        return $this;
    }

    /**
     * 设置页面说明
     *
     * @param array $explain
     * @return self
     */
    protected function setPageExplain(array $explain): self
    {
        $this->mpfSets['pageExplain'] = $explain;
        return $this;
    }

    /**
     * 设置进入页面就查询
     *
     * @return self
     */
    protected function setPageAutoQuery(): self
    {
        $this->mpfSets['pageAutoQuery'] = true;
        return $this;
    }

    /**
     * 设置页面显示的记录数
     *
     * @param int $size
     * @return self
     */
    protected function setPageSize(int $size)
    {
        $this->mpfSets['pageSize'] = $size;
        return $this;
    }

    /**
     * 设置页面自定义Tab
     *
     * @param string $action
     * @param string $text
     * @return self
     */
    protected function setPageTab(string $action, string $text): self
    {
        $this->mpfSets['pageTab'][$action] = $text;
        return $this;
    }
}