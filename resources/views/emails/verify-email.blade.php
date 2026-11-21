<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address — CCT Wellness</title>
</head>
<body style="margin: 0; padding: 0; background-color: #FDFBF7; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif; color: #1F1A1A; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FDFBF7; width: 100%; margin: 0; padding: 32px 16px;">
        <tr>
            <td align="center">
                <!-- Outer Card -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%; background-color: #FFFFFF; border: 1px solid #E8E1D5; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(31, 26, 26, 0.06);">

                    <!-- Institutional Header -->
                    <tr>
                        <td style="background-color: #8B1C1C; padding: 24px 32px; border-bottom: 4px solid #E5A91E;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="56" valign="middle" style="padding-right: 14px;">
                                        <div style="width: 52px; height: 52px; background-color: #FFF2D0; border-radius: 12px; padding: 4px; text-align: center;">
                                            <img src="{{ $logoUrl }}" alt="CCT Guidance Logo" width="44" height="44" style="display: block; width: 44px; height: 44px; object-fit: contain; border: 0;" />
                                        </div>
                                    </td>
                                    <td valign="middle">
                                        <p style="margin: 0; font-size: 20px; font-weight: 700; color: #FFFFFF; line-height: 1.2; letter-spacing: -0.2px;">
                                            CCT Wellness
                                        </p>
                                        <p style="margin: 4px 0 0 0; font-size: 13px; font-weight: 500; color: #F5F0E6; line-height: 1.3;">
                                            Guidance and Counseling Services Unit
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 32px 32px 32px;">
                            <!-- Eyebrow Badge -->
                            <p style="margin: 0 0 16px 0;">
                                <span style="display: inline-block; background-color: #F5F0E6; border: 1px solid #E8E1D5; color: #8B1C1C; font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; padding: 5px 12px; border-radius: 999px;">
                                    City College of Tagaytay &bull; Student Portal
                                </span>
                            </p>

                            <h1 style="margin: 0 0 16px 0; font-size: 26px; font-weight: 700; color: #1F1A1A; line-height: 1.25;">
                                Verify Your Email Address
                            </h1>

                            <p style="margin: 0 0 14px 0; font-size: 15px; line-height: 1.65; color: #1F1A1A;">
                                Hello {{ $user->first_name ?? 'Student' }},
                            </p>

                            <p style="margin: 0 0 26px 0; font-size: 15px; line-height: 1.65; color: #4A3E3E;">
                                Thank you for registering with the <strong>CCT Wellness Portal</strong>. To activate your account and access your scheduled student individual inventory and Guidance Office announcements, please confirm your institutional email address by clicking the button below.
                            </p>

                            <!-- Primary CTA Button (Table-based for Outlook/Gmail) -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 26px 0;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" bgcolor="#8B1C1C" style="background-color: #8B1C1C; border-radius: 12px;">
                                                    <a href="{{ $verificationUrl }}" target="_blank" rel="noopener" style="display: inline-block; padding: 14px 32px; font-size: 15px; font-weight: 700; color: #FFFFFF; text-decoration: none; border-radius: 12px;">
                                                        Verify Email Address
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiration & Security Notice -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 26px 0; background-color: #F5F0E6; border-left: 4px solid #8B1C1C; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 14px 18px; font-size: 13px; line-height: 1.6; color: #1F1A1A;">
                                        <strong>Link Expiration:</strong> This verification link will expire in <strong>{{ $expireMinutes }} minutes</strong>. If you did not create a CCT Wellness account, no further action is required.
                                    </td>
                                </tr>
                            </table>

                            <!-- Secondary Plain-Text URL Fallback -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top: 1px solid #E8E1D5; padding-top: 20px;">
                                <tr>
                                    <td style="padding-top: 20px; font-size: 13px; line-height: 1.6; color: #6B6360;">
                                        <p style="margin: 0 0 8px 0;">
                                            If you are having trouble clicking the <strong>&ldquo;Verify Email Address&rdquo;</strong> button, copy and paste the link below into your web browser:
                                        </p>
                                        <p style="margin: 0; word-break: break-all;">
                                            <a href="{{ $verificationUrl }}" target="_blank" rel="noopener" style="color: #8B1C1C; text-decoration: underline;">{{ $verificationUrl }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Institutional Footer -->
                    <tr>
                        <td style="background-color: #F5F0E6; border-top: 1px solid #E8E1D5; padding: 22px 32px; text-align: center;">
                            <p style="margin: 0 0 4px 0; font-size: 13px; font-weight: 700; color: #1F1A1A;">
                                City College of Tagaytay &mdash; Guidance and Counseling Services Unit
                            </p>
                            <p style="margin: 0 0 10px 0; font-size: 12px; color: #6B6360;">
                                CCT Wellness &bull; Supporting student well-being and psychological health.
                            </p>
                            <p style="margin: 0; font-size: 11px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; color: #8A7F7A;">
                                &copy; {{ date('Y') }} City College of Tagaytay. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
