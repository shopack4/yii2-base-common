<?php

namespace shopack\base\common\rest;

use shopack\base\common\base\BaseEnum;

abstract class enuColumnInfo extends BaseEnum
{
	const type       = 'type';
	const validator  = 'validator';
	const default    = 'default';
	const required   = 'required';
	const selectable = 'selectable';
	const virtual    = 'virtual';
	const search     = 'searchl';

	public static $messageCategory = 'aaa';

	public static $list = [
		self::type       => 'type',
		self::validator  => 'validator',
		self::default    => 'default',
		self::required   => 'required',
		self::selectable => 'selectable',
		self::virtual    => 'virtual',
		self::search     => 'search',
	];

};
