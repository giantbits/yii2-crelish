<?php
/**
 * Created by PhpStorm.
 * User: myrst
 * Date: 12.04.2017
 * Time: 21:52
 */

namespace giantbits\crelish\components;

use Underscore\Types\Arrays;
use yii\base\Component;

class CrelishBaseContentProcessor extends Component
{
  public $data;

  public static function processData($key, $data, &$processedData)
  {
    $processedData = $processedData;
  }

  public static function processJson($key, $data, &$processedData)
  {
    $processedData = $processedData;
  }

  public static function processContent($ctype, $data)
  {
    $processedData = [];

    $elementDefinition = CrelishDynamicJsonModel::loadElementDefinition($ctype);

    if ($data) {

      foreach ($data as $key => $content) {

        $fieldType = Arrays::find($elementDefinition->fields, function ($def) use ($key) {
          return $def->key == $key;
        });

        $transform = NULL;
        if (!empty($fieldType) && is_object($fieldType)) {
          $fieldType = (property_exists($fieldType, 'type')) ? $fieldType->type : 'textInput';
          $transform = (property_exists($fieldType, 'transform')) ? $fieldType->transform : null;
        }

        if (!empty($fieldType)) {

          // Get processor class.
          $processorClass = 'giantbits\crelish\plugins\\' . strtolower($fieldType) . '\\' . ucfirst($fieldType) . 'ContentProcessor';
          $transformClass = 'giantbits\crelish\components\transformer\CrelishFieldTransformer' . ucfirst($transform);

          if (strpos($fieldType, "widget_") !== false) {
            $processorClass = str_replace("widget_", "", $fieldType) . 'ContentProcessor';
          }

          if (class_exists($processorClass)) {
            $processorClass::processData($key, $content, $processedData);
          } else {
            $processedData[$key] = $content;
          }
        }

        if (!empty($transform) && class_exists($transformClass)) {
          $transformClass::afterFind($processedData[$key]);
        }
      }
    }
    return $processedData;
  }

  public static function processElement($ctype, $data)
  {
    $processedData = [];
    $elementDefinition = CrelishDynamicJsonModel::loadElementDefinition($ctype);

    if ($data) {
      foreach ($data as $attr => $value) {
        CrelishBaseContentProcessor::processFieldData($elementDefinition, $attr, $value, $processedData);
      }
    }
    return $processedData;
  }

  public static function processFieldData($elementDefinition, $attr, $value, &$finalArr)
  {
    $fieldType = 'textInput';

    // Get type of field.
    $field = Arrays::find($elementDefinition->fields, function ($value) use ($attr) {
      return $value->key == $attr;
    });

    $transform = NULL;
    if (!empty($field) && is_object($field)) {
      $fieldType = (property_exists($field, 'type')) ? $field->type : 'textInput';
      $transform = (property_exists($field, 'transform')) ? $field->transform : null;
    }

    // Get processor class.
    $processorClass = 'giantbits\crelish\plugins\\' . strtolower($fieldType) . '\\' . ucfirst($fieldType) . 'ContentProcessor';
    $transformClass = 'giantbits\crelish\components\transformer\CrelishFieldTransformer' . ucfirst($transform);

    if (strpos($fieldType, "widget_") !== FALSE) {
      $processorClass = str_replace("widget_", "", $fieldType) . 'ContentProcessor';
    }

    if (class_exists($processorClass) && method_exists($processorClass, 'processJson')) {
      $processorClass::processJson($attr, $value, $finalArr);
    } else {
      $finalArr[$attr] = $value;
    }

    if (!empty($transform) && class_exists($transformClass)) {
      $transformClass::afterFind($finalArr[$attr]);
    }

  }
}
