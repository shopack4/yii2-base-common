<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\base;

use Yii;

class BaseModule extends \yii\base\Module
{
	public function init()
	{
		parent::init();

		$class = get_called_class();
		$reflector = new \ReflectionClass($class);

		$dir = dirname($reflector->getFileName());
		Yii::setAlias('@' . $this->id, $dir);

		$commonDir = dirname($dir) . DIRECTORY_SEPARATOR . 'common';
		if (is_dir($commonDir))
			Yii::setAlias('@' . $this->id . '/common', $commonDir);
		else
			$commonDir = null;

		$this->initI18N($dir, $commonDir);

		$namespace = $reflector->getNamespaceName();
		$this->registerExtensions($dir, $namespace);
	}

	private function initI18N($dir, $commonDir)
	{
		$msgRoot = '@' . $this->id;
		if (!is_dir($dir . DIRECTORY_SEPARATOR . 'messages')) {
			if (empty($commonDir))
				return;

			if (!is_dir($commonDir . '/messages'))
				return;

			$msgRoot .= '/common';
		}

		$translation = [
			'class' => 'yii\i18n\PhpMessageSource',
			'basePath' => $msgRoot . '/messages',
			'sourceLanguage' => 'en_US',
			'forceTranslation' => true,
			'fileMap' => [
				$this->id => $this->id . '.php',
			],
		];

		Yii::$app->i18n->translations[$this->id] = $translation;
	}

	protected $_extensionList = [];
	protected $_extensionClassList = [];
	private function registerExtensions($dir, $baseNamespace)
	{
		$basePath = $dir . DIRECTORY_SEPARATOR . 'extensions';
		if (!is_dir($basePath))
			return;

		$categories = dir($basePath);
		while (false !== ($category = $categories->read())) {
			if ($category == '.' || $category == '..')
				continue;

			$types = dir($basePath . DIRECTORY_SEPARATOR . $category);
			while (false !== ($type = $types->read())) {
				if ($type == '.' || $type == '..')
					continue;

				$files = glob($basePath
					. DIRECTORY_SEPARATOR . $category
					. DIRECTORY_SEPARATOR . $type
					. DIRECTORY_SEPARATOR . '*.php');

				foreach ($files as $file) {
					if ($file == '.' || $file == '..')
						continue;

					$clsName = pathinfo($file, PATHINFO_FILENAME);

					if (isset($this->_extensionList[$category][$type][$clsName]))
						throw new \Exception('extension already registered');

					$fullClsName = $baseNamespace . "\\extensions\\" . $category . "\\" . $type . "\\" . $clsName;
					require_once ($file);
					$cls = new $fullClsName();
					if ($cls->isEnable()) {
						$this->_extensionClassList = array_replace_recursive($this->_extensionClassList, [
							$category => [
								// $type => [
									$clsName => [
										'class' => $cls,
									],
								// ],
							],
						]);

						$this->_extensionList = array_replace_recursive($this->_extensionList, [
							$category => [
								$type => [
									$clsName => [
										'title'        => $cls->getTitle(),
										'params'       => $cls->getParametersSchema(),
										'restrictions' => $cls->getRestrictionsSchema(),
										'usages'       => $cls->getUsagesSchema(),
										'webhooks'     => ($cls instanceof \shopack\base\common\classes\IWebhook ? $cls->getWebhookCommands() : null),
									],
								],
							],
						]);
					}
				}
			}
		}
	}

	public function ExtensionList($category, $type = null)
	{
		if (empty($this->_extensionList[$category]))
			return [];

		if (isset($type) && empty($this->_extensionList[$category][$type]))
			return [];

		if (isset($type))
			return $this->_extensionList[$category][$type];

		return $this->_extensionList[$category];
	}

	public function ExtensionParamsSchema($category, $key)
	{
		if (empty($this->_extensionList[$category]))
			return [];

		foreach ($this->_extensionList[$category] as $k => $v) {
			if (isset($v[$key])) {
				return $v[$key]['params'];
			}
		}

		return [];
	}

	public function ExtensionRestrictionsSchema($category, $key)
	{
		if (empty($this->_extensionList[$category]))
			return [];

		foreach ($this->_extensionList[$category] as $k => $v) {
			if (isset($v[$key])) {
				return $v[$key]['restrictions'];
			}
		}

		return [];
	}

	public function ExtensionUsagesSchema($category, $key)
	{
		if (empty($this->_extensionList[$category]))
			return [];

		foreach ($this->_extensionList[$category] as $k => $v) {
			if (isset($v[$key])) {
				return $v[$key]['usages'];
			}
		}

		return [];
	}

	public function ExtensionWebhooksSchema($category, $key)
	{
		if (empty($this->_extensionList[$category]))
			return [];

		foreach ($this->_extensionList[$category] as $k => $v) {
			if (isset($v[$key])) {
				return $v[$key]['webhooks'];
			}
		}

		return [];
	}

	public function ExtensionClass($category, $pluginName)
	{
		if (empty($this->_extensionClassList[$category][$pluginName]))
			return [];

		return $this->_extensionClassList[$category][$pluginName]['class'];
	}

	public function addDefaultRules($app)
	{
		$rules = [
			[
				'class' => 'yii\web\UrlRule',
				'pattern' => $this->id . '/<controller:[\w-]+>/<id:\d+>',
				'route' => $this->id . '/<controller>/view',
			],
			[
				'class' => 'yii\web\UrlRule',
				'pattern' => $this->id . '/<controller:[\w-]+>/<action:[\w-]+>/<id:\d+>',
				'route' => $this->id . '/<controller>/<action>',
			],
			[
				'class' => 'yii\web\UrlRule',
				'pattern' => $this->id . '/<controller:[\w-]+>/<action:[\w-]+>',
				'route' => $this->id . '/<controller>/<action>',
			],
			[
				'class' => 'yii\web\UrlRule',
				'pattern' => $this->id . '/<controller:[\w-]+>',
				'route' => $this->id . '/<controller>/index',
			],
		];

		$app->urlManager->addRules($rules, false);
	}

}
