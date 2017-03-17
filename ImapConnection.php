<?php

namespace Imap\ImapClient;

/**
 * summary
 */
class ImapConnect
{

	private $server;
	private $resource;
	public $connection;

	public function __construct()
	{
		//
	}

	public function authenticate($username, $password)
	{
		$aImapFlag = array();
		if ($this->flag === 'auto') {
			$aImapFlag[] = '/imap4rev1/notls';
			$aImapFlag[] = '/imap4rev1/notls/novalidate-cert';
			$aImapFlag[] = '/imap4rev1/tls/novalidate-cert';
			$aImapFlag[] = '/imap4rev1/ssl/notls';
			$aImapFlag[] = '/imap4rev1/tls';
			$aImapFlag[] = '/imap4rev1/ssl/tls';
			$aImapFlag[] = '/imap4rev1/ssl/notls/novalidate-cert';
			$aImapFlag[] = '/imap4rev1/ssl/tls/novalidate-cert';
		} else {
			$aImapFlag[] = $this->flag;
		}

		foreach ($aImapFlag as $flag) {
			$this->server = '{' . $this->host . ':' . $this->port . $flag . '}' . $this->mailbox;
			$this->resource = imap_open($this->server, $username, $password);
			if ($this->resource !== false) {
				break;
			}
		}

		if ($this->resource === false) {
			return false;
		}
		$this->setConnection();
		return true;
	}

	public function setConnection()
	{
		$check = @imap_check($this->resource);
		$this->connection = substr($check->Mailbox, 0, strpos($check->Mailbox, '}') + 1);
	}

	public function getServer()
	{
		return $this->server;
	}

	public function getResource()
	{
		return $this->resource;
	}
}
