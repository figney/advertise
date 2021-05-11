<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $title }}</title>
    <meta property="fb:app_id" content="{{ $app_id }}">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $site_name }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image:url" content="{{ $image_url }}">


    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image_url }}">

</head>

<body>
<div class="main">
    <div class="content">

    </div>
</div>
<script>

    let goUrl = "{!!  $go_url !!}"


    location.href = goUrl + (goUrl.includes('?') ? '&' : '?') + "lf=" + encodeURIComponent(location.href)

</script>

</body>

</html>
