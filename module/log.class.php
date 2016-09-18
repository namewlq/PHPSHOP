<?php
/**
 * Name: log类,用来记录日志
 * Date: 2016-9-18 17:11
 * Log: 
 */

class log
{
	private $logDir;
	
	public function __construct()
	{
		$this->logDir =  '../logs/';
	}
	
	public function addLog($log, $dir, $fileName)
	{
		$dirPath = $this->logDir . $dir;
		$date = new DateTime();
		if (is_dir($dirPath))
		{
			$file = $dirPath . '/' . $fileName . '-' . $date->format('Y-m-d') . '.log';
			$content = 'Log Time: ' . $date->format('Y-m-d H:i:s') . "\r\n" . $log . "\r\n";
			file_put_contents($file, $content, FILE_APPEND);
		}
		else
		{
			mkdir($dirPath);
			$this->addLog($log, $dir, $fileName);
		}
	}
}