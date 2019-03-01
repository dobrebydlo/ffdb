<?php namespace Filebase;

use Filebase\Table;
use Filebase\Support\Filesystem;
use Filebase\Format\Json;

class Query 
{
    public $table;
    public $fs;
    public $formater;
    protected $conditions=[];

    public function __construct(Table $table)
    {
        $this->table = $table;
        $this->fs = new Filesystem($table->fullPath());

        // we have access to this within $this->db()->config()->format
        $this->formater = new Json();
        
    }

    public function table()
    {
        return $this->table;
    }

    public function db()
    {
        return $this->table->db();
    }

    public function create(array $args)
    {
        // TODO:ADD START POINT FOR ID 
        // TODO:VALIDATE
        $name=$this->table()->genUniqFileId(0,'.json');
        $this->fs->write($name,$this->formater->encode($args,true));
        return $this->find($name);  
    }

    public function find($id)
    {
        // TODO:set ext dina
        // check if input has ext ...
        if(strpos($id,'.json')!==false)
        {
            $id=str_replace('.json','',$id);
        }
        if($this->fs->has($id.'.json'))
        {
            return new Document($this->table(),$id.'.json',(array)json_decode($this->fs->read($id.'.json'),true));
        }
        return new Document($this->table(),$id.'.json');
    }
    /**
    * Get a list of documents within our table
    * Returns an array of items
    *
    * @return array
    */
    public function getAll()
    {
        $items=$this->db()->fs()->files($this->table()->path(), $this->db()->config()->extension);
        foreach($items as $item)
        {
            $_items[]=new Document($this->table(),$item,json_decode(
                $this->db()->fs()->read($this->table()->name()."/".$item.".json")
            ,true));
        }
        return new Collection($_items);
        // return $this->db()->fs()->files($this->path(), $this->db()->config()->extension);
    }
    public function where($key,$con,$value)
    {
        $this->conditions['and'][]=[$key,$con,$value];
        return $this;
    }
    public function getConditions()
    {
        return $this->conditions;
    }
    public function get()
    {
        if(isset($this->conditions['and']))
        {
            return $this->filter();
        }
        return $this->getAll();
    }
    public function filter()
    {
        $items=$this->getAll();
        $result=[];
        foreach($this->conditions['and'] as $condition)
        {
            foreach ($items as $key => $value) {
                if(isset($value[$condition[0]]))
                {
                    if($value[$condition[0]]==$condition[2])
                    {
                        $result[]=$value;
                    }
                }
            }
        } 
        return $result;
    }

}