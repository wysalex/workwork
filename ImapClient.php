<?php

namespace Imap\ImapClient;

use Imap\ImapClient\ImapConnect as ImapConnect;

/**
 * summary
 */
class ImapClient
{
	public $host;
	public $port;
	public $flag;
	public $mailbox;
	public $option;
	public $n_retries;

	private $server;
	private $resource;
	public $connection;

	public $folder;

	/**
	 * @param $server connect server IP or Domain
	 * @param $port connect port IMAP 143 / IMAPS 993
	 * @param $flag
	 * @param $option
	 * @param $n_retries
	 */
	public function __construct($host, $port = 993, $flag = '', $mailbox, $option = 0, $n_retries = 0)
	{
		$this->host = $host;
		$this->port = $port;
		$this->flag = $flag;
		$this->mailbox = $mailbox;
		$this->option = $option;
		$this->n_retries = $n_retries;
	}

	public function connect($username, $password)
	{
		$connect = new ImapConnect();
		$result = $connect->authenticate($username, $password);
		if ($result) {
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
	 *
	 * @param string $separator. Default is '.'
	 * @param int $type. Has two meanings 0 and 1.
	 * If 0 return nested array, if 1 return an array of strings.
	 * @return array with folder names
	 */
	public function getFolders($separator = '.')
	{
		$folders = imap_list($this->resource, $this->connection, "*");
		return str_replace($this->connection, '', $folders);
	}

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
	public function saveMessage($file = null, $id = null)
	{
		if ($file === null) {
			return false;
		} else if ($id === null) {
			return false;
		}
		$result = @imap_savebody($this->resource, $file, $id);
		return $result;
	}

	/**
	 * delete given message
	 *
	 * @param int $id of the message
	 * @return bool success or not
	 */
	public function deleteMessage($id)
	{
		return $this->deleteMessages(array($id));
	}

	/**
	 * delete messages
	 *
	 * @return bool success or not
	 * @param $ids array of ids
	 */
	public function deleteMessages($ids)
	{
		foreach ($ids as $id) {
			imap_delete($this->imap, $id);
		}
		return imap_expunge($this->imap);
	}

	/**
	 * @see http://php.net/manual/en/function.imap-gc.php
	 */
	public function releaseCache($option = IMAP_GC_ELT)
	{
		imap_gc($resource, IMAP_GC_ELT);
	}

	public function __destruct()
	{
		if (is_resource($this->resource)) {
			imap_close($this->resource);
		}
	}
}
