<?php
class ControllerExtensionExtensionExtruder extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/extension_extruder');
		if(isset($this->request->get["code"])){
			if($this->validate()){
				$this->extrude($this->request->get["code"]);
			}else{
				$this->session->data['success'] = $this->language->get('error_permission');
				$this->response->redirect($this->url->link('extension/modification', 'token=' . $this->session->data['token'], true));
			}
		}else{
			$this->session->data['success'] = $this->language->get('text_no_code');
			$this->response->redirect($this->url->link('extension/modification', 'token=' . $this->session->data['token'], true));
		}
	}
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/extension_extruder')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
	private function extrude($extension){
		if(!$this->validate()){
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=module', true));
		}
        $extension = preg_replace("/[^a-zA-Z0-9\-\_\.]/","",$extension);
        $mainFolders = array("controller","model","language");
        $r = array();
        foreach($mainFolders as $f){
            $this->recoursiveSearch(DIR_APPLICATION.$f,$extension,$r);
            $this->recoursiveSearch(DIR_CATALOG.$f,$extension,$r);
        }
        $theme = str_replace("_","/",$this->config->get('config_theme'));
        $this->recoursiveSearch(DIR_APPLICATION."view",$extension,$r);
		$this->recoursiveSearch(DIR_CATALOG."view/$theme",$extension,$r);
		
		$OCFolder = preg_replace("/admin\//","",DIR_APPLICATION);
		$zip = new ZipArchive();
		$zip_name = DIR_DOWNLOAD."$extension.zip";
		$zip->open($zip_name, ZipArchive::CREATE | ZipArchive::OVERWRITE);
		$zip->addEmptyDir("upload");

		$extFullName = "";
		foreach($r as $file){
			if(stristr($file,"/admin/controller/"))
				$extFullName = explode("/admin/controller/",str_replace(".php","",$file))[1];
			$relativeFileName = str_replace($OCFolder,"",$file);
			$zip->addFile($file,"upload/$relativeFileName");
		}

		/* creating install.xml */
		$this->load->model('extension/extension_extruder');
		$xml = $this->model_extension_extension_extruder->getOcmod($extension);
		if($xml) $zip->addFromString("install.xml",$xml);

		/* creating install.php to get permissions to work with this module */
		$code='<?php
$this->load->model("user/user_group");
$this->model_user_user_group->addPermission($this->user->getGroupId(), "access", "'.$extFullName.'");
$this->model_user_user_group->addPermission($this->user->getGroupId(), "modify", "'.$extFullName.'");';
		if($extFullName) $zip->addFromString("install.php",$code);

		$zip->close();

		header("Content-type: application/zip");
		header("Content-Disposition: attachment; filename=$extension.ocmod.zip");
		readfile($zip_name);
		unlink($zip_name);
		exit;
	}
    private function recoursiveSearch($f,$extension,&$r){
        $dir = scandir($f);
        foreach($dir as $d){
            if(is_dir("$f/$d")){
                if(!stristr($d,".")) $this->recoursiveSearch("$f/$d",$extension,$r);
            }else{
                if(substr($d,0,strlen($extension)+1) == "$extension.") $r[] = "$f/$d";
            }
        }
    }
}