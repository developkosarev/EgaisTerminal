<?php

namespace App\Http\Controllers\m;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        $action1 = '1';

        if ($request->has('barcode')) {
            $action1 = $request->get('barcode');
        }

        return view('m\index', ["useragent" => $useragent, "action1" => $action1]);
    }

    public function about()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        return view('m\about', ["useragent" => $useragent]);
    }

    public function submitbarcode(Request $request)
    {
        $barcode = "";
        if ($request->has('BarCode')) {
            $barcode = $request->get('BarCode');
        }

        switch($barcode)
        {
            case '1':
                return redirect()->action('m\OrderController@index');
                break;
            default:
                return redirect()->action('m\ReadBarCodeController@index');
                break;
        }

        return redirect()->action('m\HomeController@index');
    }
}
