<?php

namespace MailApp;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MailServiceProvider implements ServiceProviderInterface {

  public function register(Application $app) {
    $this->app = $app;
    $this->blobStore = new BlobStore("/tmp");
    $app['imap'] = $this;
  }

  public function getSettings() {
    $sql = "SELECT * FROM settings WHERE id = ?";
    $ary = $this->app['db']->fetchAssoc($sql, array(1));

    return new MailSettings($ary['host'], $ary['user'], $ary['pass']);
  }

  public function updateSettings($host, $user, $pass) {
    $sql = "UPDATE settings set host=?, user=?, pass=? where id=1";
    $this->app['db']->executeUpdate($sql, array($host, $user, $pass));

  }

  public function fetchOverview() {
    # Not using zetacomponents here: imap_fetch_overview seems more efficient because
    # it doesn't fetch attachments (TODO: figure out if zeta has a call for this)
    $settings = $this->getSettings();
    $host = $settings->host;
    $user = $settings->user;
    $pass = $settings->pass;

    $mbox = sprintf("{%s:993/imap/ssl}INBOX", $host);
    $stream = imap_open($mbox,$user,$pass);
    if (!$stream) { 
        return array('error' => imap_last_error());
    }
    $check = imap_check($stream);
    $overview = imap_fetch_overview($stream,"1:{$check->Nmsgs}",0);
    imap_close($stream);
    return array('mails' => $overview);
  }

  public function getTransport() {
    $settings = $this->getSettings();

    $options = new \ezcMailImapTransportOptions();
    $options->ssl = true;

    $transport = new \ezcMailImapTransport( $settings->host, null, $options );
    $transport->authenticate( $settings->user, $settings->pass );
    $transport->selectMailbox( 'INBOX' );

    return $transport;
  }

  # Note: this is all rather inefficient, a more realistic app would do some aggressive caching because mails don't change.
  public function fetchByUid($uid) {
    #TODO: handle invalid uids
    $collector = $this->createPartsCollector( $uid );

    $mail = $this->collectParts($uid, $collector, 'processPart');
    // display the html text with the content IDs replaced with references to the
    // file in the webroot.
    $htmlCleaned = \ezcMailTools::replaceContentIdRefs( $collector->htmlText, $collector->cids );

    #TODO: obviously rendering html mails as-is is horribly insecure and we should
    # do much more processing to make it safe (like stripping script tags)

    return array('raw' => $mail, 'htmlCleaned' => $htmlCleaned, 'text' => $collector->text, 'headers' => $mail->headers);
  }

  public function fetchMailPart($uid, $partId) {
    $collector = $this->createPartsCollector( $uid );
    $mail = $this->collectParts($uid, $collector, 'processPart');
    return array('rawpart' => $collector->parts[$partId], 'filePath' => $this->blobStore->getPath( $uid . '.' . $partId));
  }

  public function createPartsCollector( $uid ) {
    $collector = new PartsCollector( $this->blobStore );
    $collector->uid = $uid;
    $collector->webDir = '/mail/' . $uid . '/parts';
    return $collector;
  }

  /**
   * Processes mail with given id, passing text and attachment parts to $collector->$fn;
   */
  public function collectParts($uid, $collector, $fn) {
    $transport = $this->getTransport();
    $uids = $transport->listUniqueIdentifiers();

    $lookup = array_flip($uids);
    $fetched = $transport->fetchByMessageNr($lookup[$uid]);
    $parser = new \ezcMailParser();
    $parsed = $parser->parseMail( $fetched );
    $mail = $parsed[0];

    $context = new \ezcMailPartWalkContext( array( $collector, $fn) );
    $context->filter = array( 'ezcMailFile', 'ezcMailText' );

    // use walkParts() to iterate over all parts in the first parsed e-mail
    // message.
    $mail->walkParts( $context, $mail );

    return $mail;
  }

  function boot(Application $app) {
  }
};

class MailSettings {

    public $host = 'imap.example.org';
    public $user = 'username';
    public $pass = 'password';

    public function __construct($host, $user, $pass) {
        $this->host=$host;
        $this->user=$user;
        $this->pass=$pass;
    }
}

/**
 * Origin: http://ezcomponents.org/docs/tutorials/Mail#displaying-html-mail-with-inline-images
 *
 * This class is used in the callback for each part that is walked below. It
 * takes care of copying the files and registering both file parts and the HTML
 * text.
 */
class PartsCollector
{

    public $cids;
    public $htmlText;
    public $blobStore;
    public $parts;
    public $uid;
    public $webDir;

    function __construct( $blobStore ) {
        $this->blobStore = $blobStore;
    }

    function processPart( $context, $mailPart ) {

        // if it's a file, we copy the attachment to a new location, and
        // register its CID with the class - attaching it to the location in
        // which the *web server* can find the file.
        if ( $mailPart instanceof \ezcMailFile )
        {
            // setup ID array with right location for each part
            $this->cids[$mailPart->contentId] = $this->webDir . '/' . $mailPart->contentId;
            $this->parts[$mailPart->contentId] = $mailPart;
            $this->blobStore->store( $this->uid . '.' . $mailPart->contentId, $mailPart );
        }


        // if we find a text part and if the sub-type is HTML (no plain text)
        // we store that in the classes' htmlText property.
        if ( $mailPart instanceof \ezcMailText )
        {
            if ( $mailPart->subType == 'html' )
            {
                $this->htmlText = $mailPart->text;
            }
            if ( $mailPart->subType == 'plain' )
            {
                $this->text = $mailPart->text;
            }
        }
    }
}

/**
 * Extends this class to keep track of parts, clean up old parts
 */
class BlobStore {

    public function __construct($storageDir) {
        $this->storageDir = $storageDir;
    }

    public function store($id, $mailPart) {
        # TODO: We only need to copy this part if we don't already have it
        copy($mailPart->fileName, $this->getPath($id));
    }

    public function getPath($id) {
        # TODO: We only need to copy this part if we don't already have it
        return $this->storageDir . '/part' . $id;
    }
}

?>
