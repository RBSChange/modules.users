<?php

class users_ExportAction extends change_Action
{	
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
    {
		$group = $this->getDocumentInstanceFromRequest($request);
		$users = users_UserService::getInstance()->createQuery()
			->add(Restrictions::eq('groups.id', $group->getId()))
			->add(Restrictions::published())->find();
    	
        if (count($users) == 0)
        {
            $fieldNames = $this->generateFields(f_persistentdocument_PersistentDocumentModel::getInstance('users', 'user'));
            $rows = array();
        }
        else
        {
            $fieldNames = $this->generateFields($users[0]->getPersistentModel()); 
            $rows = array(); 
            $names = array_keys($fieldNames);      
    		foreach ($users as $user)
    		{
    			$rows[] = $this->getValues($user, $names);
    		}
        }

		$fileName = "export_group_".f_util_FileUtils::cleanFilename($group->getLabel()).'_'.date('Ymd_His').'.csv';
		$options = new f_util_CSVUtils_export_options();
		$options->separator = ";";
		
		$csv = f_util_CSVUtils::export($fieldNames, $rows, $options);		
		header("Content-type: text/comma-separated-values");
		header('Content-length: '.strlen($csv));
		header('Content-disposition: attachment; filename="'.$fileName.'"');
		echo $csv;
		exit;
    }
    
    /**
     * @param f_persistentdocument_PersistentDocumentModel $model
     */
    private function generateFields($model)
    {
        $fieldNames = array();
        $key = '&modules.' . $model->getModuleName() . '.document.' . $model->getDocumentName() . '.';
        
        foreach ($model->getPropertiesInfos() as $property) 
        {
            if ($property->isDocument() && $property->isArray()) {continue;}
            $name = $property->getName();
            $translation = f_Locale::translate($key. ucfirst($name) . ';', null, null, false);
            if ($translation)
            {
            	$fieldNames[$name] = $translation;
            }
        }
        return $fieldNames;
    }
    
    /**
     * @param f_persistentdocument_PersistentDocument $document
     */
    private function getValues($document, $names)
    {
        $values = array();
        $model = $document->getPersistentModel();
		foreach ($names as $propertyName)
		{
		    $property = $model->getProperty($propertyName);
		    if ($property === null)
		    {
		        $values[$propertyName] = '';
		    } 
		    else if ($property->getType() == 'DateTime')
		    {
		        $getter = 'getUI'.ucfirst($propertyName);
		        $values[$propertyName] = $document->{$getter}();   
		    } 
			else if ($property->getType() == 'Boolean')
		    {
		        $getter = 'get'.ucfirst($propertyName);
		        $values[$propertyName] = $document->{$getter}() ? f_Locale::translate('&modules.uixul.bo.general.Yes;') : f_Locale::translate('&modules.uixul.bo.general.No;');   
		    }
			else if ($property->isDocument() && !$property->isArray())
		    {
		        $getter = 'get'.ucfirst($propertyName);
		        $doc = $document->{$getter}();
		        $values[$propertyName] = ($doc !== null) ? $doc->getLabel() : '';   
		    }
		    else
		    {
		        $getter = 'get'.ucfirst($propertyName);
		        $values[$propertyName] = $document->{$getter}();   
		    }
		}
		return $values;
	}
}