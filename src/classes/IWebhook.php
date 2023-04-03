<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\classes;

// use Yii;
// use yii\helpers\Inflector;
// use shopack\base\helpers\ArrayHelper;

interface IWebhook
{
	public function getWebhookCommands();
	public function callWebhook($command);
}
