<?namespace Intervolga\Migrato\Tool;

class Page
{
	/**
	 * @throws \Exception
	 */
	public static function checkRights()
	{
		global $USER;
		if(static::isCli())
		{
			$USER->Authorize(1);
		}
		if (!static::isCli() && !$USER->IsAdmin())
		{
			throw new \Exception("Need cli or admin login");
		}
	}

	/**
	 * @param \Exception $exception
	 */
	public static function handleException(\Exception $exception)
	{
		$report = array(
			"EXCEPTION (Class: " . get_class($exception) . ")",
			"Message: " . $exception->getMessage() . " (Code: " . $exception->getCode() . ")",
			"Location: " . $exception->getFile() . ":" . $exception->getLine(),
			"Trace:",
			"",
			$exception->getTraceAsString(),
		);
		static::showReport($report);
	}

	/**
	 * @param string[] $report
	 */
	public static function showReport(array $report)
	{
		if (static::isCli())
		{
			foreach ($report as $i => $string)
			{
				$report[$i] = iconv("UTF-8", "cp1251", $string);
			}

			echo implode("\r\n", $report)."\r\n";
			global $USER;
			$USER->Logout();
		}
		else
		{
			echo "<pre>" . implode("<br>", $report) . "</pre>";
		}
	}

	/**
	 * @return bool
	 */
	public static function isCli()
	{
		return php_sapi_name() == "cli";
	}
}