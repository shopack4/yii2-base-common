<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\behaviors;

use Yii;
use Closure;
use yii\base\Behavior;
use yii\base\Event;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord; //shopack\multilanguage\db\ActiveRecord;

class MultiAttributeBehavior extends AttributeBehavior
{
	/**
	 * Evaluates the attribute value and assigns it to the current attributes.
	 * @param Event $event
	 */
	public function evaluateAttributes($event)
	{
		if ($this->skipUpdateOnClean
			&& $event->name == ActiveRecord::EVENT_BEFORE_UPDATE
			&& empty($this->owner->dirtyAttributes)
		) {
			return;
		}

		if (!empty($this->attributes[$event->name])) {
			$attributes = (array) $this->attributes[$event->name];
			foreach ($attributes as $attribute) {
				$value = $this->getAttributeValue($event, $attribute);
				// ignore attribute names which are not string (e.g. when set by TimestampBehavior::updatedAtAttribute)
				if (is_string($attribute)) {
					$this->owner->$attribute = $value;
				}
			}
		}
	}

	/**
	 * Returns the value for the current attributes.
	 * This method is called by [[evaluateAttributes()]]. Its return value will be assigned
	 * to the attributes corresponding to the triggering event.
	 * @param Event $event the event that triggers the current attribute updating.
	 * @return mixed the attribute value
	 */
	protected function getAttributeValue($event, $attribute)
	{
		if ($this->value instanceof Closure || is_array($this->value) && is_callable($this->value)) {
			return call_user_func($this->value, $event, $attribute);
		}

		return (($this->value !== null) && is_array($this->value) && isset($this->value[$attribute])
			? $this->value[$attribute]
			: null);
	}
}
