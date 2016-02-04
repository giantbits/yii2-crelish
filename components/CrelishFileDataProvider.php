<?php
/**
 * Created by PhpStorm.
 * User: devop
 * Date: 03.02.16
 * Time: 20:57
 */

namespace crelish\components;

use yii\base\Component;
use yii\helpers\FileHelper;

class CrelishFileDataProvider extends Component
{

  private $sourceFolder;
  private $allModels;
  private $filter;

  public function __construct($sourceFolder, $settings)
  {

    $this->sourceFolder = $sourceFolder;
    $this->allModels = $this->parseFolderContent($this->sourceFolder);

    parent::__construct(); // TODO: Change the autogenerated stub

  }

  public function parseFolderContent($folder)
  {
    $filesArr = [];
    $allModels = [];

    $fullFolder = \Yii::$app->basePath . DIRECTORY_SEPARATOR . 'workspace' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $folder;

    $files = FileHelper::findFiles($fullFolder);
    if (isset($files[0])) {
      foreach ($files as $file) {
        $filesArr[] = $file;
      }
    }

    foreach ($filesArr as $file) {
      $content = file_get_contents($file);
      $allModels[] = json_decode($content, true);
    }

    return $allModels;
  }

  public function all()
  {

    return $this->allModels;

  }
}