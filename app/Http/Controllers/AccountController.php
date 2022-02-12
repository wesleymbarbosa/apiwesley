<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use File;

class AccountController extends Controller
{
    private $initial = 0;
    private $account = [];

    public function reset()
    {
        $this->saveJson($this->account);
        return response('OK', 200);
    }

    private function saveJson($data)
    {
        if(is_array($data)){
            $data = json_encode($data);
        } else {
            $data = json_encode($this->account);
        }
        
        $jsongFile = 'accounts.json';
        File::put(public_path($jsongFile), $data);
    }

    private function readJson()
    {
        $arquivo = 'accounts.json';
        $content = file_get_contents(public_path($arquivo));
        
        return json_decode($content);
    }
        
    public function balance(Request $request)
    {
        $session_account = $this->readJson();

        if ($request->account_id === false) {
            return response()->json('Conta não informada', 400);
        }

        //Buscar se conta existe na session_account
        $account = $this->existsAccount($request->account_id);
        
        // Se não existir contas
        if($account === false){
            return response()->json($this->initial, 404);
        } else {
            return response()->json($session_account[$account]->balance, 200);
        }
    }

    private function existsAccount($account_id)
    {
        $this->account = $this->readJson();

        if(!is_array($this->account) || count($this->account) <= 0){
            return false;
        }
        return array_search($account_id, array_column($this->account, 'id'));
    }

    public function event(Request $request)
    {
        if (!isset($request->type)) {
            return response()->json('Tipo não informado', 400);
        }
        if (!isset($request->amount)) {
            return response()->json('Valor não informado', 400);
        }

        switch ($request->type) {
            case 'deposit':
                $retorno = $this->deposit($request->destination, $request->amount);
            break;
            case 'withdraw':
                $retorno = $this->withdraw($request->origin, $request->amount);
            break;
            case 'transfer':
                $retorno = $this->transfer();
            break;
            
            default:
                $retorno = [
                    'data' => $this->initial, 
                    'code' => 404,
                ];
            break;
        }
        
        return response()->json($retorno['data'], $retorno['code']);
    }

    private function deposit($destination, $amount = 0)
    {
        $this->account = $this->readJson();
        $account = $this->existsAccount($destination);
        $retorno = [];

        if ($account === false) {
            $deposito = ['id' => $destination, 'balance' => $amount];
            $this->account[] = $deposito;

            $retorno = [
                'data' => ['destination' => $deposito],
                'code' => 201,
            ];            
        } else {
            $amount = $amount + $this->account[$account]->balance;
            
            $this->account[$account]->balance = $amount;
            $this->account = $this->account; 

            $retorno = [
                'data' => ['destination' => $this->account[$account]],
                'code' => 201,
            ];  
        }

        $this->saveJson($this->account);
        return $retorno;
    }

    private function withdraw($origin, $amount = 0)
    {
        $this->account = $this->readJson();
        $account = $this->existsAccount($origin);
        $retorno = [];

        if ($account === false) {
            $retorno = [
                'data' => $this->initial, 
                'code' => 404,
            ];
        } else if($this->account[$account]->balance < $amount){
            $retorno = [
                'data' => $this->initial, 
                'code' => 404,
            ];
        } else { 
            $amount = $this->account[$account]->balance - $amount;
            $this->account[$account]->balance = $amount;
            $this->saveJson($this->account);

            $retorno = [
                'data' => ['origin' => $this->account[$account]],
                'code' => 201,
            ];
        }

        return $retorno;
    }
}
