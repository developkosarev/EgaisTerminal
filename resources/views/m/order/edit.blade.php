@extends('layouts.mobile')

@section('content')
    <h6><a href="{{ route('m.order.index') }}" >0-Выход</a></h6>
    <p>Считайте штрихкод</p>

    <form action="{{ action('m\OrderController@submiteditbarcode') }}" id="formInputBarCode" method="post">
        <input id="InputBarCode" name="BarCode" title="Barcode" size="23" />
        <input type="hidden" id="order_id" name="order_id" value="{{ $order->id }}" />
        <input type="hidden" name="_token" value="{{ csrf_token() }}" >
        <input type="submit" value=".." />
    </form>

    <div> {{ $order->number  }} </div>

    <table class="table">
        <thead>
        <tr>
            <th>
                №
            </th>
            <th>
                Наименование
            </th>
            <th>
                Зак
            </th>
            <th>
                Наб
            </th>
        </tr>
        </thead>
        <tbody>

        @foreach ($order->orderlines as $item)
            <tr>
                <td>
                    {{$item->orderlineid}}
                </td>
                <td class="tddescr">
                    {{$item->productdescr}}
                </td>
                <td>
                    {{$item->quantity}}
                </td>
                <td>
                    {{$item->quantitymarks}}
                </td>
            </tr>
        @endforeach

        </tbody>
    </table>

@endsection

@section('scripts')
    window.onload = setFocus;
    document.getElementById('InputBarCode').onpaste = setPasteInputBarCode;
@endsection

