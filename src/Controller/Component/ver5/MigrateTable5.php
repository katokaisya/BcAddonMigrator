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

namespace BcAddonMigrator\Controller\Component\ver5;

use Psr\Log\LogLevel;
use Cake\Log\LogTrait;

/**
 * Class MigrateTable5
 */
class MigrateTable5
{

	/**
	 * Trait
	 */
	use LogTrait;

	/**
	 * マイグレーション
	 * @param string $plugin
	 * @param string $prefix
	 * @param string $path
	 * @return void
	 */
	public function migrate(string $plugin, string $path, bool $is5): void
	{
	    if(in_array(basename($path), \Cake\Core\Configure::read('BcAddonMigrator.ignoreFiles'))) {
            return;
        }
		$code = file_get_contents($path);
		if(!$is5) {
            $code = MigrateBasic5::addNameSpace($plugin, $path, 'Model' . DS . 'Table', $code);
            $code = self::setClassName($path, $code);
            $code = self::replaceEtc($code);
        }
		$code = MigrateBasic5::replaceCode($code, $is5);
		file_put_contents($path, $code);
		$this->log('テーブル：' . $path . ' をマイグレーションしました。', LogLevel::INFO, 'migrate_addon');
	}

	/**
	 * クラス名をファイル名に合わせる
	 * @param string $path
	 * @param string $code
	 * @return array|string|string[]|null
	 */
	public static function setClassName(string $path, string $code)
	{
		$className = basename($path, '.php');
		$code = preg_replace('/class\s+[a-zA-Z0-9]+\s/', "class $className ", $code);
		return $code;
	}

	/**
	 * その他の置き換え
	 * @param $code
	 * @return array|string|string[]|null
	 */
	public static function replaceEtc($code)
	{
		$code = preg_replace('/extends\s+AppModel/', 'extends \BaserCore\Model\Table\AppTable', $code);
		return $code;
	}
}
