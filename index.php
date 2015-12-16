<?php 

use LetsGetStarted\Framework\Application;
use LetsGetStarted\Framework\Model;
use LetsGetStarted\Framework\View;
use LetsGetStarted\Framework\Controller;

$startingTime=microtime(true);


global $lgs_application_dir;
$lgs_application_dir=__DIR__."/";

$di=array();

include "LetsGetStarted.php";


function my_autoloader($class) {
    $class=str_replace("\\", "/", $class);
    $classParts=explode("/", $class);
    //print_r($classParts);
    if ($classParts[0]=="Modules") {
        echo "app/$class.php";ob_flush();
        include __DIR__."/app/$class.php";
    } else {
        echo "app/$class.php";ob_flush();
        include __DIR__."/app/$class.php";
    }
}


spl_autoload_register('my_autoloader');





if (strpos($_SERVER['SERVER_NAME'], "192.168.")!==0) ob_start();


echo "<pre>";
print_r($_SERVER);
echo "</pre>";

ob_start();




preg_match("#/((administrator|superuser)/)?(i/([0-9]+)/)?(m/([a-zA-Z0-9_-]+)/)?(m/([a-zA-Z0-9_-]+)/)?(l/([a-zA-Z]{2,3}(-[a-zA-Z]{2,3})?)/?)?(([a-zA-Z0-9_-])+)(/([a-zA-Z0-9_]+)(/(.*))*)?#", $_SERVER["REDIRECT_URL"], $routingMatches);
echo "<pre>";
print_r($routingMatches);

$routingMatches[14]=explode("/", $routingMatches[14]);
$actionParam=$routingMatches[14][1];
array_shift($routingMatches[14]);
array_shift($routingMatches[14]);

$queryParams=$_GET;
array_shift($queryParams);

$routingParams=array(
    "accessarea" => $routingMatches[2],
    "module" => $routingMatches[6],
    "submodule" => $routingMatches[8],
    "lang" => $routingMatches[10],
    "controller" => $routingMatches[12],
    'action'     =>  $actionParam,
    'params'     => $routingMatches[14],
    'installation_id' => $routingMatches[4],
    'queryParams' => &$queryParams    
);


print_r($routingParams);

echo "</pre>";



$di['routing']=$routingParams;


$application=new Application($di);



$contents=ob_get_contents();
ob_end_clean();

if (strpos($_SERVER['SERVER_NAME'], "192.168.")!==0) ob_end_clean();

echo $contents;

$endingTime=microtime(true);
echo "<div style='position:fixed;z-index: 10000000;border: 1px solid #f00;top: 0;right: 30%;background: #f2f2f2'>".round((($endingTime-$startingTime)*1000))." ms<div>";


?>
