<?php

namespace TracRPCTest;

use TracRPC\TracRPC;

class TracRPCTest extends \PHPUnit_Framework_TestCase
{
    /**
     * We use a public Trac server for testing the requests. No mocks.
     */
    //private $tracURL = 'https://trac-hacks.org/';
    private $tracURL = 'https://josm.openstreetmap.de/';

    /**
     * @var TracRPC\TracRPC (subject under test)
     */
    protected $trac;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        if (false === extension_loaded('curl')) {
            $this->markTestSkipped('This test requires the PHP extension "cURL".');
        }

        // we use the jsonrpc endpoint by default
        $url = $this->tracURL.'jsonrpc';

        $this->trac = new \TracRPC\TracRPC($url);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        unset($this->trac);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_Constructor_RPC_Request_Without_ContentType_throwsException()
    {
        $url = $this->tracURL.'rpc';
        $this->trac = new TracRPC($url);
        $this->assertEmpty($this->trac->content_type);

        $response = $this->trac->getApiVersion();
        $this->expectException('\InvalidArgumentException');
    }

    public function test_Constructor_RPC_Request_With_Set_ContentType_JSON()
    {
        $url = $this->tracURL.'rpc';
        $this->trac = new TracRPC($url);
        $this->trac->setContentType('json');
        $response = $this->trac->getApiVersion();

        $this->assertNotNull($response);
        $this->assertTrue(is_array($response));
    }

    public function test_Constructor_RPC_Request_With_Set_ContentType_XML()
    {
        $url = $this->tracURL.'rpc';
        $this->trac = new TracRPC($url);
        $this->trac->setContentType('xml');
        $response = $this->trac->getApiVersion();
        $this->assertNotNull($response);
        $this->assertTrue(is_array($response));
    }

    public function test_Constructor_JSONRPC_Request()
    {
        $url = $this->tracURL.'jsonrpc';
        $this->trac = new TracRPC($url);

        // implict test of _setContentTypeByURL()
        $this->assertSame('json', $this->trac->content_type);

        $response = $this->trac->getApiVersion();
        $this->assertNotNull($response);
        $this->assertTrue(is_array($response));
    }

    public function test_Constructor_XMLRPC_Request()
    {
        $url = $this->tracURL.'xmlrpc';
        $this->trac = new TracRPC($url);
        $response = $this->trac->getApiVersion();
        $this->assertNotNull($response);
        $this->assertTrue(is_array($response));
    }

    public function test_Constructor_setProperties()
    {
        $url = 'http://a.trac.url/';

        $params = array(
            'username' => 'user',
            'password' => 'password',
            'multiCall' => '1',
            'json_decode' => '1'
        );

        $this->trac = new TracRPC($url, $params);

        $this->assertEquals($url, $this->trac->tracURL);
        $this->assertEquals($params['username'], $this->trac->username);
        $this->assertEquals($params['password'], $this->trac->password);
        $this->assertTrue($this->trac->multiCall);
        $this->assertTrue($this->trac->json_decode);
    }

    public function test_property_setters()
    {
        // setContentType
        $content_type = 'json';
        $this->trac->setContentType($content_type);
        $this->assertSame($content_type, $this->trac->content_type);

        // setJsonDecode
        $json_decode = true;
        $this->trac->setJsonDecode($json_decode);
        $this->assertTrue($this->trac->json_decode);

        // setMultiCall
        $multiCall = true;
        $this->trac->setMultiCall($multiCall);
        $this->assertTrue($this->trac->multiCall);

        // setPassword
        $password = 'password';
        $this->trac->setPassword($password);
        $this->assertSame($password, $this->trac->password);

        // setTracURL
        $url = 'http://your.trac.url/';
        $this->trac->setTracURL($url);
        $this->assertSame($url, $this->trac->tracURL);

        // setUser
        $username = 'john';
        $this->trac->setUser($username);
        $this->assertSame($username, $this->trac->username);
    }

    public function test_property_jsonDecode_false()
    {
        $url = $this->tracURL.'jsonrpc';
        $this->trac = new TracRPC($url);
        $this->trac->setJsonDecode(false);
        $response = $this->trac->getApiVersion();
        $this->assertSame('{"id": 1, "result": [1, 1, 5], "error": null}', $response);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage You are trying an authenticated access without providing username and password.
     */
    public function test_Constructor_TestLoginWithoutCredentials()
    {
        $this->trac = new TracRPC($this->tracURL . 'login/jsonrpc');
        $response = $this->trac->getWikiPage('TracGuide');

        $this->assertTrue(false); // this should fail with invalid login
    }

    public function test_Constructor_doRequest()
    {
        $response = $this->trac->getWikiPage('TracGuide');
        $this->assertNotNull($response);
        $this->assertTrue(is_string($response));
    }

    public function test_MultiCall_Request()
    {
        $this->trac->setMultiCall(true);

        $ticket      = $this->trac->getTicket('10000');
        $attachments = $this->trac->getTicketAttachments('list', '10000');

        $this->trac->doRequest();

        $ticket      = $this->trac->getResponse($ticket);
        $attachments = $this->trac->getResponse($attachments);

        $this->assertNotNull($ticket);
        $this->assertNotNull($attachments);
    }

    public function test_getRecentChangedWikiPages()
    {
        // no datetime set
        $response = $this->trac->getRecentChangedWikiPages();
        $this->assertNotNull($response);

        // datetime set
        $time = time();
        $response = $this->trac->getRecentChangedWikiPages($time);
        $this->assertNotNull($response);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getWikiPage_emptyName_throwsException()
    {
        // throws exception, when name is empty
        $name = '';
        $response = $this->trac->getWikiPage($name);
        $this->expectException('\InvalidArgumentException');
    }

    public function test_getWikiPage()
    {
        // raw true = HTML
        $name = 'TracGuide'; $version = '3'; $raw = true;
        $response = $this->trac->getWikiPage($name, $version, $raw);
        $this->assertNotNull($response);
        $this->assertContains('= The Trac User and Administration Guide =', $response);

        // raw false = non HTML
        $name = 'TracGuide'; $version = '3'; $raw = false;
        $response = $this->trac->getWikiPage($name, $version, $raw);
        $this->assertNotNull($response);
        $this->assertContains('<html><body><h1 id="TheTracUserandAdministrationGuide">', $response);

        // wiki.getPage
        $name = 'TracGuide'; $version = '0'; $raw = true;
        $response = $this->trac->getWikiPage($name, $version, $raw);
        $this->assertNotNull($response);
        $this->assertContains('= The Trac User and Administration Guide =', $response);

        // wiki.getPageHTML
        $name = 'TracGuide'; $version = '0'; $raw = false;
        $response = $this->trac->getWikiPage($name, $version, $raw);
        $this->assertNotNull($response);
        $this->assertContains('<html><body><h1 id="TheTracUserandAdministrationGuide">', $response);

        // wiki.getPageVersion
        $name = 'TracGuide'; $version = '2'; $raw = true;
        $response = $this->trac->getWikiPage($name, $version, $raw);
        $this->assertNotNull($response);
        $this->assertContains('= The Trac User and Administration Guide =', $response);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getWikiPageInfo_emptyName_throwsException()
    {
        // throws exception, when name is empty
        $name = '';
        $response = $this->trac->getWikiPageInfo($name);
        $this->expectException('\InvalidArgumentException');
    }

    public function test_getWikiPageInfo()
    {
        // wiki.getPageInfo
        $name = 'TracGuide'; $version = 0;
        $response = $this->trac->getWikiPageInfo($name, $version);
        $this->assertNotNull($response);

        // wiki.getPageInfoVersion
        $name = 'TracGuide'; $version = 1;
        $response = $this->trac->getWikiPageInfo($name, $version);
        $this->assertNotNull($response);
    }

    public function test_GetTicketMilestone_GetALL()
    {
        $response = $this->trac->getTicketMilestone();
        $this->assertNotNull($response);
        $this->assertTrue(is_array($response));
    }

    public function test_GetTicketMilestone_Get()
    {
        $this->markTestIncomplete('This test requires a login.');

        $response = $this->trac->getTicketMilestone('get', 'Release');
        $this->assertNotNull($response);
        $this->assertTrue(is_object($response));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_GetTicketMilestone_Get_emptyName_throwsException()
    {
        $name = '';
        $response = $this->trac->getTicketMilestone('get', $name);
        $this->expectException('\InvalidArgumentException');
    }

    public function test_GetTicketMilestone_Get_GetDatetime()
    {
        $response = $this->trac->getTicketMilestone('get', '16.02');

        $this->assertNotNull($response);
        $this->assertTrue(is_object($response));
        $responseArray = self::objectToArray($response);
        // datetime contains a string like "012-02-17T17:00:00"
        $this->assertContains('T', $responseArray['due']['1']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getTicket_emptyID_throwsException()
    {
        $id = '';
        $response = $this->trac->getTicket($id);
        $this->expectException('\InvalidArgumentException');
    }

    public function test_getTicket()
    {
        $id = '10000';
        $response = $this->trac->getTicket($id);

        $this->assertNotNull($response);
        $this->assertEquals('closed', $response['3']['status']);
        $this->assertEquals('remove access=designated and access=official from presets', $response['3']['summary']);
    }

    public function test_getTicketSeverity($action = 'get_all', $name = '', $attr = '')
    {
        $action = 'get_all'; $name = ''; $attr = '';
        $response = $this->trac->getTicketSeverity($action, $name, $attr);
        $this->assertNotNull($response);
    }

    public function test_getTicketFields()
    {
        $response = $this->trac->getTicketFields();
        $this->assertNotNull($response);
        $this->assertEquals('Priority', $response['6']['label']);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getTicketChangelog_empty_name_throwsException()
    {
        $id = ''; $when = 0;
        $response = $this->trac->getTicketChangelog($id, $when);
        $this->expectException('\InvalidArgumentException');
    }

    public function test_getTicketChangelog()
    {
        $id = '10000'; $when = 0;
        $response = $this->trac->getTicketChangelog($id, $when);
        $this->assertNotNull($response);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getTicketActions_empty_name_throwsException()
    {
        $id = '';
        $response = $this->trac->getTicketActions($id);
        $this->expectException('\InvalidArgumentException');
    }

    public function test_getTicketActions()
    {
        $id = '10000';
        $response = $this->trac->getTicketActions($id);
        $this->assertNotNull($response);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getWikiAttachments_empty_name_throwsException()
    {
        $action = 'list'; $name = ''; $file = 'avatar.gif';
        $response = $this->trac->getWikiAttachments($action, $name, $file);
        $this->expectException('\InvalidArgumentException');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getWikiAttachments_Get_empty_file_throwsException()
    {
        $action = 'get'; $name = 'abc'; $file = '';
        $response = $this->trac->getWikiAttachments($action, $name, $file);
        $this->expectException('\InvalidArgumentException');
    }

    public function test_getWikiAttachments()
    {
        $action = 'list'; $name = 'WikiStart'; $file = 'avatar.gif';
        $response = $this->trac->getWikiAttachments($action, $name, $file);
        $this->assertNotNull($response);
    }

    // ##### Ticket #####

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getTicketAttachments_empty_id_throwsException()
    {
        $action = 'list'; $id = ''; $file = ''; $desc = ''; $replace = true;
        $response = $this->trac->getTicketAttachments($action, $id, $file, $desc, $replace);
        $this->expectException('\InvalidArgumentException');
    }

    public function test_getTicketAttachments()
    {
        $action = 'list'; $id = '10000'; $file = ''; $desc = ''; $replace = true;
        $response = $this->trac->getTicketAttachments($action, $id, $file, $desc, $replace);
        $this->assertNotNull($response);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getTicketAttachments_Get_empty_file_throwsException()
    {
        $action = 'get'; $id = '10000'; $file = ''; $desc = ''; $replace = true;
        $response = $this->trac->getTicketAttachments($action, $id, $file, $desc, $replace);
        $this->expectException('\InvalidArgumentException');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getWikiUpdate_empty_name_throwsException()
    {
        $action = 'create'; $name = ''; $page = ''; $data = array();
        $response = $this->trac->getWikiUpdate($action, $name, $page, $data);
        $this->expectException('\InvalidArgumentException');
    }

    public function test_getWikiUpdate()
    {
        $this->markTestIncomplete('This test requires a login.');

        $action = 'create'; $name = 'TracGuide'; $pageContent = ''; $data = array();
        $response = $this->trac->getWikiUpdate($action, $name, $pageContent, $data);

        $this->assertNotNull($response);
    }

    public function test_getTicketSearch()
    {
        // ticket.query
        $response = $this->trac->getTicketSearch("status=new");
        $this->assertNotNull($response);
    }

    public function test_getRecentChangedTickets($date = 0)
    {
        // ticket.getRecentChanges - date false
        $response = $this->trac->getRecentChangedTickets();
        $this->assertNotNull($response);

        // ticket.getRecentChanges - date numeric
        $response = $this->trac->getRecentChangedTickets(time());
        $this->assertNotNull($response);
    }

    public function test_getApiVersion()
    {
        $response =  $this->trac->getApiVersion();

        $this->assertNotNull($response);
        $this->assertTrue(is_array($response));
        $this->assertTrue(count($response) === 3);
    }

    public function test_getWikiPages()
    {
        $response = $this->trac->getWikiPages(); // getAllPages
        $this->assertNotNull($response);
        $this->assertNotEquals(0, count($response));
        $this->assertSame('Bg:VersionHistory', $response[0]);
    }

    public function test_getWikiTextToHTML()
    {
        $this->markTestIncomplete('This test requires a login.');

        $text = '= test_header =';
        $response = $this->trac->getWikiTextToHTML($text);
        $this->assertEquals("<h1 id=\"test_header\">test_header</h1>", $response);
    }

    public function test_getSearchFilters()
    {
        $response = $this->trac->getSearchFilters();
        $this->assertNotNull($response);
        $this->assertNotEquals(0, is_array($response));
        $this->assertSame('Tickets', $response[0][1]);
        $this->assertSame('Changesets', $response[1][1]);
    }

    public function test_getTicketVersion()
    {
        $action = 'get_all'; $name = ''; $attr = array();
        $response = $this->trac->getTicketVersion($action, $name, $attr);
        $this->assertNotNull($response);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function test_getTicketVersion_empty_name_throwsException()
    {
        $action = 'get'; $name = ''; $attr = array();
        $response = $this->trac->getTicketVersion($action, $name, $attr);
        $this->expectException('\InvalidArgumentException');
    }

    public function test_getTicketStatus()
    {
        $response = $this->trac->getTicketStatus();
        $this->assertNull($response); // deprecated?
    }

    public function test_getSearch()
    {
        $this->markTestSkipped('Skipped, because its slow and runs into timeouts.');

        // without filter
        $query = 'screenshot'; $filter = '';
        $response = $this->trac->getSearch($query, $filter);
        $this->assertNotNull($response);
        $this->assertTrue(is_array($response));

        // with filter
        $query = 'screenshot'; $filter = 'tickets';
        $response = $this->trac->getSearch($query, $filter);
        $this->assertNotNull($response);
        $this->assertTrue(is_array($response));
    }

    /**
     * Converts an object into an array.
     * Handles __jsonclass__ subobject properties, too!
     *
     * @todo sub_array transformation:
     * (jsonclass ( [0] = key [1] = value ))
     * into
     * ( key => value )
     *
     * @param  object $d
     * @return array
     */
    private static function objectToArray($d = null)
    {
        // turn object properties into array
        if (is_object($d)) {
            $d = get_object_vars($d);
        }

        // iterate over all former "properties",
        // which might be objects and convert them
        foreach ($d as $key => $value) {
            if ($key == '__jsonclass__') {
                $d = $value; #@todo sub-array transformation

                continue;
            }

            if (is_object($value)) {
                $d[$key] = self::objectToArray($value);
            }
        }

        return $d;
    }
}
