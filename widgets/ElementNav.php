<?php
namespace giantbits\crelish\widgets;

use giantbits\crelish\components\CrelishFileDataProvider;
use giantbits\crelish\components\CrelishJsonDataProvider;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\Link;

class ElementNav extends Widget
{
  public $message;
  private $action;
  private $type;

  public function __construct($config = [])
  {
    if (count($config) > 0) {
      $this->action = (empty($config['action'])) ? \Yii::$app->controller->action->id : $config['action'];
      $this->type = (empty($config['type'])) ? '' : $config['type'];
    } else {
      $this->action = \Yii::$app->controller->action->id;
      $this->type = !empty($_GET['type']) ? $_GET['type'] : '';
    }

    parent::__construct(); // TODO: Change the autogenerated stub
  }

  public function init()
  {
    parent::init();
    if ($this->message === null) {
      $this->message = 'Hello World';
    }
  }

  public function run()
  {
    $nav = '';
    //$elements = new CrelishFileDataProvider('elements', ['key' => 'key', 'sort'=> ['by'=>'label', 'dir'=>'ASC']]);
    $elements = new CrelishJsonDataProvider('elements', ['key' => 'key', 'sort'=> ['by'=>'label', 'dir'=>'ASC']]);

    foreach($elements->all()['models'] as $element) {
      if(array_key_exists('selectable', $element) && $element['selectable'] === false) continue;
      $css = ($this->type == $element['key']) ? 'gc-active-filter' : '';
      $nav .= Html::tag('li', Html::a($element['label'], ['content/' . $this->action, 'type' => $element['key']]), ['class' => $css]);
    }

    return $nav;
  }
}
