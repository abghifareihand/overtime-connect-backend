<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password OTP</title>
</head>

<body>
    <p>Halo <strong>{{ $username }}</strong>,</p>
    <p>Gunakan kode OTP berikut untuk mereset password Anda:</p>
    <h1>{{ $otp }}</h1>
    <p>Jangan bagikan kode ini kepada siapa pun. Kode berlaku selama 10 menit.</p>
    <p>Terima kasih.</p>
</body>

</html>
