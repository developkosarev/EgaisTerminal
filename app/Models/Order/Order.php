<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

use App\ExciseStamp;
use App\ExciseStampBox;

class Order extends Model
{
    protected $fillable = ['date', 'number', 'barcode', 'status',
        'Quantity', 'QuantityMarks', 'DocType', 'DocId'];

    public function orderlines(){
        return $this->hasMany("App\Models\Order\OrderLine");
    }

    public function ordermarklines(){
        return $this->hasMany("App\Models\Order\OrderMarkLine");
    }

    public function orderpacklines(){
        return $this->hasMany("App\Models\Order\OrderPackLine");
    }

    public function ordererrorlines(){
        return $this->hasMany("App\Models\Order\OrderErrorLine");
    }


    public static function add($fields)
    {
        $order = new static;
        $order->fill($fields);
        $order->DocType = $fields['doc_type'];
        $order->DocId   = $fields['doc_id'];

        return $order;
    }

    public function edit($fields)
    {
        $this->fill($fields);
        $this->DocType = $fields['doc_type'];
        $this->DocId   = $fields['doc_id'];

        return $this;
    }

    public function addBarCode($barcode)
    {
        if (strlen($barcode) == 26) {
            $result = $this->addPackExciseStamp($barcode);
        } else {
            $result = $this->addExciseStamp($barcode);
        }

        return $result;
    }

    private function addErrorLine($barcode, $product_code, $f2reg_id, $errorMessage)
    {
        $orderErrorLine = new OrderErrorLine;
        $orderErrorLine->order_id     = $this->id;
        $orderErrorLine->markcode     = $barcode;
        $orderErrorLine->message      = $errorMessage;
        $orderErrorLine->product_code = $product_code;
        $orderErrorLine->f2reg_id     = $f2reg_id;
        $orderErrorLine->save();

        return $orderErrorLine;
    }

    private function addExciseStamp($barcode)
    {
        $errorBarCode = false;
        $errorMessage = "";

        //СКАНИРОВАНИЕ АКЦИЗНОЙ МАРКИ
        //101100261679680118001D5CCFC794963898C1B13E41231CKY42T7UDIJJY2AWLHS7HPGINLMY7PQPDNJALVS42WNCHYRCO257SPCSCF4ASM37BZNTLIASYRVGFUTCXDXDJPML5MMVLEEHZWPWJVI
        if (strlen($barcode) != 150 and strlen($barcode) != 68)
        {
            $errorMessage = "Не опознан ШК " . $barcode;

            $this->addErrorLine($barcode, '', '', $errorMessage);
            return ['error' => true, 'errorMessage' => $errorMessage ];
        }

        //1. Ищем марку в классификаторе акцизных марок
        $exciseStamp = ExciseStamp::find($barcode);
        if ($exciseStamp == null)
        {
            $errorMessage = "Не найдена марка в БД " . $barcode;

            $this->addErrorLine($barcode, '','', $errorMessage);
            return ['error' => true, 'errorMessage' => $errorMessage ];
        }

        //2. Ищем штрих код в уже набранных товарах в заказе, если находим ошибка.
        $orderMarkLine = OrderMarkLine::where( [['order_id', '=', $this->id],['markcode', '=', $barcode]] )->first();
        if ($orderMarkLine != null)
        {
            $errorMessage = "Товар уже сканировался " . $barcode;

            $this->addErrorLine($barcode, $orderMarkLine->productcode, $orderMarkLine->f2regid, $errorMessage);
            return ['error' => true, 'errorMessage' => $errorMessage ];
        }

        //3. Ищем марку в уже сканированных заказах, за 3 дня
        //$created_at = Carbon::now()->subDays(3);
        //["created_at", ">", $created_at]

        $orderMarkLine = OrderMarkLine::where([['markcode',   '=', $barcode],
                                               ['quantity',   '=', '1'],
                                               ["f2regid",    "=", $exciseStamp->f2regid]
        ])->first();
        if ($orderMarkLine != null)
        {
            $errorMessage = "Товар уже сканировался " . $barcode;

            $this->addErrorLine($barcode, $orderMarkLine->productcode, $orderMarkLine->f2regid, $errorMessage);
            return ['error' => true, 'errorMessage' => $errorMessage ];
        }


        //4. Ищем строку в заказе которая соответствует этой марке
        $orderLine = OrderLine::where([["order_id",   "=", $this->id],
                                       ["productcode","=", $exciseStamp->productcode],
                                       ["f2regid",    "=", $exciseStamp->f2regid]
        ])->first();
        if ($orderLine == null)
        {
            $errorMessage = "Раздел Б " . $exciseStamp->f2regid . " не найден в заказе " . $barcode;

            $this->addErrorLine($barcode, $exciseStamp->productcode, $exciseStamp->f2regid, $errorMessage);
            return ['error' => true, 'errorMessage' => $errorMessage ];
        }

        //6. Количество отсканированных марок превышено
        if ($orderLine->quantity <= $orderLine->quantitymarks ) {
            $errorMessage = "Превышено количество в наборе " . $barcode;

            $this->addErrorLine($barcode, $exciseStamp->productcode, $exciseStamp->f2regid, $errorMessage);
            return ['error' => true, 'errorMessage' => $errorMessage ];
        }

        //7. Обнуление showFirst  у заказа
        $this->orderlines->each(function ($item, $key) {
            if ($item->showfirst) {
                $item->showfirst = false;
                $item->save();
            }
        });

        DB::beginTransaction();

        try{
            $orderMarkLine = new OrderMarkLine;
            $orderMarkLine->order_id    = $this->id;
            $orderMarkLine->orderlineid = $orderLine->orderlineid;
            $orderMarkLine->productcode = $exciseStamp->productcode;
            $orderMarkLine->f2regid     = $exciseStamp->f2regid;
            $orderMarkLine->markcode    = $barcode;
            $orderMarkLine->boxnumber   = "000000000000000000000";
            $orderMarkLine->quantity    = 1;
            $orderMarkLine->savedin1c   = false;
            $orderMarkLine->save();

            $orderLine->quantitymarks = $orderLine->quantitymarks + 1;
            $orderLine->showfirst = true;
            $orderLine->save();

            DB::commit();

        } catch(\Exception $exception){
            $errorBarCode = true;
            $errorMessage = "Ошибка при записи " . $barcode;

            DB::rollback();
        }


        if ($errorBarCode && strlen($barcode) > 0) {
            $this->addErrorLine($barcode,'', '', $errorMessage);

            return ['error' => $errorBarCode, 'errorMessage' => $errorMessage ];
        }

        return ['error' => false, 'errorMessage' => ''];
    }

    private function addPackExciseStamp($barcode)
    {
        $errorBarCode = false;
        $errorMessage = "";

        //СКАНИРОВАНИЕ ЯЩИКА
        if (strlen($barcode) != 26) {
            $errorMessage = "Не опознан ШК ящика " . $barcode;

            $this->addErrorLine($barcode, '', '', $errorMessage);
            return ['error' => true, 'errorMessage' => $errorMessage ];
        }

        //1.
        $exciseStampBox = ExciseStampBox::where('barcode', '=', $barcode)->first();
        if ($exciseStampBox == null) {
            $errorMessage = "Не опознан ШК ящика " . $barcode;

            $this->addErrorLine($barcode, '', '', $errorMessage);
            return ['error' => true, 'errorMessage' => $errorMessage ];
        }

        //2. Ищем штрих код ящика в уже набранных ящиках, если находим ошибка.
        //$created_at = Carbon::now()->subDays(3);
        //Уникальный индекс
        //$orderPackLine = OrderPackLine::where([['markcode', '=', $barcode],
        //                                       ["created_at", ">", $created_at]

        $orderPackLine = OrderPackLine::where('markcode', '=', $barcode)->first();
        if ($orderPackLine != null) {
            $errorMessage = "Ящик уже сканировался " . $barcode;

            $this->addErrorLine($barcode, $orderPackLine->productcode, $orderPackLine->f2regid, $errorMessage);
            return ['error' => true, 'errorMessage' => $errorMessage ];
        }


        DB::beginTransaction();

        $order = Order::find($this->id);
        $order->orderlines;
        $order->ordermarklines;

        $lines = $exciseStampBox->excisestampboxlines;
        foreach ($lines as $line){
            $exciseStamp = ExciseStamp::find($line->markcode);

            //1. Ищем штрих код в уже набранных товарах, если находим ошибка.
            // ошибки из-за пересортицы
            //$created_at = Carbon::now()->subDays(3);
            //["created_at", ">", $created_at]

            $orderMarkLine = OrderMarkLine::where([['markcode', '=', $line->markcode],
                                                   ['quantity', '=', '1'],
                                                   ["f2regid",  "=", $exciseStamp->f2regid]
            ])->first();
            if ($orderMarkLine != null) {
                $errorBarCode = true;
                $errorMessage = "Товар уже сканировался " . $line->markcode;

                $this->addErrorLine($barcode, $orderMarkLine->productcode, $orderMarkLine->f2regid, $errorMessage);

                break;
            }

            //2. Ищем товар в строке заказов
            $orderLine = null;
            foreach ($order->orderlines as $item){
                if ($item->productcode == $exciseStamp->productcode and $item->f2regid == $exciseStamp->f2regid){
                    $orderLine = $item;
                    break;
                }
            }

            if ($orderLine == null)
            {
                $errorBarCode = true;
                $errorMessage = "Разд Б " . $exciseStamp->f2regid . " не зайден в заказе " . $line->markcode;

                $this->addErrorLine($barcode, $exciseStamp->productcode, $exciseStamp->f2regid, $errorMessage);

                break;
            }

            if ($orderLine->quantity <= $orderLine->quantitymarks ) {
                $errorBarCode = true;
                $errorMessage = "Сканирование ящика. Превышено количество в наборе " . $line->markcode;

                $this->addErrorLine($barcode, $exciseStamp->productcode, $exciseStamp->f2regid, $errorMessage);

                break;
            }

            $orderMarkLine = new OrderMarkLine;
            $orderMarkLine->order_id    = $this->id;
            $orderMarkLine->orderlineid = $orderLine->orderlineid;
            $orderMarkLine->productcode = $exciseStamp->productcode;
            $orderMarkLine->f2regid     = $exciseStamp->f2regid;
            $orderMarkLine->markcode    = $line->markcode;
            $orderMarkLine->boxnumber   = $barcode;
            $orderMarkLine->quantity    = 1;
            $orderMarkLine->savedin1c   = false;
            $orderMarkLine->save();

            $orderLine->quantitymarks = $orderLine->quantitymarks + 1;
            $orderLine->showfirst = true;
            $orderLine->save();
        }

        //Запись ящика
        if (!$errorBarCode)
        {
            $orderPackLine = new OrderPackLine;
            $orderPackLine->order_id    = $this->id;
            $orderPackLine->orderlineid = 1;
            $orderPackLine->productcode = $exciseStampBox->productcode;
            $orderPackLine->f2regid     = $exciseStampBox->f2regid;
            $orderPackLine->markcode    = $barcode;
            $orderPackLine->quantity    = 1;
            $orderPackLine->savedin1c   = false;
            $orderPackLine->save();

            DB::commit();
        }
        else
        {
            DB::rollBack();
        }




        if ($errorBarCode && strlen($barcode) > 0) {
            $this->addErrorLine($barcode,'', '', $errorMessage);

            return ['error' => $errorBarCode, 'errorMessage' => $errorMessage ];
        }

        return ['error' => false, 'errorMessage' => ''];
    }


    public function removeLineF2RegId($f2regid)
    {
        if($f2regid == null) { return; }

        OrderPackLine::where([ ['order_id','=',$this->id], ['f2regid','=',$f2regid] ])->delete();
        $deletedRows = OrderMarkLine::where([ ['order_id','=',$this->id], ['f2regid','=',$f2regid] ])->delete();

        return $deletedRows;
    }
}