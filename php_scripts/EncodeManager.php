<?php

require_once(dirname(dirname(__FILE__)) . '/settings/encode_setting.inc.php');
require_once(dirname(dirname(__FILE__)) . '/libs/predis/autoload.php');

class EncodeManager{

	static  $status_strings = array();
	const STATUS_WAITING 				= 0;
	const STATUS_DOWNLOADING			= 1;
	const STATUS_DOWNLOADED				= 2;
	const STATUS_ENCODING_HLS			= 3;
	const STATUS_ENCODING_HLS_DONE		= 4;
	const STATUS_ENCODING_MP4			= 5;
	const STATUS_ENCODING_MP4_DONE		= 6;
	const STATUS_ENCODING_HLS_ERROR		= 7;
	const STATUS_ENCODING_MP4_ERROR		= 8;
	const STATUS_UPLOADING				= 9;
	const STATUS_UPLOADING_DONE			= 10;
	const STATUS_UNK_ERROR				= 11;
	const STATUS_DOWNLOAD_ERROR			= 12;
	const STATUS_UPLOAD_ERROR			= 13;
	const STATUS_CANCELLED				= 14;
	
	const LOG_KEY_OWNER = "OWNER";
	const LOG_KEY_MOVIEID = "MOVIEID";
	const LOG_KEY_OUTPREFIX = "OUTPREFIX";
	const LOG_KEY_FILEPATH = "FILEPATH";
	const LOG_KEY_STATUS = "STATUS";
	const LOG_KEY_SRCPATH = "SRCPATH";
	const LOG_KEY_LENGTH = "LENGTH";
	const LOG_KEY_HLS_SIZE = "HLS_SIZE";
	const LOG_KEY_MP4_SIZE = "MP4_SIZE";
	
	const RETURN_OK 					= 0;
	const RETURN_ERROR_FOLDERCREATE 	=-1;
	const RETURN_ERROR_FILECREATE 		=-2;
	const RETURN_ERROR_REDIS	 		=-3;
	const RETURN_ERROR_FOLDERNOTFOUND	=-4;
	const RETURN_ERROR_FILENOTFOUND		=-5;
	const RETURN_ERROR_JOBNOTRUNNING	=-6;
	const RETURN_ERROR_PARAMETERS		=-7;
	const RETURN_ERROR_LOCALCOPYFAIL	=-8;
	
	const PREPEND = "sportslife";
	var $predisObj = null;
	
	function __construct(){
		$this->predisObj = new Predis\Client();
		self::$status_strings = array();
		self::$status_strings[self::STATUS_WAITING] ="待ち";
		self::$status_strings[self::STATUS_DOWNLOADING] ="ダウンロード中";
		self::$status_strings[self::STATUS_DOWNLOADED] ="ダウンロードス終了";
		self::$status_strings[self::STATUS_ENCODING_HLS] ="iOS　エンコード中";
		self::$status_strings[self::STATUS_ENCODING_HLS_DONE] ="iOS　エンコード終了";
		self::$status_strings[self::STATUS_ENCODING_MP4] ="Android　エンコード中";
		self::$status_strings[self::STATUS_ENCODING_MP4_DONE] ="Android　エンコード終了";
		self::$status_strings[self::STATUS_ENCODING_HLS_ERROR] ="iOS　エンコード　エラー";
		self::$status_strings[self::STATUS_ENCODING_MP4_ERROR] ="Android　エンコード　エラー";
		self::$status_strings[self::STATUS_UPLOADING] ="配信サーバーへアップロード中";
		self::$status_strings[self::STATUS_UPLOADING_DONE] ="配信サーバーへアップロード終了";
		self::$status_strings[self::STATUS_UNK_ERROR] ="システム　エラー";
		self::$status_strings[self::STATUS_DOWNLOAD_ERROR] ="ダウンロード　エラー";
		self::$status_strings[self::STATUS_UPLOAD_ERROR] ="アップロード　エラー";
		self::$status_strings[self::STATUS_CANCELLED] ="キャンセル終了";
	}
	
	//HELPER FUNCTIONS
	/**
	* Checks if a given PID is running in the background
	* @param $pid PID
	* @return true or false
	*/
	function isRunning($pid){
    	try{
        	$result = shell_exec(sprintf("ps %d", $pid));
        	if( count(preg_split("/\n/", $result)) > 2){
            	return true;
        	}
    	}catch(Exception $e){}

    	return false;
	}
	
	
	//API FUNCTIONS
	/**
	* Adds an encoding job
	* @param $strFilePath Source movie file path or ftp path 
	* @param $strOutputPrefix Output prefix of the encoded movies
	* @param $iMovieID Movie ID
	* @param $strOwner Owner of the job
	* @return $strJobID The result Job ID
	*/
	public function addEncodeJob($strFilePath, $strOutputPrefix, $iMovieID, $strOwner){
	
		if( empty($strFilePath) || empty($strOutputPrefix) || empty($iMovieID) || empty($strOwner) ){
			return EncodeManager::RETURN_ERROR_PARAMETERS;
		}
		
		//Generate the Job ID
		$iNow = time();
		$strMovieIdPaddded = str_pad($iMovieID, 10, "0", STR_PAD_LEFT);
		$strRandom8 = mt_rand(10000000, 99999999);
		$strNow = @date("YmdHis", $iNow);
		$strJobID = $strNow. '_' . $strMovieIdPaddded . '_' . $strRandom8;
		
		//Create the workfolder
		$old_umask = umask(0);
		$ret = mkdir( WORKFOLDER_DIR . $strJobID, 0777 ); 
		if( !$ret ){
			return EncodeManager::RETURN_ERROR_FOLDERCREATE;
		}
		
		//Create the log file and put initial contents
		$strLogNow = @date("Y-m-d H:i:s", $iNow);
		$strLogInsert = '';
		$strLogInsert .= $strLogNow .' '.  EncodeManager::LOG_KEY_OWNER. ' ' . $strOwner. "\r\n";
		$strLogInsert .= $strLogNow .' '.  EncodeManager::LOG_KEY_MOVIEID. ' ' . $iMovieID. "\r\n";
		$strLogInsert .= $strLogNow .' '.  EncodeManager::LOG_KEY_OUTPREFIX. ' ' . $strOutputPrefix. "\r\n";
		$strLogInsert .= $strLogNow .' '.  EncodeManager::LOG_KEY_FILEPATH. ' ' . $strFilePath. "\r\n";
		$strLogInsert .= $strLogNow .' '.  EncodeManager::LOG_KEY_STATUS. ' ' . EncodeManager::STATUS_WAITING. "\r\n";
		if( strpos ( $strFilePath , "ftp://" ) === false ){
			$strLogInsert .= $strLogNow .' '.  EncodeManager::LOG_KEY_STATUS. ' ' . EncodeManager::STATUS_DOWNLOADED. "\r\n";
			$strSrcPath = WORKFOLDER_DIR.$strJobID.'/'. basename($strFilePath);
			if (!copy($strFilePath, $strSrcPath )) {
    			return EncodeManager::RETURN_ERROR_LOCALCOPYFAIL;
			}
			$strLogInsert .= $strLogNow .' '.  EncodeManager::LOG_KEY_SRCPATH. ' ' . $strSrcPath . "\r\n";
		}
		
		$ret = file_put_contents( SPORTSLIFE_ONGOING_FOLDER . $strJobID . '.log', $strLogInsert, FILE_APPEND);
		if( !$ret ){
			return EncodeManager::RETURN_ERROR_FOLDERCREATE;
		}else{
			chmod ( SPORTSLIFE_ONGOING_FOLDER . $strJobID . '.log' , 0777 );
		}
		
		if( strpos ( $strFilePath , "ftp://" ) === false ){
			exec(SPORTSLIFE_COPY_COMMAND . $strFilePath . ' '. WORKFOLDER_DIR . $strJobID );
			$ret = $this->predisObj->RPUSH( SPORTSLIFE_REDIS_ENCODE_WAITING , $strJobID );
		}else{
			$ret = $this->predisObj->RPUSH( SPORTSLIFE_REDIS_WAITING , $strJobID );
		}

		umask($old_umask);
	
		return $strJobID;
	}
	

	/**
	* Gets encoding status info given a Job ID
	* @param $strJobId Job ID
	* @return $arrRet Status information of the Job ID
	*/
	public function getEncodeStatus($strJobId){

		$arrRet = array();

		//Check if workfolder exists
		if( is_dir(WORKFOLDER_DIR. $strJobId. '/' ) === false ){
			$arrRet["job_id"] = $strJobId;
			$arrRet["error"] = EncodeManager::RETURN_ERROR_FOLDERNOTFOUND;
			return $arrRet; 
		}
		
		//Check if job log file exists
		if( file_exists(SPORTSLIFE_ONGOING_FOLDER . $strJobId . '.log') === true ){
			$strLogFilePath = SPORTSLIFE_ONGOING_FOLDER . $strJobId . '.log';
		}else if( file_exists(SPORTSLIFE_FINISHED_FOLDER . $strJobId . '.log') === true ){
			$strLogFilePath = SPORTSLIFE_FINISHED_FOLDER . $strJobId . '.log';
		}else{
			
			$arrRet["job_id"] = $strJobId;
			$arrRet["error"] = EncodeManager::RETURN_ERROR_FILENOTFOUND;
			return $arrRet; 
		}
		
		$strJobLog = file_get_contents($strLogFilePath, FILE_USE_INCLUDE_PATH);
		$arrRet["job_id"] = $strJobId;
		$arrRet["job_log"] = $strJobLog;
		
		//Extract owner from log
		$iStart = strpos ( $strJobLog , EncodeManager::LOG_KEY_OWNER );
		if( $iStart >= 0 ){
			$iEnd = strpos ( $strJobLog , "\r\n" , $iStart);
			$iLength = $iEnd - $iStart - strlen(EncodeManager::LOG_KEY_OWNER) - 1;
			$arrRet["owner"] = substr ( $strJobLog , $iStart+strlen(EncodeManager::LOG_KEY_OWNER)+1, $iLength );
		}
		
		//Extract movieID from log
		$iStart = strpos ( $strJobLog , EncodeManager::LOG_KEY_MOVIEID );
		if( $iStart >= 0 ){
			$iEnd = strpos ( $strJobLog , "\r\n" , $iStart);
			$strTemp = substr ( $strJobLog , $iStart, $iEnd-$iStart );
			$arrTemp = explode ( ' ', $strTemp );
			$arrRet["movie_id"] = $arrTemp[1];
		}
		
		//Extract source filename from log
		$iStart = strpos ( $strJobLog , EncodeManager::LOG_KEY_FILEPATH );
		if( $iStart >= 0 ){
			$iEnd = strpos ( $strJobLog , "\r\n" , $iStart);
			$strTemp = substr ( $strJobLog , $iStart, $iEnd-$iStart );
			$arrTemp = explode ( ' ', $strTemp );
			$arrRet["src_filename"] = basename($arrTemp[1]);
		}
		
		//Extract data queued
		$arrRet["date_queued"] = substr ( $strJobLog , 0, 19 );
		
		//Extract date finished
		$iStart = strpos ( $strJobLog , EncodeManager::LOG_KEY_STATUS . ' ' . EncodeManager::STATUS_UPLOADING_DONE  );
		if( $iStart >= 0 && ($iStart - 20) > 0){
			$arrRet["date_finished"] = substr ( $strJobLog, $iStart - 20 , 19 );
		}else{
			$arrRet["date_finished"] = "";
		}
		
		//Extract status
		preg_match_all("/". EncodeManager::LOG_KEY_STATUS . " [0-9]+/",$strJobLog,$arrMatches);
		$arrTemp = explode ( ' ', $arrMatches[0][count($arrMatches[0]) - 1] );
		$arrRet["status"] = $arrTemp[1];	
		
		return $arrRet;
	}
	
	/**
	* Cancels an encoding job
	* @param $strJobId Job ID
	* @return 0: OK, < 0: Error
	*/
	public function cancelEncodeJob($strJobId){
	
		//Check if workfolder exists
		if( is_dir(WORKFOLDER_DIR. $strJobId. '/' ) === false ){
			return EncodeManager::RETURN_ERROR_FOLDERNOTFOUND;
		}
		
		/*
		//Check if job log file exists
		$strPidfile = WORKFOLDER_DIR. $strJobId . '/pid.txt';
		if( file_exists($strPidfile) === false ){
			return EncodeManager::RETURN_ERROR_FILENOTFOUND;
		}
		*/
		
		$strPids = file_get_contents($strPidfile, FILE_USE_INCLUDE_PATH);
		$arrPids = preg_split( '/\r\n|\r|\n/', $strPids );
		$arrPids = array_filter($arrPids, 'strlen');
		$iRecentPid = $arrPids[sizeof($arrPids)-3];

		$this->predisObj->SREM( SPORTSLIFE_REDIS_ENCODING , $strJobId );
		$this->predisObj->SREM( SPORTSLIFE_REDIS_DOWNLOADING , $strJobId );
		$this->predisObj->SREM( SPORTSLIFE_REDIS_UPLOADING , $strJobId );
		
		//if( EncodeManager::isRunning($iRecentPid) ){
			
			$outputfile = WORKFOLDER_DIR. $strJobId . "/preprocess_". time() . ".log";
			$pidfile = WORKFOLDER_DIR. $strJobId . "/cancelpid.txt";
			$cmd = "kill $iRecentPid";
			exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));
			file_put_contents($pidfile, "Cancel PID=[$iRecentPid]" , FILE_APPEND);
			
			$strLogNow = @date("Y-m-d H:i:s", time());
			$strLogInsert = $strLogNow .' '.  EncodeManager::LOG_KEY_STATUS. ' ' . EncodeManager::STATUS_CANCELLED. "\r\n";
			$ret = file_put_contents( SPORTSLIFE_ONGOING_FOLDER . $strJobId . '.log', $strLogInsert, FILE_APPEND);
			if( !$ret ){
				return EncodeManager::RETURN_ERROR_FILECREATE;
			}			
			
			return EncodeManager::RETURN_OK;
		/*
		}else{
			//return EncodeManager::RETURN_ERROR_JOBNOTRUNNING;
			return EncodeManager::RETURN_OK;
		}
		*/
	}
	
	/**
	* Gets information of all jobs queued including finished jobs
	* @param $strJobId Job ID
	* @return $arrRet Array of status information of jobs
	*/
	public function getEncodeQueue(){
		
		//Check if finished folder exists
		if( is_dir( SPORTSLIFE_FINISHED_FOLDER ) === false ){
			return EncodeManager::RETURN_ERROR_FOLDERNOTFOUND;
		}
		
		//Check if ongoing folder exists
		if( is_dir( SPORTSLIFE_ONGOING_FOLDER ) === false ){
			return EncodeManager::RETURN_ERROR_FOLDERNOTFOUND;
		}
		
		$arrRet = array();
		
		if ($strDirlist = opendir(SPORTSLIFE_ONGOING_FOLDER))
		{
			while(($filename = readdir($strDirlist)) !== false)
			{
				if( strstr($filename, ".log") !== false ){
					$arrRet[] = EncodeManager::getEncodeStatus(basename($filename, ".log"));
				}
			}
			closedir($strDirlist);
		}

		if ($strDirlist = opendir(SPORTSLIFE_FINISHED_FOLDER))
		{
			while(($filename = readdir($strDirlist)) !== false)
			{
				if( strstr($filename, ".log") !== false ){
					$arrRet[] = EncodeManager::getEncodeStatus(basename($filename, ".log"));
				}
			}
			closedir($strDirlist);
		}
		
		uasort($arrRet, 'EncodeManager::sort');
		$arrRet = array_reverse($arrRet);
		
		return $arrRet;
	}
	
	/**
	* Starts actual encoding of movie
	* @param $strJobId Job ID
	* @param $strLocaFilePath Local file path of the source movie
	* @param $strOutputPrefix Output prefix of the encoded movies
	* @return None: OK, < 0: If an error occurs
	*/
	public function startEncode($strJobId, $strLocaFilePath, $strOutputPrefix){
	
		//Check parameters
		if( empty($strJobId) || empty($strLocaFilePath) || empty($strOutputPrefix) ){
			return EncodeManager::RETURN_ERROR_PARAMETERS;
		}
	
		//Check if job ID workfolder exists
		if( is_dir(WORKFOLDER_DIR. $strJobId. '/' ) === false ){
			return EncodeManager::RETURN_ERROR_FOLDERNOTFOUND;
		}
		
		//Check if logfile exists
		$strLogfile = SPORTSLIFE_ONGOING_FOLDER . $strJobId . '.log';
		if( file_exists($strLogfile) === false ){
			return EncodeManager::RETURN_ERROR_FILENOTFOUND;
		}
		
		
		$outputfile = WORKFOLDER_DIR. $strJobId . "/preprocess_". time() . ".log";
		$pidfile = WORKFOLDER_DIR. $strJobId . "/pid.txt";
		$encode_logfile= WORKFOLDER_DIR. $strJobId. "/proc.log";
		$outputfolder = WORKFOLDER_DIR. $strJobId ."/";
		$cmd = SPORTSLIFE_ENCODE_COMMAND. " $strLocaFilePath $strOutputPrefix $strLogfile $encode_logfile $outputfolder $strJobId";
		exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));
		file_put_contents($pidfile, $encode_logfile . "\r\n" . $outputfile . "\r\n" , FILE_APPEND);
		
		echo "ENCODE DETAILS<br>";
		echo "cmd=$cmd <br>";
		echo "prefix=$strOutputPrefix";
		echo "outputfile=$outputfile <br>"; 
		echo "pidfile=$pidfile <br>";
		echo "encode_logfile=$encode_logfile <br>";
		
	}
	
	/**
	* Starts FTP download of a source movie
	* @param $strJobId Job ID
	* @param $strFtpFilePath FTP file path of the source movie
	* @return None: OK, < 0: If an error occurs
	*/
	public function startDownload($strJobId, $strFtpFilePath){
	
		//Check parameters
		if( empty($strJobId) || empty($strFtpFilePath) ){
			return EncodeManager::RETURN_ERROR_PARAMETERS;
		}
	
		//Check if job ID workfolder exists
		if( is_dir(WORKFOLDER_DIR. $strJobId. '/' ) === false ){
			return EncodeManager::RETURN_ERROR_FOLDERNOTFOUND;
		}
		
		//Check if logfile exists
		$strLogfile = SPORTSLIFE_ONGOING_FOLDER . $strJobId . '.log';
		if( file_exists($strLogfile) === false ){
			return EncodeManager::RETURN_ERROR_FILENOTFOUND;
		}
		
		$outputfile = WORKFOLDER_DIR. $strJobId . "/preprocess_". time() . ".log";
		$pidfile = WORKFOLDER_DIR. $strJobId . "/pid.txt";
		$process_logfile= WORKFOLDER_DIR. $strJobId. "/proc.log";
		$outputfolder = WORKFOLDER_DIR. $strJobId ."/";
		$cmd = SPORTSLIFE_DOWNLOAD_COMMAND. " $strFtpFilePath $strJobId $strLogfile $process_logfile";
		exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));
		file_put_contents($pidfile, $process_logfile . "\r\n" . $outputfile . "\r\n" , FILE_APPEND);
		
		echo "DOWNLOAD DETAILS<br>";
		echo "cmd=$cmd <br>";
		echo "outputfile=$outputfile <br>"; 
		echo "pidfile=$pidfile <br>";
		echo "process_logfile=$process_logfile <br>";
	}
	
	/**
	* Starts upload of encoded movies to origin server
	* @param $strJobId Job ID
	* @param $strMovieId Movie ID
	* @param $strPrefix Output prefix of the encoded movies
	* @return None: OK, < 0: If an error occurs
	*/
	public function startUpload($strJobId, $strMovieId, $strPrefix){
	
		//Check parameters
		if( empty($strJobId) || empty($strMovieId) || empty($strPrefix) ){
			return EncodeManager::RETURN_ERROR_PARAMETERS;
		}
	
		//Check if job ID workfolder exists
		if( is_dir(WORKFOLDER_DIR. $strJobId. '/' ) === false ){
			return EncodeManager::RETURN_ERROR_FOLDERNOTFOUND;
		}
		
		//Check if logfile exists
		$strLogfile = SPORTSLIFE_ONGOING_FOLDER . $strJobId . '.log';
		if( file_exists($strLogfile) === false ){
			return EncodeManager::RETURN_ERROR_FILENOTFOUND;
		}
		
		$outputfile = WORKFOLDER_DIR. $strJobId . "/preprocess_". time() . ".log";
		$pidfile = WORKFOLDER_DIR. $strJobId . "/pid.txt";
		$process_logfile= WORKFOLDER_DIR. $strJobId. "/proc.log";
		$outputfolder = WORKFOLDER_DIR. $strJobId ."/";
		$cmd = SPORTSLIFE_UPLOAD_COMMAND. " $strJobId $strMovieId $strPrefix $outputfile";
		exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));
		file_put_contents($pidfile, $process_logfile . "\r\n" . $outputfile . "\r\n" , FILE_APPEND);
		
		echo "UPLOAD DETAILS<br>";
		echo "cmd=$cmd <br>";
		echo "outputfile=$outputfile <br>"; 
		echo "pidfile=$pidfile <br>";
		echo "process_logfile=$process_logfile <br>";
	}
	
	/**
	* Extract encoding information from the job log file
	* @param $strJobId Job ID
	* @return $arrRet: OK, Encode information, < 0: If an error occurs
	*/
	public function readEncodeInfo($strJobId){
		$arrRet = array();

		//Check if workfolder exists
		if( is_dir(WORKFOLDER_DIR. $strJobId. '/' ) === false ){
			return EncodeManager::RETURN_ERROR_FOLDERNOTFOUND;
		}
		
		//Check if job log file exists in ongoing folder
		if( file_exists(SPORTSLIFE_ONGOING_FOLDER . $strJobId . '.log') === true ){
			$strLogFilePath = SPORTSLIFE_ONGOING_FOLDER . $strJobId . '.log';
		}else{
			return EncodeManager::RETURN_ERROR_FILENOTFOUND;
		}
		
		$strJobLog = file_get_contents($strLogFilePath, FILE_USE_INCLUDE_PATH);
		
		//Extract ftp file path from log
		preg_match_all("/". EncodeManager::LOG_KEY_FILEPATH . " [^ \r\n]+/",$strJobLog,$arrMatches);
		$arrTemp = explode ( ' ', $arrMatches[0][count($arrMatches[0]) - 1] );
		$arrRet["filepath"] = $arrTemp[1];
		if( empty($arrRet["filepath"]) ){
			return EncodeManager::RETURN_ERROR_PARAMETERS;
		}
		
		//Extract output prefix from log
		preg_match_all("/". EncodeManager::LOG_KEY_OUTPREFIX . " [^ \r\n]+/",$strJobLog,$arrMatches);
		$arrTemp = explode ( ' ', $arrMatches[0][count($arrMatches[0]) - 1] );
		$arrRet["prefix"] = $arrTemp[1];
		if( empty($arrRet["prefix"]) ){
			return EncodeManager::RETURN_ERROR_PARAMETERS;
		}
		
		//Extract movie ID from log
		preg_match_all("/". EncodeManager::LOG_KEY_MOVIEID . " [^ \r\n]+/",$strJobLog,$arrMatches);
		$arrTemp = explode ( ' ', $arrMatches[0][count($arrMatches[0]) - 1] );
		$arrRet["movie_id"] = $arrTemp[1];
		if( empty($arrRet["movie_id"]) ){
			return EncodeManager::RETURN_ERROR_PARAMETERS;
		}
		
		//Extract source video local file path from log
		preg_match_all("/". EncodeManager::LOG_KEY_SRCPATH . " [^ \r\n]+/",$strJobLog,$arrMatches);
		$arrTemp = explode ( ' ', $arrMatches[0][count($arrMatches[0]) - 1] );
		$arrRet["src_path"] = $arrTemp[1];
		
		//Extract owner from log
		preg_match_all("/". EncodeManager::LOG_KEY_OWNER . " [^ \r\n]+/",$strJobLog,$arrMatches);
		$arrTemp = explode ( ' ', $arrMatches[0][count($arrMatches[0]) - 1] );
		$arrRet["owner"] = $arrTemp[1];
		
		//Extract movie length from log
		preg_match_all("/". EncodeManager::LOG_KEY_LENGTH . " [^ \r\n]+/",$strJobLog,$arrMatches);
		$arrTemp = explode ( ' ', $arrMatches[0][count($arrMatches[0]) - 1] );
		$arrRet["length"] = $arrTemp[1];
		
		//Extract movie length from log
		preg_match_all("/". EncodeManager::LOG_KEY_HLS_SIZE . " [^ \r\n]+/",$strJobLog,$arrMatches);
		$arrTemp = explode ( ' ', $arrMatches[0][count($arrMatches[0]) - 1] );
		$arrRet["hls_size"] = $arrTemp[1];
		
		//Extract movie length from log
		preg_match_all("/". EncodeManager::LOG_KEY_MP4_SIZE . " [^ \r\n]+/",$strJobLog,$arrMatches);
		$arrTemp = explode ( ' ', $arrMatches[0][count($arrMatches[0]) - 1] );
		$arrRet["mp4_size"] = $arrTemp[1];
		
		return $arrRet;
	}
	
	static function IsFInished($status){
		switch($status){
				case EncodeManager::STATUS_CANCELLED:
				case EncodeManager::STATUS_UPLOADING_DONE:
				
				
				case EncodeManager::STATUS_ENCODING_HLS_ERROR:
				case EncodeManager::STATUS_ENCODING_MP4_ERROR:
				case EncodeManager::STATUS_DOWNLOAD_ERROR:
				case EncodeManager::STATUS_UPLOAD_ERROR:
				case EncodeManager::STATUS_UNK_ERROR:
					return true;
					break;
			default:
				return false;
		}
		return false;
	}

	static function IsError($status){
		switch($status){
				case EncodeManager::STATUS_CANCELLED:
				case EncodeManager::STATUS_ENCODING_HLS_ERROR:
				case EncodeManager::STATUS_ENCODING_MP4_ERROR:
				case EncodeManager::STATUS_DOWNLOAD_ERROR:
				case EncodeManager::STATUS_UPLOAD_ERROR:
				case EncodeManager::STATUS_UNK_ERROR:
					return true;
					break;
			default:
				return false;
		}
		return false;
	}

	static function sort($a, $b){
	    if ($a['job_id'] == $b['job_id']) {
	        return 0;
	    }
	    return ($a['job_id'] < $b['job_id']) ? -1 : 1;
	}
	

}

?>