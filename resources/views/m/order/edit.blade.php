@extends('layouts.mobile')

@section('content')
    <h5>Считайте штрихкод</h5>
    <h6><a href="{{ route('m.order') }}" >0-Выход</a></h6>

    <form action="{{ action('m\OrderController@submiteditbarcode', ['id' => $order->id]) }}" id="formInputBarCode" method="post">
        <input id="InputBarCode" name="BarCode" title="Barcode" size="22" />
        <input type="hidden" id="order_id" name="order_id" value="{{ $order->id }}" />
        <input type="hidden" name="_token" value="{{ csrf_token() }}" >
        <input type="submit" value=".." />
    </form>

    {{ isset($errorMessage) ? $errorMessage : '' }}

    @include('m.errors')

    <div> {{ $order->number  }} </div>

    <table class="table">
        <thead>
            <tr>
                <th>№</th>
                <th>Наименование</th>
                <th>Зак</th>
                <th>Наб</th>
            </tr>
        </thead>
        <tbody>

        @foreach ($order->orderlines as $item)
            @if ($item->quantity != $item->quantitymarks)
                <tr>
                    <td>
                        {{$item->orderlineid}}
                    </td>
                    <td class="tddescr">
                        {{$item->productdescr}}
                        <h6>
                            {{$item->f2regid}}
                        </h6>
                    </td>
                    <td>
                        {{$item->quantity}}
                    </td>
                    <td>
                        {{$item->quantitymarks}}
                    </td>
                </tr>
            @endif
        @endforeach

        </tbody>
    </table>

@endsection

@section('scripts')

@endsection

