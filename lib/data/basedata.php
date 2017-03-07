<? namespace Intervolga\Migrato\Data;

use Bitrix\Main\NotImplementedException;
use Intervolga\Migrato\Tool\XmlIdProvider\BaseXmlIdProvider;

abstract class BaseData
{
	protected static $instances = array();
	protected $xmlIdProvider = null;

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		if (!static::$instances[get_called_class()])
		{
			static::$instances[get_called_class()] = new static();
		}

		return static::$instances[get_called_class()];
	}

	/**
	 * @param string[] $filter
	 *
	 * @return \Intervolga\Migrato\Data\Record[]
	 */
	abstract public function getList(array $filter = array());

	/**
	 * @param Record $record
	 *
	 * @throws NotImplementedException
	 */
	public function update(Record $record)
	{
		throw new NotImplementedException("Update for " . $record->getData()->getModule() . "/" . $record->getData()->getEntityName() . " is not yet implemented");
	}

	/**
	 * @param Record $record
	 *
	 * @throws NotImplementedException
	 *
	 * @return \Intervolga\Migrato\Data\RecordId
	 */
	public function create(Record $record)
	{
		throw new NotImplementedException("Create for " . $record->getData()->getModule() . "/" . $record->getData()->getEntityName() . " is not yet implemented");
	}

	/**
	 * @param string $xmlId
	 *
	 * @throws NotImplementedException
	 */
	public function delete($xmlId)
	{
		throw new NotImplementedException("Delete for " . $this->getModule() . "/" . $this->getEntityName() . " ($xmlId) is not yet implemented");
	}

	/**
	 * @param string $xmlId
	 *
	 * @return \Intervolga\Migrato\Data\RecordId|null
	 */
	public function findRecord($xmlId)
	{
		$findRecords = static::findRecords(array($xmlId));
		return $findRecords[$xmlId];
	}

	/**
	 * @param string[] $xmlIds
	 *
	 * @return \Intervolga\Migrato\Data\RecordId|null
	 */
	public function findRecords(array $xmlIds)
	{
		$result = array();
		$allRecords = static::getList();
		foreach ($allRecords as $dbRecord)
		{
			if (in_array($dbRecord->getXmlId(), $xmlIds))
			{
				$result[$dbRecord->getXmlId()] = $dbRecord->getId();
			}
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function getModule()
	{
		$class = get_called_class();
		$tmp = str_replace("Intervolga\\Migrato\\Data\\Module\\", "", $class);
		$tmp = substr($tmp, 0, strpos($tmp, "\\"));
		$tmp = strtolower($tmp);

		return $tmp;
	}

	/**
	 * @return string
	 */
	public function getEntityName()
	{
		$class = get_called_class();
		$tmp = substr($class, strrpos($class, "\\") + 1);
		$tmp = strtolower($tmp);

		return $tmp;
	}

	/**
	 * @return string
	 */
	public function getFilesSubdir()
	{
		return "/";
	}

	/**
	 * @return \Intervolga\Migrato\Data\Runtime[]
	 */
	public function getRuntimes()
	{
		return array();
	}

	/**
	 * @param string $name
	 *
	 * @return \Intervolga\Migrato\Data\Runtime
	 */
	public function getRuntime($name)
	{
		$runtimes = $this->getRuntimes();

		return $runtimes[$name];
	}

	/**
	 * @return Link[]
	 */
	public function getDependencies()
	{
		return array();
	}

	/**
	 * @param string $name
	 *
	 * @return Link
	 */
	public function getDependency($name)
	{
		$dependencies = $this->getDependencies();

		return $dependencies[$name];
	}

	/**
	 * @return Link[]
	 */
	public function getReferences()
	{
		return array();
	}

	/**
	 * @param string $name
	 *
	 * @return Link
	 */
	public function getReference($name)
	{
		$references = $this->getReferences();

		return $references[$name];
	}

	/**
	 * @return BaseXmlIdProvider
	 */
	public function getXmlIdProvider()
	{
		return $this->xmlIdProvider;
	}

	/**
	 * @param mixed $id
	 *
	 * @return RecordId
	 */
	public function createId($id)
	{
		return RecordId::createNumericId($id);
	}
}