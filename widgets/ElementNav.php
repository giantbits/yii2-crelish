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
  private $cet;

  public function __construct($config = [])
  {
    if (count($config) > 0) {
      $this->action = (empty($config['action'])) ? \Yii::$app->controller->action->id : $config['action'];
      $this->cet = (empty($config['cet'])) ? '' : $config['cet'];
    } else {
      $this->action = \Yii::$app->controller->action->id;
      $this->cet = !empty(\Yii::$app->getRequest()->getQueryParam('cet')) ? \Yii::$app->getRequest()->getQueryParam('cet') : 'page';
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
    
    foreach(\Yii::$app->getRequest()->getQueryParams() as $param => $value) {
      $params[$param] = $value;
    }

    foreach($elements->all()['models'] as $element) {
      if(array_key_exists('selectable', $element) && $element['selectable'] === false) continue;
      $css = ($this->cet == $element['key']) ? 'gc-active-filter' : '';
      $params['cet'] = $element['key'];
      $nav .= Html::tag('li', Html::a($element['label'], \Yii::$app->getRequest()->getUrl() . '&cet=' . $element['key'], []), ['class' => $css]);
    }

    return $nav;
  }
}
