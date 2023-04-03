<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\classes;

// use Yii;

class WebhookEvent extends \shopack\base\base\Event
{
	public $command;
	public $rawBody;
}
