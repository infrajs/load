import { Path } from '/vendor/infrajs/path/Path.js'

let Load = {};
Load.unload = function (path) { //{status:200,value:''};
	var store = Load.store();
	delete store['require'][path];
	delete store['loadJSON'][path];
	delete store['loadTEXT'][path];
	//loadCSS
}
Load.loadCSS = function (src) {
	var store = Load.store('loadCSS');
	if (store[src]) return;
	store[src] = true;

	//var href=Load.theme(src);
	var style = document.createElement('style');
	//style.innerHTML='@import url("'+href+'")';
	text = Load.loadTEXT(src);
	style.innerHTML = text;
	document.getElementsByTagName('head')[0].appendChild(style);

	/*var link=document.createElement('link');
	link.type="text/css";
	link.rel="stylesheet";
	link.href=href;
	document.getElementsByTagName('head')[0].appendChild(link);*/
}
Load.store = function (name) {
	if (!name) return this.store.data;
	if (!this.store.data[name]) this.store.data[name] = {};
	return this.store.data[name];
}
Load.store.data = {
	"require": [],
	"loadJSON": [],
	"loadTEXT": []
}
Load.require = function (path) {
	var store = Load.store('require');
	if (store[path]) return store[path].value;
	store[path] = { value: true };//Метку надо ставить заранее чтобы небыло зацикливаний
	var text = Load.loadTEXT(path);

	var script = document.createElement('script');
	script.setAttribute('data-path', path);
	//try{
	//		script.appendChild(document.createTextNode(text));
	//}catch(e){//IE script не может иметь вложенный тег http://www.htmlcodetutorial.com/comments/viewtopic.php?p=2801
	script.text = text;
	//}
	document.getElementsByTagName('head')[0].appendChild(script);//document.head в ie не работает

}

Load.theme = Path.theme;

//Load.theme.prefix = '-nostore=true';
Load.loadJSON = function (path) {
	var store = Load.store('loadJSON');
	if (store[path]) {
		return store[path].value;
	}
	var text = Load.loadTEXT(path);
	store[path] = {};
	try {
		store[path].value = eval('(' + text + ')');
		store[path].status = true;
	} catch (e) {
		store[path].status = false;
	}
	return store[path].value;
}

Load._load = function (path) {//Такая функция есть в php.. возвращает иногда перменную массив а не строку
	return Load.loadTEXT(path);
}
Load.loadTEXT = function (path) {
	var store = Load.store('loadTEXT');
	if (store[path]) {
		return store[path].value;
	}
	var load_path = Load.theme(path);
	if (Load.theme.prefix) {
		if (/[\?]/.test(load_path)) load_path += '&' + Load.theme.prefix;
		else load_path += '?' + Load.theme.prefix;
	}
	var transport = false;
	var actions = [
		function () { return new XMLHttpRequest() },
		function () { return new ActiveXObject('Msxml2.XMLHTTP') },
		function () { return new ActiveXObject('Microsoft.XMLHTTP') }
	];
	for (var i = 0; i < actions.length; i++) {
		try {
			transport = actions[i]();
			break;
		} catch (e) { }
	}
	transport.open('GET', load_path, false);
	transport.setRequestHeader("Content-Type", "text/plain; charset=UTF-8");
	transport.send(null);
	var res = {};
	if (transport.readyState == 4) {
		if (transport.status == 200) {
			res.status = 200;
			res.value = transport.responseText;
		} else {
			res.status = transport.status;
			res.value = transport.responseText;
		}
	}
	store[path] = res;
	return store[path].value;
}

Load.forFS = function (str) {
	str = str.replace(/[\+\*<>\'"\|\:\/\\\\#\?\$&\s]/g, ' ');
	str = str.replace(/^\s+/g, '');
	str = str.replace(/\s+$/g, '');
	str = str.replace(/\s+/g, ' ');
	//str=str.replace(/\s/g,'-'); много переделывать, пробел остаётся посередине как есть. todo вынести этот параметр в конфиг
	return str;
}

Load.srcinfo = function (src) {
	var store = Load.store('srcinfo');
	if (store[src]) return store[src];
	var p = src.split('?');
	var file = p.shift();
	if (p.length) var query = '?' + p.join('?');
	else var query = '';

	p = file.split('/');
	file = p.pop();

	if (p.length == 0 && file.test(/^\-/)) {
		file = file.replace(/^\-/, '');
		p.push('-');
	}
	if (p.length == 0 && file.test(/^\!/)) {
		file = file.replace(/^\!/, '');
		p.push('!');
	}
	if (p.length == 0 && file.test(/^\~/)) {
		file = file.replace(/^\~/, '');
		p.push('~');
	}
	var folder = p.join('/');
	if (folder) folder += '/';

	var fdata = Load.nameinfo(file);

	fdata['query'] = query;
	fdata['src'] = src;
	fdata['path'] = folder + file;
	fdata['folder'] = folder;
	store[src] = fdata;
	return store[src];
}
Load.nameinfo = function (file) {//Имя файла без папок// Звёздочки быть не может
	var p = file.split('.');
	if (p.length > 1) {
		var ext = p.pop();
		var name = p.join('.');
		if (!name) {
			name = file;
			ext = '';
		}
	} else {
		ext = '';
		name = file;
	}
	var match = name.match(/^(\d{6})[\s\.]/);
	var date = match[1];
	var name = name.replace(/^\d+[\s\.]/, '');
	var ar = name.split("@");
	if (ar.length > 1) {
		var id = ar.pop();
		if (!id) id = 0;
		var idi = Number(id);
		idi = String(idi);//12 = '12 asdf' а если и то и то строка '12'!='12 asdf'
		if (id == idi) {
			name = ar.join('@');
		} else {
			id = false;
		}
	}
	var ans = {
		'id': id,
		'name': name.replace(/^\s+/, '').replace(/\s+$/, ''),
		'file': file,
		'date': date,
		'ext': ext
	};
	return ans;
}
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

//require('no-cache') не сохраняется в localStorage??
//require('no-cache') не сохраняется в localStorage

window.Load = Load
export { Load }