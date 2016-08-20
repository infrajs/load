# Загрузка данных
Получение json данных сгенерированных php без использования file_get_contents. С помощью require и return $data.
Единообразное получение json-ответа php файлов и json данных на сервере и на клиенте.

## Установка через composer
```json
{
	"require":{
		"infrajs/load":"~1",
		"infrajs/collect":"~1"
	}
}
```

## Использование
Все javascript зависимости собираются с помощью сборщика [infrajs/collect](https://github.com/infrajs/collect)
```html
<script src="/-collect/?js"></script>
<script>
	var data = Load.loadJSON('path/to/json.php');
</script>
```
В php
```php
use infrajs\load\Load;
$data = Load::loadJSON('path/to/json.php');
```
## Тестирование
После установки открыть файл vendor/infrajs/load/tester.php

## API

```php

$fdata = Load::nameInfo($filename); //Возвращает подробный массив описывающий имя файла
$fdata = Load::srcInfo($filename); //Возвращает подробный массив описывающий путь до файла
//$fdata содержит name, num, date, ext - всегда в нижнем регистре, file
Load::sort($list, $order); //Сортирует массив с $fdata по цифре в начале имени файла и по текусту
//Параметр order может быть 'ascending' или 'descending' распространяется только на порядок названий с цифрами в начале. Буквы сортируются в порядке алфавита в обоих случаях
```
