<?php
namespace giantbits\crelish\plugins\datalist;

use giantbits\crelish\components\CrelishDynamicJsonModel;
use giantbits\crelish\components\CrelishJsonDataProvider;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\grid\ActionColumn;

class DataList extends Widget
{
    public $data;
    public $formKey;
    public $field;

    public function init()
    {
        parent::init();

        if (!empty($this->data)) {
            $this->data = $this->processData($this->data);
        } else {
            $this->data = Json::encode(['main' => []]);
        }
    }

    private function processData($data)
    {
        return $data;
    }

    public function run()
    {

        return $this->render('datalist.twig', [
            'formKey' => $this->formKey,
            'field' => $this->field,
            'rawData' => $this->data
        ]);
    }
}
