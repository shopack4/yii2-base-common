<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\behaviors;

use Yii;
use yii\db\BaseActiveRecord;
use yii\db\Expression;
use yii\base\Exception;
use yii\behaviors\AttributesBehavior;

class RowDatesAttributesBehavior extends AttributesBehavior
{
	public $createdAtAttribute = null;
	public $createdByAttribute = null;
	public $updatedAtAttribute = null;
	public $updatedByAttribute = null;

	public function init()
	{
		$this->attributes = [];

// if (!empty($this->createdByAttribute))
// {
	// \yii\helpers\VarDumper::dump([
		// $this->createdByAttribute,
		// $this->owner,
		// $this->owner->{$this->createdByAttribute},
	// ]);
	// die();
// }

		if (!empty($this->createdAtAttribute))
			$this->attributes[$this->createdAtAttribute] = [
				BaseActiveRecord::EVENT_BEFORE_INSERT => function($event, $attribute) {
					if (empty($this->owner->getDirtyAttributes()))
						return $this->owner->{$this->createdAtAttribute};

					if (!empty($this->owner->{$this->createdAtAttribute}))
						return $this->owner->{$this->createdAtAttribute};

					return new Expression('NOW()');
				}
			];

		if (!empty($this->createdByAttribute))
			$this->attributes[$this->createdByAttribute] = [
				BaseActiveRecord::EVENT_BEFORE_INSERT => function($event, $attribute) {
					if (empty($this->owner->getDirtyAttributes()))
						return $this->owner->{$this->createdByAttribute};

					if (!empty($this->owner->{$this->createdByAttribute}))
						return $this->owner->{$this->createdByAttribute};

					if (!Yii::$app->user->isGuest)
						return Yii::$app->user->identity->usrID;
					if (!empty($this->owner->{$this->createdByAttribute}))
						return $this->owner->{$this->createdByAttribute};
					return null;
				}
			];

		if (!empty($this->updatedAtAttribute))
			$this->attributes[$this->updatedAtAttribute] = [
				BaseActiveRecord::EVENT_BEFORE_UPDATE => function($event, $attribute) {
					if (empty($this->owner->getDirtyAttributes()))
						return $this->owner->{$this->updatedAtAttribute};

					return new Expression('NOW()');
				}
			];

		if (!empty($this->updatedByAttribute != null)
				&& empty($this->attributes[$this->updatedByAttribute])
			)
			$this->attributes[$this->updatedByAttribute] = [
				BaseActiveRecord::EVENT_BEFORE_UPDATE => function($event, $attribute) {
					if (empty($this->owner->getDirtyAttributes()))
						return $this->owner->{$this->updatedByAttribute};

					if (isset(Yii::$app->user->identity) && !Yii::$app->user->getIsGuest())
						return Yii::$app->user->identity->usrID;
					if (!empty($this->owner->{$this->updatedByAttribute}))
						return $this->owner->{$this->updatedByAttribute};
					return null;
				}
			];

		if (count($this->attributes) == 0)
			throw new Exception("invalid row date parameters.");

		parent::init();
	}

}
