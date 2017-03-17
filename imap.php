#!/PGRAM/php/bin/php -q
<?php
include_once('/PDATA/htmls/class/admin/XorFileHeadEncode.php');
include_once('/PDATA/htmls/class/admin/CDbshell.php');

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

$aImapFlag = array();
if ($config->flag === 'auto') {
	$aImapFlag[] = '/imap4rev1/notls';
	$aImapFlag[] = '/imap4rev1/notls/novalidate-cert';
	$aImapFlag[] = '/imap4rev1/tls/novalidate-cert';
	$aImapFlag[] = '/imap4rev1/ssl/notls';
	$aImapFlag[] = '/imap4rev1/tls';
	$aImapFlag[] = '/imap4rev1/ssl/tls';
	$aImapFlag[] = '/imap4rev1/ssl/notls/novalidate-cert';
	$aImapFlag[] = '/imap4rev1/ssl/tls/novalidate-cert';
} else {
	$aImapFlag[] = $config->flag;
}

foreach ($aImapFlag as $flag) {
	$server = '{' . $config->server . ':' . $config->port . $flag . ($config->keep_mail ? '/readonly' : '') . '}';
	// $server = '{' . $config->server . ':' . $config->port . $flag . '/readonly}';
	recordLog(" Opening with " . $server . " (" . $config->username . ")...");
	$resource = imap_open($server, $config->username, $config->password);
	if ($resource !== false) {
		break;
	}
}

if ($resource === false) {
	recordLog(' Can_not_connect ' . $server . ': ' . htmlspecialchars(imap_last_error(), ENT_QUOTES));
	var_dump(imap_errors());
	exit;
}
$check = @imap_check($resource);
$connection = substr($check->Mailbox, 0, strpos($check->Mailbox, '}') + 1);
$list = @imap_list($resource, $connection, "*");
// recordLog(" {$aLang['_MSG_Can_not_connect']} $dest_server: " . htmlspecialchars(imap_last_error(), ENT_QUOTES));

if (empty($list)) exit;

$aUnsavedUid = $aSavedUid = $aAppendFail = array();
$nCount = $nUnfetched = $nFetched = $nUnfetchedSize = $nFetchedSize = 0;
imapUid('read', $aSavedUid);

// get total message
$oStatus->status = 'check unfetched';
updateStatus($oStatus);
foreach ($list as $key => $mailbox) {
	$mailboxName = str_replace($connection, '', $mailbox);
	if ($config->mailbox[0] !== 'ALL' && !in_array($mailboxName, $config->mailbox)) {
		unset($list[$key]);
		continue;
	}

	$check = @imap_check($resource);
	if ($check === false || $check->Mailbox !== $mailbox) {
		@imap_reopen($resource, $mailbox);
		$check = @imap_check($resource);
	}

	if ($check->Nmsgs === 0) {
		continue;
	}

	$aMailboxName = convertMailbox($mailboxName);

	$messageUids = @imap_search($resource, 'ALL', SE_UID);
	if (is_array($aSavedUid[$aMailboxName['utf7']])) {
		$aUnsavedUid[$aMailboxName['utf7']] = array_diff($messageUids, $aSavedUid[$aMailboxName['utf7']]);
	} else {
		$aUnsavedUid[$aMailboxName['utf7']] = $messageUids;
	}
	$nUnfetched += count($aUnsavedUid[$aMailboxName['utf7']]);
}
$oStatus->total = $nUnfetched;
$oStatus->status = 'fetch';
updateStatus($oStatus);

foreach ($list as $mailbox) {
	$mailboxName = str_replace($connection, '', $mailbox);
	$aMailboxName = convertMailbox($mailboxName);

	$check = @imap_check($resource);
	if ($check === false || $check->Mailbox !== $mailbox) {
		@imap_reopen($resource, $mailbox);
		$check = @imap_check($resource);
	}

	recordLog(' Check_mailbox ' . $aMailboxName['utf8']);

	$oStatus->status = 'fetch mailbox : ' . $aMailboxName['utf8'];
	updateStatus($oStatus);

	// fetch message to mail archive
	if (!is_array($aUnsavedUid[$aMailboxName['utf7']]) || empty($aUnsavedUid[$aMailboxName['utf7']])) {
		continue;
	}
	while ($uid = array_shift($aUnsavedUid[$aMailboxName['utf7']])) {
		if($nFetched > 0 && 0 === ($nFetched % 500)) {
			echo memory(true) . "\n";
		}
		if($nFetched > 0 && 0 === ($nFetched % 100)) { // clear cache to release memory
			imap_gc($resource, IMAP_GC_ELT);
		}

		$overview = @imap_fetch_overview($resource, $uid, FT_UID);
		if ($overview[0]->msgno > 0) {
			$sEmlFile = '/HDD/PDATA/imap/' . $domain . '/' . $account . '/' . $aMailboxName['utf7'] . '.' . $uid . '.S=' . $overview[0]->size . '.eml';
			if (@imap_savebody($resource, $sEmlFile, $overview[0]->msgno)) {
				$nFetched++;
				$nFetchedSize += $overview[0]->size;

				imapUid('add', $aSavedUid, $aMailboxName['utf7'], array($uid));
				recordLog(" Fetched fetched: " . $nFetched . "/ uid: " . $uid . " (" . humanSize($overview[0]->size) . ")");
				$oStatus->fetched = $nFetched;
				$oStatus->fetchedSize = $nFetchedSize;
				updateStatus($oStatus);

				// delete mail
				// is very danger please check is currect
				$nEmlSize = filesize($sEmlFile);
				if ($config->keep_mail === false && $nEmlSize > 0 && $nEmlSize === $overview[0]->size) {
					imap_delete($resource, $overview[0]->msgno);
					recordLog(" Delete mailbox: " . $aMailboxName['utf8'] . " -> msgno: " . $overview[0]->msgno);
				}

				if ($oCmt->nowSecond() > $maxProcessTime) {
					$sFetchedSize = humanSize($nFetchedSize);
					recordLog(" Fetch_timeout " . $server . ": fetched: " . $nFetched . " total size: " . $sFetchedSize . " time: " . $oCmt->nowSecond());
					$oStatus->status = 'fetch timeout';
					$oStatus->elapse = $oCmt->nowSecond();
					updateStatus($oStatus);
					addLog($config, $oStatus);
					if ($config->keep_mail === false) {
						imap_expunge($resource);
					}
					imap_close($resource);
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
				$aAppendFail[$aMailboxName['utf7']] = $uid;
			}
		}
	}
}
if ($config->keep_mail === false) {
	imap_expunge($resource);
}
imap_close($resource);

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

function convertMailbox($mailboxName)
{
	$mailboxAsciiEncode = ('ascii' == strtolower(mb_detect_encoding($mailboxName))); //判斷遠端目錄名稱編碼是否為 ASCII
	//如遠端目錄名稱是 ASCII 編碼則預設認定是 UTF7-IMAP , 反之則以偵測到的編碼來轉換成 UTF7-IMAP
	$mailbox_encode = mb_detect_encoding($mailboxName, array('ASCII', 'BIG5', 'GB2312', 'GBK')); //依指定編碼來偵測遠端目錄名稱
	$utf7Mailbox = $mailboxAsciiEncode ? $mailboxName : mb_convert_encoding($mailboxName, "UTF7-IMAP", $mailbox_encode);
	if ('ascii' == strtolower(mb_detect_encoding($mailboxName))) {
		$utf8Mailbox = mb_convert_encoding($mailboxName, 'utf-8', 'utf7-imap');
	} else {
		$utf8Mailbox = mb_convert_encoding($mailboxName, 'utf-8', $mailbox_encode);
	}
	return array(
		'utf7' => $utf7Mailbox,
		'utf8' => $utf8Mailbox,
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
