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

namespace BcAddonMigrator\Controller\Component;

use BcAddonMigrator\Utility\MigrateBehavior5;
use BcAddonMigrator\Utility\MigrateComponent5;
use BcAddonMigrator\Utility\MigrateConfig5;
use BcAddonMigrator\Utility\MigrateController5;
use BcAddonMigrator\Utility\MigrateHelper5;
use BcAddonMigrator\Utility\MigrateTable5;
use BcAddonMigrator\Utility\MigrateTemplate5;
use BcAddonMigrator\Utility\MigrateView5;
use Cake\Controller\Component;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;

/**
 * BcAddonMigrator4Component
 */
class BcAddonMigrator5Component extends Component implements BcAddonMigratorInterface
{
	
	/**
	 * プラグイン用メッセージ
	 * @var array
	 */
	public $__pluginMessage = [
		'コントローラーにおいて、$this->Session->setFlash() 利用できなくなりました。 $this->Flash->set() または、<br>$this->setMessage(\'メッセージ\', 警告メッセージかどうか：true Or false, DBにログとして保存するかどうか：true Or false, フラッシュメッセージかどうか：true Or false) を利用してください。'
	];
	
	/**
	 * テーマ用メッセージ
	 *
	 * @var array
	 */
	public $__themeMessage = [
		'ビューにおいて、$this->BcForm->create() の第２引数で、url キーの配列の中以外で action キーは利用できなくなりました。action キーは、url キーの配列の中に指定しなおしてください。（例：$this->BcForm->create(\'ModelName\', [\'url\' => [\'action\' => \'action-name\']]）',
		'jQuery2系の利用に伴い、チェックボックスの操作において、attr(\'checked\') が利用できなくなりました。prop(\'checked\') を利用してください。',
		'コントローラーのアクション名が index の場合、コントローラー名だけでアクセスできなくなりました。リンクを設定する場合、末尾にスラッシュを付与したURLに書き換えてください。'
	];
	
	/**
	 * プラグイン用メッセージを取得する
	 *
	 * @return array
	 */
	public function getPluginMessage(): array
	{
		return array_merge($this->__pluginMessage, $this->__themeMessage);
	}
	
	/**
	 * テーマ用メッセージを取得する
	 *
	 * @return array
	 */
	public function getThemeMessage(): array
	{
		return $this->__themeMessage;
	}
	
	/**
	 * プラグインのマイグレーションを実行
	 *
	 * @param string $plugin プラグイン名
	 * @param string $php phpの実行ファイルのパス
	 */
	public function migratePlugin(string $plugin): void
	{
		$plugin = $this->migrateBasicDir($plugin);
		$this->makePluginClass($plugin);
		$this->migratePluginStructure($plugin);
		
		$pluginPath = BASER_PLUGINS . $plugin . DS;
		$srcPath = $pluginPath . 'src' . DS;
		
		$this->migrateAddonConfig($plugin, 'Plugin', $pluginPath . 'config.php');
		$this->migrateConfig($pluginPath . 'config');
		$this->migrateController($plugin, $srcPath . 'Controller');
		$this->migrateComponent($plugin, $srcPath . 'Controller' . DS . 'Component');
		$this->migrateTable($plugin, $srcPath . 'Model' . DS . 'Table');
		$this->migrateBehavior($plugin, $srcPath . 'Model' . DS . 'Behavior');
		$this->migrateHelper($plugin, $srcPath . 'View' . DS . 'Helper');
		$this->migrateView($plugin, $srcPath . 'View');
		
		$templatePath = BASER_PLUGINS . $plugin . DS . 'templates' . DS;
		$this->migrateTemplate($templatePath);
	}
	
	/**
	 * プラグイン設定ファイルのマイグレーションを実行
	 * @param string $plugin
	 * @param string $type
	 * @param string $path
	 * @return void
	 */
	public function migrateAddonConfig(string $plugin, string $type, string $path)
	{
		if (!file_exists($path)) {
			$file = new File($path);
			$file->write("<?php
return [
	'type' => '{$type}',
	'title' => '{$plugin}',
	'description' => '',
	'author' => '',
	'url' => '',
];");
		} else {
			$config = include $path;
			if(is_array($config)) return;
			if(!isset($title)) $title = $plugin;
			if(!isset($description)) $description = '';
			if(!isset($author)) $author = '';
			if(!isset($url)) $url = '';
			if(!isset($adminLink)) $adminLink = [];
			if(!isset($installMessage)) $installMessage = '';
			if($adminLink) {
				if(!empty($adminLink['plugin'])) {
					$adminLink['plugin'] = \Cake\Utility\Inflector::camelize($adminLink['plugin']);
				}
				if(!empty($adminLink['controller'])) {
					$adminLink['controller'] = \Cake\Utility\Inflector::camelize($adminLink['controller']);
				}
				$adminLink = var_export($adminLink, true);
				$adminLink = str_replace('array (', '[', $adminLink);
				$adminLink = str_replace(')', ']', $adminLink);
				$adminLink = str_replace("\n", '', $adminLink);
			} else {
				$adminLink = '[]';
			}
			$file = new File($path);
			$file->write("<?php
return [
	'type' => '{$type}',
	'title' => '{$title}',
	'description' => '{$description}',
	'author' => '{$author}',
	'url' => '{$url}',
	'adminLink' => {$adminLink},
	'installMessage' => {$installMessage},
];");
		}
		$file->close();
	}
	
	/**
	 * テーマのマイグレーションを実行する
	 *
	 * @param string $theme テーマ名
	 */
	public function migrateTheme(string $theme): void
	{
		$theme = $this->migrateBasicDir($theme);
		$this->makePluginClass($theme);
		$this->migrateThemeStructure($theme);
		
		$themePath = BASER_PLUGINS . $theme . DS;
		$srcPath = $themePath . 'src' . DS;
		
		$this->migrateAddonConfig($theme, 'Theme', $themePath . 'config.php');
		$this->migrateConfig($themePath . 'config');
		$this->migrateHelper($theme, $srcPath . 'View' . DS . 'Helper');
		
		$templatePath = BASER_PLUGINS . $theme . DS . 'templates' . DS;
		$this->migrateTemplate($templatePath);
	}
	
	/**
	 * アドオンを基本的なフォルダ構成にする
	 * @param string $plugin
	 * @return string
	 */
	public function migrateBasicDir(string $plugin): string
	{
		$newName = \Cake\Utility\Inflector::camelize($plugin);
		if ($plugin !== $newName) {
			rename(BASER_PLUGINS . $plugin, BASER_PLUGINS . $newName);
		}
		$pluginPath = BASER_PLUGINS . $plugin . DS;
		if (!is_dir($pluginPath . 'src')) (new \Cake\Filesystem\Folder())->create($pluginPath . 'src');
		if (is_dir($pluginPath . 'Test')) rename($pluginPath . 'Test', $pluginPath . 'tests');
		if (is_dir($pluginPath . 'tests' . DS . 'Case')) rename($pluginPath . 'tests' . DS . 'Case', $pluginPath . 'tests' . DS . 'TestCase');
		return $newName;
	}
	
	/**
	 * プラグインクラスを作成する
	 * @param string $plugin
	 * @return void
	 */
	public function makePluginClass(string $plugin)
	{
		$srcPath = BASER_PLUGINS . $plugin . DS . 'src';
		if (file_exists($srcPath . DS . 'Plugin.php')) return;
		(new \Cake\Filesystem\Folder())->create($srcPath);
		$file = new \Cake\Filesystem\File($srcPath . DS . 'Plugin.php');
		$file->write("<?php
namespace {$plugin};
use BaserCore\BcPlugin;
class Plugin extends BcPlugin {}");
	}
	
	/**
	 * プラグインの構造変更を実行
	 *
	 * @param string $plugin プラグイン名
	 * @param string $php phpの実行ファイルのパス
	 */
	public function migratePluginStructure(string $plugin)
	{
		$pluginPath = BASER_PLUGINS . $plugin . DS;
		
		// Config
		if (is_dir($pluginPath . 'Config')) rename($pluginPath . 'Config', $pluginPath . 'Config');
		
		// View
		if (is_dir($pluginPath . 'View')) rename($pluginPath . 'View', $pluginPath . 'templates');
		
		// Controller / Model / Event / Lib / Vendor
		foreach(['Controller', 'Model', 'Event', 'Lib', 'Vendor'] as $dir) {
			if (is_dir($pluginPath . $dir)) rename($pluginPath . $dir, $pluginPath . 'src' . DS . $dir);
		}
		
		// Table
		$modelPath = BASER_PLUGINS . $plugin . DS . 'src' . DS . 'Model' . DS;
		$tablePath = BASER_PLUGINS . $plugin . DS . 'src' . DS . 'Model' . DS . 'Table' . DS;
		if(is_dir($modelPath)) {
			$files = (new \Cake\Filesystem\Folder($modelPath))->read();
			if($files[1] && !is_dir($tablePath)) {
				(new Folder())->create($tablePath);
			}
			foreach($files[1] as $file) {
				$className = \Cake\Utility\Inflector::pluralize(basename($file, '.php')) . 'Table.php';
				rename($modelPath . $file, $tablePath . $className);
			}
		}
		
		// move admin
		if (is_dir($pluginPath . 'templates')) {
			$files = (new \Cake\Filesystem\Folder($pluginPath . 'templates'))->read();
			foreach($files[0] as $dir) {
				switch($dir) {
					case 'Elements':
						rename($pluginPath . 'templates' . DS . $dir, $pluginPath . 'templates' . DS . 'element');
						$this->moveAdminTemplates($plugin, 'element');
						break;
					case 'Layouts':
						rename($pluginPath . 'templates' . DS . $dir, $pluginPath . 'templates' . DS . 'layout');
						$this->moveAdminTemplates($plugin, 'layout');
						break;
					case 'Emails':
						rename($pluginPath . 'templates' . DS . $dir, $pluginPath . 'templates' . DS . 'email');
						$this->moveAdminTemplates($plugin, 'email');
						break;
					default:
						$this->moveAdminTemplates($plugin, $dir);
						break;
				}
			}
		}
	}

	/**
	 * テーマの構造変更を実行
	 *
	 * @param string $plugin プラグイン名
	 * @param string $php phpの実行ファイルのパス
	 */
	public function migrateThemeStructure(string $plugin)
	{
		$pluginPath = BASER_PLUGINS . $plugin . DS;
		
		// Config
		if (is_dir($pluginPath . 'Config')) rename($pluginPath . 'Config', $pluginPath . 'Config');
		
		// Helper
		if (is_dir($pluginPath . 'Helper')) {
			if(!is_dir($pluginPath . 'src' . DS . 'View')) {
				(new \Cake\Filesystem\Folder())->create($pluginPath . 'src' . DS . 'View');
			}
			rename($pluginPath . 'Helper', $pluginPath . 'src' . DS . 'View' . DS . 'Helper');
		}
		
		if(!is_dir($pluginPath . 'webroot')) {
			(new \Cake\Filesystem\Folder())->create($pluginPath . 'webroot');
		}
		
		if(!is_dir($pluginPath . 'templates')) {
			(new \Cake\Filesystem\Folder())->create($pluginPath . 'templates');
		}
		
		// templates
		$files = (new \Cake\Filesystem\Folder($pluginPath))->read();
		foreach($files[0] as $dir) {
			switch($dir) {
				case 'css':
				case 'js':
				case 'img':
					rename($pluginPath . $dir, $pluginPath . 'webroot' . DS . $dir);
					break;
				case 'Elements':
					rename($pluginPath . $dir, $pluginPath . 'templates' . DS . 'element');
					$this->moveAdminTemplates($plugin, 'element');
					break;
				case 'Layouts':
					rename($pluginPath . $dir, $pluginPath . 'templates' . DS . 'layout');
					$this->moveAdminTemplates($plugin, 'layout');
					break;
				case 'Emails':
					rename($pluginPath . $dir, $pluginPath . 'templates' . DS . 'email');
					$this->moveAdminTemplates($plugin, 'email');
					break;
				case 'templates':
				case 'webroot':
				case 'config':
				case 'src':
					break;	
				default:
					rename($pluginPath . $dir, $pluginPath . 'templates' . DS . $dir);
					$this->moveAdminTemplates($plugin, $dir);
					break;
			}
		}
	}
	
	/**
	 * 管理画面用のテンプレートを移動する
	 * @param string $plugin
	 * @param string $name
	 * @return void
	 */
	public function moveAdminTemplates(string $plugin, string $name)
	{
		$templatesPath = BASER_PLUGINS . $plugin . DS . 'templates' . DS;
		if (!is_dir($templatesPath . 'Admin')) {
			(new \Cake\Filesystem\Folder())->create($templatesPath . 'Admin');
		}
		$files = (new \Cake\Filesystem\Folder($templatesPath . $name))->read();
		foreach($files[0] as $dir) {
			if ($dir !== 'admin') continue;
			$adminPath = $templatesPath . $name . DS . $dir . DS;
			$files = (new \Cake\Filesystem\Folder($adminPath))->read();
			$files = $files[0] + $files[1];
			foreach($files as $file) {
				if (!is_dir($templatesPath . 'Admin' . DS . $name)) {
					(new \Cake\Filesystem\Folder())->create($templatesPath . 'Admin' . DS . $name);
				}
				rename($adminPath . $file, $templatesPath . 'Admin' . DS . $name . DS . $file);
			}
			(new \Cake\Filesystem\Folder($adminPath))->delete($adminPath);
		}
	}
	
	/**
	 * コントローラーファイルのマイグレーションを実行
	 *
	 * @param string $path コントローラーディレクトリへの実行パス
	 */
	public function migrateController(string $plugin, string $path)
	{
		if (!is_dir($path)) return;
		$files = (new Folder($path))->read(true, true, true);
		foreach($files[1] as $file) {
			(new MigrateController5)->migrate($plugin, $file);
		}
	}
	
	/**
	 * サブフォルダを取得する
	 * @param $plugin
	 * @param $path
	 * @return array|string|string[]|null
	 */
	public function getSubDir($plugin, $path)
	{
		$path = dirname($path);
		$subDir = str_replace(BASER_PLUGINS . $plugin . DS . 'src' . DS . 'Controller', '', $path);
		$subDir = preg_replace('/^\//', '', $subDir);
		return $subDir;
	}
	
	/**
	 * コンポーネントファイルのマイグレーションを実行
	 *
	 * @param string $path コンポーネントディレクトリのパス
	 */
	public function migrateComponent(string $plugin, string $path)
	{
		if (!is_dir($path)) return;
		$files = (new Folder($path))->read(true, true, true);
		foreach($files[1] as $file) {
			(new MigrateComponent5())->migrate($plugin, $file);
		}
	}
	
	/**
	 * テーブルファイルのマイグレーションを実行
	 *
	 * @param string $path テーブルディレクトリのパス
	 */
	public function migrateTable(string $plugin, string $path)
	{
		if (!is_dir($path)) return;
		$files = (new Folder($path))->read(true, true, true);
		foreach($files[1] as $file) {
			(new MigrateTable5())->migrate($plugin, $file);
		}
	}
	
	/**
	 * ビヘイビアファイルのマイグレーションを実行
	 *
	 * @param string $path ビヘイビアディレクトリのパス
	 */
	public function migrateBehavior(string $plugin, string $path)
	{
		if (!is_dir($path)) return;
		$files = (new Folder($path))->read(true, true, true);
		foreach($files[1] as $file) {
			(new MigrateBehavior5())->migrate($plugin, $file);
		}
	}
	
	/**
	 * プラグイン設定ファイルのマイグレーションを実行
	 *
	 * @param string $path 設定ファイルのパス
	 * @param string $plugin 古いプラグイン名
	 * @param string $newPlugin 新しいプラグイン名
	 */
	public function migrateConfig($path)
	{
		if (!is_dir($path)) return;
		$Folder = new Folder($path);
		$files = $Folder->read(true, true, true);
		foreach($files[0] as $dir) {
			$this->migrateTemplate($dir);
		}
		foreach($files[1] as $file) {
			(new MigrateConfig5())->migrate($file);
		}	
	}
	
	/**
	 * ビューファイルのマイグレーションを実行
	 *
	 * @param string $path ビューディレクトリのパス
	 * @param string $plugin 古いプラグイン名
	 */
	public function migrateView(string $plugin, string $path)
	{
		if (!is_dir($path)) return;
		$files = (new Folder($path))->read(true, true, true);
		foreach($files[1] as $file) {
			(new MigrateView5())->migrate($plugin, $file);
		}
	}
	
	/**
	 * ヘルパーファイルのマイグレーションを実行
	 *
	 * @param string $path ヘルパーディレクトリのパス
	 */
	public function migrateHelper(string $plugin, string $path)
	{
		if (!is_dir($path)) return;
		$files = (new Folder($path))->read(true, true, true);
		foreach($files[1] as $file) {
			(new MigrateHelper5())->migrate($plugin, $file);
		}
	}
	
	/**
	 * テンプレートファイルのマイグレーションを実行
	 * @param string $path
	 * @return void
	 */
	public function migrateTemplate(string $path)
	{
		if (!is_dir($path)) return;
		$Folder = new Folder($path);
		$files = $Folder->read(true, true, true);
		foreach($files[0] as $dir) {
			$this->migrateTemplate($dir);
		}
		foreach($files[1] as $file) {
			(new MigrateTemplate5())->migrate($file);
		}	
	}
	
}
