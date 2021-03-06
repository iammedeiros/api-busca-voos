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

    //função para ler os dados dos arquivos csv e json e armazena-los em um array
    private function read_data() {
        $csv_data = [];
        $json_data = [];
        $row = 0;
        
        try {
            //Abrindo o arquivo csv da uberair
            $handle = fopen("data/uberair.csv", "r");
            //lendo os dados
            while ($line = fgetcsv($handle, 1000, ",")) {
                if ($row++ == 0) {
                    continue;
                }
                
                $csv_data[] = [
                    "voo" => $line[0],
                    "origem" => $line[1],
                    "destino" => $line[2],
                    "data_saida" => $line[3],
                    "saida" => $line[4],
                    "chegada" => $line[5],
                    "valor" => (float) $line[6],
                ];
            }
            fclose($handle);

            //lendo os dados do json da 99planes
            $json_data = json_decode(file_get_contents('data/99planes.json'), true);
            //junta os dados do csv e do json e devolve como retorno da função
            return array_merge($csv_data, $json_data);
        }
        catch(\Exception $ex) {
            if (config('app.debug')) {
                return response()->json(['msg' => $ex->getMessage()], 500);
            }

            return response()->json(['msg' => 'Houve um erro ao carregar os dados!'], 500);
        } 
    }

    private function getFlight($from, $to, $date) {
        try {
            //procura um voo direto
            foreach ($this->flights as $flight) {
                if ($flight["origem"] === $from && $flight["destino"] === $to 
                    && $flight["data_saida"] === $date) {
                        return $flight;
                }
            }

            //se não encontrar um voo direto, busca uma escala
            foreach ($this->flights as $flight1) {
                //verifica se existe algum voo saindo da origem
                if ($flight1["origem"] === $from && $flight1["data_saida"] === $date) {
                    foreach ($this->flights as $flight2) {
                        //se encontrar um voo, verifica se existe outro no qual a origem 
                        //é igual o destino do primeiro, e o destino é igual ao destino 
                        //final do passageiro e verifica também se a hora de chegada do primeiro voo 
                        //é menor que a hora de saida do segundo
                        if ($flight2["origem"] === $flight1["destino"] && $flight2["destino"] === $to 
                            && $flight2["data_saida"] == $date && self::hourToMinutes($flight1["chegada"]) < 
                                self::hourToMinutes($flight2["saida"])) {
                                    //conventendo as horas de saida do voo 2 e chegada do voo1 em minutos
                                    //e calculando a diferença entre as duas para determinar o tempo de espera
                                    $waitTime = self::hourToMinutes($flight2["saida"]) - self::hourToMinutes($flight1["chegada"]);
                                    
                                    //verificando se a diferença é menor que 720
                                    //720 = 12 horas
                                    if ($waitTime < 720) {
                                        $result = [
                                            "origem" => $from,
                                            "destino" => $to,
                                            "data_saida" => $date,
                                            "saida" => $flight1["saida"],
                                            "chegada" => $flight2["chegada"],
                                            "trechos" => [
                                                $flight1,
                                                $flight2
                                            ],
                                            "tempo_espera" => self::minutesToHour($waitTime)
                                        ];

                                        return $result;
                                    }
                        }
                    }
                }
            }

            return null;
        }
        catch(\Exception $ex) {
            if (config('app.debug')) {
                return response()->json(['msg' => $ex->getMessage()], 500);
            }

            return response()->json(['msg' => 'Houve um erro ao realizar a operação!'], 500);
        }
    }

    public function index(Request $request) {
        $from = $request->input('from');
        $to = $request->input('to');
        $date = $request->input('date');

        if (empty($from) || empty($to) || empty($date))
            return response()->json(['msg' => 'Parametros inválidos para a busca!'], 502);

        $result = self::getFlight($from, $to, $date);

        if ($result)
            return response()->json(['data' => $result]);
        else
            return response()->json(['msg' => 'Voo não encontrado!'], 404);
    }

    //função que recebe uma hora em hh:mm e converte em minutos
    private function hourToMinutes($fullhour) {
        $parts = explode(":", $fullhour);

        $hour = intval($parts[0]);
        $min = intval($parts[1]);

        return ($hour * 60) + $min;
    }

    //função que recebe um numero de minutos em inteiro e converte em hora no formato hh:mm
    private function minutesToHour($minutes) {
        // Define o formato de saida
        $format = '%02d:%02d';
        // Converte para o formato hora
        $hour = floor($minutes / 60);
        $minutes = $minutes % 60;

        return sprintf($format, $hour, $minutes);
    }
}
