<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AccountController extends Controller
{
    private $initial = 0;
    private $account = [];

    public function reset()
    {
        Session::flush();
        Session::regenerate();
        return response('OK', 200);
    }

    public function balance(Request $request)
    {
        if ($request->account_id === false) {
            return response()->json('Conta não informada', 400);
        }
        
        $session_account = Session::get('account');
        if (!$session_account) {
            $retorno = $this->initial;
            Session::put('account', $this->initial);
            return response()->json($retorno, 404);
        } elseif ($session_account['id'] == $request->account_id) {
            $retorno = $session_account['balance'];
        }

        return response()->json($retorno, 200);
    }
    
    public function event(Request $request)
    {
        if ($request->type === false) {
            return response()->json('Tipo não informado', 400);
        }
        if ($request->amount === false) {
            return response()->json('Valor não informado', 400);
        }

        
        switch ($request->type) {
            case 'deposit':
                $retorno = $this->deposit($request->destination, $request->amount);
            break;
            case 'withdraw':
                $retorno = $this->withdraw();
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
        $session_account = Session::get('account');
        if (isset($session_account['id']) && isset($session_account['balance'])) {
            $amount = $amount + $session_account['balance'];
        }
        $this->account = ['id' => $destination, 'balance' => $amount];
        
        Session::put('account', $this->account);

        return ['destination' => $this->account];
    }
}
