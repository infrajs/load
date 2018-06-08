<?php
namespace infrajs\load;
use infrajs\once\Once;
use infrajs\nostore\Nostore;
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
		//Once::clear('Load::req', [$path]);
		//Once::clear('Load::loadJSON', [$path]);
		Once::clear('Load::load', [$path]);
		//Once::clear('Load::loadTEXT', [$path]);
	}
	public static function sort (&$list, $order = 'descending') {
		if ($order == 'descending') {
			usort($list, function ($A, $B) {
				$a = isset($A['num'])? $A['num']: (isset($A['date'])? $A['date']: 0);
				$b = isset($B['num'])? $B['num']: (isset($B['date'])? $B['date']: 0);
				if ($a || $b) {
					if (!$b) return -1;
					if (!$a) return 1;
					if ($a == $b) return 0;
					return ($a < $b) ? 1 : -1;
				}
				$a = $A['name'];
				$b = $B['name'];
				return strcasecmp($a, $b);
			});
		} else if ($order == 'ascending') {
			usort($list, function ($A, $B) {
				$a = isset($A['num'])? $A['num']: (isset($A['date'])? $A['date']: 0);
				$b = isset($B['num'])? $B['num']: (isset($B['date'])? $B['date']: 0);
				if ($a || $b) {
					if (!$b) return -1;
					if (!$a) return 1;
					if ($a == $b) return 0;
					return ($a < $b) ? -1 : 1;
				}
				$a = isset($A['num'])? $A['num']: (isset($A['date'])? $A['date']: 0);
				$b = isset($B['num'])? $B['num']: (isset($B['date'])? $B['date']: 0);
				if (!isset($B['name'])) return 0;
				if (!isset($A['name'])) return 0;
				$a = $A['name'];
				$b = $B['name'];
				return strcasecmp($a, $b);
			});
		}
	}
	public static function pathInfo($file)
	{
		$p = explode('/', $file);
		$file = array_pop($p);

		if (sizeof($p) == 0) {
			if (preg_match("/^\~/", $file)) {
				$file = preg_replace("/^\~/", '', $file);
				$p[] = '~';
			} else if (preg_match("/^\*/", $file)) {
				$file = preg_replace("/^\-/", '', $file);
				$p[] = '-';
			} else if (preg_match("/^\|/", $file)) {
				$file = preg_replace("/^\!/", '', $file);
				$p[] = '!';
			}
			$folder = implode('/', $p);
		} else {
			$folder = implode('/', $p);
			if ($folder) $folder .= '/';
		}

		$fdata = Load::nameInfo($file);
		$fdata['src'] = $file;
		$fdata['path'] = $folder.$file;
		$fdata['folder'] = $folder;

		return $fdata;
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
				$file = preg_replace("/^\-/", '', $file);
				$p[] = '-';
			} else if (preg_match("/^\|/", $file)) {
				$file = preg_replace("/^\!/", '', $file);
				$p[] = '!';
			}
			$folder = implode('/', $p);
		} else {
			$folder = implode('/', $p);
			if ($folder) $folder .= '/';
		}

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
		if (sizeof($p) > 1 && strlen($p[sizeof($p)-1])<5) {
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
		/*
		preg_match("/^(\d+)[\s\.]/", $name, $match);
		$num = isset($match[1]) ? $match[1] : null;
		if (strlen($num) == 6) $date = $num;
		else $date = null;
		$name = preg_replace("/^\d+[\s\.]/", '', $name);
`		*/

		preg_match("/^(\d+)[\s]/", $name, $match);
		$num = isset($match[1]) ? $match[1] : null;
		if (strlen($num) == 6) $date = $num;
		else $date = null;
		$name = preg_replace("/^\d+[\s]/", '', $name);

		$id = false;
		/*$ar = explode('@', $name);
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
		}*/

		$r = preg_match('/^(.*)[@#]([^\s]+)(.*)$/', $name, $m);
		if ($r) {
			$name = $m[1].$m[3];
			$id = $m[2];
		}

		$ans = array(
			'id' => $id,
			'num' => $num,
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

		$args = array($path);

		//$res = Once::exec('Load::loadJSON', function ($path){
			$res=array();
			$res['nostore'] = Nostore::check(function () use ($path, &$text) {
				$text = Load::load($path);
			});
			if (is_string($text)) {
				$res['value'] = Load::json_decode($text);
			} else {
				$res['value'] = $text;
			}
		//	return $res;
		//}, $args);
		
		if ($res['nostore']) Nostore::on();
		return $res['value'];
		
		return $res['value'];
	}
	public static function &loadTEXT($path)
	{
		$args=array($path);
		//$res=Once::exec('Load::loadTEXT', function ($path){
			$res=array();
			$res['nostore'] = Nostore::check(function () use ($path, &$text) {
				$text = Load::load($path);
			});
			if (is_null($text)) $text = '';
			if (!is_string($text)) {
				$res['value'] = Load::json_encode($text);
			} else {
				$res['value'] = $text;
			}
		//	return $res;
		//}, $args);
	
		
		if ($res['nostore']) Nostore::on();
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

		$args = array($path);
		Once::$nextgid = 'Load::load';
		$res = Once::func(function ($path){
			//php файлы эмитация веб запроса
			//всё остальное file_get_content
			$_r_e_s_ = array();
			//$_r_e_s_['unload'] = false;
			$_r_e_s_['nostore'] = Nostore::check(function () use ($path, &$_r_e_s_) {

				/*if (Path::isDir($path)) {
					$p=explode('?', $path, 2);
					$p[0] .= 'index.php';
					$path = implode('?', $p);
				}*/
				$load_path = Path::themeq($path);
				$fdata = Load::srcInfo($load_path);
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
						$REQUEST = isset($_REQUEST)?$_REQUEST:array();
						$_REQUEST = array_merge($_GET, $_POST, $_COOKIE);


						$SERVER_REQUEST_URI = $_SERVER['REQUEST_URI'];
						$SERVER_QUERY_STRING = $_SERVER['QUERY_STRING'];
						$_SERVER['QUERY_STRING'] = $getstr;
						$_SERVER['REQUEST_URI'] = '/'.$path;

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
						$_SERVER['REQUEST_URI'] = $SERVER_REQUEST_URI;
						$_REQUEST = &$REQUEST;
						$_GET = &$GET;
						$data = $result;
						$_r_e_s_=array(); //Если в include это имя использовалось. Главное чтобы оно небыло ссылкой &
						//$_r_e_s_['unload'] = true;
						//$data='php file';
					} else {
						$data = file_get_contents($plug);
					}
					
					$_r_e_s_['status'] = 200;
					$_r_e_s_['value'] = $data;
				} else {
					$_r_e_s_['status'] = 404;
					$_r_e_s_['value'] = '';
				}
			});
			return $_r_e_s_;
		}, $args);
		//if ($res['unload']) {
		//	Load::unload($path);
		//}
		if ($res['nostore']) Nostore::on();
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
		//$json2 = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);
		
		$data = json_decode($json, true, 512);//JSON_BIGINT_AS_STRING в javascript тоже нельзя такие цифры... архитектурная ошибка.
		if (!$soft && $json && is_null($data) && !in_array($json, array('null'))) {
			echo "\n".'<pre>'."\n";
			var_dump($json);
			var_dump($data);
			throw new \Exception("json_decode error");
		}

		return $data;
	}
	public static function json_encode($mix)
	{
		return json_encode($mix, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}
}
