<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    private $airports;

    public function __construct() {
        $this->airports = json_decode(file_get_contents('data/aeroportos.json'));
    }

    public function index() {
        return response()->json(['data' => $this->airports]);
    }
}
