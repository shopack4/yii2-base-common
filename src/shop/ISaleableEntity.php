<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\shop;

//applies to the saleable model
interface ISaleableEntity
{
	public static function saleableKey();
	public static function addToBasket($basketdata, $saleableID = null);

}
