<?php
/**
 *   Copyright (C) 2020 All rights reserved.
 *
 *   FileName      ：HashTable.php
 *   Author        ：sunzhiwei
 *   Email         ：sunzhiwei@fh21.com
 *   Date          ：2020年04月14日
 *   Description   ：哈希表实现。php本身数组本身就是hash，为了演示，所以我们限制只能使用数组上下标取数据
 *   Tool          ：vim 8.0
 */

/**
 * @brief hash结构体。php没有结构体用类代替
 */
class HashStruct {
    public $datas = [null,null,null,null]; //假设性质，其他语言可以设定数组长度,所以咱们就填充4个做固定
    public $count = 0; //存入的数据数量
    public $limit = 4; //datas的长度
}

/**
 * @brief 存入数据的结构体
 */
class HashUnitStruct {
    public $key; //key
    public $value; //值
    public $next; //单链指针
}

class HashTable implements Iterator //这个继承用来实现php foreach可循环
{
    private $hashTable;

    public function __construct()
    {
        //创建一个空的结构
        $this->hashTable = new HashStruct();
    }

    /**
     * @brief 添加数据
     *
     * @param mixed $key 
     * @param mixed $value
     *
     * @return 
     */
    public function add($key,$value)
    {
        //获得需要写入的数组下标
        $index = $this->getIndex($key);

        //这里需要判断数值是不是空的。如果是空的直接写入，否则就将启用单链结构，加到已存在数据最后面数据的next下
        if($this->hashTable->datas[$index] == null)
        {
            //创建结构体，实际上类挺吃内存的，但是这里是演示所以就这样了
            $hashUnitStruct = new HashUnitStruct();
            $hashUnitStruct->key = $key;
            $hashUnitStruct->value = $value;

            //这里因为这个下标是空数据 所以就直接使用了
            $this->hashTable->datas[$index] = $hashUnitStruct;

            $this->hashTable->count++;
        } else //说明这个下标有数据，有数据存在两种情况一种是key存在，那么应该修改，否则就追加
        {
            //这里由于存在非空数据，所以要追加的这个单链表的最后数据的next下
            //注意：这里使用 & 指针
            $datas = &$this->hashTable->datas[$index];

            if($datas->key != $key) //判断第一个key 是不是和要添加的key一样，一样的话就修改他
            {
                $isadd = true; //信号用于判断是修改还是添加

                //单链循环到结尾
                while($datas->next != null)
                {
                    $datas = &$datas->next;

                    if($datas->key == $key) //循环中依次判断key 是否存在 并修改
                    {
                        $datas->value = $value;
                        $isadd = false; //重置信号表示不需要添加
                        break; 
                    }
                }

                if($isadd)
                {
                    $hashUnitStruct = new HashUnitStruct();
                    $hashUnitStruct->key = $key;
                    $hashUnitStruct->value = $value;

                    $datas->next = $hashUnitStruct;
                    $this->hashTable->count++;
                }
            } else 
            {
                $datas->value = $value;
            }
        }

        $this->expendHash();
    }

    /**
     * @brief 扩张数据是为了保证减少key的碰撞，能够快速用下标取得数据，而不是还要进行单链循环取数据
     *
     * @return 
     */
    public function expendHash()
    {
        //扩展数据 由于数量过多了那么就需要对数组进行扩张
        //扩张条件一般是 超过当前可存长度的一半时。扩展为已有数据量的2倍，比如已存入6个 那么扩展数组为12个

        //语言限制数组最大长度
        $system_max_limit = 1048576;

        if($this->hashTable->limit >= $system_max_limit)
        {
            return;
        }

        //允许超过数量
        $allow_exceed_count = intval($this->hashTable->limit / 2);
        //已经超过的数量
        $curr_exceed_count = $this->hashTable->count - $this->hashTable->limit;

        //如果已经超过了允许数量，那么就开始扩展 hashtable 的datas
        if($curr_exceed_count > $allow_exceed_count)
        {
            //新的长度
            $newLimit = $this->hashTable->count * 2;
            //系统限制的最大数组长度,如果超过了，那么就
            if($newLimit > $system_max_limit)
            {
                $newLimit = $system_max_limit;
            }

            $newHashStruct = new HashStruct();
            $newHashStruct->datas = array_pad([],$newLimit,null);
            $newHashStruct->limit = $newLimit;

            //拿出旧的 
            $oldHashStruct = $this->hashTable;

            //将新建的指向上去
            $this->hashTable = $newHashStruct;

            //然后往新的里面追加数据
            foreach($oldHashStruct->datas as $datas)
            {
                if($datas != null)
                {
                    $this->add($datas->key,$datas->value);

                    while($datas->next != null)
                    {
                        $datas = $datas->next;

                        $this->add($datas->key,$datas->value);
                    }
                }
            }

            //看一下内存
            // echo memory_get_usage().PHP_EOL;
            //解析掉旧的
            unset($oldHashStruct);
            // echo memory_get_usage().PHP_EOL;
        }
    }

    /**
     * @brief 获得某个key
     *
     * @param mixed $key
     *
     * @return 
     */
    public function get($key)
    {
        $index = $this->getIndex($key);

        //如果下标下是空数据那么返回null
        if($this->hashTable->datas[$index] == null)
        {
            return null;
        } else
        {
            $datas = $this->hashTable->datas[$index];

            if($datas->key == $key)
            {
                return $datas->value;
            }

            while($datas->next != null)
            {
                $datas = $datas->next;
                if($datas->key == $key)
                {
                    return $datas->value;
                }
            }

            //能走到这说明还是没找到，就返回null
            return null;
        }
    }

    /**
     * @brief 删除多余的key,注意这里有单链需要重新拼接
     *
     * @param mixed $key
     *
     * @return 
     */
    public function delete($key)
    {
        $index = $this->getIndex($key);

        if($this->hashTable->datas[$index] == null)
        {
            return false;
        } else
        {
            $datas = &$this->hashTable->datas[$index];

            if($datas->key == $key) //如果第一个key就是 那么这么处理
            {
                $this->hashTable->datas[$index] = $datas->next;
                $this->hashTable->count--;
                return true;
            }

            //如果第一个key不是 那么就进入单链找
            while($datas->next != null)
            {
                //先判断在去考虑要不要做删除
                if($datas->next->key == $key)
                {
                    $undatas = &$datas->next;
                    unset($undatas); //这里释放内存
 
                    //跨过去
                    $datas->next = $datas->next->next;
                    $this->hashTable->count--;
                    return true;
                } else 
                {
                    $datas = &$datas->next;
                }
            }

            //最终还是没找到可删除的
            return false;
        }
    }

    /**
     * @brief 获得所有keys..这个超级简单了。
     *
     * @return 
     */
    public function keys()
    {
        $result = [];

        foreach($this->hashTable->datas as $datas)
        {
            if($datas != null)
            {
                $result[] = $datas->key;

                while($datas->next != null)
                {
                    $datas = $datas->next;

                    $result[] = $datas->key;
                }
            }
        }

        return $result;
    }

    /**
     * @brief 同keys
     *
     * @return 
     */
    public function values()
    {
        $result = [];

        foreach($this->hashTable->datas as $datas)
        {
            if($datas != null)
            {
                $result[] = $datas->value;

                while($datas->next != null)
                {
                    $datas = $datas->next;

                    $result[] = $datas->value;
                }
            }
        }

        return $result;
    }

    /**
     * @brief 求下标，这里用的求余法，你可以寻找其他更好方法
     *
     * @param mixed $key
     *
     * @return 
     */
    protected function getIndex($key)
    {
        //要保证下标不能超过当前数组可用容量长度 所以除以可用长度的余正好作为下标使用
        return crc32($key) % $this->hashTable->limit;
    }

    public function getHashTable()
    {
        return $this->hashTable;
    }

    //下面是用来实现php foreach

    public $step = 0;
    public $fkeys = [];

    public function next()
    {
        ++$this->step;
    }

    public function current()
    {
        return $this->get($this->fkeys[$this->step]);
    }

    public function valid()
    {
        return isset($this->fkeys[$this->step]);
    }

    public function rewind()
    {
        $this->fkeys = $this->keys();
        $this->step = 0;
    }

    public function key()
    {
        return $this->fkeys[$this->step];
    }
}

$hashTable = new HashTable();
$hashTable->add('aa','112233');
$hashTable->add('bb','112233');
$hashTable->add('cc','112233');
$hashTable->add('dd','112233');
$hashTable->add('ee','112233');
$hashTable->add('ff','112233');
$hashTable->add('gg','112233');
$hashTable->add('hh','112233');
$hashTable->add('ff','sunzhiwei');
$hashTable->add('ii','112233');
$hashTable->add('jj','112233');
$hashTable->add('kk','112233');
$hashTable->add('mm','112233');
$hashTable->add('ll','sunzhiwei');
$hashTable->add('nn','112233');
$hashTable->add('oo','112233');


var_dump($hashTable->get('ll'));

$hashTable->delete('aa');
$hashTable->delete('ll');

var_dump($hashTable->get('ff'));

var_dump($hashTable->values());
var_dump($hashTable->keys());

// var_dump($hashTable->getHashTable());

foreach($hashTable as $key=>$value)
{
    var_dump($key.'=>'.$value);
}
