<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\behaviors;

use ReflectionClass;
use Yii;
// use yii\base\Behavior;
use yii\base\Component;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\base\UnknownPropertyException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use shopack\base\helpers\Url;
use yii\validators\Validator;
use yii\web\NotFoundHttpException;
use shopack\base\helpers\Html;
use shopack\base\helpers\ArrayHelper;

/**
 * @property ActiveQuery $multipleProperty
 * @property ActiveQuery $multipleProperties
 */
class MultiplePropertiesBehavior extends \shopack\base\base\Behavior
{
	/**
	 * @var string the name of the multipleProperty table
	 */
	public $foreignTableName;

	/**
	 * @var string the name of multipleProperty model class.
	 */
	public $foreignClassName;

	/**
	 * @var string the name of the foreign key field of the multipleProperty table related to base model table.
	 */
	public $foreignKeyName;

	/**
	 * @var int current primary key
	 */
	private $ownerPrimaryKey;

	/**
	 * @var string current class name
	 */
	private $ownerClassName;

	public function events()
	{
		return [
			ActiveRecord::EVENT_AFTER_FIND      => 'afterFind',
			ActiveRecord::EVENT_AFTER_UPDATE    => 'afterUpdate',
			ActiveRecord::EVENT_AFTER_INSERT    => 'afterInsert',
			ActiveRecord::EVENT_AFTER_DELETE    => 'afterDelete',
			ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
		];
	}

	/**
	 * Attaches the behavior object to the component.
	 * The default implementation will set the [[owner]] property
	 * and attach event handlers as declared in [[events]].
	 * Make sure you call the parent implementation if you override this method.
	 *
	 * @param Component $owner the component that this behavior is to be attached to.
	 *
	 * @throws InvalidConfigException
	 */
	public function attach($owner)
	{
		/** @var ActiveRecord $ownerClassName */
		/** @var ActiveRecord $owner */
		parent::attach($owner);
		if (empty($this->attributes) || !is_array($this->attributes))
		{
			throw new InvalidConfigException('Please specify attributes for the ' . get_class($this) . ' in the ' . get_class($this->owner), 103);
		}
		// if (!$this->foreignClassName)
		// {
			// $this->foreignClassName = get_class($this->owner);
			// if (substr($this->foreignClassName, -strlen('Model')) === 'Model')
				// $this->foreignClassName = substr($this->foreignClassName, 0, -strlen('Model'));
			// $this->foreignClassName .= 'PropertyModel';
		// }
		$this->ownerClassName  = get_class($this->owner);
		$ownerClassName        = $this->ownerClassName;
		$this->ownerPrimaryKey = $ownerClassName::primaryKey()[0];

		if (!$this->foreignTableName)
			$this->foreignTableName = $this->owner->tableName() . '_property';

		if (!$this->foreignKeyName)
			$this->foreignKeyName = 'pid'; //$this->owner->tableName() . '_id';

		// $rules = $owner->rules();
		// $validators = $owner->getValidators();
		// foreach ($rules as $rule)
		// {
			// if ($rule[1] == 'unique')
			// {
				// continue;
			// }
			// $rule_attributes = is_array($rule[0]) ? $rule[0] : [$rule[0]];
			// $attributes      = array_intersect(array_keys($this->attributes), $rule_attributes);
			// if (empty($attributes))
			// {
				// continue;
			// }
			// $rule_attributes = [];
			// foreach ($attributes as $key => $attribute)
			// {
				// foreach ($this->availableLanguages as $language)
				// {
					// $rule_attributes[] = $attribute . "_" . $language;
				// }
			// }
			// $params = array_slice($rule, 2);
			// if ($rule[1] !== 'required')
			// {
				// $validators[] = Validator::createValidator($rule[1], $owner, $rule_attributes, $params);
			// }
			// else
			// {
				// $validators[] = Validator::createValidator('safe', $owner, $rule_attributes, $params);
			// }
		// }
		// if (class_exists($this->foreignClassName))
		// {
			// $multipleProperty = new $this->foreignClassName;
			// foreach ($this->availableLanguages as $language)
			// {
				// foreach (array_keys($this->attributes) as $attribute)
				// {
					// $this->setTranslateAttribute($attribute . "_" . $language, $multipleProperty->$attribute);
					// if ($language == Yii::$app->language)
					// {
						// $this->setTranslateAttribute($attribute, $multipleProperty->$attribute);
					// }
				// }
			// }
		// }
	}

	/**
	 * Relation to model multipleProperties
	 * @return ActiveQuery
	 * @since 2.0.0
	 */
	public function getMultipleProperties()
	{
		return $this->owner->hasMany($this->foreignClassName, [$this->foreignKeyName => $this->ownerPrimaryKey]);
	}

	/**
	 * Relation to model multipleProperty
	 *
	 * @param $language
	 *
	 * @return ActiveQuery
	 * @since 2.0.0
	 */
	// public function getTranslation($language = null)
	// {
		// if ($language == null)
			// $language = $this->currentLanguage;

		// return $this->owner
			// ->hasOne($this->foreignClassName, [$this->foreignKeyName => $this->ownerPrimaryKey])
			// ->where([$this->languageField => $language]);
	// }

	/**
	 * Handle 'beforeValidate' event of the owner.
	 */
	public function beforeValidate()
	{
// die(Html::dump($this->attributes));
		foreach ($this->attributes as $k => $v)
		{
			if (!is_array($v))
				$v = [$v];
			$v = array_filter($v);
			if ($v === [])
				$this->attributes[$k] = null;
		}
// die(Html::dump($this->attributes));

		// foreach (array_keys($this->attributes) as $attribute)
		// {
			// $this->setTranslateAttribute($attribute, $this->getTranslateAttribute($attribute . '_' . $this->currentLanguage));
		// }
	}

	/**
	 * Handle 'afterFind' event of the owner.
	 */
	public function afterFind()
	{
		/*if ($this->owner->isRelationPopulated('multipleProperties'))
		{
			$related = $this->owner->getRelatedRecords()['multipleProperties'];
die(var_dump("afterFind", $related));
		}*/
		if ($this->owner->isRelationPopulated('multipleProperties')
			&& $related = $this->owner->getRelatedRecords()['multipleProperties']
		)
		{
			foreach ($related as $record)
			{
				foreach ($this->attributes as $k => $v)
				{
					if ($v == null)
						$v = [$record->$k];
					else
					{
						if (!is_array($v))
							$v = [$v];
						$v = array_merge($v, [$record->$k]);
					}
					$v = array_filter($v);
					$this->attributes[$k] = (count($v) > 0 ? $v : null);
				}
// die(var_dump($this->attributes, $record));
			}

			// $multipleProperties = $this->indexByLanguage($related);
			// foreach ($this->availableLanguages as $lang)
			// {
				// foreach (array_keys($this->attributes) as $attribute)
				// {
					// foreach ($multipleProperties as $multipleProperty)
					// {
						// if ($multipleProperty->{$this->languageField} == $lang)
						// {
							// $this->setTranslateAttribute($attribute . '_' . $lang, $multipleProperty->$attribute);
							// if ($lang == Yii::$app->language)
							// {
								// $this->setTranslateAttribute($attribute, $multipleProperty->$attribute);
							// }
						// }
					// }
				// }
			// }
		// }
		// else
		// {
			// if (!$this->owner->isRelationPopulated('multipleProperty'))
			// {
				// $this->owner->multipleProperty;
			// }
			// $multipleProperty = $this->owner->getRelatedRecords()['multipleProperty'];
			// if ($multipleProperty)
			// {
				// foreach (array_keys($this->attributes) as $attribute)
				// {
					// $this->owner->setTranslateAttribute($attribute, $multipleProperty->$attribute);
				// }
			// }
		// }
		// foreach (array_keys($this->attributes) as $attribute)
		// {
			// if ($this->owner->hasAttribute($attribute) && $this->getTranslateAttribute($attribute))
			// {
				// $this->owner->setAttribute($attribute, $this->getTranslateAttribute($attribute));
			// }
		}
// die(var_dump($this->attributes));
	}

	/**
	 * Handle 'afterInsert' event of the owner.
	 */
	public function afterInsert()
	{
		$this->saveMultipleProperties();
	}

	/**
	 * Handle 'afterUpdate' event of the owner.
	 */
	public function afterUpdate()
	{
		$this->saveMultipleProperties();
	}

	/**
	 * Handle 'afterDelete' event of the owner.
	 */
	public function afterDelete()
	{
		// if ($this->forceDelete)
		// {
			// $this->owner->unlinkAll('multipleProperties', true);
		// }
	}

	/**
	 * Returns a value indicating whether a property can be read.
	 * A property is readable if:
	 *
	 * - the class has a getter method associated with the specified name
	 *   (in this case, property name is case-insensitive);
	 * - the class has a member variable with the specified name (when `$checkVars` is true);
	 *
	 * @param string  $name      the property name
	 * @param boolean $checkVars whether to treat member variables as properties
	 *
	 * @return boolean whether the property can be read
	 * @see canSetProperty()
	 */
	public function canGetProperty($name, $checkVars = true)
	{
		return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name) || $this->hasMultipleProperty($name) || (new $this->foreignClassName)->canGetProperty($name, $checkVars);
	}

	/**
	 * Returns a value indicating whether a property can be set.
	 * A property is writable if:
	 *
	 * - the class has a setter method associated with the specified name
	 *   (in this case, property name is case-insensitive);
	 * - the class has a member variable with the specified name (when `$checkVars` is true);
	 *
	 * @param string  $name      the property name
	 * @param boolean $checkVars whether to treat member variables as properties
	 *
	 * @return boolean whether the property can be written
	 * @see canGetProperty()
	 */
	public function canSetProperty($name, $checkVars = true)
	{
// die(var_dump('canSetProperty', $name, $checkVars));
		return $this->hasMultipleProperty($name);
	}

	/**
	 * Returns the value of an object property.
	 *
	 * Do not call this method directly as it is a PHP magic method that
	 * will be implicitly called when executing `$value = $object->property;`.
	 *
	 * @param string $name the property name
	 *
	 * @return mixed the property value
	 * @throws UnknownPropertyException if the property is not defined
	 * @throws InvalidCallException if the property is write-only
	 * @see __set()
	 */
	public function __get($name)
	{
		try
		{
			return parent::__get($name);
		}
		catch (UnknownPropertyException $e)
		{
			if ($this->hasMultipleProperty($name))
				return $this->attributes[$name]; //$this->getTranslateAttribute($name);

			$model = new $this->foreignClassName;
			$model->{$this->foreignKeyName} = $this->owner->getPrimaryKey();
			if ($model->canGetProperty($name))
				return $model->$name;

			throw $e;
		}
	}

	/**
	 * Sets value of an object property.
	 *
	 * Do not call this method directly as it is a PHP magic method that
	 * will be implicitly called when executing `$object->property = $value;`.
	 *
	 * @param string $name  the property name or the event name
	 * @param mixed  $value the property value
	 *
	 * @throws UnknownPropertyException if the property is not defined
	 * @throws InvalidCallException if the property is read-only
	 * @see __get()
	 */
	public function __set($name, $value)
	{
		try
		{
			parent::__set($name, $value);
		}
		catch (UnknownPropertyException $e)
		{
			if ($this->hasMultipleProperty($name))
			{
				$this->attributes[$name] = $value; //$this->setTranslateAttribute($name, $value);
			}
			else
			{
				throw $e;
			}
		}
	}

	/**
	 * Checks if a property is set, i.e. defined and not null.
	 *
	 * Do not call this method directly as it is a PHP magic method that
	 * will be implicitly called when executing `isset($object->property)`.
	 *
	 * Note that if the property is not defined, false will be returned.
	 *
	 * @param string $name the property name or the event name
	 *
	 * @return boolean whether the named property is set (not null).
	 * @see http://php.net/manual/en/function.isset.php
	 */
	public function __isset($name)
	{
		if (!parent::__isset($name))
			return $this->hasMultipleProperty($name);

		return true;
	}

	/**
	 * Save related model
	 *
	 * @param array $multipleProperties
	 *
	 * @since 2.0.0
	 * @throws NotFoundHttpException
	 */
	private function saveMultipleProperties()
	{
		$propModels = $this->owner->multipleProperties;
// die(Html::dump($this->attributes, $propModels));
		$x = count($propModels);
		foreach ($this->attributes as $k => $v)
		{
			if ($v === '')
				$v = null;

			//1:sort
			if (!empty($v))
			{
				sort($v);

				//1.1: remove duplicates
				$v = array_unique($v);
			}

			$this->attributes[$k] = $v;

			//2:count
			if (!empty($v) && (count($v) > $x))
				$x = count($v);
		}
// die(Html::dump($this->attributes, $x));

		for ($c=0; $c<$x; $c++)
		{
			if ($c < count($propModels))
				$model = $propModels[$c];
			else
			{
				$model = new $this->foreignClassName;
				$model->{$this->foreignKeyName} = $this->owner->getPrimaryKey();
			}

			$allIsNull = true;
			foreach ($this->attributes as $k => $v)
			{
				$val = null;
				if ($c < count((array)$v))
					$val = $v[$c];
// Html::dump($k, $val);

				$model->$k = $val;
				$allIsNull = ($allIsNull && ($val === null));
			}
			if (!$allIsNull)
			{
// Html::dump($model->isNewRecord);
// Html::dump($model->catattprpID);
// Html::dump($model->formName());
				$done = $model->save();
				if (!$done)
					die(Html::dump(Html::errorSummary($model)));
// die(Html::dump($this->attributes, $x, $this->owner->getPrimaryKey(), $c, $allIsNull));
			}
			else if (!$model->isNewRecord)
			{
				$model->delete();
// die('delete');
			}
		}
// die('fine!');
// die();
	}

	/**
	 * @param $records
	 *
	 * @return array
	 * @since 2.0.0
	 */
	// protected function indexByLanguage($records)
	// {
		// $sorted = [];
		// foreach ($records as $record)
		// {
			// $sorted[$record->{$this->languageField}] = $record;
		// }
		// unset($records);
		// return $sorted;
	// }

	/**
	 * Whether an attribute exists
	 *
	 * @param string $name the name of the attribute
	 *
	 * @return boolean
	 * @since 2.0.0
	 */
	public function hasMultipleProperty($name)
	{
		return array_key_exists($name, $this->attributes);
	}

	public function attributeLabels()
	{
		$model = new $this->foreignClassName;
		return $model->attributeLabels();
	}

	public function attributeHints()
	{
		$model = new $this->foreignClassName;
		return $model->attributeHints();
	}

	/**
	 * @param string $name  the name of the attribute
	 * @param string $value the value of the attribute
	 *
	 * @since 2.0.0
	 */
	// public function setTranslateAttribute($name, $value)
	// {
		// $this->attributes[$name] = $value;
	// }

	/**
	 * @param string $name the name of the attribute
	 *
	 * @param null   $language
	 *
	 * @return string the attribute value
	 * @since 2.0.0
	 */
	// public function getTranslateAttribute($name, $language = null)
	// {
		// if ($language !== null)
		// {
			// $attribute = $name . '_' . $language;
		// }
		// else
		// {
			// $attribute = $name;
		// }
		// return $this->hasMultipleProperty($attribute) ? $this->attributes[$attribute] : null;
	// }

}
