<?php 
require_once 'book.php';
/**
 * cok
 */
class nhentaiSearch{
	public $search;
	public $result;
	function __construct($data) {
		if ($data != null) {			
			if (is_string($data)) {
				$p = file_get_contents($data, false, getContext);
				$this->search = json_decode($p,true);
			}elseif (is_array($data)) {
				$this->search = $data;
			}else {
				return false;
			}
		}else {
			return false;
		}
		
	}
	function results(){
		if (gettype($this->result) == "NULL") {
			return $this->result = $this->search;
		}
		return $this->result;
	}
	function num_pages() {
		return $this->search['num_pages'];
	}
	function per_page() {
		return $this->search['per_page'];
	}

}





























 ?>