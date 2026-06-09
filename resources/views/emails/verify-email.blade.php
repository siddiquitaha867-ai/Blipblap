<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome to BlipBlap</title>
</head>
<body style="margin:0;background:#eef5fb;color:#10213a;font-family:Arial,Helvetica,sans-serif;">
    @php
        $firstName = trim((string) ($user->first_name ?? ''));
        $displayName = $firstName !== '' ? $firstName : 'there';
    @endphp
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef5fb;padding:30px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #d9e8f3;border-radius:24px;overflow:hidden;box-shadow:0 22px 60px rgba(16,44,76,0.12);">
                    <tr>
                        <td style="background:#ffffff;padding:26px 30px 14px;text-align:center;">
                            <img src="{{ url('/images/blipblap/logo-inc.png') }}" alt="BlipBlap" width="170" style="display:block;width:170px;max-width:70%;height:auto;margin:0 auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#0e50ed;padding:34px 34px 36px;text-align:center;">
                            <p style="color:#bfe2ff;font-size:12px;font-weight:800;letter-spacing:.08em;margin:0 0 10px;text-transform:uppercase;">Welcome aboard</p>
                            <h1 style="color:#ffffff;font-size:36px;line-height:1.05;font-weight:500;margin:0 0 14px;">Your BlipBlap account is almost ready</h1>
                            <p style="color:#e8f4ff;font-size:16px;line-height:1.6;margin:0;">Hi {{ $displayName }}, verify your email so your eSIM orders, install details, and account updates stay linked safely.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 34px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7fbff;border:1px solid #dce9f5;border-radius:18px;">
                                <tr>
                                    <td style="padding:24px;text-align:center;">
                                        <p style="color:#5c6b7c;font-size:14px;line-height:1.6;margin:0 0 22px;">Tap the button below to confirm this email address and continue using your BlipBlap account.</p>
                                        <a href="{{ $verifyUrl }}" style="background:#0e50ed;border-radius:999px;color:#ffffff;display:inline-block;font-size:15px;font-weight:800;line-height:48px;min-width:210px;text-align:center;text-decoration:none;">Verify email</a>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:22px;">
                                <tr>
                                    <td style="background:#ffffff;border:1px solid #e2edf5;border-radius:16px;padding:18px;">
                                        <strong style="color:#10213a;display:block;font-size:15px;margin-bottom:6px;">What happens next?</strong>
                                        <p style="color:#5c6b7c;font-size:13px;line-height:1.6;margin:0;">After verification, your purchases will appear in My eSIMs, and your account profile will keep your contact details ready for faster checkout.</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#7a8694;font-size:12px;line-height:1.6;margin:24px 0 0;text-align:center;">If the button does not work, copy and paste this link into your browser:<br><a href="{{ $verifyUrl }}" style="color:#0e50ed;word-break:break-all;">{{ $verifyUrl }}</a></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f4f8fb;color:#758294;font-size:12px;line-height:1.6;padding:20px 30px;text-align:center;">
                            BlipBlap keeps your travel connection simple, fast, and ready before you fly.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
