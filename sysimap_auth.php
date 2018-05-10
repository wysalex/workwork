#!/PGRAM/php/bin/php -q
<?php
include_once('base.php');
include_once('XorFileHeadEncode.php');
include_once('system.ini');
include_once('system2.ini');

$sCurFile = basename(__file__);

/**
 * $argv
 *
 * @param 1: server
 * @param 2: port
 * @param 3: security (ssl/tls)
 * @param 4: username
 * @param 5: password
 * @param 6: mailbox
 * @param 7: error file
 * @param 8: status file
 */
if (!isset($argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6])) {
	if(st_isDebugMode($sCurFile)) trigger_error('Invaild param.!');
	exit(1);//error
}

$config = new StdClass;
$config->server = $argv[1];
$config->port = $argv[2];
$config->security = $argv[3];
$config->username = stripcslashes($argv[4]);
$config->password = stripcslashes($argv[5]);
$config->mailbox = json_decode(stripcslashes($argv[6]), true);
// $config->mailbox = json_decode('["INBOX"]', true);
// $config->mailbox = json_decode('["INBOX","INBOX.&V4NXPpD1TvaQGnfl-","INBOX.&bTtS1ZCAiss-","INBOX.&biyKZmpf-","INBOX.&dXBeOFvEkAE-","INBOX.&e6F0BpDo-","INBOX.Drafts","INBOX.Sent","INBOX.Trash","INBOX.ms &fPt9cZAad+U-","INBOX.test","INBOX.work"]', true);
// $config->mailbox = json_decode('["INBOX","INBOX.&biyKZg-1","INBOX.&biyKZg-10","INBOX.&biyKZg-11","INBOX.&biyKZg-12","INBOX.&biyKZg-13","INBOX.&biyKZg-2","INBOX.&biyKZg-20","INBOX.&biyKZg-21","INBOX.&biyKZg-22","INBOX.&biyKZg-23","INBOX.&biyKZg-3","INBOX.&biyKZg-4","INBOX.&biyKZg-5","INBOX.&biyKZg-6","INBOX.&biyKZg-7","INBOX.&biyKZg-8","INBOX.&edhbxg-","INBOX.&edhbxg-.&aXVqX1vG-","INBOX.&edhbxg-.&al9bxg-","INBOX.&edhbxg-.&bOhhDw-","INBOX.&edhbxg-.&bOhhDw-.a","INBOX.&edhbxg-.&bOhhDw-.a.s&WSk--1","INBOX.&edhbxg-.&bOhhDw-.a.s&WSk--2","INBOX.&edhbxg-.&bOhhDw-.a.s&WSk--23","INBOX.&edhbxg-.&bOhhDw-.a.s&WSk--3","INBOX.&edhbxg-.&bOhhDw-.a.s&WSk--4","INBOX.&edhbxg-.&bOhhDw-.a.s&WSk--5","INBOX.&edhbxg-.&bOhhDw-.a.s&WSk--6","INBOX.&edhbxg-.&bOhhDw-.a.s&WSk--7","INBOX.&edhbxg-.&bOhhDw-.a.s&WSk--8","INBOX.&edhbxg-.&bOhhDw-.a.s&WSk--9","INBOX.&edhbxg-.&bOhhDw-.b","INBOX.&edhbxg-.&bOhhDw-.b.s&WSk--1","INBOX.&edhbxg-.&bOhhDw-.b.s&WSk--2","INBOX.&edhbxg-.&bOhhDw-.b.s&WSk--3","INBOX.&edhbxg-.&bOhhDw-.b.s&WSk--4","INBOX.&edhbxg-.&bOhhDw-.b.s&WSk--5","INBOX.&edhbxg-.&bOhhDw-.b.s&WSk--6","INBOX.&edhbxg-.&bOhhDw-.b.s&WSk--7","INBOX.&edhbxg-.&bOhhDw-.b.s&WSk--8","INBOX.&edhbxg-.&bOhhDw-.b.s&WSk--9","INBOX.&edhbxg-.&bOhhDw-.c","INBOX.&edhbxg-.&bOhhDw-.c.s&WSk--1","INBOX.&edhbxg-.&bOhhDw-.c.s&WSk--2","INBOX.&edhbxg-.&bOhhDw-.c.s&WSk--3","INBOX.&edhbxg-.&bOhhDw-.c.s&WSk--4","INBOX.&edhbxg-.&bOhhDw-.c.s&WSk--5","INBOX.&edhbxg-.&bOhhDw-.c.s&WSk--6","INBOX.&edhbxg-.&bOhhDw-.c.s&WSk--7","INBOX.&edhbxg-.&bOhhDw-.c.s&WSk--8","INBOX.&edhbxg-.&bOhhDw-.c.s&WSk--9","INBOX.&edhbxg-.&bOhhDw-.d","INBOX.&edhbxg-.&bOhhDw-.d.s&VzA--1","INBOX.&edhbxg-.&bOhhDw-.d.s&VzA--2","INBOX.&edhbxg-.&bOhhDw-.d.s&VzA--3","INBOX.&edhbxg-.&bOhhDw-.d.s&VzA--4","INBOX.&edhbxg-.&bOhhDw-.d.s&VzA--5","INBOX.&edhbxg-.&bOhhDw-.d.s&VzA--6","INBOX.&edhbxg-.&bOhhDw-.d.s&VzA--7","INBOX.&edhbxg-.&bOhhDw-.d.s&VzA--8","INBOX.&edhbxg-.&bOhhDw-.d.s&VzA--9","INBOX.&edhbxg-.&bOhhDw-.e","INBOX.&edhbxg-.&bOhhDw-.e.s&VzA--1","INBOX.&edhbxg-.&bOhhDw-.e.s&VzA--2","INBOX.&edhbxg-.&bOhhDw-.e.s&VzA--3","INBOX.&edhbxg-.&bOhhDw-.e.s&VzA--4","INBOX.&edhbxg-.&bOhhDw-.e.s&VzA--5","INBOX.&edhbxg-.&bOhhDw-.e.s&VzA--6","INBOX.&edhbxg-.&bOhhDw-.e.s&VzA--7","INBOX.&edhbxg-.&bOhhDw-.e.s&VzA--8","INBOX.&edhbxg-.&bOhhDw-.e.s&VzA--9","INBOX.&edhbxg-.&bOhhDw-.f","INBOX.&edhbxg-.&bOhhDw-.f.s&VzA--1","INBOX.&edhbxg-.&bOhhDw-.f.s&VzA--2","INBOX.&edhbxg-.&bOhhDw-.f.s&VzA--3","INBOX.&edhbxg-.&bOhhDw-.f.s&VzA--4","INBOX.&edhbxg-.&bOhhDw-.f.s&VzA--5","INBOX.&edhbxg-.&bOhhDw-.f.s&VzA--6","INBOX.&edhbxg-.&bOhhDw-.f.s&VzA--7","INBOX.&edhbxg-.&bOhhDw-.f.s&VzA--8","INBOX.&edhbxg-.&bOhhDw-.f.s&VzA--9","INBOX.&edhbxg-.&dVlhDw-","INBOX.&edhbxg-.&dVlhDw-.a","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--1","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--2","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--3","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--4","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--5","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--6","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--7","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--8","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--9","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--a","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--b","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--c","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--d","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--e","INBOX.&edhbxg-.&dVlhDw-.a.s&c4Q--f","INBOX.&edhbxg-.&dVlhDw-.b","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--1","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--2","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--3","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--4","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--5","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--6","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--7","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--8","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--9","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--a","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--b","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--c","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--e","INBOX.&edhbxg-.&dVlhDw-.b.s&c4Q--f","INBOX.&edhbxg-.&dVlhDw-.c","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--1","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--2","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--3","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--4","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--5","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--6","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--7","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--8","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--9","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--a","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--b","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--c","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--d","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--e","INBOX.&edhbxg-.&dVlhDw-.c.s&c4Q--f","INBOX.&edhbxg-.&dVlhDw-.d","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--1","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--2","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--3","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--4","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--5","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--6","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--7","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--8","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--9","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--a","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--b","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--c","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--d","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--e","INBOX.&edhbxg-.&dVlhDw-.d.s&nsM--f","INBOX.&edhbxg-.&dVlhDw-.e","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--1","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--2","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--3","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--4","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--5","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--6","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--7","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--8","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--9","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--a","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--b","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--c","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--d","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--e","INBOX.&edhbxg-.&dVlhDw-.e.s&nsM--f","INBOX.&edhbxg-.&dVlhDw-.f","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--1","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--2","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--3","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--4","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--5","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--6","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--7","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--8","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--9","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--a","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--b","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--c","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--d","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--e","INBOX.&edhbxg-.&dVlhDw-.f.s&nsM--fd","INBOX.&edhbxg-.&i2Z5Og-","INBOX.&edhbxg-.&i2Z5Og-.a","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--1","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--2","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--3","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--4","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--5","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--6","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--7","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--8","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--9","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--a","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--b","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--c","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--d","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--e","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S.1","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S.1.1sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S.1.4","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S.1.6 sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S.1.7 sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S.2","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S.3","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S.4sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S.5sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.S.8 sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.a","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.a.Sharetech","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.a.Sharetech2","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.c","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.c.Sharetech1","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.c.Sharetech2","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.c.Sharetech3","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.e","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.e.Sharetech1","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.e.Sharetech2","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.e.Sharetech3","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.e.Sharetech4","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.e.Sharetech5","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.e.Sharetech6","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.h","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.r","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.t","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.t.dns_sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.t.ftp_sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.t.ms_sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.t.sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.t.update_sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.t.vps_sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.a.s&W4c--f.t.www_sharetech_com_tw","INBOX.&edhbxg-.&i2Z5Og-.b","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--1","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--2","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--3","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--4","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--5","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--6","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--7","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--8","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--9","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--a","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--b","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--c","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--d","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--e","INBOX.&edhbxg-.&i2Z5Og-.b.s&W4c--f","INBOX.&edhbxg-.&i2Z5Og-.c","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--1","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--2","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--3","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--4","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--5","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--6","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--7","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--8","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--9","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--a","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--b","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--c","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--d","INBOX.&edhbxg-.&i2Z5Og-.c.s&W4c--f","INBOX.&edhbxg-.&i2Z5Og-.d","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--1","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--2","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--3","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--4","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--5","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--6","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--76","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--8","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--9","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--a","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--b","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--c","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--d","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--e","INBOX.&edhbxg-.&i2Z5Og-.d.s&W5k--f","INBOX.&edhbxg-.&i2Z5Og-.e","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--1","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--2","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--3","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--4","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--5","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--6","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--7","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--8","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--9","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--a","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--b","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--c","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--d","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--e","INBOX.&edhbxg-.&i2Z5Og-.e.s&W5k--f","INBOX.&edhbxg-.&i2Z5Og-.f","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--1","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--2","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--3","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--4","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--5","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--6","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--7","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--8","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--9","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--a","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--b","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--c","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--d","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--e","INBOX.&edhbxg-.&i2Z5Og-.f.s&W5k--f","INBOX.&lvtbUFgx-","INBOX.123--11122","INBOX.Drafts","INBOX.Sent","INBOX.Sent Items","INBOX.TEST","INBOX.Trash","INBOX.rtfkk","INBOX.rtfkk.456"]', true);

list($sAccount, $sDomain) = explode('@', $config->username);

$sBaseRandName = tempnam('/tmp', $sUsername);
$sErrorFile = isset($argv[7]) ? $argv[7] : $sBaseRandName . '.err';
$sStatusFile = isset($argv[8]) ? $argv[8] : $sBaseRandName . '.status';
$sImapUserDir = $sImapDataDir . '/' . $sDomain;
if (!is_dir($sImapUserDir)) mkdir($sImapUserDir, 0644, true);
$sImapUserDir = $sImapDataDir . '/' . $sDomain . '/' . $sAccount;
if (!is_dir($sImapUserDir)) mkdir($sImapUserDir, 0644, true);

$oStatus = new StdClass;
$oStatus->status = 'initial';
$oStatus->total = 0;
$oStatus->fetched = 0;
$oStatus->fetchedSize = 0;
$oStatus->elapse = 0;
$oStatus->expire = time() + 3600;
updateStatus($oStatus);

$result = checkMailbox($config->server, $config->username, $config->password, $config->port, $config->mailbox, $config->security);

echo 'total : ' . $oStatus->total . " mail(s)\n";
updateStatus($oStatus);
if (!isset($argv[7]) && is_file($sErrorFile)) @unlink($sErrorFile);
if (!isset($argv[8]) && is_file($sStatusFile)) @unlink($sStatusFile);
echo memory(true) . "\n";
exit(0);

function checkMailbox($sServer, $sUser, $sPasswd, $nPort, $mailboxs, $sSecurity = '', $nTimeout = 15)
{
	global $oStatus;

	$sOkPat = '^\* OK\s';
	$sCmdOkPat = '^\C: OK\s';
	if ($sSecurity === 'ssl' || $sSecurity === 'tls') {
		$sServer = $sSecurity . '://' . $sServer . ':' . $nPort;
		$aContextOptions = array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
			),
		);
		$context = stream_context_create($aContextOptions);
		$fp = stream_socket_client($sServer, $errno, $errstr, $nTimeout, STREAM_CLIENT_CONNECT, $context);
	} else {
		$fp = fsockopen($sServer, $nPort, $errno, $errstr, $nTimeout);
	}
	if (!$fp) {
		$oStatus->status = 'ERROR: ' . $errno . ' - ' . $errstr;
		updateError($oStatus->status);
		updateStatus($oStatus);
		return false;
	}
	// $msg = fgets($fp);
	// echo trim($msg) . "\n";
	fputs($fp, 'C: login ' . $sUser . ' ' . $sPasswd . "\r\n");
	$result = getResponse($fp);
	if ($result !== true) {
		$oStatus->status = $result;
		updateError($oStatus->status);
		updateStatus($oStatus);
		return false;
	}
	foreach ($mailboxs as $mailbox) {
		fputs($fp, 'C: select "' . $mailbox . "\"\r\n");
		while ($msg = fgets($fp)) {
			// if (preg_match("/EXISTS$/", trim($msg))) {
			// 	$msg = preg_split("/[\s]+/", trim($msg));
			// 	// echo $msg[1] . " mail(s)\n";
			// 	$oStatus->total += $msg[1];
			// }
			if (preg_match("/^\C: OK\s/", $msg)) {
				fputs($fp, 'C: search all' . "\r\n");
				// fputs($fp, 'C: search larger 10485760' . "\r\n");
				$search = fgets($fp);
				$msg = fgets($fp);
				if (preg_match("/^\C: OK\s/", $msg)) {
					$search = trim(substr($search, 8));
					$search = preg_split("/[\s]+/", $search);
					$oStatus->total += count($search);
				}
				break;
			}
			if (preg_match("/^\C: NO\s/", $msg)) {
				break;
			}
		}
	}
	fputs($fp, 'C: logout' . "\r\n");
	$result = getResponse($fp);
	if ($result !== true) {
		$oStatus->status = $result;
		updateError($oStatus->status);
		updateStatus($oStatus);
		return false;
	}
	fclose($fp);
	return true;
}

function getResponse($fp)
{
	while ($msg = fgets($fp)) {
		// echo $msg;
		if (preg_match("/^\C: OK\s/", $msg)) {
			return true;
		}
		if (preg_match("/^\C: NO\s/", $msg)) {
			fclose($fp);
			return trim($msg);
		}
	}
}

function updateError($error)
{
	global $sErrorFile;
	$fp = fopen($sErrorFile, 'w');
	fwrite($fp, $error);
	fclose($fp);
}

function updateStatus($status)
{
	global $sStatusFile;
	$fp = fopen($sStatusFile, 'w');
	fwrite($fp, json_encode($status, JSON_PRETTY_PRINT));
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



























/*
$imap = new ImapClient($config->server, $config->port, $config->flag);
$result = $imap->connect($config->username, $config->password);
if ($result === false) {
	// recordLog(' Can_not_connect ' . $server . ': ' . htmlspecialchars(imap_last_error(), ENT_QUOTES));
	var_dump(imap_last_error());
	exit;
}
$folders = $imap->getFolders();

$aUnsavedUid = array();
$nUnfetched = 0;

// get total message
$oStatus->status = 'check unfetched';
updateStatus($oStatus);
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
	// $aUnsavedUid[$aFolderName['utf7']] = $messageUids;
	$nUnfetched += count($messageUids);
}
$oStatus->total = $nUnfetched;
$oStatus->status = 'fetch';
updateStatus($oStatus);


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
*/
