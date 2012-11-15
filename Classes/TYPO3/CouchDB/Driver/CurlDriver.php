<?php

namespace TYPO3\CouchDB\Driver;

use TYPO3\CouchDB\Exceptions\CurlExceptions;

class CurlDriver extends AbstractDriver
{
    protected $request;

    function __construct()
    {
        // test curl functionality
        if (!function_exists('curl_init'))
            throw new CurlExceptions('Curl is not installed yet.');

        parent::__construct();
    }

    protected function createRequest()
    {
        if (empty($this->request))
            throw new CurlExceptions('No curl request is set. Nothing to create!');

        $auth = '';

        // with authorization
        if (!empty($this->user))
            $auth = $this->user . ':' . $this->password . '@';

        return 'http://' . $auth . $this->host . ':' . $this->port . $this->request;
    }

    protected function doRequest($type = 'GET', $data = '', $validate = true)
    {
        $curl = curl_init();

        switch ($type) {
            case 'POST':
            case 'PUT':
                // set data content
                if (!empty($data))
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $this->arrayToJson($data));
                break;
            case 'GET':
            case 'DELETE':
                // nothing to do
                break;
            default:
                throw new CurlExceptions('Unsupported http-type: ' . $type);
        }

        // set some curl-needed parameters
        curl_setopt($curl, CURLOPT_URL, $this->createRequest());
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);

        // execute curl request
        $response = curl_exec($curl);
        curl_close($curl);

        // don't validate response
        // in some cases, we need to accept error messages
        // but validateResponse throws exceptions
        if (!$validate) return $response;

        return $this->validateResponse($response);
    }

    protected function validateResponse($response)
    {
        // no connection exception
        if (!$response)
            throw new CurlExceptions('Can not connect to database.');

        // response is valid
        if (is_string($response)) {
            $responseArray = $this->jsonToArray($response);
        } else {
            // small hack for uuids
            $responseArray = $response->uuids;
        }

        // throws exceptions when an error occurred
        if (array_key_exists('error', $responseArray)) {
            var_dump($responseArray);
            if ($responseArray->error)
                throw new CurlExceptions($responseArray->error . ': ' . $responseArray->reason);

        }

        // returns valid responses as an array
        return $responseArray;
    }

    public function selectDatabase($database)
    {
        $this->database = $database . '/';

        // test connection
        $this->request = $this->requestSelectDatabase . $this->database;
        $this->doRequest();
    }

    public function getAllDatabases()
    {
        $this->request = $this->requestAllDatabases;
        return $this->doRequest();
    }

    public function insert($data)
    {
        if (empty($this->database))
            throw new CurlExceptions('You have to select a valid database first.');

        // generates an id from couchdb server
        $id = $this->generateDocumentId();
        $this->request = $this->requestSelectDatabase . $this->database . '/' . $id . '/';
        $response = $this->doRequest('PUT', $data);

        // returns insert id
        // like last_insert_id
        return $response->id;
    }

    public function update($id, $data)
    {
        if (empty($this->database))
            throw new CurlExceptions('You have to select a valid database first.');

        // gets the revision id
        $rev = $this->getLastRevision($id);

        // set the revision id
        $data['_rev'] = $rev;

        $this->request = $this->requestSelectDatabase . $this->database . '/' . $id . '/';
        $response = $this->doRequest('PUT', $data);

        // returns updated id
        return $response->id;
    }

    public function delete($id)
    {
        if (empty($this->database))
            throw new CurlExceptions('You have to select a valid database first.');

        // gets the revision id
        $rev = $this->getLastRevision($id);

        $this->request = $this->requestSelectDatabase . $this->database . '/' . $id . '?rev=' .$rev;
        $this->doRequest('DELETE');

        // successful deleted
        return true;
    }

    public function findAll()
    {
        if (empty($this->database))
            throw new CurlExceptions('You have to select a valid database first.');

        $this->request = $this->requestSelectDatabase . $this->database . $this->requestAllDocuments;
        $response = $this->doRequest();

        // response has some unneeded information
        // therefore returns only rows
        return $response->rows;
    }

    protected function generateDocumentId()
    {
        $this->request = $this->requestGenerateDocumentId;
        $response = $this->doRequest();
        return $this->validateResponse($response)[0];
    }

    protected function getLastRevision($id)
    {
        if (empty($this->database))
            throw new CurlExceptions('You have to select a valid database first.');

        $this->request = $this->requestSelectDatabase . $this->database . '/' . $id . '/';
        $response = $this->doRequest();
        return $response->_rev;
    }
}
