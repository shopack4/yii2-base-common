<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\shop;

trait ShopModuleTrait
{
	public $registeredSaleables = [];

	public function registerSaleable($saleableModuleClass, $assetModuleClass)
	{
		if (str_starts_with($saleableModuleClass, '\\') == false)
			$saleableModuleClass = '\\' . $saleableModuleClass;

		if (str_starts_with($assetModuleClass, '\\') == false)
			$assetModuleClass = '\\' . $assetModuleClass;

		$this->registeredSaleables[$saleableModuleClass::saleableKey()] = [
			'saleable' => $saleableModuleClass,
			'asset' => $assetModuleClass,
		];
	}

}
