<?php

namespace MailApp;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MailServiceProvider implements ServiceProviderInterface {

  public function register(Application $app) {
    $this->app = $app;
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

  public function fetchByUid($uid) {
    $transport = $this->getTransport();
    $uids = $transport->listUniqueIdentifiers();

    $lookup = array_flip($uids);
    $fetched = $transport->fetchByMessageNr($lookup[$uid]);
    $parser = new \ezcMailParser();
    $mail = $parser->parseMail( $fetched );

    #TODO: handle invalid uids
    $collector = new collector();

    $context = new \ezcMailPartWalkContext( array( $collector, 'getHtmlText') );

    #collect text parts only
    $context->filter = array( 'ezcMailText' );

    // use walkParts() to iterate over all parts in the first parsed e-mail
    // message.
    $mail[0]->walkParts( $context, $mail[0] );

    // display the html text with the content IDs replaced with references to the
    // file in the webroot.
    $htmlCleaned = \ezcMailTools::replaceContentIdRefs( $collector->htmlText, $collector->cids );

    return array('mail' => $mail[0], 'htmlCleaned' => $htmlCleaned, 'text' => $collector->text);
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
class collector
{
    function getHtmlText( $context, $mailPart )
    {
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
?>
