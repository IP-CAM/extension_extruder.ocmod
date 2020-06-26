<?php
class ModelExtensionExtensionExtruder extends Model {
	public function getOcmod($code){
        $query = $this->db->query("SELECT `xml` FROM ".DB_PREFIX."modification WHERE code='$code'");
        if($query->row) return $query->row['xml'];
        return false;
    }
}