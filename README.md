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
<script src="/-collect/js.php"></script>
<script>
	var data = infra.loadJSON('path/to/json.php');
</script>
```
В php
```php
use infrajs\load\Load;
$data = Load::loadJSON('path/to/json.php');
```
## Тестирование
После установки открыть файл vendor/infrajs/load/tester.php
