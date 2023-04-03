<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\base\common\base;

class BaseGateway extends BaseExtension
{
	/*
	public function getVerbs()
	{
		return [];
	}

	public function typeBehaviors()
	{
		return Yii::$app->shopack->gateway->getBehaviorsList((new \ReflectionClass($this))->getShortName());
	}
	public function behaviors()
	{
		$behs = $this->typeBehaviors();
		if (($behs === null) || (count($behs) <= 0) || ($this->extensionModel === null))
			return [];

		//filter to assigned behaviors
		if (!isset($this->extensionModel->gtwPluginParameters['behaviors']))
			return [];

		$out = [];
		foreach ($behs as $k => $v)
		{
			if (isset($this->extensionModel->gtwPluginParameters['behaviors'][$k]))
				$out = array_merge($out, [$k => $v]);
		}
// die(var_dump($out));
		return $out;
	}

	public function createVerbModel($verb, $pluginParameters=null)
	{
		$verbs = $this->getVerbs();
		if (($verbs === null) || (count($verbs) == 0))
			return [null, null];

		foreach ($verbs as $verbDefine)
		{
			if ($verbDefine['id'] == $verb)
			{
				$verbModel = new DynamicModel();

				//1: create params
				$req = [];
				$strings = [];
				$defs = [];
				$labels = [];
				foreach ($verbDefine['params'] as $pid => $p)
				{
					$verbModel->defineAttribute($pid);
					$labels = ArrayHelper::merge($labels, [$pid => $p['label']]);

					$strings[] = $pid;
					if (isset($p['mandatory']) && $p['mandatory'])
						$req[] = $pid;
					if (isset($p['refValue']))
					{
						if (($pluginParameters !== null) && isset($pluginParameters[$p['refValue']]))
							$defs = ArrayHelper::merge($defs, [$pid => $pluginParameters[$p['refValue']]]);
					}
					else if (isset($p['value']))
					{
						$defs = ArrayHelper::merge($defs, [$pid => $p['value']]);
					}
				}

				if (count($req) > 0)
					$verbModel->addRule($req, 'required');
				if (count($strings) > 0)
					$verbModel->addRule($strings, 'safe');
				if (count($defs) > 0)
					foreach ($defs as $k => $v)
						$verbModel->{$k} = $v;
						//$verbModel->addRule($k, 'default', 'value' => $def);
					$verbModel->defineLabels($labels);

				return [$verbModel, $verbDefine];
			}
		}

		return [null, null];
	}

	public function apiCall($verb, $verbModel, $params=[])
	{
		if (empty($verb))
			return [false, 'unknown verb'];

		//test-api => apiCallTestApi
		$method = "apiCall" . Inflector::id2camel($verb);
		if (method_exists($this, $method))
			return $this->$method($verb, $verbModel, $params);

		return [false, 'unknown verb'];
	}

	public function log(
			// $gtwlogGatewayID,
			$gtwlogMethodName,
			$gtwlogRequest,
			$gtwlogResponse
		)
	{
		return GatewayLogModel::log(
			/* gtwlogGatewayID  * / $this->extensionModel->gtwID,
			/* gtwlogMethodName * / $gtwlogMethodName,
			/* gtwlogRequest    * / $gtwlogRequest,
			/* gtwlogResponse   * / $gtwlogResponse
		);
	}
	*/

}
