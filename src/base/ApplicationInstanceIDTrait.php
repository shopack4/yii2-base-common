<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\base;

use Yii;

trait ApplicationInstanceIDTrait
{
	public function getInstanceID()
	{
		$path = Yii::getAlias('@app') . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'params-local.json';
		$content = [];
		if (file_exists($path))
			$content = json_decode(file_get_contents($path), true);

		$instanceID = $content['instanceID'] ?? null;
		if (empty($instanceID)) {
			$instanceID = Yii::$app->id . '-' . uniqid(true);
			$content['instanceID'] = $instanceID;
			file_put_contents($path, json_encode($content, JSON_PRETTY_PRINT));
		}

		return $instanceID;
	}

}
