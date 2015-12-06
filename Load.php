<?php
namespace infrajs\infra;
use infrajs\infra\Config;
use infrajs\once\Once;
use infrajs\cache\Cache;
use infrajs\path\Path;

/*
	Path::tofs - В кодировку файловой системы
	Path::toutf- в объект php
	Load::json_decode(string)
	Load::json_encode(obj)
	Load::req
	Path::theme
	Load::srcInfo
	Load::nameInfo
	Load::load
	Load::loadTEXT
	Load::loadJSON
*/

class Load {
	public static function unload($path)
	{
		Once::clear('Load::req', $path);
		Once::clear('Load::loadJSON', $path);
		Once::clear('Load::load', $path);
		Once::clear('Load::loadTEXT', $path);
	}
	public static function req($path)
	{
		$args=array($path);
		Once::exec('Load::req', function($path) {
			$rpath = Path::theme($path);
			if (!$rpath) throw new \Exception('Load::req - не найден путь '.$path);
			require_once $rpath;//Просто require позволяет загрузить самого себя. А мы текущий скрипт не добавляем в список подключённых
		}, $args);
	}
	public static function srcInfo($src)
	{
		$p = explode('?', $src);
		$file = array_shift($p);
		if ($p) {
			$query = '?'.implode('?', $p);
		} else {
			$query = '';
		}

		$p = explode('/', $file);
		$file = array_pop($p);

		if (sizeof($p) == 0) {
			if (preg_match("/^\~/", $file)) {
				$file = preg_replace("/^\~/", '', $file);
				$p[] = '~';
			} else if (preg_match("/^\*/", $file)) {
				$file = preg_replace("/^\*/", '', $file);
				$p[] = '*';
			} else if (preg_match("/^\|/", $file)) {
				$file = preg_replace("/^\|/", '', $file);
				$p[] = '|';
			}
		}
		$folder = implode('/', $p);
		if ($folder) $folder .= '/';

		$fdata = Load::nameInfo($file);

		$fdata['query'] = $query;
		$fdata['src'] = $src;
		$fdata['path'] = $folder.$file;
		$fdata['folder'] = $folder;

		return $fdata;
	}
	public static function nameInfo($file)
	{
		//Имя файла без папок// Звёздочки быть не может
		$p = explode('.', $file);
		if (sizeof($p) > 1) {
			$ext = array_pop($p);
			$name = implode('.', $p);
			if (!$name) {
				$name = $file;
				$ext = '';
			}
		} else {
			$ext = '';
			$name = $file;
		}
		$fname = $name;
		preg_match("/^(\d{6})[\s\.]/", $name, $match);
		$date = @$match[1];
		$name = preg_replace("/^\d+[\s\.]/", '', $name);
		$ar = explode('@', $name);
		$id = false;
		if (sizeof($ar) > 1) {
			$id = array_pop($ar);
			if (!$id) {
				$id = 0;
			}
			$idi = (int) $id;
			$idi = (string) $idi;//12 = '12 asdf' а если и то и то строка '12'!='12 asdf'
			if ($id == $idi) {
				$name = implode('@', $ar);
			} else {
				$id = false;
			}
		}
		$ans = array(
			'id' => $id,
			'name' => trim($name),
			'fname' => $fname,
			'file' => $file,
			'date' => $date,
			'ext' => mb_strtolower($ext),
		);

		return $ans;
	}

	public static function &loadJSON($path)
	{

		$args=array($path);
		$res=Once::exec('Load::loadJSON', function ($path){
			$res=array();
			$res['cache'] = Cache::check(function () use ($path, &$text) {
				$text = Load::load($path);
			});
			if (is_string($text)) {
				$res['value'] = Load::json_decode($text);
			} else {
				$res['value'] = $text;
			}
			return $res;
		}, $args);
		
		if (!$res['cache']) header('Cache-Control: no-store');
		return $res['value'];
		
		return $res['value'];
	}
	public static function &loadTEXT($path)
	{
		$args=array($path);
		$res=Once::exec('Load::loadTEXT', function ($path){
			$res=array();
			$res['cache'] = Cache::check(function () use ($path, &$text) {
				$text = Load::load($path);
			});
			if (is_null($text)) $text = '';
			if (!is_string($text)) {
				$res['value'] = Load::json_encode($text);
			} else {
				$res['value'] = $text;
			}
			return $res;
		}, $args);
	
		
		if (!$res['cache']) header('Cache-Control: no-store');
		return $res['value'];
		
		return $res['value'];
	}
	/**
	 * Функция возвращет находимся ли мы в исполнении скрипта запущенного из браузера или скрипта подключённого c помощью Load::loadJSON Load::loadTEXT другим php скриптом
	 * можно установить false если ещё небыло никаких установок..
	 * если кто-то подключает в php через theme.php или тп... сброс в theme.php уже не сработает.
	 */
	public static function isphp($val = null)
	{
		global $FROM_PHP_PLUGIN;
		if (is_null($val)) {
			return !!$FROM_PHP_PLUGIN; //false или null = false
		} else {
			$FROM_PHP_PLUGIN = $val;
		}
	}
	private static function load($path)
	{
		$args=array($path);
		$res = Once::exec('Load::load', function ($path){
			//php файлы эмитация веб запроса
			//всё остальное file_get_content
			$res=array();
			$res['cache'] = Cache::check(function () use ($path, &$res) {
				$load_path = Path::theme($path);
				$fdata = Load::srcinfo($load_path);
				if ($load_path && $fdata['file']) {
					$plug = Path::theme($fdata['path']);
					if ($fdata['ext'] == 'php') {
						$getstr = Path::toutf($fdata['query']);//get параметры в utf8, с вопросом
						$getstr = preg_replace("/^\?/", '', $getstr);
						parse_str($getstr, $get);
						if (!$get) {
							$get = array();
						}
						$GET = $_GET;
						$_GET = $get;
						$REQUEST = $_REQUEST;
						$_REQUEST = array_merge($_GET, $_POST, $_COOKIE);

						$SERVER_QUERY_STRING = $_SERVER['QUERY_STRING'];
						$_SERVER['QUERY_STRING'] = $getstr;

						$from_php_old = Load::isphp();
						Load::isphp(true);

						ob_start();
						//headers надо ловить
						$ans = array();
						$rrr = include $plug;
						$result = ob_get_contents();
						$resecho = $result;
						ob_end_clean();

						Load::isphp($from_php_old);

						if ($rrr !== 1 && !is_null($rrr)) { //Есть возвращённый результат
							$result = $rrr;
							if ($resecho) { //Сообщение об ошибке... далее всё ломаем
								$result = $resecho.Load::json_encode($result); //Есть вывод в браузер и return
							}
						}

						$_SERVER['QUERY_STRING'] = $SERVER_QUERY_STRING;
						$_REQUEST = &$REQUEST;
						$_GET = &$GET;
						$data = $result;

						//$data='php file';
					} else {
						$data = file_get_contents($plug);
					}
					$res['status'] = 200;
					$res['value'] = $data;
				} else {
					$res['status'] = 404;
					$res['value'] = '';
				}
			});
			return $res;
		}, $args);

		if (!$res['cache']) header('Cache-Control: no-store');
		return $res['value'];
	}
	/*
	//Мультизагрузка нет, используется script.php


	//Что такое store
	//store пошёл из node где при каждом запросе страницы этот store очищался. и хранился для каждого пользователя в отдельности.
	//store нужен чтобы синтаксис в javascript и в php был одинаковый без global
	//Без store нужно заводить переменную перед функцией, в нутри функции забирать её из global, придумывать не конфликтующие имена
	//всё что хранится в store не хранится в localStorage
	//store не специфицируется... если надо отдельно в объекте заводится...

	//Много вещей отличающих node ещё и fibers

	//Личный кабинет, авторизация пользователя?

	//user.php (no-cache) заголовок getResponseHeader('no-cache')
	//Опция global для обновления связанных файлов

	//req('no-cache') не сохраняется в localStorage??
	//req('no-cache') не сохраняется в localStorage



	*/
	public static function json_decode($json, $soft = false)
	{
		//soft если об ошибке не нужно сообщать
		$json2 = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);
		$data = json_decode($json2, true, 512);//JSON_BIGINT_AS_STRING в javascript тоже нельзя такие цифры... архитектурная ошибка.
		if (!$soft && $json2 && is_null($data) && !in_array($json2, array('null'))) {
			echo '<h1>json decode error</h1>';
			echo "\n".'<pre>'."\n";
			var_dump($json);
			var_dump($data);
			echo "\n".'</pre>';
			exit;
		}

		return $data;
	}
	public static function json_encode($mix)
	{
		return json_encode($mix, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}
}