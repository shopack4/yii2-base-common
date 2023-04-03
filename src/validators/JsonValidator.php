<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\validators;

use Yii;
use yii\validators\Validator;

class JsonValidator extends Validator
{
  public function init()
  {
    parent::init();
    if ($this->message === null)
      $this->message = Yii::t('yii', '{attribute} must be an array (json).');
  }

  public function validateAttribute($model, $attribute)
  {
    $value = $model->$attribute;

    if (is_array($value) == false)
      $this->addError($model, $attribute, $this->message);
  }

  protected function validateValue($value)
  {
    if (is_array($value) == false)
      return [Yii::t('yii', $this->message), []];

    return null;
  }

}
