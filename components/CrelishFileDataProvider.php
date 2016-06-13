<?php
/**
 * Created by PhpStorm.
 * User: devop
 * Date: 03.02.16
 * Time: 20:57
 */

namespace giantbits\crelish\components;

use Underscore\Types\Arrays;
use yii\base\Component;
use yii\data\ArrayDataProvider;
use yii\grid\ActionColumn;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\LinkPager;

class CrelishFileDataProvider extends Component
{

  private $sourceFolder;
  private $allModels;
  private $filter;
  private $_columns;
  private $key = 'uuid';

  public function __construct($sourceFolder, $settings = [])
  {

    $this->sourceFolder = $sourceFolder;
    $this->allModels = $this->parseFolderContent();

    if (Arrays::has($settings, 'key')) {
      $this->key = $settings['key'];
    }

    if (Arrays::has($settings, 'filter')) {
      $this->filterModels($settings['filter']);
    }

    if (Arrays::has($settings, 'sort')) {
      $this->sortModels($settings['sort']);
    }

    parent::__construct(); // TODO: Change the autogenerated stub
  }

  public function getColumns()
  {
    $columns = [];
    $filePath = \Yii::getAlias('@app/workspace/elements') . DIRECTORY_SEPARATOR . $this->sourceFolder . '.json';

    $fieldDefinitions = Json::decode(file_get_contents($filePath), false);

    $columns[] = 'uuid';
    $columns[] = 'type';
    $columns[] = 'path';
    $columns[] = 'slug';
    $columns[] = 'state';

    foreach ($fieldDefinitions->fields as $field) {
      if (!empty($field->visibleInGrid) && $field->visibleInGrid) {
        $columns[] = $field->key;
      }
    }

    $columns[] = [
      'class' => ActionColumn::className(),
      'template' => '{update}',
      'buttons' => [
        'update' => function ($url, $model) {
          return Html::a('<span class="glyphicon glyphicon-edit"></span>', $url, [
            'title' => \Yii::t('app', 'Edit'),
          ]);
        }
      ],
      'urlCreator' => function ($action, $model, $key, $index) {
        if ($action === 'update') {
          $url = Url::toRoute(['content/update', 'type' => $this->sourceFolder, 'uuid'=>$model['uuid']]);
          return $url;
        }
      }
    ];

    return array_values($columns);
  }

  private function filterModels($filter)
  {

    foreach ($filter as $key => $value) {
      $this->allModels = Arrays::filterBy($this->allModels, $key, $value);
    }
  }

  private function sortModels($sort)
  {
    $this->allModels = Arrays::sort($this->allModels, function ($model) use ($sort) {
      return $model[$sort['by']];
    }, $sort['dir']);
  }

  public function parseFolderContent()
  {
    $filesArr = [];
    $allModels = [];

    $fullFolder = \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'workspace' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $this->sourceFolder;

    $files = FileHelper::findFiles($fullFolder);
    if (isset($files[0])) {
      foreach ($files as $file) {
        $filesArr[] = $file;
      }
    }

    foreach ($filesArr as $file) {
      $content = file_get_contents($file);
      $modelArr = json_decode($content, true);
      $modelArr['id'] = $file;
      $modelArr['type'] = $this->sourceFolder;
      $allModels[] = $modelArr;
    }

    return $allModels;
  }

  public function all()
  {
    $provider = new ArrayDataProvider([
      'key' => $this->key,
      'allModels' => $this->allModels,
      'pagination' => [
        'totalCount' => count($this->allModels),
        'pageSize' => 15,
        'forcePageParam' => true,
        //'route' => $_GET['pathRequested'],
        //'urlManager' => \Yii::$app->getUrlManager(),
      ],
    ]);

    $models = $provider->getModels();

    $pager = LinkPager::widget([
      'pagination' => $provider->getPagination(),
      'maxButtonCount' => 10
    ]);

    $result = ['models' => array_values($models), 'pager' => $pager];

    return $result;
  }

  public function raw()
  {
    $provider = new ArrayDataProvider([
      'key' => $this->key,
      'allModels' => $this->allModels,
      'sort' => [
        'attributes' => [$this->key, 'systitle'],
      ],
      'pagination' => [
        'totalCount' => count($this->allModels),
        'pageSize' => 15,
        'forcePageParam' => true,
        //'route' => $_GET['pathRequested'],
        //'urlManager' => \Yii::$app->getUrlManager(),
      ],
    ]);

    return $provider;
  }
}
