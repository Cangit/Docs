<?php
/* _routeAnalyzer /_docs/analyzer/{route} */

namespace Cangit\Beatrix\Docs\controller;

class Analyzer
{

    /* Crappy code, will refactor. */
    public function get($app)
    {
        $data['appRoot'] = APP_ROOT;
        $data['file']['path'] = 'app/config/routes.yml';
        $routes = $app['cache']->file('beatrixRoutes', $data['file']['path'], 'yml');
        $data['httphost'] = $app['request']->server->get('HTTP_HOST');
        $data['controllerFolder'] = 'src/controller/';
        $routeToAnalyze = $app['request']->attributes->get('route');


        if ($routeToAnalyze !== null AND $routeToAnalyze !== 'onlyRoutesWithExceptions'){
            
            if (!array_key_exists($routeToAnalyze, $routes)){
                exit('Resource not found');
            }

            $route = $routes[$routeToAnalyze];
            $path = str_replace('\\', '/', $route['defaults']['_controller']);
            $data['controllerFile'] = $data['controllerFolder'].$path.'.php';

            $data['routeFile'] = file_get_contents($data['controllerFile']);
            $data['routeName'] = $routeToAnalyze;
            $html = $app['twig']->render('@root/vendor/cangit/docs/Cangit/Beatrix/docs/lib/routes/view.twig', $data);
            $response = $app->response($html);
            return $response;
        }


        $data['routes'] = [];
        foreach($routes as $routeName => $route){
            
            if (substr($route['path'], 0, 6) === '/_docs'){
                continue;
            }

            $route['routeIdentifier'] = $routeName;
            $path = str_replace('\\', '/', $route['defaults']['_controller']);
            $route['controllerPath'] = $data['controllerFolder'].$path.'.php';

            if (is_readable($route['controllerPath'])){
                $route['fileExist'] = true;
                try{
                    $headers = $app->compileAllowHeaders('controller\\'.$route['defaults']['_controller']);
                    $route['availibleMethods'] = $headers['Allow'];
                    $route['availibleMethods'] = str_replace("HEAD, OPTIONS", '<span class="unimportant">HEAD, OPTIONS</span>', $route['availibleMethods']);
                    $route['classExists'] = true;
                } catch(\Exception $e) {
                    $route['availibleMethods'] = '';
                    $route['classExists'] = false;
                }
            } else {
                $route['fileExist'] = false;
                $route['classExists'] = false;
                $route['availibleMethods'] = '';
            }
            
            if($routeToAnalyze == 'onlyRoutesWithExceptions' && $route['classExists'] === true){
                // Skipping route
            } else{
                $data['routes'][] = $route;
            }

        }

        $html = $app['twig']->render('@root/vendor/cangit/docs/Cangit/Beatrix/docs/lib/routes/analyzer.twig', $data);
        $response = $app->response( $html );

        return $response;
    }

    public function post($app)
    {
        $data['file']['path'] = 'app/config/routes.yml';
        $routes = $app['cache']->file('beatrixRoutes', $data['file']['path'], 'yml');
        $data['httphost'] = $app['request']->server->get('HTTP_HOST');
        $data['controllerFolder'] = 'src/controller/';
        $routeToAnalyze = $app['request']->request->get('create');

        if ($routeToAnalyze !== null){
            
            if (!array_key_exists($routeToAnalyze, $routes)){
                exit('Could not create resource. Definitions not found in routes.yml');
            }

            $route = $routes[$routeToAnalyze];
            $path = str_replace('\\', '/', $route['defaults']['_controller']);
            $route['controllerPath'] = $data['controllerFolder'].$path.'.php';

            if (is_readable($route['controllerPath'])){
                exit('Could not create resource. Controller file already exists.');
            }
            
            $data['className'] = substr(strrchr($route['defaults']['_controller'], '\\'), 1);
            $position = strrpos($route['defaults']['_controller'], '\\');
            $data['namespace'] = substr($route['defaults']['_controller'], 0, $position);
            $data['routeIdentifier'] = $routeToAnalyze;
            $data['routePath'] = $route['path'];

            $dir = str_replace('\\', '/', $data['namespace']);

            $php = $app['twig']->render('@root/vendor/cangit/docs/Cangit/Beatrix/docs/lib/routes/createController.twig', $data);
            if(is_dir('src/controller/'.$dir)){
                // Moving straight ahead.
            } else {
                mkdir('src/controller/'.$dir, 0777, true);
            }

            if(file_put_contents($route['controllerPath'], $php, LOCK_EX) === false){
                exit('Could not create resource. failed to write file');
            }

            chmod($route['controllerPath'], 0777);
            
            return $this->get($app);

        } else {
            exit('Could not create resource. Missing get attribute "route"');
        }

    }

    public function put($app)
    {
        $data['file']['path'] = 'app/config/routes.yml';
        $data['name'] = $app['request']->request->get('name');
        $data['path'] = $app['request']->request->get('path');
        $data['controller'] = $app['request']->request->get('controller');
        $data['attr'] = $app['request']->request->get('attributes');

        if ($data['name'] == ''){
            exit('A name must be provided.');
        }
        if ($data['path'] == ''){
            exit('A path must be provided.');
        }
        if ($data['controller'] == ''){
            exit('A controller must be provided.');
        }
        if ($data['attr'] == ''){
            $data['attr'] = null;
        }

        $data['path'] = str_replace('\\', '/', $data['path']);
        $data['controller'] = str_replace('/', '\\', $data['controller']);

        $insertData = $app['twig']->render('@root/vendor/cangit/docs/Cangit/Beatrix/docs/lib/routes/createRoute.twig', $data);

        if(file_put_contents($data['file']['path'], $insertData, FILE_APPEND | LOCK_EX) === false){
            exit('Could not create route. Failed to write file.');
        }

        return $this->get($app);

    }

}
