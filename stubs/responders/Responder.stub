<?php

namespace App\Responders;

class Responder
{
    public static function respond()
    {
        return new self();
    }

    public function withView($view, $data = [])
    {
        return view($view, $data);
    }

    public function withJSON($data)
    {
        return response()->json($data);
    }
}