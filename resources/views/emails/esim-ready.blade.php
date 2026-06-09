<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your BlipBlap eSIM is ready</title>
</head>
<body style="margin:0;background:#eef5fb;color:#10213a;font-family:Arial,Helvetica,sans-serif;">
    @php
        $config = $contentConfig ?? [];
        $steps = $config['steps'] ?? [
            'Connect your phone to Wi-Fi before installing.',
            'Open your phone camera or go to cellular/mobile data settings and choose add eSIM.',
            'Scan the QR code in this email.',
            'Follow the phone prompts, then name the line BlipBlap if asked.',
            'When you reach your destination, turn on this eSIM for mobile data and enable data roaming for the BlipBlap line.',
        ];
        $heading = $config['heading'] ?? 'Your eSIM is ready to install';
        $intro = $config['intro'] ?? 'Thanks for choosing BlipBlap. Your travel data plan is ready. Scan the QR code below and follow the steps to connect.';
        $manualHeading = $config['manual_heading'] ?? 'Manual install details';
        $manualIntro = $config['manual_intro'] ?? 'Use these only if your phone asks for manual eSIM setup instead of QR scanning.';
        $footer = $config['footer'] ?? 'Keep this email safe until your trip is complete. If installation is interrupted, open your BlipBlap account and check the same install details there.';
        $iosLabel = $config['ios_label'] ?? 'Open Apple install link';
        $androidLabel = $config['android_label'] ?? 'Open Android install link';
        $iosInstallUrl = data_get($esim->install_details, 'assignment.iosInstallUrl') ?: data_get($esim->install_details, 'response.iosInstallUrl');
        $androidInstallUrl = data_get($esim->install_details, 'assignment.androidInstallUrl') ?: data_get($esim->install_details, 'response.androidInstallUrl');
    @endphp
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef5fb;padding:30px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border:1px solid #d9e8f3;border-radius:24px;overflow:hidden;box-shadow:0 22px 60px rgba(16,44,76,0.12);">
                    <tr>
                        <td style="background:#ffffff;padding:24px 30px 12px;text-align:center;">
                            <img src="{{ url('/images/blipblap/logo-inc.png') }}" alt="BlipBlap" width="176" style="display:block;width:176px;max-width:70%;height:auto;margin:0 auto;">
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#0e50ed;padding:34px 34px 38px;text-align:center;">
                            <p style="color:#bfe2ff;font-size:12px;font-weight:800;letter-spacing:.08em;margin:0 0 10px;text-transform:uppercase;">Ready to connect</p>
                            <h1 style="color:#ffffff;font-size:38px;line-height:1.04;font-weight:500;margin:0 0 14px;">{{ $heading }}</h1>
                            <p style="color:#e8f4ff;font-size:16px;line-height:1.6;margin:0;">{{ $intro }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 34px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="vertical-align:top;width:240px;padding:0 24px 22px 0;">
                                        <div style="background:#f7fbff;border:1px solid #dce9f5;border-radius:20px;padding:18px;text-align:center;">
                                            <p style="color:#0e50ed;font-size:12px;font-weight:800;letter-spacing:.08em;margin:0 0 12px;text-transform:uppercase;">Scan to install</p>
                                            @if ($qrImageData)
                                                <img src="{{ $message->embedData($qrImageData, 'blipblap-esim-qr.png', $qrMime) }}" alt="BlipBlap eSIM QR code" width="188" style="display:block;width:188px;max-width:100%;height:auto;margin:0 auto;">
                                            @else
                                                <div style="background:#ffffff;border:1px dashed #b7cce0;border-radius:16px;color:#0e50ed;font-size:20px;font-weight:800;padding:66px 0;">QR code</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="vertical-align:top;padding:0 0 22px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#10213a;border-radius:20px;">
                                            <tr>
                                                <td style="padding:22px;">
                                                    <p style="color:#8fd1ff;font-size:12px;font-weight:800;letter-spacing:.08em;margin:0 0 12px;text-transform:uppercase;">Plan summary</p>
                                                    <p style="color:#ffffff;font-size:18px;line-height:1.35;margin:0 0 18px;">{{ $plan?->title ?? $esim->nickname ?? $order->bundle_code }}</p>
                                                    <p style="color:#b8c5d5;font-size:12px;font-weight:800;margin:0 0 5px;">ICCID</p>
                                                    <p style="color:#ffffff;font-size:14px;line-height:1.45;margin:0 0 14px;word-break:break-word;">{{ $esim->iccid }}</p>
                                                    <p style="color:#b8c5d5;font-size:12px;font-weight:800;margin:0 0 5px;">Order reference</p>
                                                    <p style="color:#ffffff;font-size:14px;line-height:1.45;margin:0;word-break:break-word;">{{ $order->order_reference }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #e2edf5;margin-top:8px;padding-top:26px;">
                                <tr>
                                    <td>
                                        <h2 style="color:#10213a;font-size:26px;font-weight:500;line-height:1.15;margin:0 0 16px;">How to connect your eSIM</h2>
                                        @foreach ($steps as $index => $step)
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 10px;">
                                                <tr>
                                                    <td style="width:38px;vertical-align:top;">
                                                        <div style="background:#0e50ed;border-radius:999px;color:#ffffff;font-size:14px;font-weight:800;height:30px;line-height:30px;text-align:center;width:30px;">{{ $index + 1 }}</div>
                                                    </td>
                                                    <td style="color:#334155;font-size:15px;line-height:1.55;padding-top:4px;">{{ $step }}</td>
                                                </tr>
                                            </table>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7fbff;border:1px solid #dce9f5;border-radius:18px;margin-top:24px;">
                                <tr>
                                    <td style="padding:22px;">
                                        <h3 style="color:#0e50ed;font-size:20px;margin:0 0 8px;">{{ $manualHeading }}</h3>
                                        <p style="color:#5c6b7c;font-size:13px;line-height:1.55;margin:0 0 16px;">{{ $manualIntro }}</p>
                                        <p style="font-size:13px;line-height:1.55;margin:0 0 12px;"><strong>SM-DP+ address:</strong><br>{{ $esim->smdp_address ?: 'Available in your BlipBlap account' }}</p>
                                        <p style="font-size:13px;line-height:1.55;margin:0 0 12px;"><strong>Matching ID:</strong><br>{{ $esim->matching_id ?: 'Available in your BlipBlap account' }}</p>
                                        <p style="font-size:13px;line-height:1.55;margin:0;word-break:break-word;"><strong>Activation code:</strong><br>{{ $esim->activation_code ?: 'Available in your BlipBlap account' }}</p>
                                    </td>
                                </tr>
                            </table>

                            @if ($iosInstallUrl || $androidInstallUrl)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:18px;">
                                    <tr>
                                        <td style="background:#e8f4ff;border-radius:18px;padding:20px;">
                                            <h3 style="color:#0e50ed;font-size:19px;margin:0 0 12px;">Direct install links</h3>
                                            @if ($iosInstallUrl)
                                                <p style="font-size:14px;line-height:1.6;margin:0 0 10px;"><a href="{{ $iosInstallUrl }}" style="color:#0e50ed;font-weight:800;">{{ $iosLabel }}</a></p>
                                            @endif
                                            @if ($androidInstallUrl)
                                                <p style="font-size:14px;line-height:1.6;margin:0;"><a href="{{ $androidInstallUrl }}" style="color:#0e50ed;font-weight:800;">{{ $androidLabel }}</a></p>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;">
                                <tr>
                                    <td style="text-align:center;">
                                        <a href="{{ url('/my-esims') }}" style="background:#0e50ed;border-radius:999px;color:#ffffff;display:inline-block;font-size:15px;font-weight:800;line-height:48px;min-width:190px;text-align:center;text-decoration:none;">Open My eSIMs</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color:#6f7f91;font-size:13px;line-height:1.6;margin:24px 0 0;text-align:center;">{{ $footer }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f4f8fb;color:#758294;font-size:12px;line-height:1.6;padding:20px 30px;text-align:center;">
                            Need help installing? Reply to this email or open your BlipBlap account for the same install details.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
