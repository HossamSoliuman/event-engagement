<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role;
        if ($role == 'admin') {
            return 'admin';
        } else if ($role == 'user')
            return 'user';
    }
}
