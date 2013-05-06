<?php
/* _docsTrueHome /_docs */

namespace Cangit\Beatrix\Docs\controller;

class LetsGo
{

    public function get($app)
    {
        
        $html = $app['twig']->render('@root/vendor/cangit/docs/Cangit/Beatrix/docs/lib/letsgo.twig');
        $response = $app->response( $html );

        return $response;
    }

}
