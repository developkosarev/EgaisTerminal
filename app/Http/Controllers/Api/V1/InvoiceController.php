<?php

namespace App\Http\Controllers\api\v1;

use App\Models\Invoice\Invoice;
use App\Models\Invoice\InvoiceLine;
use App\Models\Invoice\InvoiceMarkLine;
use App\Department;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //GET
        $invoice = Invoice::with('invoiceLines')
            ->orderBy('number', 'desc')
            ->paginate(50);

        return $invoice;
    }

    public function indexLic()
    {
        //GET
        $invoice = Invoice::orderBy('number', 'desc')
            ->paginate(50);

        return $invoice;
    }

    public function indexReadLine()
    {
        $invoiceReadLine = InvoiceReadLine::where('savedin1c', '=', false)->orderBy('invoice_id')->get();

        return response()->json($invoiceReadLine);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //POST
        $newInvoice = $request->all();

        $department = null;
        if ($request->has('department_code')) {
            $department = Department::where('code', $request->get('department_code'))->first();
        }

        $invoice = Invoice::where('doc_id', $newInvoice['doc_id'])->first();
        if ($invoice == null) {
            $invoice = Invoice::add($newInvoice);
        } else {
            $invoice->edit($newInvoice);
        }
        if ($department != null) {$invoice->setDepartment($department->id);}
        $invoice->save();


        if (array_key_exists('lines', $newInvoice))
        {
            $invoice->deleteLines();
            $newInvoicesLines = $newInvoice['lines'];
            foreach ($newInvoicesLines as $line) {
                $invoice->addLines($line);
            }
        }

        if (array_key_exists('marklines', $newInvoice))
        {
            $invoice->deleteMarkLines();
            $newMarkInvoicesLines = $newInvoice['marklines'];
            foreach ($newMarkInvoicesLines as $line) {
                $invoice->addMarkLines($line);
            }
        }

        if (array_key_exists('packlines', $newInvoice))
        {
            $invoice->deletePackLines();
            $newPackInvoicesLines = $newInvoice['packlines'];
            foreach ($newPackInvoicesLines as $line) {
                $invoice->addPackLines($line);
            }
        }

        if (array_key_exists('palletlines', $newInvoice))
        {
            $invoice->deletePalletLines();
            $newPalletInvoicesLines = $newInvoice['palletlines'];
            foreach ($newPalletInvoicesLines as $line) {
                $invoice->addPalletLines($line);
            }
        }

        return $invoice;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //GET
        return Invoice::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invoice $invoice)
    {
        //PUT
        $newInvoice = $request->all();

        if (array_key_exists('marklines', $newInvoice))
        {
            $newMarkInvoicesLines = $newInvoice['marklines'];
            foreach ($newMarkInvoicesLines as $line) {
                $invoiceMarkLine = InvoiceMarkLine::where([['invoice_id', '=', $invoice->id],['mark_code', '=', $line['mark_code']]])->first();
                if ($invoiceMarkLine == null) {
                    $invoiceMarkLine = InvoiceMarkLine::add($line, $invoice);
                } else {
                    $invoiceMarkLine->edit($line);
                }

            }
        }

        return $invoice;
    }

    public function updateMarkLine(Request $request, $id)
    {
        //PUT

        $invoiceReadLine = InvoiceReadLine::findOrFail($id);
        $invoiceReadLine->savedin1c = true;
        $invoiceReadLine->save();

        return $invoiceReadLine;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        //DELETE
        //
    }
}
