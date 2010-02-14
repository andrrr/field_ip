<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Error</h2><p>You cannot directly access this file</p>');

	Class extension_field_ip extends Extension{
	
		public function about(){
			return array('name' => 'Field: IP',
						'version' => '0.9',
						'release-date' => '2010-02-14',
						'author' => array('name' => 'Andrey Lubinov',
						'email' => 'andrey.lubinov@gmail.com')
				 		);
		}
		
		public function install(){
		
			try {
			
				Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_fields_ip` (
						`id` int(11) unsigned NOT NULL auto_increment,
						`field_id` int(11) unsigned NOT NULL,
						`hide` ENUM('yes', 'no') DEFAULT 'no',
						PRIMARY KEY  (`id`),
						KEY `field_id` (`field_id`)
				);");

			} catch(Exception $e) { return false; }

			return true;
		}
		
		public function uninstall(){
		
			Symphony::Database()->query('DROP TABLE IF EXISTS `tbl_fields_ip`');
			
		}
		
	}

?>