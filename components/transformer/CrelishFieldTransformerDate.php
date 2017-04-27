<?php

namespace giantbits\crelish\components\transformer;

/**
 *
 */
class CrelishFieldTransformerDate extends CrelishFieldBaseTransformer
{

    /**
     * [transform description]
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public static function beforeSave(&$value)
    {

        if(((string) (int) $value === $value)
            && ($value <= PHP_INT_MAX)
            && ($value >= ~PHP_INT_MAX)) {
            $value = (string) $value;
        } else {
            $value = (string) strtotime($value);
        }
    }

    public static function afterFind(&$value)
    {
        if(empty($value)){
           $value = null;
        }
        parent::afterFind($value); // TODO: Change the autogenerated stub
    }

}
