<?php 

use LetsGetStarted\Framework\Application;
use LetsGetStarted\Framework\Model;
use LetsGetStarted\Framework\View;
use LetsGetStarted\Framework\Controller;







session_start();         

$startingTime=microtime(true);

global $lgs_application_dir, $nocache, $cli_server;

$opcache_installed=false;
if (function_exists('opcache_reset')) {
    $opcache_installed=true;
}


$lgs_application_dir=__DIR__.'/';
$nocache=false;

//ini_set('opcache.revalidate_freq', '120');

//echo '<pre>';
//print_r(opcache_get_configuration());
//echo '</pre>';



if (php_sapi_name() == 'cli-server') {
    $cli_server=true; 
} else {
    $cli_server=false; 
}



global $is_app_installed;

/*if  ($_SERVER["SERVER_NAME"]=="83.238.134.85") {
    echo "Access denied (ze względów bezpieczeństwa, odblokowany po wdrożeniu zaległych mechanizmów ochronnych lub na specjalną prośbę)."; die();
} */   
error_reporting(E_ERROR | E_WARNING | E_PARSE);


    
    global $baseUriForTheApp, $basePathForTheApp, $_url_get_var;

    $baseUriForTheApp=substr($_SERVER['REDIRECT_URL'], 0, strrpos($_SERVER['REDIRECT_URL'], '/public'.$_GET['_url']));
    if (strlen($baseUriForTheApp)>0&&strrpos($baseUriForTheApp, "/")!==strlen($baseUriForTheApp)-1) {
        $baseUriForTheApp=str_replace('/public', '', $baseUriForTheApp.'/');
    } else {
        if ($_SERVER['REDIRECT_URL']!='/'&&strrpos($_SERVER['REDIRECT_URL'], '/public')!==0) $baseUriForTheApp=$_SERVER['REDIRECT_URL'];
    }

    $serverPortURLPart="";
    if ($_SERVER['SERVER_PORT']!='80') {
        $serverPortURLPart=':'.$_SERVER['SERVER_PORT'];
    }
    
    $serverURLAddress=((isset($_SERVER['HTTPS'])&&strlen($_SERVER['HTTPS'])>0)?'https://':'http://').$_SERVER['SERVER_NAME'].$serverPortURLPart.'/';


    
    $basePathForTheApp=__DIR__.'/../';
    

    
    $is_installed_file_path=realpath(__DIR__.'/../app/config/isapplicationinstalled.txt');
    $is_app_installed=false;
    if (
            (!is_file($is_installed_file_path)||strtolower(trim(file_get_contents($is_installed_file_path)))!=='true')
            
    ) {
            $instcontfilecontents=file_get_contents(__DIR__.'/../app/controllers/InstallationController.php');
            preg_match_all('/public[\s\t]+function[\s\t]+([\da-zA-Z-]+)Action[\s\t]*\(/sU', $instcontfilecontents, $urlParts);
            
            $paths_not_built_in_server=array();
            $paths_built_in_server=array();
            for($i=0;$i<count($urlParts[1]);$i++) {
                    $paths_not_built_in_server[$i]=$baseUriForTheApp.'/public/installation/'.$urlParts[1][$i];
                    $paths_built_in_server[$i]='/installation/'.$urlParts[1][$i];
            } 
            $paths_not_built_in_server[]=$baseUriForTheApp.'/public/installation/p';
            $paths_built_in_server[]='/installation/p';
            $paths_not_built_in_server[]=$baseUriForTheApp.'/public/installation/p/';
            $paths_built_in_server[]='/installation/p/';
            
            if (
                    (!$cli_server&&$_SERVER['REDIRECT_URL']!=$baseUriForTheApp.'/public/installation'&&!in_array($_SERVER['REDIRECT_URL'], $paths_not_built_in_server))
                  ||($cli_server&&$_SERVER['REQUEST_URI']!='/installation'&&!in_array($_SERVER['REQUEST_URI'], $paths_built_in_server))
            ) header('Location: '.$baseUriForTheApp.'/installation');    
    } else {
        $is_app_installed=true;
    }







$di=new stdClass();
$di->config=include(__DIR__.'/../app/config/config.php');

include __DIR__.'/LetsGetStarted.php';

if ($nocache) {
    opcache_reset();
} else {
    if (!opcache_is_script_cached ( __DIR__.'/LetsGetStarted.php')) { 
        opcache_compile_file (__DIR__.'/LetsGetStarted.php' );
    }
}










   
                  
function lgs_autoloader($class) {
        
    global $nocache, $opcache_installed;
    
    $class=str_replace('\\', '/', $class);
    $classParts=explode('/', $class);

    if ($classParts[0]=='Models') {
        $includepath=__DIR__.'/../app/'.$class.'.php';
    } else if ($classParts[0]=='Controllers') {
        $includepath= __DIR__.'/../app/'.$class.'.php';
    } else {
        $includepath= __DIR__.'/../app/Controllers/'.$class.'.php';
    }

    include($includepath);
    
    if ($opcache_installed) {
        
        // reset in other logically better place so not to do it twice if ($nocache) opcache_reset();  
    
        if (!opcache_is_script_cached ( $includepath  )) {
            echo '<br / >some not cached';
            opcache_compile_file ( $includepath ); 
        } else {
            echo '<br / >some cached';
        }
        
    }   
    
    
}


spl_autoload_register('lgs_autoloader');


$lgs_code_exec_keys=array();
function lgs_code_exec_time($key = 'standard-key') {

    global $lgs_code_exec_keys;

    $lgs_code_exec_keys[$key]=microtime(true);

}

function lgs_code_exec_time_finish($key = 'standard-key') {

    global $lgs_code_exec_keys;
    $lgs_code_exec_keys[$key]=(string) round(
            (microtime(true)-$lgs_code_exec_keys[$key])*1000000
        );

    
}    




if (strpos($_SERVER['SERVER_NAME'], '192.168.')!==0) ob_start();



ob_start();


if (empty($_SERVER['REDIRECT_URL'])) {
    $_SERVER['REDIRECT_URL']=$_SERVER['REQUEST_URI'];
}

$matchingPattern='#'.$baseUriForTheApp.'((administrator|superuser)/)?(i/([0-9]+)/)?(m/([a-zA-Z0-9_-]+)/)?(m/([a-zA-Z0-9_-]+)/)?(l/([a-zA-Z]{2,3}(-[a-zA-Z]{2,3})?)/?)?(([a-zA-Z0-9_-])+)(/([a-zA-Z0-9_]+)(/(.*))*)?#';

preg_match($matchingPattern, str_replace('/public', '', $_SERVER['REDIRECT_URL']), $routingMatches);

$posA=strpos($routingMatches[14], '?');
if ($cli_server) {
    parse_str(parse_url($routingMatches[14])['query'], $queryParams);
} else {
    $queryParams=$_GET;
    unset($queryParams['_url']);
}

if ($posA!==false) $routingMatches[14]=substr($routingMatches[14], 0, $posA);
    $routingMatches[14]=explode('/', $routingMatches[14]);
    $actionParam=(empty($routingMatches[14][1]))?'index':$routingMatches[14][1];
if (empty($routingMatches[12])) {
    $actionParam='index';
    $routingMatches[12]='index';
}


array_shift($routingMatches[14]);
array_shift($routingMatches[14]);


$di->routing=array(
    'accessarea' => $routingMatches[2],
    'module' => $routingMatches[6],
    'submodule' => $routingMatches[8],
    'lang' => $routingMatches[10],
    'controller' => $routingMatches[12],
    'action'     =>  $actionParam,
    'params'     => $routingMatches[14],
    'installation_id' => $routingMatches[4],
    'queryParams' => &$queryParams    
);





$application=new Application($di);



$contents=ob_get_contents();
ob_end_clean();

if (false&&strpos($_SERVER['SERVER_NAME'], '192.168.')!==0) ob_end_clean();

echo $contents;

$endingTime=microtime(true);
echo '<div style="position:absolute;z-index: 10000000;border: 1px solid #f00;top: 0;left: 30%;background: #f2f2f2">'.round((($endingTime-$startingTime)*1000)).' ms<div>';
echo '<pre>microseconds:';
print_r($lgs_code_exec_keys);

echo '</pre>';


?>
