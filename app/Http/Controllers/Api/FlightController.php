<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    private $flights;

    public function __construct() {
        $this->flights = self::read_data();
    }

    //função para ler os dados dos arquivos csv e json e armazena-los em um array json
    private function read_data() {
        $data = [];
        $handle = fopen("data/uberair.csv", "r");
        $row = 0;
        
        //lendo os dados do csv
        while ($line = fgetcsv($handle, 1000, ",")) {
            if ($row++ == 0) {
                continue;
            }
            
            $data[] = [
                "voo" => $line[0],
                "origem" => $line[1],
                "destino" => $line[2],
                "data_saida" => $line[3],
                "saida" => $line[4],
                "chegada" => $line[5],
                "valor" => (float) $line[6]
            ];
        }
        fclose($handle);
        
        //junta os dados do csv e do json e devolve como retorno da função
        return array_merge($data, json_decode(file_get_contents('data/99planes.json'), true));
    }

    private function getFlight(Request $request) {
        $flight = null;
        $origin = $request->input('origem');
        $destiny = $request->input('destino');
        $date = $request->input('data');

        foreach ($this->flights as $item) {
            if ($item["origem"] === $origin && $item["destino"] === $destiny 
                && $item["data_saida"] === $date) {
                    $flight = $item;
            }
        }

        return $flight;
    }

    public function index(Request $request) {
        $result = self::getFlight($request);

        if ($result)
            return response()->json(['data' => $result]);
        else
            return response()->json(['data' => 'Voo não encontrado!']);
    }
}
