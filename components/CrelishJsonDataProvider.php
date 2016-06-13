<?php
/**
 * Created by PhpStorm.
 * User: devop
 * Date: 03.02.16
 * Time: 20:57
 */

namespace giantbits\crelish\components;

use Underscore\Parse;
use Underscore\Types\Arrays;
use yii\base\Component;
use yii\data\ArrayDataProvider;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\grid\ActionColumn;
use yii\helpers\Url;

class CrelishJsonDataProvider extends Component
{

  private $type;
  private $allModels;
  private $definitions;
  private $key = 'uuid';
  private $uuid;
  private $pathAlias;

  public function __construct($type, $settings = [], $uuid = null)
  {
    $ds = DIRECTORY_SEPARATOR;
    $this->type = $type;

    $this->pathAlias = ($this->type == 'elements') ? '@app/workspace/' : '@app/workspace/data/';

    if (!empty($uuid)) {
      $this->uuid = $uuid;
      $this->allModels[] = \yii\helpers\Json::decode(file_get_contents(\Yii::getAlias($this->pathAlias) . $ds . $type . $ds . $uuid . '.json'));
    } else {
      $this->allModels = $this->parseFolderContent($this->type);
    }

    if (Arrays::has($settings, 'filter')) {
      $this->filterModels($settings['filter']);
    }

    if (Arrays::has($settings, 'sort')) {
      $this->sortModels($settings['sort']);
    }

    parent::__construct(); // TODO: Change the autogenerated stub
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

  public function parseFolderContent($folder)
  {
    $filesArr = [];
    $allModels = [];

    $fullFolder = \Yii::getAlias($this->pathAlias). DIRECTORY_SEPARATOR . $folder;

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
      $modelArr['type'] = $this->type;
      $allModels[] = $modelArr;
    }

    return $allModels;
  }

  public function all()
  {
    $provider = new ArrayDataProvider([
      'key' => 'id',
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

  public function one()
  {
    return $this->allModels[0];
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

  public function delete()
  {
    $ds = DIRECTORY_SEPARATOR;
    return @unlink(\Yii::getAlias($this->pathAlias) . $ds . $this->type . $ds . $this->uuid . '.json');
  }

  public function getDefinitions()
  {
    $filePath = \Yii::getAlias('@app/workspace/elements') . DIRECTORY_SEPARATOR . $this->type . '.json';
    $this->definitions = new \stdClass();

    // Add core fields.
    $this->definitions->fields[] = Json::decode('{ "label": "UUID", "key": "uuid", "type": "textInput", "visibleInGrid": true, "rules": [["string", {"max": 128}]], "options": {"disabled":true}}', false);
    $this->definitions->fields = array_merge($this->definitions->fields, Json::decode(file_get_contents($filePath), false)->fields);
    $this->definitions->fields[] = Json::decode('{ "label": "State", "key": "state", "type": "textInput", "visibleInGrid": true, "rules": [["string", {"max": 128}]], "options": {"disabled":true}}', false);

    return $this->definitions;
  }

  public function getColumns()
  {
    $columns = [];

    foreach ($this->getDefinitions()->fields as $field) {
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
          $url = Url::toRoute(['content/update', 'type' => $this->type, 'uuid'=>$model['uuid']]);
          return $url;
        }
      }
    ];

    return array_values($columns);
  }
}
