<?php

//
abstract class node extends mutall {
    //
    //Short version of the fullname
    public $name;
    //
    //The full 
    public $full_name;
    //
    //The creation date.
    public $creation_date;
    //
    //The modification date.
    public $modification_date;
    //
    //
    function __construct(
            string $name,
            string $full_path
            
    ) {
        //
        //Get the name of this folder which is the last 
        $this->name=$basename;
        //
        //Save the fullname 
        $this->full_name = $path;
        //
        //This is the creation date
        $this->creation_date = filectime($this->full_name);
        //
        //Get the date this folder was modified
        $this->modification_date= filemtime($this->full_name);
        //
        //Get the size of this folder 
        $this->size= filesize($this->full_name);
    }
    //Form a complete node structure using the initial path and return 
    //the node.
    static function export(
        //
        //The initial path is either a file or a folder depending on the 
        //target specification.The path may be relative or absolute.
        //e.g  absolute: /pictures/water/logo.jpeg.
        //     relative:  pictures/water/logo.jpeg.
        string $initial_path,
        string /*"file"|"folder"*/ $target
    ): node {
        //
        //1. Make the initial path absolute. 
        //e.g /pictures/water/logo.jpeg.
        $abs_path= $this->get_absolute_path($initial_path);
        //
        //2.Separate the target file and the node path from the initial absolute 
        //path. e.g /pictures/water,  logo.jpeg
        //
        //2.1) the node path 
        $node_path_str= pathinfo($abs_path, PATHINFO_DIRNAME);
        //
        //2.2)The target path depends on the use specifications
        $taget_file = $target==="file" 
            ? pathinfo($abs_path, PATHINFO_FILENAME)
            :"";
        //
        //3. Build the node network from the node path array
        //
        //3.1) Convert the node path into an array 
        $node_path= split($node_path_str, "/");
        //
        //Reverse the elements in the node path
        $reversed= array_reverse($node_path);
        //
        //Create a root folder, i.e., one with no parent. 
        $node= new rich_folder($reversed, "", null);
        
        //
        //Return the node promised 
        return $node;
     
        
    }
}
class folder extends node{
    //
    //
    function __construct(
        string $name,
        string $full_name,           
        ?folder $parent
    ) {
        //
        parent::__construct($name, $full_name, $parent);
        //
        $this->children = null;
    }
}
//
//
class rich_folder extends folder {
    //
    //
    function __construct(
            //
            //Reversed path i.e., the node at the top of the stack 
            //corresponds  to the name of the path
            array $node_path,
            string $full_name,           
            ?branch $parent
    ) {
        //Ensure that there is atleast one element in the path
        if(count($node_path===0)){
            throw  new myerror("Empty path not allowed");
        }
        //
        //Get the name of this folder which is at the top of the stack 
        $name = $node_path[count($node_path)-1];
        //
        //Concatenate the name to complete the full name
        $full_name = $full_name."/".$name;
        //
        //Pop the last component of the node path to avoid recreating
        //this folder.
        $node_path2 = array_pop($node_path);
        //
        parent::__construct($name, $full_name, $parent);
        //
        //Use a generator to retrieve the children indexed by their names
        $this->children = iterator_to_array(
                    $this->get_children()
                );
        //
        //Only do the promotion if there are node paths still remaining 
        //to be generated
        if(count($node_path2)===0){return;}
        //
        $node = new rich_folder($node_path2, $full_name, $this);
        //
        //Promote the current ordinary node to a rich one.
        $this->children[$node_path2[count(node_path2)-1]] = $node;
    }
    //
    //A generator function that yields a node children obtained using 
    //scan dir()
    function get_children(){
        //
        //Scan the server for folders in this full_path
        $paths = scandir($this->full_name);
        //
        //Map all the ifolders to nodes. 
        foreach ($paths as $path){
            //
            //Get the basename of this path
            $name = pathinfo($path,PATHINFO_BASENAME);
            //
            //Yield an ordinary folder/file, indexed by the name
            //
            //Find out whether this is a folder or a file
            //if file create new file else new folder
            if(is_dir($path)){
                //
                yield $name => new folder($name, $path, $this);
            }else{
                //
                yield $name => new file($name, $path, $this);
            }
        } 
    }
}
//
class file extends node {
    function __construct(
        //
        //The name of the leaf
        string $name,
        //
        //The full path of this leaf
        string $full_name,
        //
        //The visual representation of this leaf
        string $icon,
        //
        //The optional parent
        ?branch $parent
    ) {
       parent::__construct($name, $full_name, $icon, $parent); 
    }
}
