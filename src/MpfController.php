<?php
namespace mpf;

abstract class MpfController
{

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
     * @param array $data
     */
    protected function addOperatLog(array &$data): void
    {
        // @todo
    }

    private function check(string $action = '')
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
        if ($action && ! isset($this->mpfSets['operats'][$action]) && ! isset($this->mpfSets['recordOperats'][$action]) &&
             ! isset($this->mpfSets['resultsOperats'][$action]) && ! isset($this->mpfSets['tabOperats'][$action])) {
            $this->mpfReturnErr('没有此功能');
        }
    }

    public function actionIndex()
    {
        $this->check();

        $data['title'] = $this->mpfSets['pageTitle'];

        $data['tabs']['result']['title'] = '结果';
        if ($this->mpfSets['pageAutoQuery']) {

            $data['tabs']['result']['data'] = $this->getResult();
        } else {
            $data['tabs']['result']['data'] = [];
        }

        $data['tabs']['query']['title'] = '查询';
        $data['tabs']['query']['data'] = $this->mpfModel->getPageQuery();

        if ($this->mpfSets['pageExplain']) {
            $data['tabs']['explain']['title'] = '说明';
            $data['tabs']['explain']['data'] = $this->mpfSets['pageExplain'];
        }

        $haveOperat = $this->checkUserOperat();

        if ($haveOperat && isset($this->mpfSets['tabOperats']['import'])) {
            $data['tabs']['import']['title'] = '导入';
            if ($this->mpfSets['tabOperats']['import']) {
                $data['tabs']['import']['data'] = ['html' => 'a', 'text' => '下载模版',
                    'link' => $this->mpfSets['tabOperats']['import']];
            } else {
                $data['tabs']['import']['data'] = [];
            }
        }

        if ($haveOperat && isset($this->mpfSets['tabOperats']['add'])) {
            $data['tabs']['add']['title'] = '添加';
            $data['tabs']['add']['data'] = $this->mpfModel->getPageEdit();
        }

        $this->mpfReturnSuc($data);
    }

    public function actionResult()
    {
        $this->check();

        try {
            $this->mpfReturnSuc($this->getResult());
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    protected function &getResult()
    {
        $haveOperat = $this->checkUserOperat();
        $pageSize = $this->mpfSets['pageSize'] ? $this->mpfSets['pageSize'] : $this->getUserPageSize();

        $data = $this->mpfModel->getPageResult($_POST + $_GET, $pageSize);

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
                            'choose' => ['html' => 'input', 'type' => 'checkbox', 'name' => "ckb[$i]",
                                'id' => "ckb$i", 'value' => "{$result['pk']}"]] + $result;
                    }
                    if ($this->mpfSets['recordOperats']) {
                        foreach ($this->mpfSets['recordOperats'] as $action => $text) {
                            $data['results'][$i]['operat'][] = ['html' => 'a', 'text' => $text,
                                'link' => "?act={$action}&pk={$result['pk']}"];
                        }
                    }
                }
            }
            if ($this->mpfSets['needCheckbox']) {
                $data['operats'][] = ['html' => 'input', 'type' => 'checkbox', 'text' => '全选', 'join' => 'ckb'];
            }
            foreach ($this->mpfSets['resultsOperats'] as $action => $text) {
                if (is_array($text)) { // updates
                    foreach ($text as $i => $update) {
                        $data['operats'][] = ['html' => 'input', 'type' => 'submit', 'text' => $$update['text'],
                            'action' => "?act={$action}&upid={$i}"];
                    }
                }
                $data['operats'][] = ['html' => 'input', 'type' => 'submit', 'text' => $text,
                    'action' => "?act={$action}"];
            }
        }
        if ($data['results'] && $this->mpfSets['operats']) {
            $data['operats'][] = ['html' => 'input', 'type' => 'button',
                'text' => $this->mpfSets['operats']['export'], 'action' => '?act=export'];
        }
        return $data;
    }

    public function actionEdit()
    {
        $this->check('edit');

        if (! isset($_GET['pk']) || ! $_GET['pk']) {
            $this->mpfReturnErr('缺少请求参数或参数值为空');
        }
        try {
            $data['tabs']['edit']['title'] = '编辑';
            $data['tabs']['edit']['data'] = $this->mpfModel->getPageEdit($_GET['pk']);
            $this->mpfReturnSuc($data);
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    public function actionSave()
    {
        try {
            if (isset($_POST['pk'])) {
                $this->check('edit');

                $data = $this->mpfModel->mpfUpdate($_POST['pk'], $_POST['rcd']);

                if ($data === true) {
                    $data = ['更新了文件'];
                }
                $this->addOperatLog($data);

                $this->mpfReturnFlush('保存成功');
            } else {
                $this->check('add');

                $data = $this->mpfModel->mpfCreate($_POST['rcd']);

                $this->addOperatLog($data);

                if ($_POST['state']) {
                    $this->mpfReturnSuc([], '保存成功');
                } else {
                    $this->mpfReturnFlush('保存成功');
                }
            }
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    public function actionSaves()
    {
        $this->check('saves');

        $pkvs = $_POST['ckb'];
        $recodes = $_POST['edit'];
        if (! $pkvs || ! $recodes) {
            $this->mpfReturnErr('没有选择要保存的数据');
        }
        try {
            $data = $this->mpfModel->mpfSaves($pkvs, $records);

            $this->addOperatLog($data);
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    public function actionUpdates()
    {
        $this->check('updates');

        $pkvs = $_POST['ckb'];
        $upid = $_GET['upid'];
        if (! $pkvs) {
            $this->mpfReturnErr('没有选择要' . $this->mpfSets['resultsOperats'][$upid]['text'] . '的数据');
        }
        try {
            $data = $this->mpfModel->mpfUpdates($pkvs, $this->mpfSets['resultsOperats'][$upid]['update']);

            $this->addOperatLog($data);
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    public function actionDeletes()
    {
        $this->check('deletes');

        $pkvs = $_POST['ckb'];
        if (! $pkvs) {
            $this->mpfReturnErr('没有选择要删除的数据');
        }
        try {
            $data = $this->mpfModel->mpfDeletes($pkvs);

            $this->addOperatLog($data);
        } catch (\Exception $e) {
            $this->mpfReturnErr($e->getMessage());
        }
    }

    public function actionExport()
    {
        $this->check('export');

        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $this->mpfModel->mpfExportExcel($_POST, $this->mpfSets['pageTitle']);
        } else {
            $this->mpfModel->mpfExportCsv($_POST, $this->mpfSets['pageTitle']);
        }
    }

    public function actionImport()
    {
        $this->check('import');

        if (! isset($_FILES['import']['tmp_name'])) {
            $this->mpfReturnErr('没有导入的文件');
        }

        $info = pathinfo($_FILES['import']['name']);
        $ext = strtolower($info['PATHINFO_EXTENSION ']);

        if ($ext == 'csv') {
            $return = $this->mpfModel->mpfImportCsv($_FILES['import']['tmp_name']);
        } elseif (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $return = $this->mpfModel->mpfImportExcel($_FILES['import']['tmp_name']);
        } else {
            $this->mpfReturnErr('不支持的文件格式');
        }
        $data = $return['data'];
        $this->addOperatLog($data);

        $this->mpfReturnSuc([], "成功{$return['suc']},失败{$return['err']}," . implode('\n', $return['msg']));
    }

    protected function mpfReturnSuc(array $data, string $msg = ''): void
    {
        $this->mpfReturn(1, $data, $msg);
    }

    protected function mpfReturnErr(string $msg): void
    {
        $this->mpfReturn(0, [], $msg);
    }

    protected function mpfReturnJump(string $url): void
    {
        $this->mpfReturn(2, [], $url);
    }

    protected function mpfReturnFlush(string $msg): void
    {
        $this->mpfReturn(3, [], $msg);
    }

    protected function mpfReturn(int $code, array $data, string $msg): void
    {
        header('Content-Type:application/json');
        echo json_encode(compact('code', 'data', 'msg'), JSON_UNESCAPED_UNICODE);
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
        $this->mpfModel = new $name(['isMpf' => true, 'notTable' => true]);
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
        $this->mpfModel = new $name(
            ['isMpf' => true, 'username' => 'manage', 'password' => '123456',
                'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=laraveltest']);
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
        $this->mpfSets['recordOperats']['copy'] = '复制';
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