<?php
/* _debugLogReader /_docs/logreader/{logfile} */

namespace Cangit\Beatrix\Docs\controller;

class LogReader
{

    public function get($app)
    {

        $logfile = $app['request']->attributes->get('logfile');

        if ($logfile !== null){

            $reader = new \Dubture\Monolog\Reader\LogReader('app/log/debug/'.$logfile);
            
            foreach ($reader as $log){
                if(isset($log['context'])){
                    $log['context'] = print_r($log['context'], true);
                }
                if($log !== []){
                    $data['entries'][] = $log;
                }
                
            }

            if (isset($data['entries'])){
                $data['entries'] = array_reverse($data['entries']);
            } else {
                $data['entries'] = [];
            }

            $data['logfile'] = $logfile;

            $html = $app['twig']->render('_docs/displaylog.twig', $data);
            $response = $app->response($html);
            
        } else {

            if ($handle = opendir('app/log/debug')){
                while (false !== ($entry = readdir($handle))){
                    if ($entry == '.' OR $entry == '..'){
                        continue;
                    }
                    $data['logdir'][] = $entry;
                }

                closedir($handle);
            }

            $html = $app['twig']->render('_docs/logreader.twig', $data);
            $response = $app->response($html);

        }

        return $response;
    }

}
