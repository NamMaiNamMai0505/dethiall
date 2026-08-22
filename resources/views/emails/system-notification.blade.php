@php
    $sentAt = optional($notification->created_at)
        ->timezone(config('app.timezone'))
        ->format('H:i · d/m/Y') ?? now()->format('H:i · d/m/Y');
    $referenceCode = '#'.str_pad((string) $notification->id, 6, '0', STR_PAD_LEFT);
    $preheader = \Illuminate\Support\Str::limit(
        trim(preg_replace('/\s+/u', ' ', (string) $notification->message) ?? ''),
        125
    );
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $notification->title }}</title>
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            min-width: 100% !important;
            background: #e9eff7 !important;
        }

        table, td {
            border-collapse: collapse !important;
            mso-table-lspace: 0 !important;
            mso-table-rspace: 0 !important;
        }

        img {
            border: 0;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        a {
            text-decoration: none;
        }

        @media only screen and (max-width: 620px) {
            .email-shell {
                width: 100% !important;
                border-radius: 0 !important;
            }

            .mobile-pad {
                padding-left: 22px !important;
                padding-right: 22px !important;
            }

            .brand-copy {
                padding-left: 12px !important;
            }

            .brand-kicker {
                font-size: 10px !important;
                letter-spacing: 1.2px !important;
            }

            .brand-name {
                font-size: 17px !important;
            }

            .hero-title {
                font-size: 25px !important;
                line-height: 32px !important;
            }

            .info-column {
                display: block !important;
                width: 100% !important;
            }

            .info-column + .info-column {
                padding-left: 0 !important;
                padding-top: 10px !important;
            }

            .action-button {
                display: block !important;
            }

            .header-icon-cell {
                display: none !important;
            }
        }
    </style>
</head>
<body style="margin:0;padding:0;width:100%;min-width:100%;background:#e9eff7;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#172033;-webkit-font-smoothing:antialiased;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;mso-hide:all;">
        {{ $preheader }}
    </div>
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">
        &nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#e9eff7;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table class="email-shell" role="presentation" width="680" cellspacing="0" cellpadding="0" border="0"
                       style="width:100%;max-width:680px;background:#ffffff;border:1px solid #dbe4f0;border-radius:22px;overflow:hidden;box-shadow:0 22px 60px rgba(15,35,65,0.14);">
                    <tr>
                        <td height="6" style="height:6px;background:{{ $typeColor }};font-size:0;line-height:0;">&nbsp;</td>
                    </tr>

                    <tr>
                        <td class="mobile-pad" style="padding:28px 36px;background:#071a33;background-image:linear-gradient(135deg,#071a33 0%,#0b3157 62%,#0f5c73 100%);">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle" width="76" style="width:76px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="center" valign="middle"
                                                    style="width:68px;height:68px;background:#ffffff;border:1px solid rgba(255,255,255,0.7);border-radius:17px;box-shadow:0 8px 22px rgba(0,0,0,0.2);">
                                                    <img src="{{ $logoUrl }}"
                                                         alt="Logo Trường Cao đẳng Hậu cần 2"
                                                         width="58"
                                                         height="58"
                                                         style="display:block;width:58px;height:58px;object-fit:contain;margin:0 auto;">
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="brand-copy" valign="middle" style="padding-left:18px;">
                                        <div class="brand-kicker" style="margin:0 0 7px;color:#9ed9e7;font-size:11px;font-weight:700;line-height:15px;letter-spacing:1.8px;text-transform:uppercase;">
                                            Hệ thống thông tin điện tử
                                        </div>
                                        <div class="brand-name" style="margin:0;color:#ffffff;font-size:20px;font-weight:800;line-height:26px;">
                                            Trường Cao đẳng Hậu cần 2
                                        </div>
                                        <div style="margin-top:3px;color:#c6d7e9;font-size:12px;line-height:18px;">
                                            Nền tảng Quản lý đào tạo
                                        </div>
                                    </td>
                                    <td class="header-icon-cell" align="right" valign="middle" width="58" style="width:58px;">
                                        <table role="presentation" align="right" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="center" valign="middle"
                                                    style="width:50px;height:50px;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.22);border-radius:15px;color:#ffffff;font-size:24px;line-height:50px;">
                                                    {{ $typeIcon }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-pad" style="padding:34px 38px 10px;background:#ffffff;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        <span style="display:inline-block;padding:7px 13px;border:1px solid {{ $typeColor }};border-radius:999px;color:{{ $typeColor }};font-size:11px;font-weight:800;line-height:14px;letter-spacing:1px;text-transform:uppercase;">
                                            {{ $typeLabel }}
                                        </span>
                                    </td>
                                    <td align="right" style="color:#7b8ba3;font-size:11px;font-weight:700;letter-spacing:0.5px;">
                                        {{ $referenceCode }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:25px 0 7px;color:#53647b;font-size:15px;line-height:24px;">
                                Kính gửi <strong style="color:#16243a;">{{ $recipientName }}</strong>,
                            </p>
                            <h1 class="hero-title" style="margin:0;color:#0b1f3a;font-size:30px;font-weight:800;line-height:38px;letter-spacing:-0.45px;">
                                {{ $notification->title }}
                            </h1>
                            <div style="width:54px;height:4px;margin-top:18px;background:{{ $typeColor }};border-radius:999px;font-size:0;line-height:0;">&nbsp;</div>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-pad" style="padding:22px 38px 8px;background:#ffffff;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                   style="width:100%;background:#f7f9fc;border:1px solid #dfe7f1;border-radius:16px;">
                                <tr>
                                    <td width="6" style="width:6px;background:{{ $typeColor }};border-radius:16px 0 0 16px;font-size:0;line-height:0;">&nbsp;</td>
                                    <td style="padding:22px 24px;">
                                        <div style="margin-bottom:9px;color:#7b8ba3;font-size:10px;font-weight:800;line-height:14px;letter-spacing:1.25px;text-transform:uppercase;">
                                            Nội dung thông báo
                                        </div>
                                        <div style="margin:0;color:#293a52;font-size:15px;line-height:25px;">
                                            {!! nl2br(e($notification->message)) !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if(!empty($actionUrl))
                        <tr>
                            <td class="mobile-pad" align="center" style="padding:24px 38px 12px;background:#ffffff;">
                                <table role="presentation" align="center" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td align="center" bgcolor="{{ $typeColor }}"
                                            style="background:{{ $typeColor }};border-radius:12px;box-shadow:0 10px 24px rgba(16,43,75,0.2);">
                                            <a class="action-button"
                                               href="{{ $actionUrl }}"
                                               style="display:inline-block;padding:15px 31px;color:#ffffff;font-size:14px;font-weight:800;line-height:18px;letter-spacing:0.15px;">
                                                Mở thông tin trên hệ thống&nbsp;&nbsp;→
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td class="mobile-pad" style="padding:20px 38px 8px;background:#ffffff;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="info-column" width="50%" valign="top" style="width:50%;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                               style="background:#f7f9fc;border:1px solid #e2e9f2;border-radius:13px;">
                                            <tr>
                                                <td style="padding:15px 17px;">
                                                    <div style="color:#8291a6;font-size:10px;font-weight:800;line-height:14px;letter-spacing:1px;text-transform:uppercase;">
                                                        Thời gian gửi
                                                    </div>
                                                    <div style="margin-top:5px;color:#1c2d45;font-size:13px;font-weight:700;line-height:19px;">
                                                        {{ $sentAt }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="info-column" width="50%" valign="top" style="width:50%;padding-left:10px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                               style="background:#f7f9fc;border:1px solid #e2e9f2;border-radius:13px;">
                                            <tr>
                                                <td style="padding:15px 17px;">
                                                    <div style="color:#8291a6;font-size:10px;font-weight:800;line-height:14px;letter-spacing:1px;text-transform:uppercase;">
                                                        Phân hệ
                                                    </div>
                                                    <div style="margin-top:5px;color:#1c2d45;font-size:13px;font-weight:700;line-height:19px;">
                                                        {{ $notification->module ?: 'Hệ thống chung' }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if(!empty($actionUrl))
                        <tr>
                            <td class="mobile-pad" style="padding:16px 38px 6px;background:#ffffff;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td style="padding:14px 16px;background:#eef5fb;border:1px solid #d9e7f3;border-radius:12px;">
                                            <div style="color:#4c6078;font-size:11px;font-weight:700;line-height:16px;">
                                                Không mở được nút? Sao chép đường dẫn an toàn bên dưới:
                                            </div>
                                            <div style="margin-top:5px;font-size:11px;line-height:17px;word-break:break-all;">
                                                <a href="{{ $actionUrl }}" style="color:#0b5d82;text-decoration:underline;">{{ $actionUrl }}</a>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td class="mobile-pad" style="padding:20px 38px 32px;background:#ffffff;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="28" valign="top" style="width:28px;color:#66809d;font-size:16px;line-height:22px;">🔔</td>
                                    <td style="color:#6c7d92;font-size:12px;line-height:19px;">
                                        Thông báo này đã được đồng bộ với chuông thông báo trên website. Vui lòng đăng nhập bằng tài khoản cá nhân để xem dữ liệu đầy đủ và cập nhật mới nhất.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-pad" style="padding:25px 38px;background:#071a33;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle" width="50" style="width:50px;">
                                        <img src="{{ $logoUrl }}"
                                             alt="CDHC2"
                                             width="42"
                                             height="42"
                                             style="display:block;width:42px;height:42px;object-fit:contain;background:#ffffff;border-radius:10px;padding:2px;">
                                    </td>
                                    <td valign="middle" style="padding-left:13px;">
                                        <div style="color:#ffffff;font-size:13px;font-weight:800;line-height:19px;">
                                            Quản lý đào tạo · CDHC2
                                        </div>
                                        <div style="margin-top:2px;color:#91a8c1;font-size:11px;line-height:17px;">
                                            Email tự động từ hệ thống — vui lòng không trả lời.
                                        </div>
                                    </td>
                                    <td align="right" valign="middle">
                                        <a href="{{ $appUrl }}"
                                           style="display:inline-block;padding:8px 11px;border:1px solid #31506e;border-radius:9px;color:#9ed9e7;font-size:11px;font-weight:700;">
                                            {{ parse_url($appUrl, PHP_URL_HOST) ?: $appUrl }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:680px;">
                    <tr>
                        <td align="center" style="padding:17px 20px 0;color:#8595a9;font-size:10px;line-height:16px;">
                            © {{ date('Y') }} Trường Cao đẳng Hậu cần 2 · Thông tin phục vụ công tác quản lý đào tạo.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
