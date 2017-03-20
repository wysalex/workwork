#!/PGRAM/php/bin/php -q
<?php
include_once('/PDATA/htmls/class/admin/XorFileHeadEncode.php');
include_once('/PDATA/htmls/class/admin/CDbshell.php');
include_once('/PDATA/htmls/class/admin/ImapClient.php');

$configFile = $argv[1];

if (!is_file($configFile)) {
	exit;
}

$db = new CDbShell();

$config = json_decode(file_get_contents($configFile));

$imapId = $config->id;
$username = $config->username;
list($account, $domain) = explode('@', $username);

$target = '/HDD/PCONF/imap/' . $domain . '/' . $account . '/imap.' . $username;
$logFile = $target . '.log';
$uidFile = $target . '.uid';
$sStatusFile = '/ram/tmp/imap/status/imap_status_' . $imapId;

$oCmt = new countMicroTime(); //time elapse
$maxProcessTime = 3600; // timeout 1hr

$oStatus = new StdClass;
$oStatus->status = 'initial';
$oStatus->total = 0;
$oStatus->fetched = 0;
$oStatus->fetchedSize = 0;
$oStatus->elapse = 0;
$oStatus->expire = time() + 3600;
updateStatus($oStatus);

$imap = new ImapClient($config->server, $config->port, $config->flag);
$result = $imap->connect($config->username, $config->password);
if ($result === false) {
	recordLog(' Can_not_connect ' . $server . ': ' . htmlspecialchars(imap_last_error(), ENT_QUOTES));
	var_dump(imap_last_error());
	exit;
}
$folders = $imap->getFolders();

$aUnsavedUid = $aSavedUid = $aAppendFail = array();
$nCount = $nUnfetched = $nFetched = $nUnfetchedSize = $nFetchedSize = 0;
imapUid('read', $aSavedUid);

foreach ($folders as $key => $folder) {
	if ($config->mailbox[0] !== 'ALL' && !in_array($folder, $config->mailbox)) {
		unset($folders[$key]);
		continue;
	}

	$imap->selectFolder($folder);
	$check = $imap->getResponseMailbox();
	if ($check->Nmsgs === 0) {
		continue;
	}

	$aFolderName = convertFolder($folder);

	$messageUids = $imap->getMessages();
	if (is_array($aSavedUid[$aFolderName['utf7']])) {
		$aUnsavedUid[$aFolderName['utf7']] = array_diff($messageUids, $aSavedUid[$aFolderName['utf7']]);
	} else {
		$aUnsavedUid[$aFolderName['utf7']] = $messageUids;
	}
	$nUnfetched += count($aUnsavedUid[$aFolderName['utf7']]);
}
$oStatus->total = $nUnfetched;
$oStatus->status = 'fetch';
updateStatus($oStatus);

foreach ($folders as $folder) {
	$aFolderName = convertFolder($folder);

	$imap->selectFolder($folder);
	$check = $imap->getResponseMailbox();
	if ($check->Nmsgs === 0) {
		continue;
	}

	recordLog(' Check_mailbox ' . $aFolderName['utf8']);

	$oStatus->status = 'fetch mailbox : ' . $aFolderName['utf8'];
	updateStatus($oStatus);

	// fetch message to mail archive
	if (!is_array($aUnsavedUid[$aFolderName['utf7']]) || empty($aUnsavedUid[$aFolderName['utf7']])) {
		continue;
	}
	while ($uid = array_shift($aUnsavedUid[$aFolderName['utf7']])) {
		if($nFetched > 0 && 0 === ($nFetched % 500)) {
			echo memory(true) . "\n";
		}
		if($nFetched > 0 && 0 === ($nFetched % 100)) { // clear cache to release memory
			$imap->releaseCache();
		}

		$overview = $imap->getMessageInfo($uid);
		if ($config->skip_size > 0 && $overview->size > $config->skip_size) {
			continue;
		}
		if ($overview->msgno > 0) {
			$sEmlFile = '/HDD/PDATA/imap/' . $domain . '/' . $account . '/' . $aFolderName['utf7'] . '.' . $uid . '.S=' . $overview->size . '.eml';
			if ($imap->saveMessage($sEmlFile, $overview->msgno)) {
				$nFetched++;
				$nFetchedSize += $overview->size;

				imapUid('add', $aSavedUid, $aFolderName['utf7'], array($uid));
				recordLog(" Fetched fetched: " . $nFetched . "/ uid: " . $uid . " (" . humanSize($overview->size) . ")");
				$oStatus->fetched = $nFetched;
				$oStatus->fetchedSize = $nFetchedSize;
				updateStatus($oStatus);

				// delete mail
				// is very danger please check is currect
				$nEmlSize = filesize($sEmlFile);
				if ($config->keep_mail === false && $nEmlSize > 0 && $nEmlSize === $overview->size) {
					$imap->deleteMessage($overview->msgno);
					recordLog(" Delete mailbox: " . $aFolderName['utf8'] . " -> msgno: " . $overview->msgno);
				}

				if ($oCmt->nowSecond() > $maxProcessTime) {
					$sFetchedSize = humanSize($nFetchedSize);
					recordLog(" Fetch_timeout " . $server . ": fetched: " . $nFetched . " total size: " . $sFetchedSize . " time: " . $oCmt->nowSecond());
					$oStatus->status = 'fetch timeout';
					$oStatus->elapse = $oCmt->nowSecond();
					updateStatus($oStatus);
					addLog($config, $oStatus);

					echo "Timeout\n";
					echo "================\n";
					echo 'Fetched : ' . $nFetched . "\n";
					echo 'Total size : ' . $sFetchedSize . "\n";
					echo 'END ' . memory(true) . "\n";
					echo 'Time : ' . $oStatus->elapse . "\n";
					exit;
				}
			} else {
				recordLog(" WARNING message " . $uid . " not saved. " . imap_last_error());
				//發生錯誤跳過並紀錄
				$aAppendFail[$aFolderName['utf7']] = $uid;
			}
		}
	}
}
if ($config->keep_mail === false) {
	imap_expunge($resource);
}

if(!empty($aAppendFail)) {
	foreach($aAppendFail as $dMbox => $uid) {
		recordLog(' mailbox ' . mb_convert_encoding($dMbox, "UTF-8", "UTF7-IMAP") . ' message can not be transferred successfully. Uid: ' . $uid);
	}
}

$oStatus->status = 'end';
$oStatus->elapse = $oCmt->nowSecond();
updateStatus($oStatus);
addLog($config, $oStatus);
echo "Fetch end\n";
echo "================\n";
echo 'Fetched : ' . $nFetched . "\n";
$sFetchedSize = humanSize($nFetchedSize);
echo 'Total size : ' . $sFetchedSize . "\n";
echo 'END ' . memory(true) . "\n";
echo 'Time : ' . $oStatus->elapse . "\n";

recordLog(' End fetch');
unlink($sStatusFile);
exit;

function convertFolder($folder)
{
	$folderAsciiEncode = ('ascii' == strtolower(mb_detect_encoding($folder))); //判斷遠端目錄名稱編碼是否為 ASCII
	//如遠端目錄名稱是 ASCII 編碼則預設認定是 UTF7-IMAP , 反之則以偵測到的編碼來轉換成 UTF7-IMAP
	$folder_encode = mb_detect_encoding($folder, array('ASCII', 'BIG5', 'GB2312', 'GBK')); //依指定編碼來偵測遠端目錄名稱
	$utf7Folder = $folderAsciiEncode ? $folder : mb_convert_encoding($folder, "UTF7-IMAP", $folder_encode);
	if ('ascii' == strtolower(mb_detect_encoding($folder))) {
		$utf8Folder = mb_convert_encoding($folder, 'utf-8', 'utf7-imap');
	} else {
		$utf8Folder = mb_convert_encoding($folder, 'utf-8', $folder_encode);
	}
	return array(
		'utf7' => $utf7Folder,
		'utf8' => $utf8Folder,
	);
}

function imapUid($sFileType, &$aSavedUid, $utf7Mailbox = '',array $aMid = array())
{
	global $uidFile;

	switch ($sFileType) {
		case 'read':
			if (is_file($uidFile)) {
				$aFile = file($uidFile);
				foreach($aFile as $sVal) {
					if(preg_match("/\[(\w+.*)\]/", $sVal, $match)) {
						$sTitle = $match[1];
						$aSavedUid[$sTitle] = array();
					}
					if(preg_match("/(\d+)/", $sVal, $match2)) {
						$aSavedUid[$sTitle][] = $match2[1];
					}
				}
			}
			break;
		case 'add':
			if(isset($aSavedUid[$utf7Mailbox])) {
				$aSavedUid[$utf7Mailbox] = array_merge($aSavedUid[$utf7Mailbox], $aMid);
			} else {
				$aSavedUid[$utf7Mailbox] = $aMid;
			}
		case 'write':
			$sContent = '';
			foreach($aSavedUid as $sMailBox => $aUid) {
				$sContent .= "[" . $sMailBox . "]\n";
				$sContent .= join("\n", $aUid) . "\n\n";
			}
			$fp = fopen($uidFile, 'w');
			fwrite($fp, $sContent);
			fclose($fp);
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

function addLog($config, $status)
{
	global $db;

	$aData = array(
		'sysimap_id' => $config->id,
		'receive'=> $status->fetched,
		'receive_size'=> $status->fetchedSize,
		'elapse'=> $status->elapse,
		'errors'=> isset($status->erors) ? $status->erors : '',
	);
	$aFields = array_keys($aData);
	$aValues = array_values($aData);
	trigger_error(
		sprintf(
			'addLog %s : %d mail(s), size %s, elapse %.1f second(s)',
			$config->username,
			$status->fetched,
			humanSize($status->fetchedSize),
			$status->elapse
		)
	);
	$return = $db->insert('sysimap_log', $aFields, $aValues);
}

function recordLog($sMsg)
{
	global $logFile;
	$fp = fopen($logFile, 'a');
	fwrite($fp, date("m-d H:i:s") . " " . $sMsg . "\n");
	fclose($fp);
}

function memory($peak)
{
	if ($peak) {
		return humanSize(memory_get_peak_usage());
	} else {
		return humanSize(memory_get_usage());
	}
}
