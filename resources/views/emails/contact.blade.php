<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us Inquiry</title>
    <style>
        :root {
            --gohub-primary: #6f42c1;
            --gohub-secondary: #D032D0;
            --gohub-success: #7ed321;
            --gohub-info: #1C16AF;
            --gohub-warning: #f37f29;
            --gohub-danger: #d0021b;
            --gohub-light: #F5F2FC;
            --gohub-dark: #1c1c1c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gohub-light);
            color: var(--gohub-dark);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        h1 {
            font-size: 28px;
            color: var(--gohub-primary);
            margin-bottom: 20px;
        }

        p {
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .info {
            font-weight: bold;
            color: var(--gohub-primary);
        }

        .message {
            white-space: pre-wrap;
            background-color: var(--gohub-light);
            padding: 15px;
            border-radius: 5px;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }

        .footer {
            font-size: 12px;
            color: #888;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Contact Us Inquiry</h1>
        <div class="card">
            <p><span class="info">Name:</span> {{ $content['name'] }}</p>
            <p><span class="info">Email:</span> {{ $content['email'] }}</p>
            <p><span class="info">Message:</span></p>
            <p class="message">{{ $content['message'] }}</p>
        </div>
        <hr>
        <p>Sent from your website contact form.</p>
        <p class="footer">This email was sent from your website's contact form.</p>
    </div>
</body>

</html>
