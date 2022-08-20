<?php

/*
 * Copy DB
 * 
 * @author Anuj Jaha
 * @email er.anujjaha@gmail.com
 * @gitHub https://github.com/anujjaha/clonedb
 */
class CopyDb 
{
  protected $sourceHost     = 'localhost';
  protected $sourceDb       = 'test1';
  protected $sourceUsername = 'root';
  protected $sourcePassword = 'root@123';

  protected $destiHost     = 'localhost';
  protected $destiDb       = 'test2';
  protected $destiUsername = 'root';
  protected $destiPassword = 'root@123';

  /**
   * Start - Entry point
   *
   */
  public function start()
  {
    // Primary Connection
    $primaryConnection    = $this->getPrimaryConnection();

    // Secondary Connection
    $secondaryConnection  = $this->getSecondaryConnection();

    // Start to Copy Tables
    $this->copyTables($primaryConnection);
  }

  /**
   * Get Primary Connection
   *
   */
  protected function getPrimaryConnection()
  {
    try 
    {
      return $this->connectNewdb($this->sourceHost, $this->sourceDb, $this->sourceUsername, 
        $this->sourcePassword);
    }
    catch(PDOException $e)
    {
      $this->pr($e->getMessage());
    }
  }

  /**
   * Get Secondary Connection
   *
   */
  protected function getSecondaryConnection()
  {
    // Secondary Connection
    try 
    {
      $secondaryConnection = $this->connectNewdb($this->destiHost, $this->destiDb, $this->destiUsername, $this->destiPassword);

      // remove existing tables if found
      $this->deleteAllTables($secondaryConnection);

      return $secondaryConnection;
    }
    catch(PDOException $e)
    {
      return $this->createNewDb($this->destiHost, $this->destiDb, 
        $this->destiUsername, $this->destiPassword);
    }
  }

  /**
   * Print - Debug
   *
   * @param any $input
   * @param bool $die
   */
  protected function pr($input, $die = true)
  {
    echo "<pre>";
    print_r($input);

    if($die)
    {
      die;
    }

    echo "</pre>";
  }

  /**
   * Copy Tables
   *
   * @param object $connection
   * @param bool $log
   * @return bool
   */
  public function copyTables($connection, $log = true)
  {
    $tables = $connection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $sr     = 0;
    if(isset($tables) && !empty($tables))
    {
      $sourceDbName       = $this->sourceDb;
      $destinationDbName  = $this->destiDb;

      // Set Destination DB
      $connection->exec("USE {$destinationDbName}");
      
      foreach ($tables as $tableName) 
      {
        $createCommand = $connection->query("SHOW CREATE TABLE `{$sourceDbName}`.`{$tableName}`")->fetchColumn(1);

        $carefulCreateCommand = str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $createCommand);

        $connection->exec($carefulCreateCommand);
        $connection->exec("INSERT INTO `{$destinationDbName}`.`{$tableName}` SELECT * FROM `{$sourceDbName}`.`{$tableName}`");

        if($log) 
        {
          echo "Data for table `{$tableName}` copied successfully.<br />" . PHP_EOL;
        }

        $sr++;
      }
    }

    return $sr;
  }

  /**
   * Delete DB
   *
   * @param object $connection
   * @param string $database
   * @return bool
   */
  protected function deleteDb($connection, $database)
  {
    return $connection->exec("DROP DATABASE IF EXISTS " . $database);
  }

  /**
   * Delete All Tables
   *
   * @param object $connection
   * @return bool
   */
  protected function deleteAllTables($connection)
  {
    $tables = $connection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    if(isset($tables) && !empty($tables))
    {
      foreach ($tables as $tableName) {
        $connection->exec('drop table '.$tableName);
      }
    }

    return true;
  }

  /**
   * Is Db Exists
   *
   * @param object $connection
   * @param string $database
   * @return bool
   */
  protected function isDbExists($connection, $database)
  {
    try 
    {
      return $connection->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    } 
    catch (PDOException $th)
    {
      return new PDOException();
    } 
  }

  /**
   * Connect NEW DB
   *
   * @param string $host
   * @param string $db
   * @param string $username
   * @param string $pass
   * @return object|exception
   */
  protected function connectNewdb($host = 'localhost', $db = null, $username = '', $pass = '')
  {
    try {
      return new PDO("mysql:host=".$host.";dbname=".$db , $username, $pass);
    }
    catch(PDOException $th)
    {
      throw new PDOException();
    }
  }

  /**
   * Create NEW DB
   *
   * @param string $host
   * @param string $db
   * @param string $username
   * @param string $pass
   * @return bool
   */
  protected function createNewDb($host = 'localhost', $db = null, $username = '', $pass = '')
  {
    if(isset($db) && !empty($db))
    {
      $newDb = new PDO("mysql:host=$host", $username, $pass);
      return $newDb->exec("CREATE DATABASE $db");
    }

    return false;
  }
}

$object = new CopyDb();

$object->start();