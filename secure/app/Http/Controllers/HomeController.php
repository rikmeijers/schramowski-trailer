<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function index()
    {
        return auth()->check()
            ? redirect()->route('dashboard')
            : redirect()->route('login.form');
    }
}
