<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your BlipBlap eSIM top-up is active</title>
</head>
<body style="margin:0;background:#eef5fb;color:#10213a;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef5fb;padding:30px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #d9e8f3;border-radius:24px;overflow:hidden;box-shadow:0 22px 60px rgba(16,44,76,0.12);">
                    <tr>
                        <td style="background:#ffffff;padding:24px 30px 12px;text-align:center;">
                            <img src="{{ url('/images/blipblap/logo-inc.png') }}" alt="BlipBlap" width="176" style="display:block;width:176px;max-width:70%;height:auto;margin:0 auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#0e50ed;padding:34px;text-align:center;">
                            <p style="color:#bfe2ff;font-size:12px;font-weight:800;letter-spacing:.08em;margin:0 0 10px;text-transform:uppercase;">Top-up active</p>
                            <h1 style="color:#ffffff;font-size:36px;line-height:1.05;font-weight:500;margin:0 0 14px;">Your data top-up has been applied</h1>
                            <p style="color:#e8f4ff;font-size:16px;line-height:1.6;margin:0;">The selected package was added to your existing BlipBlap eSIM. You do not need a new QR code.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 34px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#10213a;border-radius:20px;">
                                <tr>
                                    <td style="padding:22px;">
                                        <p style="color:#8fd1ff;font-size:12px;font-weight:800;letter-spacing:.08em;margin:0 0 12px;text-transform:uppercase;">Top-up summary</p>
                                        <p style="color:#ffffff;font-size:18px;line-height:1.35;margin:0 0 18px;">{{ $plan?->title ?? $order->bundle_code }}</p>
                                        <p style="color:#b8c5d5;font-size:12px;font-weight:800;margin:0 0 5px;">ICCID</p>
                                        <p style="color:#ffffff;font-size:14px;line-height:1.45;margin:0 0 14px;word-break:break-word;">{{ $esim->iccid }}</p>
                                        <p style="color:#b8c5d5;font-size:12px;font-weight:800;margin:0 0 5px;">Order reference</p>
                                        <p style="color:#ffffff;font-size:14px;line-height:1.45;margin:0;word-break:break-word;">{{ $order->order_reference }}</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#334155;font-size:15px;line-height:1.7;margin:24px 0 0;">If your eSIM is already installed, keep using the same BlipBlap line. The top-up will be available on that existing eSIM once the provider finishes activation.</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;">
                                <tr>
                                    <td style="text-align:center;">
                                        <a href="{{ url('/my-esims') }}" style="background:#0e50ed;border-radius:999px;color:#ffffff;display:inline-block;font-size:15px;font-weight:800;line-height:48px;min-width:190px;text-align:center;text-decoration:none;">Open My eSIMs</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f4f8fb;color:#758294;font-size:12px;line-height:1.6;padding:20px 30px;text-align:center;">
                            Need help? Reply to this email or open your BlipBlap account.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
