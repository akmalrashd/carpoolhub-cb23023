<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>Verify your CarpoolHub email address</title>
</head>
{{--
    Table-based layout with inline styles throughout — email clients (Outlook,
    Gmail app) strip <style> blocks and ignore flexbox/grid, so anything not
    inlined on the element itself silently disappears in the wild even though
    it renders fine here or in a browser preview.
--}}
<body style="margin:0; padding:0; background-color:#F4EFE2; font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F4EFE2;">
    <tr>
        <td align="center" style="padding:40px 16px;">

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:480px;">

                {{-- Card — logo, body, and footer all live inside so the whole
                     email reads as one card floating on the canvas background,
                     matching the site's own login/register cards. --}}
                <tr>
                    <td style="background-color:#FFFFFF; border:1px solid #ECE7DA; border-radius:18px; padding:32px;">

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">

                            {{-- Logo lockup --}}
                            <tr>
                                <td align="center" style="padding-bottom:28px;">
                                    <img src="{{ asset('assets/branding/logo-small-b.png') }}" width="36" height="36" alt="CarpoolHub" style="display:inline-block; vertical-align:middle; border:0;">
                                    <span style="font-family:Arial,Helvetica,sans-serif; font-size:22px; font-weight:800; color:#0B1220; vertical-align:middle; margin-left:8px;">Carpool<span style="color:#E6B800;">Hub</span></span>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <h1 style="margin:0 0 10px; font-family:Arial,Helvetica,sans-serif; font-size:21px; font-weight:700; color:#0B1220;">
                                        Verify your email address
                                    </h1>
                                    <p style="margin:0 0 24px; font-family:Arial,Helvetica,sans-serif; font-size:14px; line-height:1.6; color:#64748B;">
                                        Hi{{ $userName ? ' ' . $userName : '' }}, welcome to CarpoolHub! Please
                                        confirm that
                                        (<strong style="color:#1F2937;">{{ $userEmail }}</strong>)
                                        is really yours by clicking the button below.
                                    </p>
                                </td>
                            </tr>

                            {{-- CTA button --}}
                            <tr>
                                <td align="center" style="padding-bottom:24px;">
                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td align="center" style="background-color:#FACC15; border-radius:14px;">
                                                <a href="{{ $verifyUrl }}" target="_blank" style="display:inline-block; padding:14px 32px; font-family:Arial,Helvetica,sans-serif; font-size:15px; font-weight:700; color:#2A1E04; text-decoration:none;">
                                                    Verify email address
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td style="border-top:1px solid #ECE7DA; padding-top:20px;">
                                    <p style="margin:0 0 8px; font-family:Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.6; color:#94A3B8;">
                                        This link expires in {{ $expireMinutes }} minutes. If the button
                                        above doesn't work, copy and paste this URL into your browser:
                                    </p>
                                    <p style="margin:0; font-family:Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.6; word-break:break-all;">
                                        <a href="{{ $verifyUrl }}" style="color:#2563EB;">{{ $verifyUrl }}</a>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <td style="padding-top:20px; padding-bottom:14px;">
                                    <p style="margin:0; font-family:Arial,Helvetica,sans-serif; font-size:12.5px; line-height:1.6; color:#94A3B8;">
                                        Didn't create a CarpoolHub account? You can safely ignore this
                                        email.
                                    </p>
                                </td>
                            </tr>

                            {{-- Footer --}}
                            <tr>
                                <td align="center" style="border-top:1px solid #ECE7DA; padding-top:14px;">
                                    <p style="margin:0; font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#94A3B8;">
                                        &copy; {{ date('Y') }} CarpoolHub. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>
</body>
</html>
