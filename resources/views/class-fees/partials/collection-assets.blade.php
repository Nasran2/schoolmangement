@once
    @php
        $collectionJs = public_path('js/fee-collection.js');
        $collectionCss = public_path('css/fee-collection.css');
    @endphp
    <link rel="stylesheet" href="{{ asset('css/fee-collection.css') }}?v={{ is_file($collectionCss) ? filemtime($collectionCss) : '20261023' }}">
    <script src="{{ asset('js/fee-collection.js') }}?v={{ is_file($collectionJs) ? filemtime($collectionJs) : '20261023' }}"></script>
@endonce
