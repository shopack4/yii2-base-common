<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\models;

use shopack\base\common\enums\enuModelScenario;

trait BasketModelTrait
{
	public $OwnerUserID;
	public $Items;
	public $VoucherID;
	public $Amount;
	public $Desc;
	public $Sign;

	public function primaryKeyValue() {
		return null;
	}

	public static function columnsInfo() {
		return [];
	}

	public function loadFromBasketData($base64basketdata)
	{
		$basketdata = base64_decode($base64basketdata);

	}

}
