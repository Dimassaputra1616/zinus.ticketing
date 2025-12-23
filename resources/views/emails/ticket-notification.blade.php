<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tiket Baru Masuk</title>
</head>
<body>
    <h2>🚨 Tiket Baru Masuk!</h2>
    <p>Ada tiket baru yang dikirim oleh <b>{{ $ticket->pelapor }}</b>.</p>

    <p>
        <b>Judul:</b> {{ $ticket->judul }} <br>
        <b>Email:</b> {{ $ticket->email }} <br>
        <b>Kategori:</b> {{ $ticket->kategori }} <br>
        <b>Prioritas:</b> {{ $ticket->prioritas }} <br>
        <b>Status:</b> {{ ucfirst($ticket->status) }}
    </p>

    @if($ticket->deskripsi)
        <p><b>Deskripsi:</b></p>
        <blockquote>{{ $ticket->deskripsi }}</blockquote>
    @endif

    <p>
        📅 <b>Dikirim pada:</b> {{ $ticket->created_at->format('d M Y H:i') }}
    </p>

    <hr>
    <p>
        Klik link berikut untuk melihat tiket di dashboard admin:<br>
        <a href="{{ url('/dashboard') }}">{{ url('/dashboard') }}</a>
    </p>

    <hr>
    <small>Pesan otomatis dari sistem IT Ticketing Zinus Dream Tangerang</small>
</body>
</html>
