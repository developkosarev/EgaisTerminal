<?php

namespace App\Http\Controllers\m;

use Carbon\Carbon;
use App\Models\Inventory\Inventory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $inventory = Inventory::orderBy("number", 'desc')->simplePaginate(4);

        $barcode = $request->input('barcode', '');

        return view('m/inventory/index', ['inventory' => $inventory, 'barcode' => $barcode]);
    }

    public function create(Request $request)
    {
        $date = Carbon::now();
        $number = '1';

        return view('m/inventory/create', ['date' => $date, 'number' => $number]);
    }

    public function store(Request $request)
    {
        //$this->validate($request, [
        //    'title'	=>	'required' //РѕР±СЏР·Р°С‚РµР»СЊРЅРѕ
        //]);

        Inventory::add($request->all());
        return redirect()->route('m.inventory');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Inventory\Inventory  $inventory
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $inventory = Inventory::find($id);
        if ($inventory == null)
        {
            return redirect()->action('m\InventoryController@index');
        }
        //$returnedInvoice->returnedInvoicelines;
        //$returnedInvoice->returnedInvoicelines = $returnedInvoice->returnedInvoicelines->sortBy('lineid')->sortByDesc('show_first');

        $errorMessage = '';
        if ($request->has('errorMessage')) {
            $errorMessage = $request->get('errorMessage');
        }

        return view('m/inventory/edit', ['inventory' => $inventory, 'errorMessage' => $errorMessage]);
    }

    public function submitbarcode(Request $request)
    {
        $barcode = $request->input('BarCode', '');

        if ($barcode == '0') {
            return redirect()->action('m\HomeController@index');
        } else if ($barcode == '1') {
            return redirect()->action('m\HomeController@create');
        }

        $this->validate($request,
            ['BarCode'	=>
                ['required',
                    'min:9',
                    'max:12',
                ]
            ]
            ,
            ['BarCode.required' => 'Заполните ШК',
                'BarCode.min'      => 'ШК минимум 9 символов',
                'BarCode.max'      => 'ШК максимум 12 символов'
            ]
        );

        if (strlen($barcode) > 8 and strlen($barcode) < 13) {
            $barcode = str_replace("*", "", $barcode);
        }

        /*
        $inventory = Inventory::where('barcode', '=', $barcode)->first();
        if ($inventory != null) {
            return redirect()->action('m\InventoryController@edit', ['id' => $inventory->id]);
        } else {
            return redirect()->back()->withErrors(['BarCode' => 'Не найдена инвентаризация № ' . $barcode]);
        }
        */
    }

    public function submiteditbarcode(Request $request)
    {
        $barcode  = $request->input('BarCode', '');
        $inventory_id = $request->input('inventory_id', '');

        if ($barcode == '0') {
            return redirect()->action('m\InventoryController@index');
        }

        $this->validate($request,
            ['BarCode'	=>
                ['required',
                    'min:9',
                    'max:150',
                ]
            ]
            ,
            ['BarCode.required' => 'Заполните ШК',
                'BarCode.min'      => 'ШК минимум 9 символов',
                'BarCode.max'      => 'ШК максимум 150 символов'
            ]
        );

        /*

        $order = Order::find($order_id);
        $result = $order->addBarCode($barcode);

        $errorBarCode = $result['error'];
        $errorMessage = $result['errorMessage'];

        if ($errorBarCode) {
            //return redirect()->action('m\OrderController@edit', ['id' => $order_id, 'errorMessage' => $errorMessage ]);
            return redirect()->back()->withErrors(['errorMessage' => $errorMessage]);
        }

        return redirect()->action('m\OrderController@edit', ['id' => $order_id]);

        */
    }

}