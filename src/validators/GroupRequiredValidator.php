<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\i18n\PhpMessageSource;
use yii\validators\Validator;

class GroupRequiredValidator extends Validator
{
	/**
	 * @var integer the minimun required quantity of attributes that must to be filled.
	 * Defaults to 1.
	 */
	public $min = 1;
	/**
	 * @var string|array the list of attributes that should receive the error message. Required.
	 */
	public $in;
	/**
	 * @inheritdoc
	 */
	public $skipOnEmpty = false;
	/**
	 * @inheritdoc
	 */
	public $skipOnError = false;
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->in === null) {
			throw new InvalidConfigException('The `in` parameter is required.');
		} elseif (! is_array($this->in) && count(preg_split('/\s*,\s*/', $this->in, -1, PREG_SPLIT_NO_EMPTY)) <= 1) {
			throw new InvalidConfigException('The `in` parameter must have at least 2 attributes.');
		}

		if (!isset(Yii::$app->get('i18n')->translations['message*'])) {
			Yii::$app->get('i18n')->translations['message*'] = [
			'class' => PhpMessageSource::class,
			'basePath' => __DIR__ . '/messages',
			'sourceLanguage' => 'en-US'
			];
		}

		if ($this->message === null) {
			$this->message = Yii::t('app', 'You must fill at least {min} of the attributes {attributes}.');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($model, $attribute)
	{
		$attributes = is_array($this->in) ? $this->in : preg_split('/\s*,\s*/', $this->in, -1, PREG_SPLIT_NO_EMPTY);
		$chosen = 0;
		foreach ($attributes as $attributeName) {
			$value = $model->$attributeName;
			$attributesListLabels[] = '"' . $model->getAttributeLabel($attributeName). '"';
			$chosen += !empty($value) ? 1 : 0;
		}
		if (!$chosen || $chosen < $this->min) {
			$attributesList = implode(', ', $attributesListLabels);
			$message = strtr($this->message, [
				'{min}' => $this->min,
				'{attributes}' => $attributesList,
			]);
			$model->addError($attribute, $message);
		}
	}
	/**
	 * @inheritdoc
	 * @since: 1.1
	 */
	public function clientValidateAttribute($model, $attribute, $view)
	{
		$attributes = is_array($this->in) ? $this->in : preg_split('/\s*,\s*/', $this->in, -1, PREG_SPLIT_NO_EMPTY);
		// $attributes = array_map('strtolower', $attributes); // yii lowercases attributes
		$attributesJson = json_encode(array_map('strtolower', $attributes));
		$attributesLabels = [];
		foreach ($attributes as $attr) {
			$attributesLabels[] = '"' . $model->getAttributeLabel($attr) . '"';
		}
		$message = strtr($this->message, [
			'{min}' => ($this->min == 1 ? 'یکی' : $this->min . ' عدد'),
			'{attributes}' => implode(Yii::t('app', ' or '), $attributesLabels),
		]);
		$form = strtolower($model->formName());
		return <<<JS
function groupRequiredValidator() {
	var atributes = $attributesJson;
	var formName = '$form';
	var chosen = 0;
	$.each(atributes, function(key, attr){
		var obj = $('#' + formName + '-' + attr);
		var val = obj.val();
		chosen += val ? 1 : 0;
	});
	if (!chosen || chosen < $this->min) {
		messages.push('$message');
	} else {
		$.each(atributes, function(key, attr){
			var attrId = formName + '-' + attr;
			\$form.yiiActiveForm('updateAttribute', attrId, '');
		});
	}
}
groupRequiredValidator();
JS;
	}
}
