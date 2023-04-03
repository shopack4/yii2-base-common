<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\classes;

use Yii;
// use shopack\base\helpers\ArrayHelper;

trait WebhookTrait
{
	public static $PARAM_ALLOWED_CALLER_IP	= 'allowedCallerIP';
	public static $PARAM_ALLOWED_CALLER_URL	= 'allowedCallerUrl';

	public function webhookTraitParameters()
	{
		return [
			[
				'id' => self::$PARAM_ALLOWED_CALLER_IP,
				'type' => 'string',
				'mandatory' => 0,
				'label' => 'Allowed Caller IPs',
				'style' => 'direction:ltr',
			],
			[
				'id' => self::$PARAM_ALLOWED_CALLER_URL,
				'type' => 'string',
				'mandatory' => 0,
				'label' => 'Allowed Caller URLs',
				'style' => 'direction:ltr',
			],
		];
	}

	//$this->extensionModel->gtwPluginParameters
	public function validateCaller($pluginParameters)
	{
		//check ip
		if (!empty($pluginParameters[self::$PARAM_ALLOWED_CALLER_IP]))
		{
			if (Yii::$app->request->remoteIP === null)
				return [false, "Caller IP not provided."];

			$ips = explode(";", $pluginParameters[self::$PARAM_ALLOWED_CALLER_IP]);
			$found = false;
			foreach ($ips as $ip)
			{
				if (Yii::$app->request->remoteIP == $ip)
				{
					$found = true;
					break;
				}
			}
			if (!$found)
				return [false, "Caller IP (" . Yii::$app->request->remoteIP . ") not allowed."];
		}

		//check referrer
		if (!empty($pluginParameters[self::$PARAM_ALLOWED_CALLER_URL]))
		{
			if (Yii::$app->request->referrer === null)
				return [false, "Caller Url not provided."];

			$urls = explode(";", $pluginParameters[self::$PARAM_ALLOWED_CALLER_URL]);
			$found = false;
			foreach ($urls as $url)
			{
				if (Yii::$app->request->referrer == $url)
				{
					$found = true;
					break;
				}
			}
			if (!$found)
				return [false, "Caller Url (" . Yii::$app->request->referrer . ") not allowed."];
		}

		return true;
	}
}
