<?php // $Id: events.php,v 1.1 2008/11/29 20:24:26 poltawski Exp $

$handlers = array (
    'user_deleted' => array (
         'handlerfile'      => '/portfolio/type/googledocs/lib.php',
         'handlerfunction'  => 'portfolio_googledocs_user_deleted', 
         'schedule'         => 'cron'
     ),
);

?>
