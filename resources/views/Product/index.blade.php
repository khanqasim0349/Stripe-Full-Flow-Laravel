<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>


</head>

<body class="antialiased">
    <div style="display: flex; gap: 3rem">
        @foreach ($products as $product)
            <div class="flex: 1">
                <img src="{{ $product->image }}" alt="Error loading" style="max-width: 100%">
                <h2>Name: {{ $product->name }}</h2>
                <p>Price: {{ $product->price }}</p>
            </div>
        @endforeach
    </div>
    <div>
        <p>
            <form action="{{route('checkout')}}" method="POST">
                @csrf
                <button>Checkout</button>
            </form>
        </p>
    </div>
</body>

</html>
