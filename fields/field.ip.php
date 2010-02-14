<?php
	
	Class fieldIp extends Field {
	
		public function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = __('IP');
			
			// Set default
			$this->set('show_column', 'no');
			$this->set('hide', 'no');

		}

		public function allowDatasourceOutputGrouping(){
			return true;
		}
		
		public function allowDatasourceParamOutput(){
			return true;
		}

		public function groupRecords($records){
			
			if(!is_array($records) || empty($records)) return;
			
			$groups = array($this->get('element_name') => array());
			
			foreach($records as $r){
				$data = $r->getData($this->get('id'));
				
				$value = $data['value'];
				
				if(!isset($groups[$this->get('element_name')][$value])){
					$groups[$this->get('element_name')][$value] = array('attr' => array('value' => long2ip($value)),
																		 'records' => array(), 'groups' => array());
				}	
																					
				$groups[$this->get('element_name')][$value]['records'][] = $r;
								
			}

			return $groups;
		}

		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
		
		
			if ($this->get('hide') != 'yes') {
			
				$value = long2ip($data['value']);
				
				$label = Widget::Label($this->get('label'));
				$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL), 'text',
array('disabled'=>'disabled')));

				if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
				else $wrapper->appendChild($label);
			
			}
			
		}
		
		public function isSortable(){
			return true;
		}
		
		public function canFilter(){
			return true;
		}
		
		public function canImport(){
			return true;
		}

		public function cleanValue($value) {
			return ip2long(html_entity_decode($this->Database->cleanValue($value)));
		}

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC'){
			$joins .= "LEFT OUTER JOIN `tbl_entries_data_".$this->get('id')."` AS `ed` ON (`e`.`id` = `ed`.`entry_id`) ";
			$sort = 'ORDER BY ' . (in_array(strtolower($order), array('random', 'rand')) ? 'RAND()' : "`ed`.`value` $order");
		}

		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {
			$field_id = $this->get('id');

			if (self::isFilterRegex($data[0])) {
				$this->_key++;
				$pattern = str_replace('regexp:', '', parent::cleanValue($data[0]));
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND INET_NTOA(t{$field_id}_{$this->_key}.value) REGEXP '{$pattern}'
				";
				
			} elseif ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND t{$field_id}_{$this->_key}.value = '{$value}'
					";
				}
				
			} else {
				if (!is_array($data)) $data = array($data);
				
				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}
				
				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
						ON (e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND t{$field_id}_{$this->_key}.value IN ('{$data}')
				";
			}
			
			return true;
		}

		private function __applyValidationRule($data){			
			return General::validateString($data, '/^\d{1,3}[.]\d{1,3}[.]\d{1,3}[.]\d{1,3}$/');
		}
		
		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
		
			$status = self::__OK__;
			$message = NULL;
			
			$data = $_SERVER['REMOTE_ADDR'];
			
			if(!$this->__applyValidationRule($data)) $data = '0.0.0.0';
		
			$result = array(
				'value' => ip2long($data)
			);
			
			return $result;
		}
		
		public function prepareTableValue($data, XMLElement $link=NULL) {
			
			$value = long2ip($data['value']);
			
			if ($link) {
				$link->setValue($value);
				
				return $link->generate();
			}
			
			return $value;
		}

		public function canPrePopulate(){
			return true;
		}

		public function appendFormattedElement(&$wrapper, $data, $encode=false){

			$value = long2ip($data['value']);

			$wrapper->appendChild(
				new XMLElement(
					$this->get('element_name'), $value, array('raw' => $data['value'])
				)
			);
		}
		
		public function commit(){

			if(!parent::commit()) return false;
			
			$id = $this->get('id');

			if($id === false) return false;
			
			$fields = array();
			
			$fields['field_id'] = $id;
			$fields['hide'] = $this->get('hide');
			
			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
				
			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());
					
		}

		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);	
			
			$this->appendShowColumnCheckbox($wrapper);	
			
			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][hide]', 'yes', 'checkbox');
			if ($this->get('hide') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue(__('%s Hide this field on publish page', array($input->generate())));
			$wrapper->appendChild($label);

		}

		public function createTable(){
			
			return Symphony::Database()->query(
			
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `value` int(10) unsigned NOT NULL,
				  PRIMARY KEY  (`id`),
				  KEY `entry_id` (`entry_id`),
				  KEY `value` (`value`)
				) TYPE=MyISAM;"
			
			);
		}		

	}

