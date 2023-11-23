<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.7
 * @license       https://basercms.net/license/index.html MIT License
 */

namespace BcAddonMigrator\Utility;

/**
 * Class MigrateBasic5
 */
class MigrateBasic5
{
	
	public static function replaceCode(string $code): string
	{
		$code = preg_replace('/new Folder\(/', 'new \Cake\Filesystem\Folder(', $code);
		$code = preg_replace('/new File\(/', 'new \Cake\Filesystem\File(', $code);
		$code = preg_replace('/new BcZip\(/', 'new \BaserCore\Utility\BcZip(', $code);
		$code = preg_replace('/App::uses\(.+?;\n/', '', $code);
		$code = preg_replace('/Configure::/', '\Cake\Core\Configure::', $code);
		$code = preg_replace('/Inflector::/', '\Cake\Utility\Inflector::', $code);
		$code = preg_replace('/ClassRegistry::init\(/', '\Cake\ORM\TableRegistry::getTableLocator()->get(', $code);
		$code = preg_replace('/\sgetVersion\(\)/', '\BaserCore\Utility\BcUtil::getVersion()', $code);
		return $code;
	}
	
	/**
	 * ネームスペースを追加する
	 * @param string $plugin
	 * @param string $path
	 * @param string $code
	 * @return string
	 */
	public static function addNameSpace(string $plugin, string $path, string $layerPath, string $code)
	{
		if (preg_match('/namespace/', $code)) return $code;
		
		$path = dirname($path);
		$path = str_replace(BASER_PLUGINS . $plugin . DS . 'src' . DS . $layerPath, '', $path);
		$nameSpace = $plugin . "\\" . str_replace(DS, "\\", $layerPath);
		if ($path) {
			$nameSpace .= "\\" . preg_replace('/^\//', '', $path);
		}
		$codeArray = explode("\n", $code);
		array_splice($codeArray, 1, 0, 'namespace ' . $nameSpace . ';');
		return implode("\n", $codeArray);
	}
	
}
