<?php

class ImapConnect
{
	private $host;
	private $port;
	private $flag;
	private $mailbox;
	private $option;
	private $n_retries;

	private $server;
	private $resource;
	public $connection;

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
			if ($this->port == 993) {
				$aImapFlag = array_reverse($aImapFlag);
			}
		} else {
			$aImapFlag[] = $this->flag;
			if ($this->port == 993) {
				$aImapFlag[] = '/imap4rev1/ssl/tls/novalidate-cert';
				$aImapFlag[] = '/imap4rev1/ssl/notls/novalidate-cert';
			}
		}

		foreach ($aImapFlag as $flag) {
			if ($this->host == 'outlook.office365.com') {
				$flag .= '/authuser=' . $username;
			}
			$this->server = '{' . $this->host . ':' . $this->port . $flag . '}' . $this->mailbox;
			$this->resource = imap_open($this->server, $username, $password, OP_HALFOPEN, 1);
			if ($this->resource !== false) {
				break;
			} else {
				trigger_error(imap_last_error());
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
