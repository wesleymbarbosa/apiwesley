<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class JsonService
{
    private $file  = 'accounts.json';
    private $array = [];

    public function save($data)
    {
        if(is_array($data)){
            $data = json_encode($data);
        } else {
            $data = json_encode($this->array);
        }

        File::put(public_path($this->file), $data);

        return file_exists(public_path($this->file));
    }

    public function read()
    {
        $content = file_get_contents(public_path($this->file));
        
        return json_decode($content);
    }

}
