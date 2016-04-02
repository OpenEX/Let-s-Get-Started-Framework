<?php 
namespace LetsGetStarted\Framework;

class Controller {

    protected $di;
    
    function __construct($di) {

        $this->di=$di;
        
        if (method_exists($this, 'onConstruct')) {
            $this->onConstruct();
        }
        
        if (method_exists($this, 'initialize')) {
            $this->initialize();
        };
    
    }
    
}

class Model {
    
    public static $staticdi;
    public static $staticdb;
    public static $table_name;
    public $di=null;
    public $db=null;
    
    
    public static function getTableName($calledClassName) {

        if (method_exists($calledClassName, 'getCustomTableName')) {
             $calledClassName::$table_name=$calledClassName::getCustomTableName();     
        } else {
             $classParts=explode('\\', $calledClassName);
             $calledClassName::$table_name=strtolower($classParts[count($classParts)-1]);
        }
        
        
    }
    

    public function serialize(&$results) {

        $consecutivedi=array();
        $consecutivedb=array();
        foreach ($results as $result) {
            $consecutivedi[]=$result->di;
            unset($result->di);
            $consecutivedb[]=$result->db;
            unset($result->db);
        }    
        
        $serializeddb=serialize($results);
        
        $i=0;
        foreach ($results as $result) {
            $result->di=$consecutivedi[$i];
            $result->db=$consecutivedb[$i];
            $i++;
        }    
        
        return $serializeddb;
        
    }


   public static function convertSQLQueryIntoArrayQuery($query, $type='where', $modelBasedQuery=true) {
       $queryArray=array();
       
       
       return $queryArray;
   } 
    
   public static function dbqoute($string) {
        return str_replace('\'', '\'\'', $string);
    }
    
   public static function dbdoubleqoute($string) {
        return str_replace('"', '""', $string);
    }
    
   public static function queryModelObjectsCollection($query, $calledClassName, $tableDumpCacheKeyName) {
       static $invokationcounter;
       $invokationcounter++;
       echo '<pre>';
echo $query;echo '<br />';
// zrób jakieś jedno proste porównanie na stringach, że id=10, bo pregsplit pochłania za dużo.

lgs_code_exec_time('sqlmatch'.$invokationcounter); 
           $matches=preg_split('/[\s\t\n]*([()])?(?:([\[\]a-zA-Z0-9_.-]+)[\s\t\n]*'.
           '(LIKE|=|>|<|<>|>=|<=)'.
           '[\s\t\n]*(\d+|(?:\')(?:[^\']+|(?:\'\')+)*(?:\')))[\s\t\n]*([()])?/si', 
           $query, -1, PREG_SPLIT_DELIM_CAPTURE);
lgs_code_exec_time_finish('sqlmatch'.$invokationcounter); 


lgs_code_exec_time('echo'.$invokationcounter); 
echo 'ala ma kota';
lgs_code_exec_time_finish('echo'.$invokationcounter); 


       
       print_r($matches);
       
       $wherePartOfQueryArray=self::convertSQLQueryIntoArrayQuery($query);
       
       //$calledClassName::$cached_results[$tableDumpCacheKeyName]
   }    
    
    public static function find($query=null, $param=null, $is_find_first=false, $return_array=false, $no_force_doing_all_queries_on_a_whole_loaded_table_invokation=false) {

        global $opcache_installed;
        
        $calledClassName=get_called_class();
        if (empty($calledClassName::$table_name)) {
            self::getTableName($calledClassName);
        }    
            
        // zrób wykrywanie, że jest dostępna lista wszystkich objektów (teraz, nie z opcache)
        // jeśli tak, to spróbój odpytać te objekty parsując fragment where
        // tyle narazie, parser będzie można potem rozwijać i używać do niesqlowych baz danych
        
        
        
        $selectPartOfFinalQuery='SELECT * FROM '.$calledClassName::$table_name;
        
        $tableDumpCacheKeyName='objects-table-dump'.$selectPartOfFinalQuery;
        
        if ($query===null&&!empty($calledClassName::$cached_results[$tableDumpCacheKeyName])) {
            echo '<br />even faster';
            return $calledClassName::$cached_results[$tableDumpCacheKeyName];
        } if ($no_force_doing_all_queries_on_a_whole_loaded_table_invokation===false&&$calledClassName::$force_doing_all_queries_on_a_whole_loaded_table) {
            if (empty($calledClassName::$cached_results[$tableDumpCacheKeyName])) {
                $calledClassName::find(null, null, false, false, true);
                echo '<br />loading table from opcache to force queries on the model property';
            }
            echo '<br />SQL WHERE CLAUSE PARSER CAN be used to request all cached objects from memory';
            if ($query===null) return $calledClassName::$cached_results[$tableDumpCacheKeyName]; else {
                 echo '<br />parse query to query collection of objects not database';
                 return self::queryModelObjectsCollection($query, $calledClassName, $tableDumpCacheKeyName);
            }
        }        

        $limitPartOfFinalQuery=($is_find_first?' LIMIT 1':'');        
        $finalQuery=$selectPartOfFinalQuery.($query?' WHERE '.$query:'').$limitPartOfFinalQuery;
        $finalCacheKeyName=($is_find_first?'one-object-':(empty($query)?'objects-table-dump':'objects-collection-')).$finalQuery;
        $cachePHPFilePath=self::$staticdi->config['application']['cacheDir'].base64_encode($finalCacheKeyName).'.php';
        
        if (!empty($calledClassName::$cache_results)&&!empty($calledClassName::$cached_results[$finalCacheKeyName])) {
            echo '<br />findFirst cloned object';
            $results=&$calledClassName::$cached_results[$finalCacheKeyName];
        } else if (!empty($calledClassName::$memory_cache)&&!empty($calledClassName::$cache_results)&&empty($calledClassName::$cached_results[$finalCacheKeyName])&&opcache_is_script_cached ( $cachePHPFilePath  )) {
            echo '<br />findFirst take from opcache';
            $results=include($cachePHPFilePath);
            if (is_array($results)) {
                foreach ($results as $result) {
                    $result->di=self::$staticdi;
                    $result->db=self::$staticdb;
                }
            } else {
                $results->di=self::$staticdi;
                $results->db=self::$staticdb;
            }
            $calledClassName::$cached_results[$finalCacheKeyName]=&$results;
            return $results;
        } else {            
            echo '<br />findFirst take from db';
            if (!Model::$staticdb->connected) Model::$staticdb->getConnection();
            $stmt=Model::$staticdb->connection->prepare($finalQuery);
            if ($is_find_first) {
                $stmt->execute();
                $results=$stmt->fetchObject($calledClassName);
            } else {
                $stmt->setFetchMode(\PDO::FETCH_CLASS, $calledClassName);
                $stmt->execute();
                $results = $stmt->fetchAll();
            }

        }    
            
        if (!empty($calledClassName::$cache_results)) {

            if ($calledClassName::$memory_cache&&$opcache_installed) {
                
                // reset in other logically better place so not to do it twice if ($nocache) opcache_reset();  

                include($cachePHPFilePath);
                
                if (!opcache_is_script_cached ( $cachePHPFilePath  )) { 
                    file_put_contents($cachePHPFilePath, '<'.'?php return unserialize(htmlspecialchars_decode(\''.htmlspecialchars(self::serialize($results), ENT_QUOTES).'\',ENT_QUOTES));');
                    opcache_compile_file ( $cachePHPFilePath );
                }
                
            }   
    
            
            
            //echo '<br />findFirst check if i can cache and cache result for the query';
            $calledClassName::$cached_results[$finalCacheKeyName]=&$results;
        }    
        
        return $results;

 
    }

    public static function findFirst($query=null, $param=null) {

        return self::find($query, $param, true);
        
    }
    
    
    function __construct($di=null, $db=null) {
        
        if ($db!==null) {
            $this->db=$db;
        } 
        
        if ($di!==null) {
            $this->di=$di;
            if (empty($this->db)) $this->db=$this->di->db;
        } else {
            $this->di=self::$staticdi;
            if (empty($this->db)) $this->db=self::$staticdb;
        }
    }
    
}

class View {
    
    protected $di;
    
    function __construct($di) {
        
        $this->vars=array();
        $this->defaults=array();
        $this->di=$di;
    
    }

}


class Flow {
    
    protected $di;
    private $is_post;
    
    function __construct($di) {
        
        $this->di=$di;
    
    }

}


class Db {
    
    protected $di;
    public $connected;    
    public $connection;
    
    public function getConnection() {
        if ($connected) return $connection;
        switch ($this->di->config['database']['adapter']) {
            case 'Sqlite': 
                        try {
                            $this->connection = new \PDO('sqlite:/home/slawek/Desktop/robota/letsgetstarted/data/3333.sqlite');
                            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                            $connected=true;
                        } catch(\PDOException $e) {
                            echo 'Connection failed: ' . $e->getMessage();
                            return false;
                        }                
                        break; 
            case 'Mysql': 
                        try {
                            $this->connection = new \PDO('mysql:host='.$di->config['database']['host'].';dbname='.$di->config['database']['dbname'], $di->config['database']['username'], $di->config['database']['password']);
                            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                            $connected=true;
                        } catch(\PDOException $e) {
                            echo 'Connection failed: ' . $e->getMessage();
                            return false;
                        }                
                        break; 
            case 'Postgresql': 
                        try {
                            $this->connection = new \PDO('postgresql:host='.$di->config['database']['host'].';dbname='.$di->config['database']['dbname'], $di->config['database']['username'], $di->config['database']['password']);
                            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                            $connected=true;
                        } catch(\PDOException $e) {
                            echo 'Connection failed: ' . $e->getMessage();
                            return false;
                        }                
                        break; 
        }    
        
        return $connection;
        
    }


    function __construct($di) {
        
        $this->di=$di;
        $this->is_connected=false;    
        
    }
    
}

class Application {
    
    protected $di;
    
    function __construct($di) {

        
        global $lgs_application_dir, $opcache_installed, $nocache;
        
        $this->di=$di;
        
        $controllerNamespace='';
        if (!empty($di->routing['module'])) {
            $controllerNamespace.='\\Modules\\'.ucfirst(strtolower($di->routing['module']));
            if (!empty($di->routing['submodule'])) $controllerNamespace.='\\Submodules\\'.ucfirst(strtolower($di->routing['submodule']));
            $this->viewPath=$lgs_application_dir.'app'.$controllerNamespace.'/views/';
        } else {
            $this->viewPath=$lgs_application_dir.'app/views/';
        }
        
        $controllerNamespace.='\\Controllers';
        
        $controllerNamespaceName=ucfirst(strtolower($di->routing['controller']));
        $controllerNamespace.='\\'.$controllerNamespaceName;    
        
        $controllerNamespaceClassName=$controllerNamespace.'Controller';    

    
        if ($opcache_installed) {
            
            $layoutId=1; // depends on a file main layout/template now done in a dummy way
    
            $macrosPath=__DIR__.'/../app//views/partials/macros/global_scope_profiles/profile-'.$layoutId.'.php';    
            if ($nocache) {
                require_once __DIR__.'/../app//views/partials/macros/global_scope_profiles/profile-'.$layoutId.'.php';
            } if (!opcache_is_script_cached ( $macrosPath  )) { 
                opcache_compile_file ( $macrosPath ); 
            }
            
        }   
        
        if (!isset($di->view)) {
            $di->view=new View($di);
        }
        if (!isset($di->flow)) {
            $di->flow=new Flow($di);
        }
        
        if (!isset($di->db)) {
            $di->db=new Db($di);
        }

        Model::$staticdi=$di;
        Model::$staticdb=$di->db;
        
        $controller=new $controllerNamespaceClassName($di);
        $actionFunctionName=$di->routing['action'].'Action';
        
        $this->templateRelativePath=$controllerNamespaceName.'/'.$di->routing['action'].'.php';
        

        $controller->$actionFunctionName();
        
        $templatePath=$this->viewPath.$this->templateRelativePath;
        if (file_exists($templatePath)) include(str_replace('\\', '/', $templatePath));
        
    }
    
}
