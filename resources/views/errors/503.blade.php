<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#181818">
    <title>Sedang Ngopi | SPMB MARSA</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            background: #181818;
            color: #e5e5e5;
            font-family: Arial, Helvetica, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 740px;
            padding: 24px 20px 20px;
            background: #303030;
            border-radius: 14px;
            text-align: center;
        }

        .brand {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 9px;
            font-size: 16px;
            font-weight: 700;
        }

        .brand svg,
        .coffee svg {
            color: #00b85a;
        }

        .status {
            display: inline-block;
            margin-top: 16px;
            padding: 4px 10px;
            border-radius: 20px;
            background: #454545;
            color: #dedede;
            font-size: 12px;
            font-weight: 600;
        }

        .coffee {
            margin: 16px 0;
        }

        h1 {
            margin: 0 0 14px;
            font-size: clamp(20px, 4vw, 23px);
            line-height: 1.4;
        }

        .message {
            margin: 0 0 16px;
            color: #c5c5c5;
            font-size: 14px;
            line-height: 1.8;
        }

        .notice {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 17px 14px;
            border-radius: 13px;
            background: #202020;
            color: #c5c5c5;
            font-size: 12px;
            line-height: 1.5;
        }

        .retry {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 12px;
            padding: 15px 21px;
            border: 0;
            border-radius: 13px;
            background: #07853e;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }

        .retry:hover {
            background: #069447;
        }

        .retry:focus-visible {
            outline: 3px solid #8ee8b1;
            outline-offset: 3px;
        }

        footer {
            margin-top: 15px;
            color: #c5c5c5;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .card { padding: 28px 16px; }
            .notice { align-items: flex-start; }
            .message { font-size: 14px; }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">
            <svg width="21" height="21" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="2" aria-hidden="true">
                <path d="m2 10 10-5 10 5-10 5-10-5Z"/>
                <path d="M6 12v5c3.5 3 8.5 3 12 0v-5"/>
                <path d="M22 10v6"/>
            </svg>
            <span>SPMB MARSA</span>
        </div>

        <div class="status">Sedang Dalam Pemeliharaan</div>

        <div class="coffee" aria-hidden="true">
            <svg width="34" height="34" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round">
                <path d="M4 9h13v7a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5V9Z"/>
                <path d="M17 10h2a3 3 0 0 1 0 6h-2"/>
                <path d="M8 3v3M12 3v3M16 3v3"/>
            </svg>
        </div>

        <h1>Sebentar, Mas Etmint Lagi Ngopi ☕</h1>

        <p class="message">
            Mas Etmint sedang istirahat ngopi dulu demi menjaga
            kesehatan mental dan untuk meningkatkan kualitas layanan.
        </p>

        <div class="notice">
            <span aria-hidden="true">ⓘ</span>
            <span>
                Website akan kembali tersedia setelah
                pemeliharaan selesai.
            </span>
        </div>

        <button class="retry" type="button"
                onclick="window.location.reload()">
            <span aria-hidden="true">⟳</span>
            Coba Lagi
        </button>

        <footer>SMK Ma'arif 9 Kebumen</footer>
    </main>
</body>
</html>
