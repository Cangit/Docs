<?php
/* _docsApiResources /_docs/api/{article} */

namespace Cangit\Beatrix\Docs\controller;

class Api
{

    public function get($app)
    {

        $data['article'] = $app['request']->attributes->get('article');
        $response = $app->response();

        if (!file_exists(APP_ROOT.'/vendor/cangit/docs/Cangit/Beatrix/Docs/lib/api/' . $data['article'] . '.twig')){
            $response->setStatusCode(404);
            $data['article'] = '404';
        }
        
        $response->setContent( $app['twig']->render('@root/vendor/cangit/docs/Cangit/Beatrix/Docs/lib/api.twig', $data) );
        
        return $response;

    }

}
