<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function index() {
        $airpots = json_decode(file_get_contents('data/aeroportos.json'));

        return response()->json(['data' => $airpots]);
    }
}
