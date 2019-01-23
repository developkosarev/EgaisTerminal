@extends('layouts.mobile')

@section('content')
    <div>Отгрузка товара</div>
    <form action="{{ action('m\OrderController@submitbarcode') }}" id="formInputBarCode" method="post">
        <input id="InputBarCode" name="BarCode" title="Barcode" size="22" />
        <input type="hidden" name="_token" value="{{ csrf_token() }}" >
        <input type="submit" value=".." />
    </form>
    <h6><a href="{{ route('m.home.index') }}" >0-Выход</a></h6>

    {{ isset($barcode) ? $barcode : '0' }}

    <h5>Документы набора</h5>

    <table class="table">
        <thead>
        <tr>
            <th>
                Номер
            </th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($order as $item)
            <tr>
                <td>
                    {{$item->number}}
                </td>
                <td>
                    <a href="{{ route('m.order.edit', ['id' => $item->id]) }}" >Собрать</a>
                </td>
            </tr>
        @endforeach

        </tbody>
    </table>

    {{ $order->links("pagination.m-simple") }}

@endsection

@section('scripts')
@endsection

