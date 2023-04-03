<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\rest;

interface ActiveRecordInterface {
	public static function columnsInfo();
	public function primaryKeyValue();
}
