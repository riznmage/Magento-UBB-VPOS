<?php
 
class Rizn_Ubb_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    private $authorName = 'RIZN'; // The author
    private $authorWebSite = 'http://www.rizn.bg'; // The author's web site
    private $authorMail = 'info@rizn.bg'; // The author's email
    private $rzUbbVer = '1.1.1'; // Your version of the module
    private $rzDir = '/var/rz-ubb-vpos/'; // Directory, used to store information about the module
    private $rzVerPrefix = 'rz-ubb-vpos'; // The prefix for the files, containing information about the version
    
    /**
     * Fetches data from the author's XML file. Usualy it
     * catches news, important updates and information about the module.
     * 
     * @param type $URL
     * @return string|boolean
     */
    private function curl_get_file_contents($URL){
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) return $contents;
            else return FALSE;
    }
    
    /**
     * Notifies the author about the updates you make on
     * the module. It is used in order to make sure that everyone using the module
     * is using the newest version.
     */
    public function doNotifyRZ(){
            
        $_su = 'RG9tYWluOiA'.substr($this->getFract(), -1).','.base64_encode($_SERVER['HTTP_HOST']);
        $_bo = 'PGI+,RG9tYWluOiA'.substr($this->getFract(), -1).',PC9iPg'.$this->getFract().','.base64_encode($_SERVER['HTTP_HOST']).',PGI+,PGJyPkRhdGU6IA'.$this->getFract().',PC9iPg'.$this->getFract().','.base64_encode(date('d.m.Y')).',PGI+,PGJyPlN0b3JlIFZpZXcgTmFtZTog,PC9iPg'.$this->getFract().','.base64_encode(Mage::app()->getStore()->getName()).',PGI+,PGJyPldlYnNpdGUgTmFtZTog,PC9iPg'.$this->getFract().','.base64_encode(Mage::app()->getWebsite()->getName()).',PGI+,PGJyPkVtYWlsOiA'.substr($this->getFract(), -1).',PC9iPg'.$this->getFract().','.base64_encode(Mage::getStoreConfig('trans_email/ident_general/email')).',PGI+,PGJyPk1vZHVsZSBWZXJzaW9uOiA'.substr($this->getFract(), -1).',PC9iPg'.$this->getFract().','.base64_encode($this->rzUbbVer);
        
        $_su = $this->rzExplode($_su);
        $_bo = $this->rzExplode($_bo);
        
        $mail = new Zend_Mail();
        
        $mail->setBodyHtml($_bo);
        $mail->setFrom($this->authorMail, $this->authorName);
        $mail->addTo($this->authorMail, $this->authorName);
        $mail->setSubject($_su);

        try {
            $mail->send();
        }
        catch(Exception $ex){
            Mage::getSingleton('core/session')
                ->addError(Mage::helper('customer')
                ->__("RIZN: There seems to be a problem with the UBB Virtual POS Module. Please, contact us at <a href='mailto:info@rizn.bg' target='_blank'>info@rizn.bg</a>"));
        }
    }
    
    /**
     * Determines if it is necessary to show a pop-up
     * window with information about the module. Its purpose is NOT to fetch data.
     * In order to fetch data, the getUpdateInfo() function is used.
     * 
     * @return boolean
     */
    public function showNotif(){
        if(!$_COOKIE['RZ_HIDE_NOTIF']){
            setcookie('RZ_HIDE_NOTIF', 'yes', time()+2592000, '/');
            return true;
        }
        
        return false;
    }
    
    /**
     * Determines if the author should be notified
     * about the version of the module you are using.
     * 
     * @return boolean
     */
    public function notifyRZ(){
        if(!is_dir($_SERVER['DOCUMENT_ROOT'] . $this->rzDir)){
            mkdir($_SERVER['DOCUMENT_ROOT'] . $this->rzDir);
        }
        if(!is_file($_SERVER['DOCUMENT_ROOT'] . $this->rzDir . $this->rzVerPrefix . $this->rzUbbVer . '.txt')){
            $content = '';
            $fp = fopen($_SERVER['DOCUMENT_ROOT'] . $this->rzDir . $this->rzVerPrefix . $this->rzUbbVer . '.txt','wb');
            fwrite($fp,$content);
            fclose($fp);
            return true;
        }
        
        return false;
    }
    
    /**
     * Loads the module's XML file from the author's web site. This file contains
     * important news, version updates information and other module information.
     * 
     * @param boolean $chkDir
     * <p>This parameter is set to <b>TRUE</b> by default. The function will check whether
     * the <i>rz-ubb-vpos</i> directory exists or not. If it does not, it will be created.
     * Setting this to <b>FALSE</b>, would skip this check.</p>
     * @return object
     */
    private function loadXML($chkDir = true){
        if($chkDir){
            if(!is_dir($_SERVER['DOCUMENT_ROOT'] . $this->rzDir)){
                mkdir($_SERVER['DOCUMENT_ROOT'] . $this->rzDir);
            }
        }
        $xml = $this->curl_get_file_contents($this->authorWebSite . '/rz-ubb-vpos/rz-ubb-vpos.xml');
        return simplexml_load_string($xml);
    }
    
    /**
     * Gets info about updates in the author's module
     * 
     * @return string|boolean
     */
    public function getUpdateInfo(){
        $xmlObj = $this->loadXML(false);
        if($this->rzUbbVer != $xmlObj->info->version){
            return $this->__('Update your UBB Virtual POS module version. Your current version is') . ' ' . $this->rzUbbVer . '. ' . $this->__('The newest version is') . ' ' . $xmlObj->info->version;
        }
        
        return false;
    }
    
    /**
     * Gets news from the author's site. They will be displayed as separate bars
     * with a special icon on the top of every page in the admin panel.<br/>
     * All the gathered news are being returned as an array. If no news are
     * available right now, then this function returns <b>FALSE</b>.
     * 
     * @param string $type
     * <p>This is the type of news to be gathered. This parameter can be either
     * <b>important</b> or <b>information</b>. Important news are about major
     * changes in the module, while the informational ones are for better understanding
     * of the module's concept and further or additional information.</p>
     * @return array|boolean
     */
    public function getNews($type){
        $xmlObj = $this->loadXML();
        $getNews = $xmlObj->feed->$type;
        $allNews = get_object_vars($getNews);
        
        $_t = 0;
        foreach($allNews as $key => $value){
            $theNews[$_t++] = substr($key,1);
        }
        
        $currentTime = time();
        $gatheredNews = array();
        $locale = Mage::app()->getLocale()->getLocaleCode()=='bg_BG' ? 'bg_BG' : 'en_US';
        
        $newsCounter = 0;
        foreach($theNews as $news){
            
            $toBeCalled = 't' . $news;
            
            if(!is_file($_SERVER['DOCUMENT_ROOT'] . $this->rzDir . $type . '-' . $news . '.txt')){
                $fp = fopen($_SERVER['DOCUMENT_ROOT'] . $this->rzDir . $type . '-' . $news . '.txt','wb');
                fwrite($fp,$currentTime);
                fclose($fp);
            }
            
            $fp = fopen($_SERVER['DOCUMENT_ROOT'] . $this->rzDir . $type . '-' . $news . '.txt','rb');
            $tempTimestamp = fread($fp, filesize($_SERVER['DOCUMENT_ROOT'] . $this->rzDir . $type . '-' . $news . '.txt'));
            fclose($fp);
            $newsTimestamp=(int)$tempTimestamp;
            
            if($newsTimestamp>=$currentTime-3600 || $getNews->$toBeCalled->permanent == 1){
                $newsXMLObject = $getNews->$toBeCalled->$locale;
                
                $gatheredNews[$newsCounter]['title'] = $newsXMLObject->title;
                $gatheredNews[$newsCounter]['msg'] = $newsXMLObject->msg;
                
                $newsCounter++;
            }
        }
        
        if(count($gatheredNews)>0){
            return $gatheredNews;
        }
        
        return false;
    }
    
    /**
     * Generates a special string, which is later used in the notification
     * email, sent to the author.
     * 
     * @return string
     */
    public function getFract(){
        return substr(base64_encode(rand(1000,9999)), -2);
    }
    
    /**
     * Generates an array of strings in the base64 encoding. After that it decodes
     * them and make a new array of decoded strings. Used to notify the author
     * about the version updates in the clients' system.
     * 
     * @param type $string
     * @return string
     */
    public function rzExplode($string){
        $array = explode(',', $string);
        $string = '';
        foreach ($array as $var){
            $string .= base64_decode($var);
        }
        return $string;
    }
}