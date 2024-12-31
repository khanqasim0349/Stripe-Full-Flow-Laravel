<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

</head>

<body class="antialiased">
    <h1>Success</h1>
    <p>Name: {{ $customer['name'] }}</p>
    <p>Email: {{ $customer['email'] }}</p>
    <p>Phone: {{ $customer['phone'] }}</p>
</body>

</html>
