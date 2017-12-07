<? namespace Intervolga\Migrato\Data;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Intervolga\Migrato\Tool\ExceptionText;
use Intervolga\Migrato\Tool\XmlIdProvider\OrmXmlIdProvider;

abstract class BaseOrmData extends BaseData
{
    const ORM_ENTITY_PARENT_CLASS_NAME = '\Bitrix\Main\Entity\DataManager';
    const ORM_BOOLEAN_FIELD_CLASS_NAME = '\Bitrix\Main\Entity\BooleanField';
    const ORM_DATETIME_FIELD_CLASS_NAME = '\Bitrix\Main\Entity\DatetimeField';

    private $moduleName = '';
    private $ormEntityClass = '';

    /**
     * @return string
     */
    public function getOrmEntityClass()
    {
        return $this->ormEntityClass;
    }

    /**
     * @param string $ormEntityClass
     */
    public function setOrmEntityClass($ormEntityClass)
    {
        $this->ormEntityClass = $ormEntityClass;
    }

    /**
     * @return string
     */
    public function getModule()
    {
        return $this->moduleName;
    }

    /**
     * @param string $moduleName
     */
    public function setModule($moduleName)
    {
        $this->moduleName = $moduleName;
    }

    public function update(Record $record)
    {
        $dataManger = $this->ormEntityClass;
        $id = $record->getId()->getValue();
        $recordAsArray = $this->recordToArray($record);

        $updateResult = $dataManger::update($id, $recordAsArray);
        if(!$updateResult->isSuccess())
        {
            throw new \Exception(ExceptionText::getFromResult($updateResult));
        }
    }

    public function getList(array $filter = array())
    {
        $ormEntityElements = [];
        $dataManager = $this->ormEntityClass;

        $getListResult = $dataManager::getList();
        while($ormEntityElement = $getListResult->fetch())
        {
            $record = new Record($this);
            $recordId = $this->createId($ormEntityElement['ID']);
            $record->setId($recordId);
            $record->setXmlId($ormEntityElement['XML_ID']);
            $record->addFieldsRaw($this->getElementFields($ormEntityElement));

            $ormEntityElements[] = $record;
        }

        return $ormEntityElements;
    }

    abstract public function configureOrm();

    protected function configure()
    {
        $this->configureOrm();
        $this->processUserOrmEntity();

        $this->setEntityNameLoc($this->entityName);
        $this->xmlIdProvider = new OrmXmlIdProvider($this, $this->ormEntityClass);

    }

    protected function createInner(Record $record)
    {
        $dataManager = $this->ormEntityClass;
        $recordAsArray = $this->recordToArray($record);

        $addResult = $dataManager::add($recordAsArray);
        if($addResult->isSuccess())
        {
            return $this->createId($addResult->getId());
        }
        else
        {
            throw new \Exception(ExceptionText::getFromResult($addResult));
        }
    }

    protected function deleteInner(RecordId $id)
    {
        $dataManager = $this->ormEntityClass;

        $deleteResult = $dataManager::delete($id->getValue());
        if(!$deleteResult->isSuccess())
        {
            throw new \Exception(ExceptionText::getFromResult($deleteResult));
        }
    }

    private function processUserOrmEntity()
    {
        if(Loader::includeModule($this->moduleName))
        {
            $this->processEntityClassName($this->ormEntityClass);
            $this->processEntityName($this->entityName);
        }
        else
        {
            $exceptionMessage = Loc::getMessage(
                'INTERVOLGA_MIGRATO.ORM_ENTITY.MODULE_NOT_INCLUDED',
                [
                    '#ORM_ENTITY#' => $this->ormEntityClass,
                    '#MODULE_NAME#' => $this->moduleName

                ]
            );
            throw new ArgumentException($exceptionMessage);
        }
    }

    private function getElementFields($ormEntityElement)
    {
        $fieldsRaw = [];
        $dataManager = $this->ormEntityClass;

        $fields = $dataManager::getEntity()->getFields();
        $fieldNames = array_keys($fields);

        foreach ($fieldNames as $key)
        {
            $fieldsRaw[$key] = $ormEntityElement[$key];
        }

        return $fieldsRaw;
    }

    private function processEntityClassName($entityClassName)
    {
        if(is_string($entityClassName))
        {
            if($entityClassName[0] !== '\\')
            {
                $entityClassName = '\\' . $entityClassName;
            }
        }
        else
        {
            $exceptionMessage = $this->getMessageWithOrmEntity(
                'INTERVOLGA_MIGRATO.ORM_ENTITY.NOT_STRING_ARGUMENT',
                $entityClassName
            );
            throw new ArgumentException($exceptionMessage);
        }

        $isCorrect = $this->isEntityClassNameCorrect($entityClassName);
        if($isCorrect->result != true)
        {
            throw new ArgumentException($isCorrect->message);
        }
    }

    private function processEntityName($entityName)
    {
        if($entityName == '')
        {
            $this->entityName = substr(strrchr($this->ormEntityClass, '\\'), 1);

        }
    }

    private function isEntityClassNameCorrect($entityClassName)
    {
        $result = null;

        try
        {
            $entityReflectionClass = new \ReflectionClass($entityClassName);

            if(!$entityReflectionClass->isSubclassOf(static::ORM_ENTITY_PARENT_CLASS_NAME))
            {
                $result = $this->getResultObject(
                    false,
                    $this->getMessageWithOrmEntity(
                        'INTERVOLGA_MIGRATO.ORM_ENTITY.NOT_PROPER_SUBCLASS',
                        $entityClassName
                    )
                );
            }
            else
            {
                $result = $this->getResultObject(true);
            }
        }
        catch(\ReflectionException $exc)
        {
            $result = $this->getResultObject(
                false,
                $this->getMessageWithOrmEntity(
                    'INTERVOLGA_MIGRATO.ORM_ENTITY.NOT_EXISTS',
                    $entityClassName
                )
            );
        }

        return $result;
    }

    private function getResultObject($result, $message = 'ok')
    {
        return (object)[
            'result' => $result,
            'message' => $message
        ];
    }

    private function getMessageWithOrmEntity($messageCode, $entityClassName)
    {
        return Loc::getMessage(
            $messageCode,
            ['#ORM_ENTITY#' => $entityClassName]
        );
    }

    private function recordToArray(Record $record)
    {
        $recordAsArray = $record->getFieldsRaw();
        $this->castFields($recordAsArray);

        return $recordAsArray;
    }

    private function castFields(&$recordAsArray)
    {
        $dataManager = $this->ormEntityClass;
        $booleanField = static::ORM_BOOLEAN_FIELD_CLASS_NAME;
        $dataTimeField = static::ORM_DATETIME_FIELD_CLASS_NAME;

        $fields = $dataManager::getEntity()->getFields();
        foreach ($fields as $field)
        {
            $fieldName = $field->getName();
            if($field instanceof $booleanField)
            {
                $recordAsArray[$fieldName] = boolval($recordAsArray[$fieldName]);
            }
            elseif($field instanceof $dataTimeField)
            {
                $recordAsArray[$fieldName] = new DateTime($recordAsArray[$fieldName]);
            }
        }
    }

    // TODO: Add casting for DateField, FloatField, EnumField. Test before adding.
    // TODO: Check if has xml id
}