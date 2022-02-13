<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\JsonService;


class AccountController extends Controller
{
    private $initial = 0;
    private $account = [];

    public function __construct(JsonService $jsonService)
    {
        $this->jsonService = $jsonService;
        $this->account = $this->jsonService->read();
    }

    public function reset()
    {
        $this->jsonService->save([]);
        return response('OK', 200);
    }

    public function balance(Request $request)
    {
        if ($request->account_id === false) {
            return response()->json('Conta não informada', 400);
        }

        //Buscar se conta existe na session_account
        $account = $this->existsAccount($request->account_id);

        // Se não existir conta
        if($account === false){
            return response()->json($this->initial, 404);
        } else {
            return response()->json($this->account[$account]->balance, 200);
        }
    }

    private function existsAccount($account_id)
    {
        if(!is_array($this->account) || count($this->account) <= 0){
            return false;
        }
        // Retorna index do array account
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
                $retorno = $this->transfer($request->origin, $request->destination, $request->amount);
            break;
            default:
                $retorno = [
                    'data' => $this->initial, 
                    'code' => 404,
                ];
            break;
        }
        // Salvar transação event
        $this->jsonService->save($this->account);

        return response()->json($retorno['data'], $retorno['code']);
    }

    private function deposit($destination, $amount = 0)
    {
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
            $deposito = $this->account[$account];

            $retorno = [
                'data' => ['destination' => $deposito],
                'code' => 201,
            ];  
        }

        return $retorno;
    }

    private function withdraw($origin, $amount = 0)
    {
        $account = $this->existsAccount($origin);
        $retorno = [];

        if ($account === false) {
            $retorno = [
                'data' => $this->initial, 
                'code' => 404,
            ];
        } else if($this->account[$account]->balance < $amount){
            //Saldo insuficiente, retorna saldo atual
            $retorno = [
                'data' => $this->account[$account]->balance, 
                'code' => 404,
            ];
        } else { 
            $amount = $this->account[$account]->balance - $amount;
            $this->account[$account]->balance = $amount;

            $retorno = [
                'data' => ['origin' => $this->account[$account]],
                'code' => 201,
            ];
        }

        return $retorno;
    }

    private function transfer($origin, $destination, $amount = 0)
    {
        $account_origin = $this->existsAccount($origin);
        $retorno = [];

        if ($account_origin === false) {
            $retorno = [
                'data' => $this->initial, 
                'code' => 404,
            ];
        } else if($this->account[$account_origin]->balance < $amount){
            //Saldo insuficiente da conta origem, retorna saldo atual dela
            $retorno = [
                'data' => $this->account[$account_origin]->balance, 
                'code' => 404,
            ];
        } else {
            /*
            $amount_origin = $this->account[$account_origin]->balance - $amount;
            $amount_destin = $this->account[$account_destin]->balance + $amount;
            $this->account[$account_origin]->balance = $amount_origin;
            $this->account[$account_destin]->balance = $amount_destin;
            */
            $retirada = $this->withdraw($origin, $amount);

            if($retirada['code'] == 201 && isset($retirada['data'])){
                $deposito = $this->deposit($destination, $amount);
                $retorno['data']['origin'] = $retirada['data']['origin'];
                $retorno['data']['destination'] = $deposito['data']['destination'];

                $retorno['code'] = 201;
            }            
        }

        return $retorno;
    }
}
