<?php
function GetCurFullPath($filename) {
	return getcwd().DIRECTORY_SEPARATOR.$filename;
}
function ReplacePathSeparator($path) {
	return str_replace('/', DIRECTORY_SEPARATOR, $path);
}
?>
