<?php

namespace App\Models\Invoice;

use Illuminate\Database\Eloquent\Model;

class InvoicePackLine extends Model
{
    protected $table = 'doc_invoice_pack_line';

    protected $fillable = ['line_id', 'line_identifier', 'pack_number', 'pallet_number'];

    public function invoice(){
        return $this->belongsTo("App\Models\Invoice\Invoice");
    }
}
