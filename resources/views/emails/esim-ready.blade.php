<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your BlipBlap eSIM is ready</title>
</head>
<body style="margin:0;background:#f3f4f4;color:#152238;font-family:Arial,Helvetica,sans-serif;">
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
    @endphp
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f4;padding:28px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:18px;overflow:hidden;">
                    <tr>
                        <td style="background:#0b70ff;color:#ffffff;padding:26px 30px;">
                            <div style="font-size:22px;font-weight:700;">BlipBlap</div>
                            <h1 style="font-size:34px;line-height:1.05;margin:24px 0 10px;font-weight:500;">{{ $heading }}</h1>
                            <p style="font-size:15px;line-height:1.6;margin:0;">{{ $intro }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="width:220px;vertical-align:top;padding-right:24px;">
                                        <div style="background:#f8fafb;border:1px solid #dce7ef;border-radius:14px;padding:14px;text-align:center;">
                                            @if ($qrImageData)
                                                <img src="{{ $message->embedData($qrImageData, 'blipblap-esim-qr.png', $qrMime) }}" alt="BlipBlap eSIM QR code" width="180" style="display:block;width:180px;max-width:100%;height:auto;margin:0 auto;">
                                            @else
                                                <div style="color:#0b70ff;font-size:20px;font-weight:700;padding:64px 0;">QR code</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="vertical-align:top;">
                                        <p style="color:#6f7782;font-size:12px;font-weight:700;margin:0 0 6px;">Plan</p>
                                        <p style="font-size:16px;line-height:1.45;margin:0 0 16px;">{{ $plan?->title ?? $esim->nickname ?? $order->bundle_code }}</p>

                                        <p style="color:#6f7782;font-size:12px;font-weight:700;margin:0 0 6px;">ICCID</p>
                                        <p style="font-size:15px;margin:0 0 16px;">{{ $esim->iccid }}</p>

                                        <p style="color:#6f7782;font-size:12px;font-weight:700;margin:0 0 6px;">Order reference</p>
                                        <p style="font-size:15px;margin:0;">{{ $order->order_reference }}</p>
                                    </td>
                                </tr>
                            </table>

                            <div style="border-top:1px solid #e8edf1;margin:28px 0 0;padding-top:24px;">
                                <h2 style="color:#0b70ff;font-size:24px;font-weight:500;margin:0 0 14px;">How to connect your eSIM</h2>
                                <ol style="margin:0;padding-left:22px;color:#152238;font-size:15px;line-height:1.7;">
                                    @foreach ($steps as $step)
                                        <li>{{ $step }}</li>
                                    @endforeach
                                </ol>
                            </div>

                            <div style="background:#eef6fb;border-radius:14px;margin-top:24px;padding:18px;">
                                <h3 style="color:#0b70ff;font-size:18px;margin:0 0 12px;">{{ $manualHeading }}</h3>
                                <p style="color:#6f7782;font-size:13px;line-height:1.5;margin:0 0 14px;">{{ $manualIntro }}</p>
                                <p style="font-size:13px;line-height:1.5;margin:0 0 10px;"><strong>SM-DP+ address:</strong><br>{{ $esim->smdp_address ?: 'Available in your BlipBlap account' }}</p>
                                <p style="font-size:13px;line-height:1.5;margin:0 0 10px;"><strong>Matching ID:</strong><br>{{ $esim->matching_id ?: 'Available in your BlipBlap account' }}</p>
                                <p style="font-size:13px;line-height:1.5;margin:0;word-break:break-word;"><strong>Activation code:</strong><br>{{ $esim->activation_code ?: 'Available in your BlipBlap account' }}</p>
                            </div>

                            @php
                                $iosInstallUrl = data_get($esim->install_details, 'assignment.iosInstallUrl') ?: data_get($esim->install_details, 'response.iosInstallUrl');
                                $androidInstallUrl = data_get($esim->install_details, 'assignment.androidInstallUrl') ?: data_get($esim->install_details, 'response.androidInstallUrl');
                            @endphp

                            @if ($iosInstallUrl || $androidInstallUrl)
                                <div style="background:#f8fafb;border:1px solid #dce7ef;border-radius:14px;margin-top:18px;padding:18px;">
                                    <h3 style="color:#0b70ff;font-size:18px;margin:0 0 12px;">Direct install links</h3>
                                    @if ($iosInstallUrl)
                                        <p style="font-size:13px;line-height:1.6;margin:0 0 10px;"><a href="{{ $iosInstallUrl }}" style="color:#0b70ff;">{{ $iosLabel }}</a></p>
                                    @endif
                                    @if ($androidInstallUrl)
                                        <p style="font-size:13px;line-height:1.6;margin:0;"><a href="{{ $androidInstallUrl }}" style="color:#0b70ff;">{{ $androidLabel }}</a></p>
                                    @endif
                                </div>
                            @endif

                            <p style="color:#6f7782;font-size:13px;line-height:1.6;margin:24px 0 0;">{{ $footer }} <a href="{{ url('/my-esims') }}" style="color:#0b70ff;">My eSIMs</a>.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
