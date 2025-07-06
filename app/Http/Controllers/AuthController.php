<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login()
    {
        return view('login'); // create a login.blade.php view
    }

    public function doLogin(Request $request)
    {
        $password = $request->input('password');

        if ($password === '36902003') {
            session(['password' => $password]);
            return redirect('/gallery');
        }

        return redirect('/login')->with('error', 'Invalid password');
    }

    public function gallery()
    {
        return view('gallery'); // create a gallery.blade.php view
    }
}
