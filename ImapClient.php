<?php
include_once('/PDATA/htmls/class/admin/ImapConnection.php');

class ImapClient
{
	public $host;
	public $port;
	public $flag;
	public $mailbox;
	public $option;
	public $n_retries;
	private $username;
	private $password;

	private $server;
	private $resource;
	public $connection;

	public $folder;

	/**
	 * @param $server connect server IP or Domain
	 * @param $port connect port IMAP 143 / IMAPS 993
	 * @param $flag
	 * @param $mailbox
	 * @param $option
	 * @param $n_retries
	 */
	public function __construct($host, $port = 993, $flag = '', $mailbox = '', $option = 0, $n_retries = 0)
	{
		$this->host = $host;
		$this->port = $port;
		$this->flag = $flag;
		$this->mailbox = $mailbox;
		$this->option = $option;
		$this->n_retries = $n_retries;
	}

	/**
	 * connect IMAP
	 *
	 * @param string $username
	 * @param string $password
	 */
	public function connect($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
		$connect = new ImapConnect($this->host, $this->port, $this->flag, $this->mailbox, $this->option, $this->n_retries);
		try {
			$result = $connect->authenticate($username, $password);
		} catch (Exception $e) {
			trigger_error(json_encode($e));
			$result = false;
		}
		$result = $connect->authenticate($username, $password);
		if ($result === true) {
			$this->server = $connect->getServer();
			$this->resource = $connect->getResource();
			$this->connection = $connect->connection;
		}
		return $result;
	}

	/**
	 * select given folder
	 *
	 * @param string $folder name
	 * @return bool successfull opened folder
	 */
	public function selectFolder($folder)
	{
		$result = imap_reopen($this->resource, $this->connection . $folder);
		if ($result === true) {
			$this->folder = $folder;
		}
		return $result;
	}

	/**
	 * Returns all available folders
	 */
	public function getFolders()
	{
		$folders = imap_list($this->resource, $this->connection, "*");
		return str_replace($this->connection, '', $folders);
	}

	/**
	 * Get Messages by Criteria
	 *
	 * @see http://php.net/manual/en/function.imap-search.php
	 *
	 * @param string $criteria ALL, UNSEEN, FLAGGED, UNANSWERED, DELETED, UNDELETED, etc (e.g. FROM "joey smith")
	 * @param $option
	 *
	 * @return array
	 */
	public function getMessages($criteria = 'ALL', $option = SE_UID)
	{
		$result = imap_search($this->resource, $criteria, $option);
		if ($result === false) {
			return array();
		} else {
			return $result;
		}
	}

	/**
	 * Get object response mailbox
	 *
	 * @return object
	 */
	public function getResponseMailbox()
	{
		return imap_check($this->resource);
	}

	/**
	 * @see http://php.net/manual/en/function.imap-fetch-overview.php
	 */
	public function getMessageInfo($id, $option = FT_UID)
	{
		$overview = @imap_fetch_overview($this->resource, $id, $option);
		return $overview[0];
	}

	/**
	 * @see http://php.net/manual/en/function.imap-savebody.php
	 */
	public function saveMessage($file = null, $id = null, $part_number = '', $options = FT_PEEK)
	{
		if ($file === null) {
			return false;
		} else if ($id === null) {
			return false;
		}
		$result = @imap_savebody($this->resource, $file, $id, $part_number, $options);
		return $result;
	}

	/**
	 * delete given message
	 *
	 * @param int $id of the message
	 * @param $options You can set the FT_UID which tells the function to treat the msg_number argument as an UID.
	 *
	 * @return bool success or not
	 */
	public function deleteMessage($id, $options = 0)
	{
		return $this->deleteMessages(array($id));
	}

	/**
	 * delete messages
	 *
	 * @param $ids array of ids
	 * @param $options You can set the FT_UID which tells the function to treat the msg_number argument as an UID.
	 *
	 * @return bool success or not
	 */
	public function deleteMessages($ids, $options = 0)
	{
		foreach ($ids as $id) {
			imap_delete($this->resource, $id, $options);
		}
		return imap_expunge($this->resource);
	}

	public function ping()
	{
		return imap_ping($this->resource);
	}

	public function reconnect()
	{
		if (is_resource($this->resource)) {
			imap_close($this->resource);
		}
		$this->resource = null;
		return $this->connect($this->username, $this->password);
	}

	/**
	 * @see http://php.net/manual/en/function.imap-gc.php
	 */
	public function releaseCache($option = IMAP_GC_ELT)
	{
		imap_gc($this->resource, IMAP_GC_ELT);
	}

	public function getResource()
	{
		return $this->resource;
	}

	public function getServer()
	{
		return $this->server;
	}

	public function __destruct()
	{
		if (is_resource($this->resource)) {
			imap_close($this->resource);
		}
	}
}
