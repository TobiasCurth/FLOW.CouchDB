<?php
namespace TYPO3\CouchDB\Driver;

use TYPO3\CouchDB\Interfaces\CouchDBInterface;

abstract class AbstractDriver implements CouchDBInterface
{
    // connection
    protected $host;
    protected $port;
    protected $database;
    protected $user;
    protected $password;

    // urls
    protected $requestAllDatabases = '/_all_dbs/';
    protected $requestSelectDatabase = '/';
    protected $requestGenerateDocumentId = '/_uuids';
    protected $requestAllDocuments = '/_all_docs?include_docs=true';

    // set standard parameters
    function __construct($host = 'localhost', $port = 5984, $user = '', $password = '')
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
    }

    public function authorize($user, $password) {
        $this->user = $user;
        $this->password = $password;
    }

    // executes a given request
    // returns valid response
    abstract protected function doRequest($type = 'GET', $data = '', $validate = true);


    // converter
    protected function jsonToArray($json) { return json_decode($json); }
    protected function arrayToJson($data) {
        return (!$json_data = json_encode($data)) ? false : $json_data;
    }





}
