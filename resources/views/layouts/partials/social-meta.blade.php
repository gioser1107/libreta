@php
    $shareTitle = config('libreta.company');
    $shareDescription = config('libreta.description');
    $shareUrl = url('/');
    $shareImage = asset('og.png');
@endphp

<meta name="description" content="{{ $shareDescription }}">
<meta property="og:type" content="website">
<meta property="og:locale" content="es_VE">
<meta property="og:site_name" content="{{ $shareTitle }}">
<meta property="og:title" content="{{ $shareTitle }}">
<meta property="og:description" content="{{ $shareDescription }}">
<meta property="og:url" content="{{ $shareUrl }}">
<meta property="og:image" content="{{ $shareImage }}">
<meta property="og:image:secure_url" content="{{ $shareImage }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $shareTitle }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $shareTitle }}">
<meta name="twitter:description" content="{{ $shareDescription }}">
<meta name="twitter:image" content="{{ $shareImage }}">
