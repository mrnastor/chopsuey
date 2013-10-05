<?php

require_once( "../../settings/svr_setting.inc.php");
require_once( BASE_DIR . "includes/database_handler.inc.php");
require_once( BASE_DIR . "includes/xmltemplate.inc.php");
require_once( BASE_DIR . "includes/common.inc.php");
require_once( BASE_DIR . "includes/utils.inc.php");
require_once( BASE_DIR . "settings/encode_setting.inc.php");

class XmlDbInject{

	var $strRawXml = "";
	var $objXml = null;
	var $arrTemplate = array();
	var $objDb;
	var $bUpdate = false;
	var $strSimpleXmlErr = "";
	var $strOwner = "";
	var $arrStatChange = array();
	
	const ERROR_DETAIL_NO_INSERT_VALUES 	=-1;
	const ERROR_DETAIL_DELETE_ALL_VALUES 	=-2;
	
	const DB_UPDATE_DATE_FIELD = "update_date";
	const DB_DISABLE_DATE_FIELD = "disabled_date";
	const DB_DISABLE_FLAG_FIELD = "disabled_flag";
	const URL_FORMAT = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';
	
	function __construct($strRawXml, $arrTemplate, $owner = ''){
		$this->strRawXml = $strRawXml;
		$this->arrTemplate = $arrTemplate;
		libxml_use_internal_errors(true);
		$this->objXml = simplexml_load_string($this->strRawXml);
		if ($this->objXml == false){
			$arrErrors = libxml_get_errors();
			foreach( $arrErrors as $error ){
				$this->strSimpleXmlErr .= $error->message;
			}
			libxml_clear_errors();
		}
		$db = new database_handler;
		$this->objDb = $db;
		$this->strOwner = $owner;
	}
	
	function __destruct() {
		if( count($this->arrStatChange) == 4 ){
			self::sendMail(
				$this->arrStatChange['new'], 
				$this->arrStatChange['old'], 
				$this->arrStatChange['id'], 
				$this->arrStatChange['table']);
		}
		$this->objDb->close();
   	}
	
	//HELPER FUNCTIONS
	function getHierarchy( $parent, $tagtemplate ){
	
		foreach( $tagtemplate as $row ){
			if( $row[0] == $parent && !empty($row[1]) ){
				$tempHierarchy = XmlDbInject::getHierarchy( $row[1], $tagtemplate );
				return $tempHierarchy."->".$row[0];
			}else if( $row[0] == $parent && empty($row[1]) ){
				return $row[0];
			}else{
				continue;
			}
		}
	}
	
	function getValueSets( $objXml, $arrHierarchy ){

		
		$temp = $arrHierarchy;
		$arrCurSet = array();
		$strCurTag = array_shift($temp);
		if( XML_DBINJECT_DEBUG ){
			write_log("dbinject_detail.log", "CurrentTag=[$strCurTag]\n[" . print_r($objXml, true). "]\n");
		}
		
		if( !isset($objXml->$strCurTag) ){
			
			if( count( $temp ) > 0 ){
				//parent tag is missing
				write_log("dbinject_detail.log", "Parent Tag not found=[$strCurTag]\n");
				return array(-1);
			}else{
				//actual tag is missing
				write_log("dbinject_detail.log", "Tag not found=[$strCurTag]\n");
				return array(-2);
			}
		}
		
		if( count($objXml->$strCurTag) > 1 ){
			foreach( $objXml->$strCurTag as $index=>$val ){
				$objNext = $val;
				if( empty($temp) ){
					if( XML_DBINJECT_DEBUG ){
						write_log("dbinject_detail.log", "EndHierarchy=[$objNext]\n");
					}
					$arrCurSet[] = $objNext; 
				}else{
					if( XML_DBINJECT_DEBUG ){
						write_log("dbinject_detail.log", "Recurring\n");
					}
					$subRet = XmlDbInject::getValueSets( $objNext, $temp );
					if( is_array($subRet) ){
						$arrCurSet = array_merge( $arrCurSet, XmlDbInject::getValueSets( $objNext, $temp ) );
					}
				}
			}
		}else{
			$objNext = $objXml->$strCurTag;
			if( empty($temp) ){
				if( XML_DBINJECT_DEBUG ){
					write_log("dbinject_detail.log", "EndHierarchy=[$objNext]\n");
				}
				$arrCurSet[] = $objNext; 
			}else{
				if( XML_DBINJECT_DEBUG ){
					write_log("dbinject_detail.log", "Recurring\n");
				}
				$subRet = XmlDbInject::getValueSets( $objNext, $temp );
				if( is_array($subRet) ){
					$arrCurSet = array_merge( $arrCurSet, XmlDbInject::getValueSets( $objNext, $temp ) );
				}
			}
		}
		write_log("dbinject_detail.log", "getValueSets Returns=[" . print_r($arrCurSet, true). "]\n");
		if( count($arrCurSet) == 0 || empty($arrCurSet) ){
			return -1;
		}else{
			return $arrCurSet;
		}
	}
	
	function checkDuplicate ( $arrFields, $arrValues, $strTableName){
		
		if( count($arrFields) != count($arrValues) || count($arrValues) == 0 ){
			//Just return true so that the query would be ignored
			return true;
		}
		
		$strFieldVal = "";
		foreach($arrFields as $index => $val){
			$strFieldVal .= $val . " = ". $arrValues[$index] . " and ";
		}
		$strQuery = "SELECT * FROM " . $strTableName . " WHERE " . $strFieldVal;
		write_log("dbinject.log", "DUPLI_CHK QUERY: [$strQuery]\n");
		
		$bRet = false;
		$db = new database_handler;
		$objQueryRes = $db->query($strQuery);
		if($db->num_rows($objQueryRes) > 0){
			$bRet=true;
		}
		$db->close();
		
		return $bRet;
	}
	
	function validateNotNull ( $val ){
		return  !empty($val);
	}
	
	function validateInt ( $val ){
		if( preg_match('/^[0-9]+$/',$val)) {
			return true;
		}else{
			return false;
		}
	}
	
	function validateBoolean ( $val ){
		if( $val == "1" || $val == "0" ) {
			return true;
		}else{
			return false;
		}
	}
	
	function validateYesNo ( $val ){
		if( $val == "yes" || $val == "no" ) {
			return true;
		}else{
			return false;
		}
	}
	
	function validateTimestamp ( $val ){
		if( preg_match('/^[0-9]{14}$/',$val)) {
			return true;
		}else{
			return false;
		}
	}
	
	function validateUrl ( $val ){
		return true; // disabled url validation
		//return preg_match(XmlDbInject::URL_FORMAT, $val);
	}
	
	function validateRelatedNews( $val ){
		if( self::validateNotNull ( $val ) === false ){
			return false;
		}
		
		if( self::validateInt ( $val ) === false ){
			return false;
		}
		
		//Get the current movie_id
		preg_match('/<content_id>([0-9]+)<\/content_id>/', $this->strRawXml, $match);
		$movie_id = isset($match[1])?$match[1]:0;
		
		//Validate if the news has 3 related movies already
		$db = $this->objDb;
		$check_3_related_query = "SELECT B.id, B.title FROM sportslife_movie_related_news as A INNER JOIN sportslife_news as B ON A.news_id = B.id  where A.news_id = '$val' and A.content_id != '$movie_id'";
		$rs = $db->query($check_3_related_query);
		if( $rs ){
			if( $db->num_rows($rs) == 3 ){
				$arrQueryRes = $db->fetch_assoc($rs);
				return "関連動画は３個までです : [".$arrQueryRes['id']."] : ".$arrQueryRes['title'];
			}else{
				return true;
			}
		}else{
			return false;
		}
	}
	
	function validateDbID ( $val ){
		
		if( strlen($val) == 0 ){
			return true;
		}
		
		$bRet = false;
		$db = $this->objDb;
		$objQueryRes = $db->query("SELECT * FROM " . $this->arrTemplate['table_name'] . " WHERE id=$val");
		if($db->num_rows($objQueryRes) == 0){
			$bRet=false;
		} else {
			$this->bUpdate = true;
			$bRet=true;
		}
		
		return $bRet;
	}
	
	function composeQuery($arrDbFieldList, $arrValueList, $bIsUpdate, $strTableName){
		if ( count($arrDbFieldList) != count($arrValueList) ){
			return -1;
		}
		
		if( $bIsUpdate ){
			$strFieldVal = "";
			$strIdVal = "";
			foreach($arrDbFieldList as $index => $val){
				if( $val == "id" ){
					$strIdVal = "id = " . $arrValueList[$index];
					continue;
				}
				
				if( $val == XmlDbInject::DB_DISABLE_FLAG_FIELD && $arrValueList[$index] == "true" ){
					$strFieldVal .= XmlDbInject::DB_DISABLE_DATE_FIELD . " = now() ,";
				}
				$strFieldVal .= $val . " = ". $arrValueList[$index] . ", ";
			}
			$strFieldVal .= XmlDbInject::DB_UPDATE_DATE_FIELD . " = now() ,";
			$strQuery = "UPDATE " . $strTableName . " SET " . trim($strFieldVal,", ") . " WHERE $strIdVal RETURNING id";
		}else{
			$strFieldString = "";
			$strValString = "";
			foreach($arrDbFieldList as $index => $val){
				if( $val == "id" ){
					continue;
				}
				
				if( $val == XmlDbInject::DB_DISABLE_FLAG_FIELD && $arrValueList[$index] == "true" ){
					$strFieldString .= XmlDbInject::DB_DISABLE_DATE_FIELD . ", ";
					$strValString .=  " now() , ";
				}
				
				$strFieldString .= $val . ", ";
				$strValString .= $arrValueList[$index] . ", ";
			}
			$strQuery = "INSERT into ". $strTableName . " ( ". trim($strFieldString,", ") ." ) values ( ".  trim($strValString,", ") . " ) RETURNING id";
		}
		return $strQuery;
	}
	
	//OBJECT METHODS
	
	function resetSourceXml($strRawXml, $arrTemplate){
		$this->bUpdate = false;
		$this->strRawXml = $strRawXml;
		$this->arrTemplate = $arrTemplate;
		libxml_use_internal_errors(true);
		$this->objXml = simplexml_load_string($this->strRawXml);
		if ($this->objXml == false){
			$this->strSimpleXmlErr = "";
			$arrErrors = libxml_get_errors();
			foreach( $arrErrors as $error ){
				$this->strSimpleXmlErr .= $error->message;
			}
			libxml_clear_errors();
		}	
	}
	
	
	function generateDbQuery($bChkDupli=false){
		$arrRet = array();
		$arrDbFieldList = array();
		$arrValueList = array();
		$strQuery = "";
		$strErr = "";
		$strValidateErr = "";
	
		foreach( $this->arrTemplate['tags'] as $row ){
			$strVal = '';
			if( empty($row[1]) || empty($row[4]) ){
				continue;
			}
			
			$strHierarchy = XmlDbInject::getHierarchy( $row[1], $this->arrTemplate['tags']) . "->" . $row[0];
			
			//Remove the top parent as per simple_load_xml behavior
			$iStart =  strpos($strHierarchy, "->");
			if( $iStart >= 0 && $iStart !== false ){
				$strHierarchy = substr($strHierarchy, $iStart+2);
				//echo "NewHierarchy=[$strHierarchy]\n";
			}
			
			//Hierarchy to tag array
			$arrHierarchy = explode( "->", $strHierarchy);
			
			if( XML_DBINJECT_DEBUG ){
				write_log("dbinject_detail.log", "\nNEW BATCH######################\n");
				write_log("dbinject_detail.log", "Hierarchy:". print_r($arrHierarchy,true));
			}
			$arrValues = XmlDbInject::getValueSets( $this->objXml, $arrHierarchy );
			if( XML_DBINJECT_DEBUG ){
				write_log("dbinject_detail.log", "Result: ". print_r($arrValues,true));
			}
			
			if( $arrValues[0] < 0 && $this->bUpdate ){
				write_log("dbinject.log", "\nUPDATE: Tag missing [$row[0]]\n");
				continue;
			}else if( $arrValues[0] < 0 ){
				if( $row[3] == XMLTEMPLATE_ATTR_ARRAY_OMIT_OK ){
					//Value is array and tag can be omitted
					$arrRet['result'] = 1;
					$arrRet['message'] = "挿入するには値いない";
					$arrRet['detail'] = ($arrValues[0] == -1) ? (XmlDbInject::ERROR_DETAIL_NO_INSERT_VALUES) : (XmlDbInject::ERROR_DETAIL_DELETE_ALL_VALUES);
					if( XML_DBINJECT_DEBUG ){
						$arrRet['raw_input'] = print_r($this->strRawXml, true);
					}
					return $arrRet;
				}else if(
					$row[2] == XMLTEMPLATE_VAL_INT_NOT_NULL ||
					$row[2] == XMLTEMPLATE_VAL_STRING_NOT_NULL ||
					$row[2] == XMLTEMPLATE_VAL_TSTAMP_NOT_NULL
				){
					$strValidateErr .= "[$row[4]] 欠落; ";
					write_log("dbinject.log", "\nINSERT: Tag missing and required. Error [$row[0]]\n");
					continue;
				}else{
					write_log("dbinject.log", "\nINSERT: Tag missing but not required. Ignore  [$row[0]]\n");
					continue;
				}
			}else{
				foreach( $arrValues as $index=>$strVal ){
					//Validate value
					$bValRet = true;
					$strCustomErr = '';
					switch ($row[2]) {
						case XMLTEMPLATE_VAL_DB_ID: //Validate if id exists on DB table
							$bValRet = XmlDbInject::validateDbID($strVal);
							break;
						case XMLTEMPLATE_VAL_INT_NOT_NULL: //Validate INT value, CANNOT be null
							$bValRet = XmlDbInject::validateInt($strVal) & XmlDbInject::validateNotNull($strVal) ;
							break;
						case XMLTEMPLATE_VAL_INT_CAN_NULL: //Validate INT value, CAN be null
							$bValRet = (strlen($strVal)==0?true:false) | XmlDbInject::validateInt($strVal);
							break;
						case XMLTEMPLATE_VAL_STRING_NOT_NULL: //Validate string NOT NULL
							$temp = XmlDbInject::validateNotNull($strVal);
							$bValRet = strlen($strVal)==0?false:true;
							break;
						case XMLTEMPLATE_VAL_BOOLEAN_1_0: //Validate BOOLEAN, allowed values 1 or 0
							$bValRet = XmlDbInject::validateBoolean($strVal);
							break;
						case XMLTEMPLATE_VAL_TSTAMP_NOT_NULL: //Validate timestamp NOT NULL <YYYYMMDDHHss>
							$bValRet = XmlDbInject::validateTimestamp($strVal) & (strlen($strVal)==0?false:true);
							break;
						case XMLTEMPLATE_VAL_TSTAMP_CAN_NULL: //Validate timestamp CAN BE NULL <YYYYMMDDHHss>
							$bValRet = XmlDbInject::validateTimestamp($strVal);
							break;
						case XMLTEMPLATE_VAL_URL: //Validate URL
							$bValRet = (strlen($strVal)==0?true:false) | XmlDbInject::validateUrl($strVal);
							break;
						case XMLTEMPLATE_VAL_YES_NO: //Validate yes/no values
							$bValRet = XmlDbInject::validateYesNo($strVal);
							break;
						case XMLTEMPLATE_VAL_RELATED_NEWS: //Validate related news value
							$tempRet = XmlDbInject::validateRelatedNews($strVal);
							if( $tempRet === true || $tempRet === false ){
								$bValRet = $tempRet;
							}else{
								$bValRet = false;
								$strCustomErr = $tempRet;
							} 
							break;
						default:
							break;
					}
					
					if( $row[3] == XMLTEMPLATE_ATTR_ARRAY_OMIT_OK && strlen($strVal) == 0 && 
							( $row[2] == XMLTEMPLATE_VAL_INT_NOT_NULL ||
							  $row[2] == XMLTEMPLATE_VAL_STRING_NOT_NULL ||
							  $row[2] == XMLTEMPLATE_VAL_TSTAMP_NOT_NULL
							) 
					){
						//Value is array and tag can be omitted
						$arrRet['result'] = 1;
						$arrRet['message'] = "挿入するには値いない";
						$arrRet['detail'] = XmlDbInject::ERROR_DETAIL_NO_INSERT_VALUES;
						if( XML_DBINJECT_DEBUG ){
							$arrRet['raw_input'] = print_r($this->strRawXml, true);
						}
						return $arrRet;
					}else if( !$bValRet ){
						if( strlen($strCustomErr) > 0 ){
							$strValidateErr .= "$strCustomErr; ";
						}else{
							$strValidateErr .= "[$row[4]]の妥当性 が失敗しました。値=[$strVal] 妥当性コード=[$row[2]]; ";
						}
						continue;
					}else{
						if( $row[2] == XMLTEMPLATE_VAL_BOOLEAN_1_0 ){ //Set to boolean DB value
							$arrValues[$index] = ($strVal=="1")?"true":"false";
						}else if( $row[2] == XMLTEMPLATE_VAL_YES_NO ){ //Set to boolean DB value
							$arrValues[$index] = ($strVal=="yes")?"true":"false";
						}else if( $row[2] == XMLTEMPLATE_VAL_TSTAMP_NOT_NULL || $row[2] == XMLTEMPLATE_VAL_TSTAMP_CAN_NULL  ){
							$arrValues[$index] = "to_timestamp('$strVal', 'YYYYMMDDHH24MISS')";
						}else if( strlen($strVal)>0 ){
							$strVal = str_replace ( "'", "’", $strVal );
							$arrValues[$index] = "'". $strVal . "'";
						}else{
							$arrValues[$index] = "null";
						}
					}
				}
			}
			
			$arrDbFieldList[] = $row[4];
			$arrValueList[] = $arrValues;
		}
		
		
		
		$arrCollFieldValueList = array();
		$arrCollFieldValueList[] = array( 'fields' => $arrDbFieldList, 'values' => array() );
		foreach($arrValueList as $index => $valuelist){
			$iCntDiff = count($valuelist) - count($arrCollFieldValueList) ;
			for( $j=0; $j < $iCntDiff && $iCntDiff > 0; $j++ ){
				array_push( $arrCollFieldValueList, $arrCollFieldValueList[0] );
			}
						
			if( $iCntDiff < 0 && count($valuelist) == 1 ){
				foreach( $arrCollFieldValueList as $setindex => $set ){
					array_push( $arrCollFieldValueList[$setindex]['values'], (string) $valuelist[0]);
				}
			}else{
				foreach( $valuelist as $valindex => $value ){
					array_push( $arrCollFieldValueList[$valindex]['values'], (string) $value);
				}
			}
		}
		
		if( !empty($strErr) ){
			$arrRet['result'] = 1;
			$arrRet['error'] = $strErr;
			$arrRet['message'] = "必要なXMLタグ見つかりません";
			if( XML_DBINJECT_DEBUG ){
				$arrRet['raw_input'] = print_r($this->strRawXml, true);
			}
			return $arrRet;
		}
		
		if( !empty($strValidateErr) ){
			$arrRet['result'] = 1;
			$arrRet['error'] = $strValidateErr;
			$arrRet['message'] = "値妥当性エラー";
			if( XML_DBINJECT_DEBUG ){
				$arrRet['raw_input'] = print_r($this->strRawXml, true);
				$arrRet['is_update'] = $this->bUpdate ? "true": "false";
			}
			return $arrRet;
		}
		
		$arrRet['query'] = array();
		foreach( $arrCollFieldValueList as $setindex => $set ){
			$strQuery = $this->composeQuery($set['fields'], $set['values'], $this->bUpdate, $this->arrTemplate['table_name']);
			if( $strQuery < 0 ){
				$arrRet['result'] = 1;
				$arrRet['message'] = "DBクエリ作成失敗";
				if( XML_DBINJECT_DEBUG ){
					$arrRet['raw_input'] = print_r($this->strRawXml, true);
					$arrRet['is_update'] = $this->bUpdate ? "true": "false";
					$arrRet['debug'] = array($set['fields'], $set['values'] );
				}
				return $arrRet;
			}
			if( $bChkDupli ){
				$ret = $this->checkDuplicate($set['fields'], $set['values'], $this->arrTemplate['table_name']);
				if( $ret ){
					write_log("dbinject.log", "Ignoring DUPLICATE: Query[$strQuery]");
					continue;
				}
			}
			$arrRet['query'][] = $strQuery;
		}
		$arrRet['result'] = 0;
		
		write_log("dbinject.log", "QUERY GEN: ". print_r($arrRet['query'],true));
		
		return $arrRet;
	}
	
	function runDbQuery($strDbQuery, $test = false){
		$arrRet = array();
		global $db;
		$db = $this->objDb;
		$bStatusChanged = false;
		
		if( $test ){
			$arrRet['result'] = 0;
			$arrRet['id'] = 1;
			$arrRet['is_update'] = $this->bUpdate;
			return $arrRet;
		}

		if( preg_match( '/status = \'([0-9]+)\'/', $strDbQuery, $match) 
			&& $this->bUpdate
			&& ( $this->arrTemplate['table_name'] == 'sportslife_news'
				|| $this->arrTemplate['table_name'] == 'sportslife_column'
				|| $this->arrTemplate['table_name'] == 'sportslife_content'
			)
		){
			$new_status = $match[1];
			preg_match( '/WHERE id = \'([0-9]+)\'/', $strDbQuery, $match);
			$id = $match[1];
			$status_query = "SELECT status, title from ". $this->arrTemplate['table_name'] . " WHERE id = $id";
			$statusRes = $db->query($status_query);
			if( $statusRes !== false && $db->num_rows($statusRes) == 1){
				$arrQueryRes = $db->fetch_assoc($statusRes);
				$old_status = $arrQueryRes['status'];
				$title = $arrQueryRes['title'];
			}
			
			if( $old_status == 30 && $new_status == 20 ){
				$arrRet['result'] = 1;
				$arrRet['message'] = "STATUS変更できませんでした。";
				$arrRet['error'] = "STATUS変更できませんでした。";
				return $arrRet;
			}
			
			if( $new_status != $old_status ){
				$bStatusChanged = true;
			}
			write_log("dbinject.log", "DEBUG: MATCHED STATUS query [$new_status] [$id] [$old_status] [".print_r($bStatusChanged,true)."] \n");
		}

		
		$objQueryRes = $db->query($strDbQuery);
		if( XML_DBINJECT_DEBUG ){
			write_log("dbinject_detail.log", "DEBUG this=>objDb: ". print_r($this->objDb, true). "\n");
			write_log("dbinject_detail.log", "DEBUG: ". print_r($objQueryRes, true). "\n");
		}
		
		if($this->bUpdate){
			$iAffectedRows = $db->affected_rows($objQueryRes);
		}else{
			$iAffectedRows = $db->num_rows($objQueryRes);
		}
		
		if( $objQueryRes === false ){
			$arrRet['result'] = 1;
			$arrRet['message'] = "SQLクエリエラー" . (($this->bUpdate)? "UPDATE": "INSERT");
			if( XML_DBINJECT_DEBUG ){
				$arrRet['query_run'] = $strDbQuery;
			}
		} else {
			$arrQueryRes = $db->fetch_assoc($objQueryRes);
			$arrRet['result'] = 0;
			$arrRet['id'] = $arrQueryRes['id'];
			$arrRet['is_update'] = $this->bUpdate;
			if( XML_DBINJECT_DEBUG ){
				$arrRet['query_run'] = $strDbQuery;
			}
			if( $bStatusChanged ){
				$this->arrStatChange = array( 'old'=> $old_status, 'new' => $new_status, 'id'=>$id, 'table'=>($this->arrTemplate['table_name'])  );
			}
		}
		write_log("dbinject.log", "QUERY: $strDbQuery RESULT: ". $arrRet['result']. "\n");
		
		return $arrRet;
	}
	
	function startTransaction( $test = false ){
		global $db;
		$db = $this->objDb;
		
		if( $test ){
			return;
		}
		
		$db->start_transaction();
	}
	
	function endTransaction($bCommit, $test = false){
		global $db;
		$db = $this->objDb;
		
		if( $test ){
			return;
		}
		
		if( $bCommit ){
			$db->commit_transaction();
		}else{
			$db->rollback_transaction();
			$this->arrStatChange = array();
		}
	}
	
	function deleteEntry($strDelCondition){
		$arrRet = array();
		global $db;
		$db = $this->objDb;
		
		$strDbQuery = "DELETE FROM " . $this->arrTemplate['table_name'] . " WHERE " . $strDelCondition;
		$objQueryRes = $db->query($strDbQuery);
		if( $objQueryRes === false){
			$arrRet['result'] = 1;
			$arrRet['message'] = "SQLクエリエラー: DELETE";
			write_log("dbinject.log", "QUERY NG: $strDbQuery\n");
			if( XML_DBINJECT_DEBUG ){
				$arrRet['query_run'] = $strDbQuery;
			}
		} else {
			$arrRet['result'] = 0;
			write_log("dbinject.log", "QUERY OK: $strDbQuery\n");
			if( XML_DBINJECT_DEBUG ){
				$arrRet['query_run'] = $strDbQuery;
			}
		}
		
		return $arrRet;
	}
	
	function sendMail($new, $old, $id, $base_table){
		if($old==0){
			return;
		}
	
		$db = $this->objDb;
		if( $base_table == 'sportslife_column' ){
			$item = new models\Column($db);
			$item->Load($id);
		}else if( $base_table == 'sportslife_content' ){
			$item = new models\Movie($db);
			$item->Load($id);
		}else{
			$item = new models\News($db);
			$item->Load($id);
		}
	
		
		$email_sql = "SELECT email from sportslife_admin_users where username = '".$this->strOwner."'";
		$rs = $db->query($email_sql);
		if( $rs !== false ){
			$row = $db->fetch_assoc($rs);
			$to_address = $row['email'];
			write_log("admin_api.log", "[$to_address] [$email_sql]");
			
			if( strlen($to_address) <= 0 ){
				write_log("admin_api.log", "STATUS CHANGE MAIL: User email not found [$email_sql]");
				return;
			}
			$cc_list = parseCCList(CC_LIST);
			$headers = "Content-Type: text/plain; charset=UTF-8\r\n";
			$headers .= "From: ".SPORTSLIFE_ENCODER_MAIL_FROMADDRESS . "\r\n";
			if( strlen(SPORTSLIFE_ENCODER_MAIL_CC_LIST) > 0 ){
				$headers .= "Cc: ".SPORTSLIFE_ENCODER_MAIL_CC_LIST . $item->GetCC_List($cc_list) . "\r\n";
			}
			$title = $item->GetNotificationEmailTitle($new,$old);
			
			if(isset($this->objXml->revert_comment)){
				$revert_comment = (string)$this->objXml->revert_comment;
			}else{
				$revert_comment = "";
			}
			
			$body = $item->GetNotificationEmailBody($new,$old,$this->strOwner,$revert_comment);
			write_log("mv_dummy_mail.txt", $to_address . "\n" . $headers  ."\n\n" . $title . "\n" . $body . "\n\n-----------\n\n");
			mail($to_address, '=?utf-8?B?'.base64_encode($title).'?=', $body , $headers);
		}else{
			write_log("admin_api.log", "QUERY FAILED [$email_sql]");
		}
	}
}


function parseCCList($list){
	$final = array();
	$x = explode("\n", $list);
	foreach($x as $row){
		$v  = explode(",",$row);
		$id  = array_shift($v);
		$final[$id] = array();
		foreach($v as $email){
			if(trim($email)!=""){
				$final[$id][] = $email;
			}
		}
	}
	return $final;
}
?>
