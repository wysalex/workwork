#!/PGRAM/php/bin/php -q
<?php
declare(ticks = 1);

register_shutdown_function('shutdown');

pcntl_signal(SIGTERM, 'sig_handler'); // kill -15
pcntl_signal(SIGHUP,  'sig_handler'); // kill -1

include_once('system.ini');
include_once($HTMLS . '/class/admin/XorFileHeadEncode.php');
include_once($HTMLS . '/class/admin/CDbshell.php');
include_once($HTMLS . '/class/admin/ImapClient.php');

$nPid = getmypid();
$configFile = $argv[1];
/**
 * @param $action (normal/clear)
 * @param $type (proxy/migration)
 */
$action = $argv[2] ? $argv[2] : 'normal';
$type = $argv[3] ? $argv[3] : 'proxy';

$aAction = array(
	'normal',
	'clear',
);

if (!is_file($configFile)) {
	trigger_error('config file error');
	exit;
}

if (!in_array($action, $aAction)) {
	trigger_error('argv 2 error');
	exit;
}

$db = new CDbShell();
$aDeletedUid = $aUnsavedUid = $aSavedUid = $aAppendFail = array();
$nCount = $nUnfetched = $nFetched = $nUnfetchedSize = $nFetchedSize = $nDelCount = 0;
$nErrorCount = 0;

$config = json_decode(file_get_contents($configFile));

$username = $config->username;
list($account, $domain) = explode('@', $username);

$target = substr($configFile, 0, -4);
$logFile = $target . 'log';
$uidFile = $target . 'uid';
$sStatusFile = $config->status_file;

$oCmt = new countMicroTime(); // time elapse
$maxProcessTime = 3600; // timeout 1hr

$oStatus = new StdClass;
$oStatus->status = '<span ma-lang="_STATUS_Initial"></span>';
$oStatus->start = $oCmt->nSec;
$oStatus->total = 0;
$oStatus->fetched = 0;
$oStatus->fetchedSize = 0;
$oStatus->delCount = 0;
$oStatus->elapse = 0;
$oStatus->expire = time() + 3600;
$oStatus->error = '';
$oStatus->id = $config->id;
$oStatus->username = $config->username;
$oStatus->pid = getmypid();
updateStatus($oStatus);

if ($type === 'migration') {
	include_once('/PDATA/htmls/class/admin/migration.php');
	$oMigration = new migration();
	$nFlag = 0;
	$sSql = 'SELECT nFlag FROM migration WHERE id = ?';
	$sth = $db->query($sSql, array($config->id));
	if ($aRow = $db->fetch_array($sth, PDO::FETCH_ASSOC)) {
		$nFlag = $aRow['nFlag'];
	}
	$fp = fopen($logFile, 'w');
	fclose($fp);
}
if ($type === 'migration') {
	$aRow = array(
		'server' => $config->server,
	);
	updateMigrationStatus($aRow);
}

if ($type === 'proxy') {
	recordLog('========================================================================================================================');
	recordLog('Initial action: ' . $action . ', server: ' . $config->server . ', port: ' . $config->port . ', flag: ' . $config->flag);
} else if ($type === 'migration') {
	recordLog('<span ma-lang="_MSG_IMAP_MIGRATION_TOOL_READY_TO_START"></span>.........');
	recordLog('<span ma-lang="_MSG_Opening"></span> ' . $config->server . ' (' . $config->username . ').....');
}
imap_timeout(IMAP_READTIMEOUT, 30);
imap_timeout(IMAP_OPENTIMEOUT, 30);
$result = false;
$imap = new ImapClient($config->server, $config->port, $config->flag);
try {
	$result = $imap->connect($config->username, $config->password);
} catch (Exception $e) {
	if ($type === 'proxy') {
		recordLog('Exception $e: ' . json_encode($e));
	}
}
if ($result === false) {
	$imap_last_error = imap_last_error();
	if ($type === 'proxy') {
		recordLog('Can not connect ' . $config->server . ': ' . $imap_last_error);
	} else if ($type === 'migration') {
		$nFlag = $nFlag |= $oMigration->oFlag->getBit('failed');
		recordLog('<span class="Warning" ma-lang="_MSG_Can_not_connect"></span> ' . $config->server . ': ' . htmlspecialchars($imap_last_error, ENT_QUOTES));
	}
	$oStatus->error = $imap_last_error;
	$oStatus->fetched = -1;
	addLog($oStatus);
	trigger_error($oStatus->error);
	exit;
}
if ($type === 'proxy') {
	recordLog('Server "' . $config->server . '" connected! IMAP flag: ' . $imap->getServer());
}
$folders = $imap->getFolders();

imapUid('read', $aSavedUid);

// get total unfetched message
$oStatus->status = '<span ma-lang="_STATUS_CheckUnfetched"></span>';
updateStatus($oStatus);
foreach ($folders as $key => $folder) {
	if (!in_array($folder, $config->mailbox)) {
		unset($folders[$key]);
		continue;
	}

	$sStart = microtime(true);
	$imap->selectFolder($folder);
	$sEnd = microtime(true) - $sStart;

	$check = $imap->getResponseMailbox();

	if ($type === 'proxy') {
		recordLog('Select mailbox: ' . mb_convert_encoding($folder, 'utf-8', 'utf7-imap') . ' Use time: ' . $sEnd . 's');
	}

	if ($check->Nmsgs === 0) {
		unset($folders[$key]);
		updateStatus($oStatus);

		if ($type === 'proxy') {
			recordLog('No mail!');
		}

		continue;
	}

	$aFolderName = convertFolder($folder);

	$messageUids = $imap->getMessages();

	$start = microtime(true);

	if (isset($aSavedUid[$aFolderName['base64']]) && is_array($aSavedUid[$aFolderName['base64']])) {
		$aUnsavedUid[$aFolderName['base64']] = array_diff($messageUids, $aSavedUid[$aFolderName['base64']]);
		if ($action === 'clear') {
			$aDeletedUid[$aFolderName['utf7']] = array_values(array_intersect($messageUids, $aSavedUid[$aFolderName['base64']]));
		}
	} else {
		$aUnsavedUid[$aFolderName['base64']] = $messageUids;
	}
	$nUnfetched += count($aUnsavedUid[$aFolderName['base64']]);

	$end = microtime(true) - $start;
	if ($type === 'proxy') {
		recordLog('Check mailbox use time: ' . $end . 's');
	}

	$oStatus->total = $nUnfetched;
	updateStatus($oStatus);
}
$oStatus->total = $nUnfetched;
$oStatus->status = '<span ma-lang="_STATUS_StartFetch"></span>';
updateStatus($oStatus);

$imap_errors = imap_errors();
if ($imap_errors !== false && $type === 'proxy') {
	recordLog('Before fetch mailbox. imap_errors: ' . json_encode($imap_errors));
}
foreach ($folders as $folder) {
	$aFolderName = convertFolder($folder);

	$imap->selectFolder($folder);

	if ($type === 'proxy') {
		recordLog('Check mailbox ' . $aFolderName['utf8']);
	} else if ($type === 'migration') {
		recordLog('<span ma-lang="_MSG_CheckMailbox"></span>&nbsp;' . $aFolderName['utf8']);
	}
	$imap_errors = imap_errors();
	if ($imap_errors !== false && $type === 'proxy') {
		recordLog('After check mailbox. imap_errors: ' . json_encode($imap_errors));
	}

	$oStatus->status = '<span ma-lang="_STATUS_FetchMailbox"></span> : ' . $aFolderName['utf8'];
	updateStatus($oStatus);

	// fetch message to mail archive
	if (!is_array($aUnsavedUid[$aFolderName['base64']]) || empty($aUnsavedUid[$aFolderName['base64']])) {
		continue;
	}
	while ($uid = array_shift($aUnsavedUid[$aFolderName['base64']])) {
		// clear cache to release memory
		if($nFetched > 0 && 0 === ($nFetched % 25)) {
			echo memory() . "\n";
			$imap->releaseCache();
		}

		$imap_errors = imap_errors();
		$getMessageStart = microtime(true);
		$overview = $imap->getMessageInfo($uid);
		if ($overview->msgno == 0 || $nErrorCount >= 5) {
			$imap_last_error = imap_last_error();

			imapUid('write', $aSavedUid);
			$oStatus->elapse = $oCmt->nowSecond();
			updateStatus($oStatus);
			if ($type === 'proxy') {
				recordLog('connect lost! error message: ' . json_encode(imap_errors()));
			} else if ($type === 'migration') {
				recordLog('<span ma-lang="_MSG_ConnectionLost"></span>&nbsp;<span ma-lang="_MSG_ErrorMessage"></span>&nbsp;' . htmlspecialchars($imap_last_error, ENT_QUOTES));
			}
			$try = 0;
			while ($try < 5) {
				$try++;
				$imap_errors = imap_errors();

				if ($type === 'proxy') {
					recordLog('try reconnect.....');
				} else if ($type === 'migration') {
					recordLog('<span ma-lang="_MSG_TryReconnect"></span>');
				}
				if ($imap->reconnect()) {
					if ($type === 'proxy') {
						recordLog('connected');
					} else if ($type === 'migration') {
						recordLog('<span ma-lang="_MSG_Connected"></span>');
					}
					$imap->selectFolder($folder);
					$overview = $imap->getMessageInfo($uid);
					if ($overview->msgno == 0) {
						$imap_last_error = imap_last_error();
						if ($type === 'proxy') {
							recordLog('connect lost! error message: ' . $imap_last_error);
						} else if ($type === 'migration') {
							recordLog('<span ma-lang="_MSG_ConnectionLost"></span>&nbsp;<span ma-lang="_MSG_ErrorMessage"></span>&nbsp;' . htmlspecialchars($imap_last_error, ENT_QUOTES));
						}
						continue;
					}
					$nErrorCount = 0;
					break;
				} else {
					if ($type === 'proxy') {
						recordLog('connect error! error message: ' . imap_last_error());
					}
				}
			}

			if ($try >= 5) {
				$status = clone $oStatus;
				$status->elapse = $oCmt->nowSecond();
				$status->fetched = -5;
				$status->erors = json_encode(imap_errors());
				addLog($status);
				continue;
			}
		}

		if ($config->skip_size > 0 && $overview->size > $config->skip_size) {
			continue;
		}
		if ($overview->msgno > 0) {
			$imap_errors = imap_errors();
			if ($type === 'proxy' && $imap_errors !== false) {
				recordLog(json_encode($imap_errors));
			}
			$sEmlFile = $config->maildir . '/new/' . $aFolderName['base64'] . '.' . $uid . '.S=' . $overview->size . '.U=' . $overview->udate . '.eml';
			$result = $imap->saveMessage($sEmlFile, $overview->msgno);
			if ($result) {
				$nEmlSize = filesize($sEmlFile);
				if ($nEmlSize <= 0) {
					imapUid('write', $aSavedUid);
					$oStatus->elapse = $oCmt->nowSecond();
					updateStatus($oStatus);
					unlink($sEmlFile);
					if ($type === 'proxy') {
						$imap_errors = imap_errors();
						recordLog('WARNING: mailbox "' . $aFolderName['utf8'] . '", message uid :' . $uid . ' not save success, eml size is 0. imap_errors: ' . json_encode($imap_errors));
						// recordLog('try reconnect.....');
					}
					// if ($imap->reconnect()) {
					// 	if ($type === 'proxy') {
					// 		recordLog('connected');
					// 	}
					// 	$imap->selectFolder($folder);
					// }
					// 發生錯誤跳過並紀錄
					$aAppendFail[$aFolderName['utf7']]++;
					$nErrorCount++;
					continue;
				}

				touch($sEmlFile, $overview->udate);
				$nErrorCount = 0;
				$nFetched++;
				$nFetchedSize += $overview->size;

				imapUid('add', $aSavedUid, $aFolderName['base64'], $uid);
				if ($type === 'proxy') {
					recordLog('Fetched : ' . $nFetched . ' / uid: ' . $uid . ' (' . humanSize($overview->size) . ') udate: ' . $overview->udate);
				} else if ($type === 'migration') {
					recordLog('<span ma-lang="_MSG_FetchedMail"></span> uid : ' . $uid . ', size : ' . humanSize($overview->size));
				}
				$oStatus->fetched = $nFetched;
				$oStatus->fetchedSize = $nFetchedSize;
				updateStatus($oStatus);

				// delete mail
				if ($config->keep_mail === false) {
					$imap->deleteMessage($overview->msgno);
					recordLog('Delete mailbox: ' . $aFolderName['utf8'] . ' -> msgno: ' . $overview->msgno);
				} else if ($action === 'clear') {
					$imap->deleteMessage($overview->msgno);
					// imapUid('remove', $aSavedUid, $aFolderName['base64'], array($uid));
					recordLog("Delete mailbox: " . $aFolderName['utf8'] . " -> msgno: " . $overview->msgno);
					$nDelCount++;
					$oStatus->delCount = $nDelCount;
					updateStatus($oStatus);
				}

				if ($config->interval_sync && $oCmt->nowSecond() > $maxProcessTime) {
					imapUid('write', $aSavedUid);
					$sFetchedSize = humanSize($nFetchedSize);
					$oStatus->status = '<span ma-lang="_STATUS_FetchTimeout"></span>';
					$oStatus->elapse = $oCmt->nowSecond();
					updateStatus($oStatus);
					addLog($oStatus);

					recordLog('Fetch timeout ' . $server . ': fetched: ' . $nFetched . ' total size: ' . $sFetchedSize . ' time: ' . $oStatus->elapse);
					exit;
				}
				$getMessageTime = $getMessageStart - microtime(true);
				if ($getMessageTime < 0.2) {
					usleep(200000);
				}
			} else {
				recordLog('WARNING: mailbox "' . $aFolderName['utf8'] . '", message uid :' . $uid . ' not save success. imap_errors: ' . json_encode(imap_errors()));
				// 發生錯誤跳過並紀錄
				$aAppendFail[$aFolderName['utf7']]++;
				$nErrorCount++;
			}
		} else {
			recordLog('WARNING: mailbox "' . $aFolderName['utf8'] . '", message ' . $uid . ' not save success, msgno is 0. imap_errors: ' . json_encode(imap_errors()));
			// 發生錯誤跳過並紀錄
			$aAppendFail[$aFolderName['utf7']]++;
			$nErrorCount++;
		}
	}
	if (isset($aDeletedUid[$aFolderName['utf7']]) && !empty($aDeletedUid[$aFolderName['utf7']])) {
		$imap->deleteMessages($aDeletedUid[$aFolderName['utf7']], FT_UID);
		recordLog('Delete mailbox: ' . $aFolderName['utf8'] . ' -> uid: ' . json_encode($aDeletedUid[$aFolderName['utf7']]));
		$nDelCount += count($aDeletedUid[$aFolderName['utf7']]);
		$oStatus->delCount = $nDelCount;
		updateStatus($oStatus);

		$imap->releaseCache();
	}
}

imapUid('write', $aSavedUid);

$oStatus->status = '<span ma-lang="_STATUS_Complete"></span>';
$oStatus->elapse = $oCmt->nowSecond();
updateStatus($oStatus);
addLog($oStatus);
$sFetchedSize = humanSize($nFetchedSize);

if ($type === 'proxy') {
	recordLog('End fetch, total fetched: ' . $nFetched . ' total size: ' . $sFetchedSize . ' time: ' . $oStatus->elapse);
} else if ($type === 'migration') {
	$nFlag = $nFlag |= $oMigration->oFlag->getBit('transferred');
	recordLog('<span ma-lang="_MSG_IMAP_MIGRATION_TOOL"></span> <span ma-lang="_MSG_Finish"></span>.....');
}
exit;



function convertFolder($folder)
{
	$folderAsciiEncode = ('ascii' == strtolower(mb_detect_encoding($folder))); //判斷遠端目錄名稱編碼是否為 ASCII
	//如遠端目錄名稱是 ASCII 編碼則預設認定是 UTF7-IMAP , 反之則以偵測到的編碼來轉換成 UTF7-IMAP
	$folder_encode = mb_detect_encoding($folder, array('ASCII', 'BIG5', 'GB2312', 'GBK')); //依指定編碼來偵測遠端目錄名稱
	$utf7Folder = $folderAsciiEncode ? $folder : mb_convert_encoding($folder, "UTF7-IMAP", $folder_encode);
	if ('ascii' === strtolower(mb_detect_encoding($folder))) {
		$utf8Folder = mb_convert_encoding($folder, 'utf-8', 'utf7-imap');
	} else {
		$utf8Folder = mb_convert_encoding($folder, 'utf-8', $folder_encode);
	}
	return array(
		'base64' => base64_encode($utf7Folder),
		'utf7' => $utf7Folder,
		'utf8' => $utf8Folder,
	);
}

function imapUid($sFileType, &$aSavedUid, $mailbox = '', $uid = 0)
{
	global $uidFile;

	switch ($sFileType) {
		case 'read':
			if (is_file($uidFile)) {
				$aSavedUid = json_decode(file_get_contents($uidFile), true);
			} else {
				file_put_contents($uidFile, json_encode(array()));
			}
			break;
		case 'add':
			if(isset($aSavedUid[$mailbox])) {
				$aSavedUid[$mailbox][] = $uid;
			} else {
				$aSavedUid[$mailbox] = array($uid);
			}
			break;
		case 'write':
			if (isset($aSavedUid) && !empty($aSavedUid)) {
				$fp = fopen($uidFile, 'w');
				fwrite($fp, json_encode($aSavedUid, JSON_PRETTY_PRINT));
				fclose($fp);
			}
			break;
	}
}

function updateStatus($status)
{
	global $sStatusFile;
	$fp = fopen($sStatusFile, 'w');
	fwrite($fp, json_encode($status, JSON_PRETTY_PRINT));
	fclose($fp);
}

function addLog($status)
{
	global $db, $type;

	if ($type === 'proxy') {
		$aData = array(
			'sysimap_id' => $status->id,
			'date' => date('Y-m-d H:i:s'),
			'receive'=> $status->fetched,
			'receive_size'=> $status->fetchedSize,
			'elapse'=> $status->elapse,
			'errors'=> $status->error ?? '',
		);
		$aFields = array_keys($aData);
		$aValues = array_values($aData);
		trigger_error(
			sprintf(
				'addLog %s : %d mail(s), size %s, elapse %.1f second(s)',
				$status->username,
				$status->fetched,
				humanSize($status->fetchedSize),
				$status->elapse
			)
		);
		$db->insert('sysimap_log', $aFields, $aValues);
	}
}

function recordLog($sMsg)
{
	global $logFile, $type, $nPid;

	$fp = fopen($logFile, 'a');
	$sMsg = date('m-d H:i:s') . ' ' . $sMsg;
	if ($type === 'proxy') {
		fwrite($fp, '[' . $nPid . ']' . $sMsg . PHP_EOL);
	} else {
		fwrite($fp, $sMsg . PHP_EOL);
	}
	fclose($fp);
}

function updateMigrationStatus($aRow)
{
	global $db, $config;

	$sWhere = ' id = ' . $config->id;
	$aFields = array_keys($aRow);
	$aValues = array_values($aRow);
	$db->update('migration', $aFields, $aValues, $sWhere);
}

function memory($peak = true)
{
	if ($peak) {
		return humanSize(memory_get_peak_usage());
	} else {
		return humanSize(memory_get_usage());
	}
}

function shutdown()
{
	global $action, $config, $type, $configFile, $sStatusFile, $oMigration, $nFlag;
	global $aAppendFail;

	if(!empty($aAppendFail)) {
		foreach($aAppendFail as $dMbox => $count) {
			if ($type === 'proxy') {
				recordLog('mailbox ' . mb_convert_encoding($dMbox, "UTF-8", "UTF7-IMAP") . ' message can not be transferred successfully. Total: ' . $count);
			} else if ($type === 'migration') {
				recordLog('<span class="to-sprintf Warning" data-params=\'["' . mb_convert_encoding($dMbox, "UTF-8", "UTF7-IMAP") . '", "' . $count . '"]\' ma-lang="_MSG_Warn_Not_Fetched_Num" ></span>');
			}
		}
	}

	if (is_file($sStatusFile)) {
		@unlink($sStatusFile);
	}
	if ($action === 'clear') {
		@unlink($configFile);
	}
	if ($type === 'migration') {
		$nFlag = $nFlag &= ~$oMigration->oFlag->getBit('in_queue');
		$aRow = array(
			'nFlag' => $nFlag,
			'last_check' => time(),
		);
		updateMigrationStatus($aRow);
	}
	echo 'imap.php shutdown', PHP_EOL;
}

function sig_handler($signo)
{
	global $type, $aSavedUid, $oStatus, $nFetchedSize, $oCmt, $oMigration, $nFlag;
	global $imap;

	imap_close($imap->getResource());
	imapUid('write', $aSavedUid);
	$sFetchedSize = humanSize($nFetchedSize);
	$oStatus->status = '<span ma-lang="_STATUS_FetchTimeout"></span>';
	$oStatus->elapse = $oCmt->nowSecond();
	updateStatus($oStatus);
	addLog($oStatus);
	if ($type === 'proxy') {
		recordLog('Has been killed.');
	} else if ($type === 'migration') {
		$nFlag = $nFlag |= $oMigration->oFlag->getBit('break');
		recordLog('<span class="red">[The user interrupts the process] !</span>');
	}
	exit;
}
