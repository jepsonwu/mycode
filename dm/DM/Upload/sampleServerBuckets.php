<?php
$buckets = array(
        'caibei'    =>      array('path'=>'/var/www/html/upload/caibei/','domain'=>'http://upload.caibei.com/'),
        'dn'    =>      array('path'=>'/var/www/html/upload/dn/'),
        'tm'    =>  array('path'=>'/var/www/html/upload/tm/','domain'=>'http://upload.tm.cn/'),
        'haidai'    =>      array('path'=>'/var/www/html/upload/haidai/','domain'=>'http://upload-haimi.duomai.com/',
            'water' =>  array(
                'img'   =>  '/var/www/html/upload/haidai/water.png',
                'type'  =>  1,
                'place' =>  9
            ),
        ),
        'test'    =>      array(
            'path'  =>  'e:/web/upload/',
            'domain' =>  'http://upload.test.cm/',
            'water'=>array(
                'img'=>'e:/web/upload/daigou.png',
                'type'=>1,
                'place'=>1
            ),
        ),
);

$accessLocalKey = 'dm system duomai.com';
