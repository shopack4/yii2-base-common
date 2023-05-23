<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\web;

use shopack\base\common\base\ApplicationInstanceIDTrait;

class Application extends \yii\web\Application
{
	use ApplicationInstanceIDTrait;

	public $isJustForMe = true;

}
