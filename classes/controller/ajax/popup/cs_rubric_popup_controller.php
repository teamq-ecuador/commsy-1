<?php
	interface cs_rubric_popup_controller {
		public function initPopup($item);
		
		public function getFieldInformation($sub = '');
		
		public function save($form_data, $additional = array());
		
		public function getReturn();
	}