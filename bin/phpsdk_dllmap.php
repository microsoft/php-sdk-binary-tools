<?php

/*
	- go through all zip files in the given dirs non recursively
	- read all the dll filenames from those zips
	- create mappings between dll filename and zip filename

	Usage:
	php dllmap.php path0 [ path1 ... ] > dllmapping.json
*/


/*$dirs = array(
	"C:\\tmp\\core_deps\\vc9\\x86",
	"C:\\tmp\\core_deps\\vc11\\x86",
	"C:\\tmp\\core_deps\\vc11\\x64",
);*/

/*$dirs = array(
	"C:\\tmp\\libs",
);*/

$dirs = array();
foreach (array_slice($_SERVER["argv"], 1) as $item) {
	if (file_exists($item) && is_dir($item)) {
		$dirs[] = $item;
	}
}

if (empty($dirs)) {
	echo "Nothing to do\n";
	die;
}


$out = array(
	"vc9" => array(
		"x86" => array(),
		"x64" => array(),
	),
	"vc11" => array(
		"x86" => array(),
		"x64" => array(),
	),
	"vc12" => array(
		"x86" => array(),
		"x64" => array(),
	),
	"vc14" => array(
		"x86" => array(),
		"x64" => array(),
	),
);


foreach ($dirs as $path) {
	$dir = new DirectoryIterator($path);
	foreach ($dir as $fileinfo) {
		if ($fileinfo->isDot() || $fileinfo->isDir()) {
			continue;
		}

		$pathname = $fileinfo->getPathname();
		$filename = $fileinfo->getFilename();

		if (substr($filename, -3) != "zip") {
			continue;
		}

		if (!preg_match(",.*-(vc\d+)-(x\d\d)\.zip,", $filename, $m)) {
			continue;
		}

		$crt = $m[1];
		$arch = $m[2];

		$zip = new ZipArchive();

		$zip->open($pathname);

		$dlls = array();

		for( $i = 0; $i < $zip->numFiles; $i++ ){
			$stat = $zip->statIndex( $i );
			
			if (substr($stat['name'], -3) != "dll") {
				continue;
			}

			$dlls[] = basename($stat['name']);
		} 
		
		$zip->close();
		unset($zip);


		if (!empty($dlls)) {
			$out[$crt][$arch][$filename] = $dlls;
		}
	}
}

echo json_encode($out);

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: sw=4 ts=4 fdm=marker
 * vim<600: sw=4 ts=4
 */
