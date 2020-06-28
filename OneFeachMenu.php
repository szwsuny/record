<?php
/**
 *   Copyright (C) 2020 All rights reserved.
 *
 *   FileName      ：OneFeachMenu.php
 *   Author        ：sunzhiwei
 *   Email         ：xue5521@qq.com
 *   Date          ：2020年06月28日
 *   Description   ：
 *   Tool          ：vim 8.2
 */

class OneFeachMenu
{
    public function run()
    {
        $nodeDatas = [
            ['id'=> 1,'pid'=>0],
            ['id'=> 4,'pid'=>2],
            ['id'=> 2,'pid'=>1],
            ['id'=> 3,'pid'=>2],
        ];

        $result = [];
        $presult = [];

        foreach($nodeDatas as $node)
        {
            $pid = ConvertHelper::toInt($node['pid']);
            $id = ConvertHelper::toInt($node['id']);

            $node = ['data' => $node];

            if($pid == $id)
            {
                $pid = 0;
            }

            if($pid == 0)
            {
                if(isset($presult[$id]))
                {
                    $presult[$id]['data'] = $node['data'];
                } else 
                {
                    $result[$id]['data'] = $node;
                    $presult[$id] = &$result[$id]['data'];
                }
            } else
            {
                if(isset($presult[$id]))
                {
                    if(isset($presult[$pid]))
                    {
                        $n_nn = $result[$id]['nodes'];
                        unset($result[$id]);
                        $presult[$pid]['nodes'][$id] = [
                            'data' => $node['data'],
                            'nodes' => $n_nn
                        ];

                        $presult[$id] = &$presult[$pid]['nodes'][$id];

                    } else 
                    {
                        $n_nn = $result[$id]['nodes'];
                        unset($result[$id]);
                        $result[$pid]['nodes'][$id] = [
                            'data' => $node['data'],
                            'nodes' => $n_nn
                        ];

                        $presult[$pid] = &$result[$pid];
                        $presult[$id] = &$presult[$pid]['nodes'][$id];
                    }

                } else 
                {
                    if(isset($presult[$pid]))
                    {
                        $presult[$pid]['nodes'][$id] = $node;
                        $presult[$id] = &$presult[$pid]['nodes'][$id];
                    } else 
                    {
                        $result[$pid] = [
                            'nodes' => [
                                $id => $node
                            ],
                        ];

                        $presult[$pid] = &$result[$pid];
                        $presult[$id] = &$result[$pid]['nodes'][$id];
                    }
                }
            }

        }

    }
}

(new OneFeachMenu())->run(); 
