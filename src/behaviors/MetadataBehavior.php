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
use shopack\base\db\MetadataActiveRecordTrait;

/**
 * @property ActiveQuery $metadata
 * @property ActiveQuery $metadatas
 */
class MetadataBehavior extends \shopack\base\base\Behavior
{
	/**
	 * @var string the name of the metadata table
	 */
	public $foreignTableName;

	/**
	 * @var string the name of metadata model class.
	 */
	public $foreignClassName;

	/**
	 * @var string the name of the foreign key field of the metadata table related to base model table.
	 */
	public $foreignKeyName;

	public $keyPrefix;
	public $uniqueAttributes; //برای جستجوی تغییرات هنگام ذخیره از این کلیدها استفاده میشود

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

	public function init()
	{
		parent::init();

		if (empty($this->keyPrefix))
		{
			throw new InvalidConfigException('Please specify keyPrefix for the ' . get_class($this) . ' in the ' . get_class($this->owner), 103);
		}

		array_unshift($this->attributes, "{$this->keyPrefix}ValueNumber");
		array_unshift($this->attributes, "{$this->keyPrefix}ValueString");

		// array_unshift($this->uniqueAttributes, "{$this->keyPrefix}Key");
		array_unshift($this->uniqueAttributes, "{$this->keyPrefix}ValueNumber");
		// array_unshift($this->uniqueAttributes, "{$this->keyPrefix}ValueString");
	}

	public function attach($owner)
	{
		parent::attach($owner);

		if (empty($this->attributes) || !is_array($this->attributes))
		{
			throw new InvalidConfigException('Please specify attributes for the ' . get_class($this) . ' in the ' . get_class($this->owner), 103);
		}

		$this->ownerClassName  = get_class($this->owner);
		$ownerClassName        = $this->ownerClassName;
		$this->ownerPrimaryKey = $ownerClassName::primaryKey()[0];

		if (!$this->foreignTableName)
			$this->foreignTableName = $this->owner->tableName() . '_meta';

		if (!$this->foreignKeyName)
			$this->foreignKeyName = 'pid'; //$this->owner->tableName() . '_id';
	}

	/**
	 * Relation to model metadatas
	 * @return ActiveQuery
	 * @since 2.0.0
	 */
	public function getMetadatas()
	{
		return $this->owner->hasMany($this->foreignClassName, [$this->foreignKeyName => $this->ownerPrimaryKey]);
	}

	/**
	 * Handle 'beforeValidate' event of the owner.
	 */
	public function beforeValidate()
	{
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
		if (!$this->owner->isRelationPopulated('metadatas'))
			return;

		$related = $this->owner->getRelatedRecords()['metadatas'];
		foreach ($related as $metadataModel)
		{
			$field = "{$this->keyPrefix}ID";
			$metadataValue = [
				$field => $metadataModel->$field,
			];
			foreach ($this->attributes as $attr)
			{
				if (!empty($metadataModel->$attr))
					$metadataValue[$attr] = $metadataModel->$attr;
			}
			$field = "{$this->keyPrefix}Key";
			$this->addMetadata($metadataModel->$field, $metadataValue);
		}
// die(Html::dump($this->_internalStorage));
	}

	/**
	 * Handle 'afterInsert' event of the owner.
	 */
	public function afterInsert()
	{
		$this->saveMetadatas();
	}

	/**
	 * Handle 'afterUpdate' event of the owner.
	 */
	public function afterUpdate()
	{
		$this->saveMetadatas();
	}

	/**
	 * Handle 'afterDelete' event of the owner.
	 */
	public function afterDelete()
	{
		// if ($this->forceDelete)
		// {
			// $this->owner->unlinkAll('metadatas', true);
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
		return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name) || $this->hasMetadata($name) || (new $this->foreignClassName)->canGetProperty($name, $checkVars);
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
		return $this->hasMetadata($name);
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
			if ($this->hasMetadata($name))
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
			if ($this->hasMetadata($name))
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
			return $this->hasMetadata($name);

		return true;
	}

	private function processOneMetadataForSave($storageKey, &$storageValue, &$metadataModels)
	{
		if (empty($storageValue["{$this->keyPrefix}ID"]))
		{
			//find ID from $metadataModels
			$found = ArrayHelper::findFirst($metadataModels, function($item) use (/*$this, */$storageKey, $storageValue) {
				//1: check key
				$field = "{$this->keyPrefix}Key";
				if ($item->$field != $storageKey)
					return false;

				//2: compare all values in uniqueAttributes
				foreach ($this->uniqueAttributes as $uqatr)
				{
					$val = ($storageValue[$uqatr] ?? null);
					if ($item->$uqatr != $val)
						return false;
				}

				//3: check if this id is not belongs to other storage items
				$field = "{$this->keyPrefix}ID";
				$ID = $item->$field;
				$found = ArrayHelper::findFirst($this->_internalStorage, function($item) use ($field, $ID) {
					return (isset($item[$field]) && ($item[$field] == $ID));
				},
				function($item) { //$checkRecursiveCallback
					return ArrayHelper::isIndexed($item);
				});
				if ($found)
					return false;

				//OK: found
				return true;
			},
			function($item) { //$checkRecursiveCallback
				return ArrayHelper::isIndexed($item);
			});
			if ($found)
			{
				$field = "{$this->keyPrefix}ID";
				$storageValue["{$this->keyPrefix}ID"] = $found->$field;
			}
		} //if (empty($storageValue["{$this->keyPrefix}ID"]))

		//still id is empty
		if (empty($storageValue["{$this->keyPrefix}ID"]))
		{
			$metadataModel = new $this->foreignClassName();
			$metadataModel->{$this->foreignKeyName} = $this->owner->{$this->ownerPrimaryKey};
			$field = "{$this->keyPrefix}Key";
				$metadataModel->$field = $storageKey; //$storageValue[$field];
// die(Html::dump($this->attributes, $storageKey, $storageValue));
			$count = 0;
			foreach ($this->attributes as $v)
			{
				if (isset($storageValue[$v]))
				{
					$metadataModel->$v = $storageValue[$v];
					++$count;
				}
			}
			if ($count == 0)
			{
				//!!!!!!!!!!!!!!!!!!!!!
				throw new \Exception('hah (1)!!!');
			}
			// $metadataModel->modificationState = MetadataActiveRecordTrait::MODIFICATIONSTATE_INSERT;
			$metadataModels[] = $metadataModel;
		}
		else
		{
			$field = "{$this->keyPrefix}ID";
			$ID = $storageValue[$field];
			$metadataModel = ArrayHelper::findFirst($metadataModels, function($item) use ($field, $ID) {
				return ($item->$field == $ID);
			},
			function($item) { //$checkRecursiveCallback
				return ArrayHelper::isIndexed($item);
			});
			if (!$metadataModel)
			{
				//!!!!!!!!!!!!!!!!!!!!!
				throw new \Exception('hah (2)!!!');
			}
// Html::dump($storageValue, $this->attributes);
// die();
			foreach ($this->attributes as $k => $v)
			{
				if (isset($storageValue[$v]) && ($metadataModel->$v != $storageValue[$v]))
					$metadataModel->$v = $storageValue[$v];

				// if ($metadataModel->$k != $v)
					// $metadataModel->$k = $v;
			}
		}
	}
	private function saveMetadatas()
	{
		$metadataModels = $this->owner->metadatas;

		foreach ($this->_internalStorage as $storageKey => &$storageValues)
		{
			if (ArrayHelper::isIndexed($storageValues))
			{
				foreach ($storageValues as &$storageValue)
					$this->processOneMetadataForSave($storageKey, $storageValue, $metadataModels);
			}
			else
			{
				$this->processOneMetadataForSave($storageKey, $storageValues, $metadataModels);
			}
		} //foreach ($this->_internalStorage as $storageKey => &$storageValues)
// die(Html::dump(count($metadataModels)));

		//2: do actions (insert, update, delete)
		foreach ($metadataModels as $modelKey => $metadataModel)
		{
			$field = "{$this->keyPrefix}ID";
			//check if id is empty (new record -> insert)
			if ($metadataModel->isNewRecord)
			{
				$metadataModel->save();
			}
			else
			{
				//2: search id in storage
				$ID = $metadataModel->$field;
				$found = ArrayHelper::findFirst($this->_internalStorage, function($item) use ($field, $ID) {
					return ($item[$field] == $ID);
				},
				function($item) { //$checkRecursiveCallback
					return ArrayHelper::isIndexed($item);
				});

				if ($found)
				{
					//3: update
					if (!empty($metadataModel->getDirtyAttributes()))
						$metadataModel->save();
				}
				else
				{
					//4: delete
					$metadataModel->delete();
					unset($metadataModels[$modelKey]);
				}
			}
		}
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
	public function hasMetadata($name)
	{
		return array_key_exists($name, $this->attributes);
	}

	public function attributeLabels()
	{
		$model = new $this->foreignClassName;
		return $model->attributeLabels();
	}

	private $_internalStorage = [];

	/**
	 * $key : string
	 * $value : num|string|array
	 */
	public function addMetadata($key, $value)
	{
		if (empty($key))
			throw new InvalidConfigException("key is empty");

		if (is_array($value))
		{
			// if (!isset($value["{$this->keyPrefix}ID"]))
				// $value = array_merge(["{$this->keyPrefix}ID" => null], $value);

			if (isset($this->_internalStorage[$key]))
			{
				if (!ArrayHelper::isIndexed($this->_internalStorage[$key]))
					$this->_internalStorage[$key] = [$this->_internalStorage[$key]];
				array_push($this->_internalStorage[$key], $value);
			}
			else
				$this->_internalStorage[$key] = $value;
		}
		elseif (is_numeric($value))
			$this->addMetadataNumber($key, $value);
		else
			$this->addMetadataString($key, $value);
	}
	public function addMetadataNumber($key, $value)
	{
		$this->addMetadata($key, ["{$this->keyPrefix}ValueNumber" => $value]);
	}
	public function addMetadataString($key, $value)
	{
		$this->addMetadata($key, ["{$this->keyPrefix}ValueString" => $value]);
	}

	public function getMetadata()
	{
		return $this->_internalStorage;
	}

	public function clearMetadata()
	{
		$this->_internalStorage = [];
	}

	/**
	 * $values : array
	 */
	public function setMetadata($value, $parentKey=null)
	{
		if (empty($value))
			return;

		if (is_array($value))
		{
			if (ArrayHelper::isIndexed($value))
			{
				foreach ($value as $val)
					$this->setMetadata($val, $parentKey);
			}
			else //non-indexed
			{
				$firstKey = array_keys($value)[0];
				if (in_array($firstKey, $this->attributes))
					$this->addMetadata($parentKey, $value);
				else
				{
					foreach ($value as $key => $val)
					{
						if (empty($parentKey))
							$this->setMetadata($val, $key);
						else
							$this->setMetadata($val, $parentKey . '.' . $key);
					}
				}
			}
		}
		else
			$this->addMetadata($parentKey, $value);
	}

}
