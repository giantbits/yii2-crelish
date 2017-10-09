<?php

namespace giantbits\crelish\controllers;

use giantbits\crelish\components\CrelishBaseController;
use giantbits\crelish\components\CrelishSnapshotManager;
use yii\filters\AccessControl;


class SnapshotController extends CrelishBaseController
{
    public $layout = 'crelish.twig';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['login'],
                        'roles' => ['?'],
                    ],
                    [
                        'allow' => true,
                        'actions' => [],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }


    public function init()
    {
        parent::init();
    }

    public function actionIndex() {
        $snapShots = CrelishSnapshotManager::getSnapshots();
        $error = null;
        if (isset($_POST['name'])) {
            if (!preg_match('/^[a-z0-9]+$/i',$_POST['name'])) {
                $error = \Yii::t('crelish','Please enter only alphanumeric characters for the snapshot name');
            } else if (strlen($_POST['name']) == 0) {
                $error = \Yii::t('crelish','Please enter a name for your new snapshot');
            } else if (isset($snapShots[$_POST['name']])) {
                $error = \Yii::t('crelish','There is already a snapshot with that name');
            } else {
                header('location:/crelish/snapshot/create?name='.urlencode($_POST['name']));
                die();
            }
        }
        return $this->render('index.twig',['snapshots'=>$snapShots,'error'=>$error]);
    }

    public function actionCreate() {
        $session = \Yii::$app->session;
        $session['snapshotName'] = $_GET['name'];
        $session['snapshotTasks'] = [
            new SnapshotCopyFolderTask("@app/themes/" . \Yii::$app->params['crelish']['theme'],"@app/themes/snapshots/" . $session['snapshotName'] . '/' . \Yii::$app->params['crelish']['theme']),
            new SnapshotCopyFolderTask("@app/workspace","@app/workspace/snapshots/" . $session['snapshotName'] . '/workspace',["snapshots"]),
        ];

        return $this->render('working.twig',['name'=>$session['snapshotName']]);
    }

    public function actionRun() {
        $session = \Yii::$app->session;
        $max = 100;
        $tasks = $session['snapshotTasks'];
        while (count($tasks) && $max > 0) {
            $task = array_shift($tasks);
            $task->run($tasks);
            $max--;
        }
        $session['snapshotTasks'] = $tasks;
        if (count($tasks) > 0) {
            return $this->render('working.twig',['name'=>$session['snapshotName']]);
        } else {
            CrelishSnapshotManager::createSnapshotEntry($session['snapshotName']);
            return $this->render('done.twig',['name'=>$session['snapshotName']]);
        }
    }
}

class SnapshotCopyFolderTask {
    var $source;
    var $exceptions;
    var $target;

    public function __construct($source,$target,$expetions=[]) {
        if (substr($source,0,1)=='@') {
            $this->source = \Yii::getAlias($source);
        } else {
            $this->source = $source;
        }
        if (substr($target,0,1)=='@') {
            $this->target = \Yii::getAlias($target);
        } else {
            $this->target = $target;
        }
        $this->exceptions = $expetions;
        $this->exceptions[] = '.';
        $this->exceptions[] = '..';
        $this->exceptions[] = '.gitignore';
    }

    public function run(&$tasks) {
        $parts = explode("/", $this->target);
        $folder = '';
        foreach ($parts as $part) {
            $folder .= $part . '/';
            if (!file_exists($folder)) {
                mkdir($folder);
            }
        }
        $files = scandir($this->source);
        foreach ($files as $file) {
            if (!in_array($file, $this->exceptions)) {
                $s = $this->source . '/' . $file;
                if (is_file($s)) {
                    $tasks[] = new SnapshotCopyFileTask($this->source . '/' . $file, $this->target . '/' . $file);
                } else {
                    $tasks[] = new SnapshotCopyFolderTask($this->source . '/' . $file, $this->target . '/' . $file);
                }
            }
        }
    }
}

class SnapshotCopyFileTask {
    var $source;
    var $exceptions;
    var $target;

    public function __construct($source,$target,$expetions=[]) {
        if (substr($source,0,1)=='@') {
            $this->source = \Yii::getAlias($source);
        } else {
            $this->source = $source;
        }
        if (substr($target,0,1)=='@') {
            $this->target = \Yii::getAlias($target);
        } else {
            $this->target = $target;
        }
        $this->exceptions = $expetions;
        $this->exceptions[] = '.';
        $this->exceptions[] = '..';
    }

    public function run(&$tasks) {
        $session = \Yii::$app->session;
        $parts = explode(".",$this->source);
        $ext = array_pop($parts);
        $content = file_get_contents($this->source);
        //TODO: replacements!
        switch (strtolower($ext)) {
            case 'twig':
            case 'php':
                $content = str_replace(
                    [
                        "app/themes/" . \Yii::$app->params['crelish']['theme'],
                        "app\\themes\\" . \Yii::$app->params['crelish']['theme'],
                    ],
                    [
                        "app/themes/snapshots/" . $session['snapshotName'] . '/' . \Yii::$app->params['crelish']['theme'],
                        "app\\themes\\snapshots\\" . $session['snapshotName'] . '\\' . \Yii::$app->params['crelish']['theme'],
                    ],
                    $content);
                break;
        }
        file_put_contents($this->target,$content);
    }
}
