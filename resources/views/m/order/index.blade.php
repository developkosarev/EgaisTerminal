@extends('layouts.mobile')

@section('content')
    <h6><a href="{{ route('m.home.index') }}" >0-Выход</a></h6>
    <p>Считайте номер документa</p>

    <form action="{{ action('m\OrderController@submitbarcode') }}" id="formInputBarCode" method="post">
        <input id="InputBarCode" name="BarCode" title="Barcode" size="23" />
        <input type="hidden" name="_token" value="{{ csrf_token() }}" >
        <input type="submit" value=".." />
    </form>

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



@endsection

@section('scripts')
    window.onload = setFocus;
    document.getElementById('InputBarCode').onpaste = setPasteInputBarCode;
@endsection
