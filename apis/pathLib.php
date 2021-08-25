<?php
function GetCurFullPath(string $filename): string {
	return getcwd() . DIRECTORY_SEPARATOR . $filename;
}
function ReplacePathSeparator(string $path): string {
	return str_replace('/', DIRECTORY_SEPARATOR, $path);
}
?>
