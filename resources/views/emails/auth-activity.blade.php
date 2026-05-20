<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $activity }} akun</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.6;">
    <h1 style="font-size: 20px; margin-bottom: 16px;">{{ $activity }} berhasil</h1>

    <p>Halo {{ $userName }},</p>

    <p>
        Akun Anda baru saja melakukan {{ strtolower($activity) }} pada
        {{ $occurredAt->timezone(config('app.timezone'))->format('d M Y H:i') }}.
    </p>

    <table cellpadding="6" cellspacing="0" style="border-collapse: collapse; margin-top: 16px;">
        <tr>
            <td style="font-weight: bold;">IP address</td>
            <td>{{ $ipAddress }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Perangkat</td>
            <td>{{ $userAgent ?: 'Unknown' }}</td>
        </tr>
    </table>

    <p style="margin-top: 20px;">
        Jika aktivitas ini bukan Anda, segera ubah password akun Anda.
    </p>

    <p>Terima kasih,<br>{{ config('app.name') }}</p>
</body>
</html>
