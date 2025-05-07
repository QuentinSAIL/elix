<?php

namespace App\Http\Controllers;

use App\Services\GoCardlessDataService;
use Illuminate\Http\Request;

class BankDataController extends Controller
{
    public function showAccounts(Request $request, GoCardlessDataService $gocardless)
    {
        $accounts = $request->user()->accounts;
        dd($gocardless);
        foreach ($accounts as $account) {
            $account->updateFromGocardless($gocardless);
        }
    }
}
