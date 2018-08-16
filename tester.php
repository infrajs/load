<?php
namespace infrajs\load;
if (!is_file('vendor/autoload.php')) {
    chdir('../../../'); //Согласно фактическому расположению файла
    require_once('vendor/autoload.php');
}

/**
 * Load::srcInfo - Принимает url строку, разбивает url на массив значений.
 * Возвращает ассоциативный массив со следующими элементами:
 * 'id', 'name', 'fname', 'file', 'date', 'ext' (смотри описание в Load::nameInfo)
 * 'query' => Если есть GET данные, то они записываются в этот параметр
 * 'src' => Полный путь, переданный аргументом
 * 'path' => Полный путь без параметров GET
 * 'folder' => Путь до папки
 *
 */
$ans = Load::srcInfo('test.site.ru/folder/upload.php?test=1&test2=2');
assert($ans['query'] === '?test=1&test2=2');
assert($ans['src'] === 'test.site.ru/folder/upload.php?test=1&test2=2');
assert($ans['path'] === 'test.site.ru/folder/upload.php');
assert($ans['folder'] === 'test.site.ru/folder/');

$ans = Load::srcInfo('~test.site.ru');
assert($ans['path'] === '~test.site.ru');

/**
 * Load::nameInfo - Принимает имя файла и возвращает в массиве следующую информацию по нему:
 * 'id' => boolean false, если в имени файла присутствует @ и после данного знака стоит число, то возвращает это число
 * в текстовом формате
 * 'name' => строковое имя файла до расширения (.php, .xml, .xlsx и т.п.), при этом если вначале имени файла стоит
 * шестизначное число и после этого числа стоит точка или пробел, то это число не включается в имя.
 * если в имени файла имеется знак @ и после этого знака идет целое число, то это число так же не включается в имя.
 * 'fname' => строковое полное имя файла до расширения (.php, .xml, .xlsx и т.п.)
 * 'file' => строковое полное имя файла с расширением
 * 'date' => null, если спереди имени файла стоит шестизначное число и после этого числа стоит точка или пробел, то возвращается это число
 * 'ext' => строковое расширение файла (php, xml, xlsx и т.п.)
 */

$ans = Load::nameInfo('110316 test@24.txt');
assert($ans['id'] === '24');
assert($ans['name'] === 'test');
assert($ans['fname'] === '110316 test@24');
assert($ans['file'] === '110316 test@24.txt');
assert($ans['date'] === '110316');
assert($ans['ext'] === 'txt');

assert(!!Load::loadTEXT('-load/Load.js'));

$conf = Load::loadJSON('-load/.infra.json');
assert($conf['tester']=='tester.php');

echo '{"result":1,"msg":"Все тесты выполнены"}';