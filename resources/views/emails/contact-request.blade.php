<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New BlipBlap contact request</title>
</head>
<body style="margin:0;background:#eef5fb;color:#10213a;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef5fb;padding:28px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #d9e8f3;border-radius:22px;overflow:hidden;">
                    <tr>
                        <td style="background:#0e50ed;padding:28px 30px;">
                            <p style="color:#bfe2ff;font-size:12px;font-weight:800;letter-spacing:.08em;margin:0 0 8px;text-transform:uppercase;">Contact request</p>
                            <h1 style="color:#ffffff;font-size:30px;line-height:1.12;margin:0;">{{ $data['topic'] }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 30px;">
                            <p><strong>Name:</strong> {{ $data['name'] }}</p>
                            <p><strong>Email:</strong> {{ $data['email'] }}</p>
                            <p><strong>Order reference:</strong> {{ $data['order_reference'] ?: 'Not provided' }}</p>
                            <p style="margin-top:24px;"><strong>Message</strong></p>
                            <p style="white-space:pre-line;line-height:1.6;">{{ $data['message'] }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
