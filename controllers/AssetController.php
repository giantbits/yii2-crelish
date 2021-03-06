<?php
/**
 * Created by PhpStorm.
 * User: devop
 * Date: 29.11.15
 * Time: 17:19
 */

namespace giantbits\crelish\controllers;

use ColorThief\ColorThief;
use giantbits\crelish\components\CrelishBaseController;
use giantbits\crelish\components\CrelishDynamicModel;
use yii\base\Controller;
use yii\helpers\Html;
use yii\web\UploadedFile;
use yii\helpers\Json;
use yii\helpers\Url;
use giantbits\crelish\components\CrelishDataProvider;
use yii\filters\AccessControl;

class AssetController extends CrelishBaseController
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
    $this->enableCsrfValidation = false;
    parent::init();
  }

  public function actionIndex()
  {
    $this->enableCsrfValidation = false;
    $filter = null;
    if (!empty($_GET['cr_content_filter'])) {
      $filter = ['freesearch' => $_GET['cr_content_filter']];
    }

    $modelProvider = new CrelishDataProvider('asset', ['filter' => $filter], NULL);
    $checkCol = [
      [
        'class' => 'yii\grid\CheckboxColumn'
      ],
      [
        'label' => \Yii::t('crelish', 'Preview'),
        'format' => 'raw',
        'value' => function ($model) {
          $preview = \Yii::t('crelish', 'n/a');

          switch ($model['mime']) {
            case 'image/jpeg':
            case 'image/gif':
            case 'image/png':
              $preview = Html::img($model['src'], ['style' => 'width: 80px; height: auto;']);
          }

          return $preview;
        }
      ]
    ];
    $columns = array_merge($checkCol, $modelProvider->columns);

    $rowOptions = function ($model, $key, $index, $grid) {
      return ['onclick' => 'location.href="update.html?ctype=asset&uuid=' . $model['uuid'] . '";'];
    };

    return $this->render('index.twig', [
      'dataProvider' => $modelProvider->raw(),
      'filterProvider' => $modelProvider->getFilters(),
      'columns' => $columns,
      'ctype' => $this->ctype,
      'rowOptions' => $rowOptions
    ]);
  }

  public function actionUpdate()
  {
    $uuid = !empty(\Yii::$app->getRequest()->getQueryParam('uuid')) ? \Yii::$app->getRequest()->getQueryParam('uuid') : null;
    $model = new CrelishDynamicModel([], ['uuid' => $uuid, 'ctype' => 'asset']);

    // Save content if post request.
    if (!empty(\Yii::$app->request->post()) && !\Yii::$app->request->isAjax) {
      $model->attributes = $_POST['CrelishDynamicJsonModel'];

      if ($model->validate()) {
        $model->save();
        \Yii::$app->session->setFlash('success', 'Asset saved successfully...');
        header("Location: " . Url::to(['asset/update', 'uuid' => $model->uuid]));
        exit(0);
      } else {
        //var_dump($model->errors);
        \Yii::$app->session->setFlash('error', 'Asset save failed...');
      }
    }

    $alerts = '';
    foreach (\Yii::$app->session->getAllFlashes() as $key => $message) {
      $alerts .= '<div class="c-alerts__alert c-alerts__alert--' . $key . '">' . $message . '</div>';
    }

    return $this->render('update.twig', [
      'model' => $model,
      'alerts' => $alerts
    ]);
  }

  public function actionUpload()
  {
    $file = UploadedFile::getInstanceByName('file');

    if ($file) {
      $destName = time() . '_' . $file->name;
      $targetFile = \Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $destName;
      $file->saveAs($targetFile);

      $model = new CrelishDynamicModel([], ['ctype' => 'asset']);
      $model->systitle = $destName;
      $model->title = $destName;
      $model->src = \Yii::getAlias('@web') . '/' . 'uploads' . '/' . $destName;
      $model->mime = mime_content_type($targetFile);
      $model->size = $file->size;
      $model->state = 2;
      $model->save();
    }

    return false;
  }

  public function actionDelete()
  {
    $uuid = !empty(\Yii::$app->getRequest()->getQueryParam('uuid')) ? \Yii::$app->getRequest()->getQueryParam('uuid') : null;
    $modelProvider = new CrelishDynamicJsonModel([], ['ctype' => 'asset', 'uuid' => $uuid]);
    if (@unlink(\Yii::getAlias('@webroot') . $modelProvider->src) || !file_exists(\Yii::getAlias('@webroot') . $modelProvider->src)) {
      $modelProvider->delete();
      \Yii::$app->session->setFlash('success', 'Asset deleted successfully...');
      header("Location: " . Url::to(['asset/index']));
      exit(0);
    };

    \Yii::$app->session->setFlash('danger', 'Asset could not be deleted...');
    header("Location: " . Url::to(['asset/index', ['uuid' => $modelProvider->uuid]]));
    exit(0);
  }
}
