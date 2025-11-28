<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

@foreach($orders as $salesOrder)

    {{-- Include your existing PDF template --}}
    @include('sales-orders.single-print', ['salesOrder' => $salesOrder])

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif

@endforeach

</body>
</html>
