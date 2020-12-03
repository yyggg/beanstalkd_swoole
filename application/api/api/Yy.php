<?php
// +----------------------------------------------------------------------
// | [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Author: 杨雁
// +----------------------------------------------------------------------
// | Date: 2020/11/20 10:55
// +----------------------------------------------------------------------

namespace app\api\api;

use app\api\service\BeanstalksService;

class Yy extends Common
{
    public function test()
    {
        $callBlack = [
            'demo1',
            'demo1',
            'demo1',
            'demo1',
            'demo1',
            'demo',
            'demo',
            'demo',
        ];
        for($i = 0; $i<8; $i++) {
            $data = [
                'callback' => $callBlack[$i],
                'a' => 1,
                'b' => 2,
                'c' => 3,
            ];
            $res = BeanstalksService::producer($data);
            echo $res->getId() . " \n";
        }
    }

}
