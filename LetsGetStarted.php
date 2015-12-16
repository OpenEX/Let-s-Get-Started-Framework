<?php 
namespace LetsGetStarted\Framework;

class Controller {

}

class Model {

}

class View {

}

class Application {
    function __construct(array $di) {
        global $lgs_application_dir;
        //echo "<pre>";
        //print_r($di);
        //echo "</pre>";
        
        $controllerNamespace="";
        if (!empty($di["routing"]["module"])) {
            $controllerNamespace.="\\Modules\\".ucfirst(strtolower($di["routing"]["module"]));
            if (!empty($di["routing"]["submodule"])) $controllerNamespace.="\\Submodules\\".ucfirst(strtolower($di["routing"]["submodule"]));
            $this->viewPath=$lgs_application_dir."app".$controllerNamespace."/views/";
        } else {
            $this->viewPath=$lgs_application_dir."app/views/";
        }
        
        $controllerNamespace.="\\Controllers";
        
        $controllerNamespaceName=ucfirst(strtolower($di["routing"]["controller"]));
        $controllerNamespace.="\\$controllerNamespaceName";    
        
        $controllerNamespaceClassName=$controllerNamespace."Controller";    
        echo $controllerNamespace;
        $controller=new $controllerNamespaceClassName();
        $actionFunctionName=$di["routing"]["action"]."Action";
        
        $this->templateRelativePath=$controllerNamespaceName."/".$di["routing"]["action"].".php";
        
        
        $controller->$actionFunctionName();
        
        include(str_replace("\\", "/", $this->viewPath.$this->templateRelativePath));
        
    }
}
