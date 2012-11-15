<?php
namespace TYPO3\CouchDB\Interfaces;

interface CouchDBInterface
{
    // Basic methods
    public function selectDatabase($database);
    public function getAllDatabases();
    public function findAll();

    // CRUD methods
    public function insert($data);
    public function update($id, $data);
    public function delete($id);

}
