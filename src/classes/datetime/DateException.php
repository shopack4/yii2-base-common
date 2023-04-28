<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\classes\datetime;

class DateException extends \Exception
{
	public function __construct($message)
	{
		parent::__construct($message);
	}
}
