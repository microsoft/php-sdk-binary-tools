<?php

namespace SDK\Build\PGO\Server;

use SDK\Build\PGO\Interfaces\Server\DB;
use SDK\Build\PGO\Abstracts\Server;
use SDK\Build\PGO\Config as PGOConfig;
use SDK\{Exception, FileOps};
use SDK\Build\PGO\Tool\PackageWorkman;

class PostgreSQL extends Server implements DB
{
	use FileOps;

	protected $conf;
	protected $base;
	protected $data_dir;
	protected $name = "PostgreSQL";

	public function __construct(PGOConfig $conf)
	{
		$this->conf = $conf;
		$this->base = $conf->getSrvDir(strtolower($this->name));
		$this->data_dir = $this->base . DIRECTORY_SEPARATOR . "data";
	}

	protected function setupDist()
	{
		$user = $this->conf->getSectionItem($this->name, "user");
		if (!$user) {
			$user = trim(shell_exec("pwgen -1 -s 8"));
			$this->conf->setSectionItem($this->getName(), "user", $user);
		}
		$pass = $this->conf->getSectionItem($this->name, "pass");
		if (!$pass) {
			$pass = trim(shell_exec("pwgen -1 -s 8"));
			$this->conf->setSectionItem($this->getName(), "pass", $pass);
		}

		if (!is_dir($this->data_dir)) {
			$pwfile = tempnam(sys_get_temp_dir(), "tmp");
			if (strlen($pass) !== file_put_contents($pwfile, $pass)) {
				throw new Exception("Couldn't write '$pwfile'.");
			}
			$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "initdb.exe --auth=trust --nosync --username=$user --pwfile=$pwfile --encoding=UTF8 " . $this->data_dir;
			//$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "initdb.exe --auth=trust --nosync --username=$user --encoding=UTF8 " . $this->data_dir;
			/*echo "$cmd\n";
			echo file_get_contents($pwfile) . "\n";*/
			exec($cmd);
			unlink($pwfile);
		}
	}

	public function prepareInit(PackageWorkman $pw, bool $force = false) : void
	{
		$url = $this->conf->getSectionItem($this->name, "pkg_url");
		$pw->fetchAndUnzip($url, "postgresql.zip", $this->conf->getSrvDir(), "postgresql", $force);
	}

	public function init() : void
	{
		echo "Initializing " . $this->name . ".\n";

		$this->setupDist();

		$this->up();
		$this->down(true);

		echo $this->name . " initialization done.\n";
	}

	public function up() : void
	{
		echo "Starting " . $this->name . ".\n";


		$host = $this->conf->getSectionItem($this->name, "host");
		$port = $this->conf->getSectionItem($this->name, "port");

		$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "pg_ctl.exe start -D " . $this->data_dir . " -o \"-h $host -p $port\"";
		$h = popen($cmd, "r");
		/* XXX error check*/
		pclose($h);

		echo $this->name . " started.\n";
	}

	public function down(bool $force = false) : void
	{
		echo "Stopping " . $this->name . ".\n";


		$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "pg_ctl.exe stop -D " . $this->data_dir . " -m fast";
		exec($cmd);

		if ($force) {
			//sleep(1);
			//exec("taskkill /f /im nginx.exe >nul 2>&1");
		}

		echo $this->name . " stopped.\n";
	}

	public function createDb(string $db_name) : void
	{
		$user = $this->conf->getSectionItem($this->name, "user");
		$host = $this->conf->getSectionItem($this->name, "host");
		$port = $this->conf->getSectionItem($this->name, "port");

		$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "createdb.exe -h $host -p $port -U $user $db_name";
		exec($cmd);
	}

	public function dropDb(string $db_name) : void
	{
		$user = $this->conf->getSectionItem($this->name, "user");
		$host = $this->conf->getSectionItem($this->name, "host");
		$port = $this->conf->getSectionItem($this->name, "port");

		$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "dropdb.exe --if-exists -h $host -p $port -U $user $db_name";
		exec($cmd);
	}

	public function query(string $s, string $db = NULL) : void
	{
		$ret = NULL;

		$user = $this->conf->getSectionItem($this->name, "user");
		$host = $this->conf->getSectionItem($this->name, "host");
		$port = $this->conf->getSectionItem($this->name, "port");

		$db_arg = $db ? "-d $db" : "";
		$cmd = $this->base . DIRECTORY_SEPARATOR . "bin" . DIRECTORY_SEPARATOR . "psql.exe -h $host -p $port -U $user $db_arg -c \"$s\"";
		shell_exec($cmd);
	}
}
