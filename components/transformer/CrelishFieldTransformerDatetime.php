<?php

namespace giantbits\crelish\components\transformer;

/**
 *
 */
class CrelishFieldTransformerDatetime extends CrelishFieldBaseTransformer
{

    /**
     * [transform description]
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public static function beforeSave(&$value)
    {
        if(strpos($value, ".") !== false) {
            $value = (string) strtotime($value);
        }
    }

    public static function afterFind(&$value)
    {
        if (empty($value)) {
            $value = null;
        }
        parent::afterFind($value); // TODO: Change the autogenerated stub
    }
}
