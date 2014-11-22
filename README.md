### TracRPC

The purpose of this class is to interact with the Trac API from a remote
location by remote procedure calls.

Trac is a project management and bug/issue tracking system.
http://trac.edgewall.org/

Trac by itself does not provide an API. You must install the XmlRpcPlugin. Trac
then provides anonymous and authenticated access to the API via two protocols
XML-RPC and JSON-RPC.
http://trac-hacks.org/wiki/XmlRpcPlugin/

### Requirements

* PHP 5.3.0 or higher
* The PHP Extensions "JSON" and "cURL" are required.
* Trac with XmlRpcPlugin

### Features

* Make Requests to the TRAC API

### Installation

a) Download the ZIP from Github, then extract the library file and include it.

b) Installation via Composer

To add PHPTracRPC as a local, per-project dependency to your project, simply add a dependency on `jakoch/PHP-TracRPC` to your project's `composer.json` file.

    {
        "require": {
            "jakoch/PHPTracRPC": "dev-master"
        }
    }

### Usage

```
include 'TracRPC.php';

$credentials = array('username' => 'username', 'password' => 'password');

$trac = new TracRPC('http://trac.example.com/login/jsonrpc', $credentials);

// Single Call Example
$result = $trac->getTicket('32');

if ($result === false) {
    die('ERROR: '.$trac->getErrorMessage());
} else {
    var_dump($result);
}

// Multi Call Example
$trac->setMultiCall(true);

$ticket = $trac->getTicket('32');
$attachments = $trac->getTicketAttachments('list', '32');

$trac->doRequest();

$ticket = $trac->getResponse($ticket);
$attachments = $trac->getResonse($attachments);
var_dump($ticket, $attachments);
```
