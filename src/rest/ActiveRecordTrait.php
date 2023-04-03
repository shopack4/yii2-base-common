<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\rest;

use Yii;
use shopack\base\common\rest\enuColumnInfo;
use shopack\base\common\helpers\StringHelper;

trait ActiveRecordTrait
{
	public static function canViewColumn($column)
	{
		$columnsInfo = static::columnsInfo();
		if (empty($columnsInfo[$column]))
			return false;

		return self::_canViewColumn($column, $columnsInfo[$column]);
	}

	private static function _canViewColumn($column, $columnInfo)
	{
		if (isset($columnInfo[enuColumnInfo::selectable])) {
			if (is_array($columnInfo[enuColumnInfo::selectable])) {
				foreach ($columnInfo[enuColumnInfo::selectable] as $perm) {
					$p = (array)$perm;
					if (Yii::$app->user->hasPriv($p[0], $p[1] ?? '1')) {
						return true;
					}
				}
			} else if (is_bool($columnInfo[enuColumnInfo::selectable])
					&& $columnInfo[enuColumnInfo::selectable]) {
				return true;
			}
		}
		return false;
	}

	protected static $_selectableColumns = null;
	public static function selectableColumns($prfix = null)
  {
		$_class = get_called_class();

		if (empty(self::$_selectableColumns[$_class])) {
			$columns = [];

			$columnsInfo = static::columnsInfo();
			foreach ($columnsInfo as $column => $info) {
				if (self::_canViewColumn($column, $info)) {
					$columns[] = $column;
				}
			}

			self::$_selectableColumns[$_class] = $columns;
		}

		if (empty($prfix))
			return self::$_selectableColumns[$_class];

		$columns = [];
		foreach (self::$_selectableColumns[$_class] as $column) {
			$columns[] = $prfix . '.' . $column;
		}
		return $columns;
  }

	protected static $_rules = null;
  public function rules()
  {
		$_class = get_called_class();
		$isSearchModel = str_ends_with($_class, 'SearchModel');

		if (empty(self::$_rules[$_class])) {
			$rules = [];

			$columnsInfo = static::columnsInfo();
			foreach ($columnsInfo as $column => $info) {
				// if (isset($info[enuColumnInfo::virtual]) && $info[enuColumnInfo::virtual])
				// 	continue;

				if ($isSearchModel) {
					if (isset($info[enuColumnInfo::search])) {
						if ($info[enuColumnInfo::search] !== false) {
							// if (is_bool($info[enuColumnInfo::search])) {
								if (isset($info[enuColumnInfo::type]))
									$rule = array_merge([$column], (array)$info[enuColumnInfo::type]);
								else
									$rule = [$column, 'safe'];
							// } else {
							// 	$rule = array_merge([$column], (array)$info[enuColumnInfo::search]);
							// }
							$rules[] = $rule;
						}
					}
				} else {
					if (isset($info[enuColumnInfo::type])) {
						$rule = array_merge([$column], (array)$info[enuColumnInfo::type]);
						$rules[] = $rule;
					}

					if (isset($info[enuColumnInfo::validator])) {
						$rule = array_merge([$column], (array)$info[enuColumnInfo::validator]);
						$rules[] = $rule;
					}

					if (isset($info[enuColumnInfo::default])) {
						$rule = [
							$column,
							'default',
							'value' => $info[enuColumnInfo::default]
						];
						$rules[] = $rule;
					}

					if (isset($info[enuColumnInfo::required]) && $info[enuColumnInfo::required]) {
						$rule = [
							$column,
							'required'
						];
						$rules[] = $rule;
					}
				}
			}

			if ($isSearchModel == false) {
				if (method_exists($this, 'traitExtraRules'))
					$rules = array_merge_recursive($rules, $this->traitExtraRules());
			}

			if (method_exists($this, 'extraRules'))
				$rules = array_merge_recursive($rules, $this->extraRules());

			self::$_rules[$_class] = $rules;
		}

		return self::$_rules[$_class];
	}

	protected function processStringColumns()
	{
		$columnsInfo = static::columnsInfo();
		foreach ($columnsInfo as $column => $info) {
			// if (empty($info[enuColumnInfo::type]))
			// 	continue;

			// if (((array)$info[enuColumnInfo::type])[0] != 'string')
			// 	continue;

			if (is_string($this->$column))
				$this->$column = trim($this->$column);

			if (($this->$column === '') &&
					(empty($info[enuColumnInfo::required]) || !$info[enuColumnInfo::required])
			) {
				$this->$column = null;
			}

			if (is_string($this->$column) && (empty($this->$column) == false)) {
				$this->$column = StringHelper::fixPersianCharacters($this->$column);
			}

		}
	}

	public function beforeSave($insert)
  {
		$this->processStringColumns();
		return parent::beforeSave($insert);
  }

}
