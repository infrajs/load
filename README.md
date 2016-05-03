# Загрузка данных
Получение json данных сгенерированных php без использования file_get_contents. С помощью require и return $data.
Единообразное получение json-ответа php файлов и json данных на сервере и на клиенте.

## Установка через composer
{
	"require":{
		"infrajs/load":"~1",
		"infrajs/collect":"~1"
	}
}

## Использование
Все зависимости собираются с помощью сборщика [infrajs/collect](https://github.com/infrajs/collect)
```
<script src="-collect/js.php"></script>
```

## Тестирование
После установки открыть файл vendor/infrajs/load/tester.php