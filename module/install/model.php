<?php
/**
 * The model file of install module of ZenTaoASM.
 *
 * @copyright   Copyright 2009-2011 青岛易软天创网络科技有限公司 (QingDao Nature Easy Soft Network Technology Co,LTD www.cnezsoft.com)
 * @license     LGPL (http://www.gnu.org/licenses/lgpl.html)
 * @author      Chunsheng Wang <chunsheng@cnezsoft.com>
 * @package     install
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php
class installModel extends model
{
    /**
     * Get the php version.
     * 
     * @access public
     * @return string
     */
    public function getPhpVersion()
    {
        return PHP_VERSION;
    }

    /**
     * Check php version.
     * 
     * @access public
     * @return bool
     */
    public function checkPHP()
    {
        return $result = version_compare(PHP_VERSION, '5.2.0') >= 0 ? 'ok' : 'fail';
    }

    /**
     * Check pdo extension.
     * 
     * @access public
     * @return bool
     */
    public function checkPDO()
    {
        return $result = extension_loaded('pdo') ? 'ok' : 'fail';
    }

    /**
     * Check pdo_mysql extension.
     * 
     * @access public
     * @return bool
     */
    public function checkPDOMySQL()
    {
        return $result = extension_loaded('pdo_mysql') ? 'ok' : 'fail';
    }

    /**
     * Get the temp root.
     * 
     * @access public
     * @return array
     */
    public function getTmpRoot()
    {
        $result['path']    = $this->app->getTmpRoot();
        $result['exists']  = is_dir($result['path']);
        $result['writable']= is_writable($result['path']);
        return $result;
    }

    /**
     * Check the temp root.
     * 
     * @access public
     * @return bool
     */
    public function checkTmpRoot()
    {
        $tmpRoot = $this->app->getTmpRoot();
        return $result = (is_dir($tmpRoot) and is_writable($tmpRoot)) ? 'ok' : 'fail';
    }

    /**
     * Get the data root.
     * 
     * @access public
     * @return array
     */
    public function getDataRoot()
    {
        $result['path']    = $this->app->getAppRoot() . 'www' . $this->app->getPathFix() . 'data';
        $result['exists']  = is_dir($result['path']);
        $result['writable']= is_writable($result['path']);
        return $result;
    }

    /**
     * Check the data root.
     * 
     * @access public
     * @return bool
     */
    public function checkDataRoot()
    {
        $dataRoot = $this->app->getAppRoot() . 'www' . $this->app->getPathFix() . 'data';
        return $result = (is_dir($dataRoot) and is_writable($dataRoot)) ? 'ok' : 'fail';
    }

    /**
     * Get the ini file info.
     * 
     * @access public
     * @return string
     */
    public function getIniInfo()
    {
        $iniInfo = '';
        ob_start();
        phpinfo(1);
        $lines = explode("\n", strip_tags(ob_get_contents()));
        ob_end_clean();
        foreach($lines as $line) if(strpos($line, 'ini') !== false) $iniInfo .= $line . "\n";
        return $iniInfo;
    }

    /**
     * Get the web root.
     * 
     * @access public
     * @return string
     */
    public function getWebRoot()
    {
        return rtrim(str_replace('\\', '/', pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME)), '/') . '/';
    }

    /**
     * Check the user config.
     * 
     * @access public
     * @return object
     */
    public function checkConfig()
    {
        $return = new stdclass();
        $return->result = 'ok';

        /* Connect db. */
        $this->setDBParam();
        $this->dbh = $this->connectDB();
        if(!is_object($this->dbh))
        {
            $return->result = 'fail';
            $return->error  = $this->lang->install->errorConnectDB . $this->dbh;
            return $return;
        }

        /* Get the mysql version. */
        $version = $this->getMysqlVersion();

        /* If the db don't exists, try create it. */
        if(!$this->dbExists())
        {
            if(!$this->createDB($version))
            {
                $return->result = 'fail';
                $return->error  = $this->lang->install->errorCreateDB;
                return $return;
            }
        }
        elseif($this->post->clearDB == false)
        {
            $return->result = 'fail';
            $return->error  = $this->lang->install->errorDBExists;
            return $return;
        }

        /* Create the tables. */
        if(!$this->createTable($version))
        {
            $return->result = 'fail';
            $return->error  = $this->lang->install->errorCreateTable;
            return $return;
        }

        return $return;
    }

    /**
     * Set the database param.
     * 
     * @access public
     * @return void
     */
    public function setDBParam()
    {
        $this->config->db->host     = $this->post->dbHost;
        $this->config->db->name     = $this->post->dbName;
        $this->config->db->user     = $this->post->dbUser;
        $this->config->db->password = $this->post->dbPassword;
        $this->config->db->port     = $this->post->dbPort;
        $this->config->db->prefix   = $this->post->dbPrefix;

    }

    /**
     * Connect to db.
     * 
     * @access public
     * @return object
     */
    public function connectDB()
    {
        $dsn = "mysql:host={$this->config->db->host}; port={$this->config->db->port}";
        try 
        {
            $dbh = new PDO($dsn, $this->config->db->user, $this->config->db->password);
            $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $dbh->exec("SET NAMES {$this->config->db->encoding}");
            return $dbh;
        }
        catch (PDOException $exception)
        {
             return $exception->getMessage();
        }
    }

    /**
     * Check the database exits or not.
     * 
     * @access public
     * @return bool
     */
    public function dbExists()
    {
        $sql = "SHOW DATABASES like '{$this->config->db->name}'";
        return $this->dbh->query($sql)->fetch();
    }

    /**
     * Get the mysql version.
     * 
     * @access public
     * @return string
     */
    public function getMysqlVersion()
    {
        $sql = "SELECT VERSION() AS version";
        $result = $this->dbh->query($sql)->fetch();
        return substr($result->version, 0, 3);
    }

    /**
     * Create database.
     * 
     * @param  string $version 
     * @access public
     * @return bool
     */
    public function createDB($version)
    {
        $sql = "CREATE DATABASE `{$this->config->db->name}`";
        if($version > 4.1) $sql .= " DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
        return $this->dbh->query($sql);
    }

    /**
     * Create tables.
     * 
     * @param  string $version 
     * @access public
     * @return bool
     */
    public function createTable($version)
    {
        $dbFile = $this->app->getAppRoot() . 'db' . $this->app->getPathFix() . 'zentaoasm.sql';
        $tables = explode(';', file_get_contents($dbFile));
        foreach($tables as $table)
        {
            $table = trim($table);
            if(empty($table)) continue;

            if(strpos($table, 'CREATE') !== false and $version <= 4.1)
            {
                $table = str_replace('DEFAULT CHARSET=utf8', '', $table);
            }
            elseif(strpos($table, 'DROP') !== false and $this->post->clearDB != false)
            {
                $table = str_replace('--', '', $table);
            }
            $table = str_replace('`zt_', $this->config->db->name . '.`zt_', $table);
            $table = str_replace('zt_', $this->config->db->prefix, $table);
            if(!$this->dbh->query($table)) return false;
        }
        return true;
    }

    /**
     * Create a site, grant the priv.
     * 
     * @access public
     * @return void
     */
    public function grantPriv()
    {
        if($this->post->password == '') die(js::error($this->lang->install->errorEmptyPassword));

        /* Set the admin. */
        $admin = new stdclass();
        $admin->id       = 100000;
        $admin->account  = $this->post->account;
        $admin->realname = $this->post->account;
        $admin->company  = $this->post->company;
        $admin->password = md5($this->post->password);
        $admin->role     = 'admin';
        $this->dao->insert(TABLE_USER)->data($admin)->autoCheck()->check('account', 'notempty')->exec();
        $this->dao->insert(TABLE_COMPANY)->set('name')->eq($this->post->company)->exec();
    }

    /**
     * Save the admin user.
     * 
     * @access public
     * @return bool
     */
    public function saveAdminUser()
    {
        $myConfig = $this->app->getConfigRoot() . 'my.php';
        $contents = file_get_contents($myConfig);
        $contents .= $this->createAdminConfig($this->post->account);
        if(is_writable($myConfig)) return file_put_contents($myConfig, $contents);
        return false;
    }

    /**
     * Create the admin config.
     * 
     * @param  string $account 
     * @access public
     * @return string
     */
    public function createAdminConfig($account)
    {
        $account  = ',' . trim($account) . ',';
        $contents = "\n\$config->admin->users = '$account';\n";
        $contents .= "\$config->admin->supers = '$account';";
        return $contents;
    }
}
