<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class AccountController extends Controller
{
    private $initial = 0;
    private $account = [];

    public function reset()
    {
        Session::forget('account');
        Session::flush();
        Session::regenerate();

        Session::flash('account', $this->account);

        return response('OK', 200);
    }

    public function balance(Request $request)
    {
        Session::reflash();
        $session_account = Session::get('account');
        if ($request->account_id === false) {
            return response()->json('Conta não informada', 400);
        }

        //Buscar se conta existe na session_account
        $account = $this->existsAccount($request->account_id);
        
        // Se não existir contas
        if($account === false){
            return response()->json($this->initial, 404);
        } else {
            return response()->json($session_account[$account]['balance'], 200);
        }
    }

    private function existsAccount($account_id)
    {
        Session::reflash();
        $session_account = Session::get('account');

        if(!is_array($session_account) || count($session_account) <= 0){
            return false;
        }
        return array_search($account_id, array_column($session_account, 'id'));
    }

    public function event(Request $request)
    {
        Session::reflash();
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
                $retorno = [];
            break;
        }
        
        return response()->json($retorno, 201);
    }

    private function deposit($destination, $amount = 0)
    {
        Session::reflash();
        $session_account = Session::get('account');
        $account = $this->existsAccount($destination);
        $retorno = [];

        if ($account === false) {
            $deposito = ['id' => $destination, 'balance' => $amount];
            $this->account[] = $deposito;

            $retorno = ['destination' => $deposito];            
        } else {
            $amount = $amount + $session_account[$account]['balance'];
            
            $session_account[$account]['balance'] = $amount;
            $this->account = $session_account; 

            $retorno['destination'] = $session_account[$account];
        }
        
        Session::flash('account', $this->account);
        return Session::get('account');
        return $retorno;
    }

    private function withdraw($origin, $amount = 0)
    {
        Session::reflash();
        $session_account = Session::get('account');
        $account = $this->existsAccount($origin);
        $retorno = [];



        return $retorno;
    }
}
