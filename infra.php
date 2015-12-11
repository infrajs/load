<?php
namespace infrajs\load;
use infrajs\ans\Ans;

Ans::$conf['isReturn'] = function () {
	return Load::isphp();
};
