<?php
namespace mpf\controller;

use mpf\model\Module;
use mpf\MpfController;

class Codeupdate extends MpfController
{

    protected function mpfInit()
    {
        $this->setPageTitle('代码更新');
    }

    public function actionIndex()
    {
        $this->check();

        $data['title'] = $this->mpfSets['pageTitle'];

        $data['menu'] = Module::getMpfCache()[1]['menu'];

        $data['tabs']['result']['titles'] = ['f1' => '更新结果'];

        $out = ['命令未执行'];
        if (substr(PHP_OS, 0, 3) != 'WIN') {
            $cmd = 'git pull'; // 'svn update'
            $return = '';
            exec($cmd, $out, $return);
        }
        foreach ($out as $v) {
            $data['tabs']['result']['results'][] = ['f1' => $v];
        }

        $this->mpfReturnPage('index', $data);
    }
}
