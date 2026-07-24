<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma requerida</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:Arial,Helvetica,sans-serif;color:#1a1a2e;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="min-height:100vh;">
        <tr>
            <td align="center" style="padding:24px 16px;">
                <table role="presentation" width="100%" style="max-width:480px;background-color:#ffffff;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="padding:32px 28px 8px 28px;text-align:center;">
                            <p style="margin:0 0 24px 0;font-size:14px;font-weight:bold;color:#1a3c5e;text-transform:uppercase;letter-spacing:1px;">
                                NodoxSPD
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 16px 28px;">
                            <p style="margin:0 0 20px 0;font-size:15px;line-height:1.6;color:#555;">
                                Te han solicitado revisar y firmar el siguiente documento:
                            </p>
                            <p style="margin:0 0 8px 0;font-size:18px;font-weight:bold;color:#1a1a2e;">
                                {{ $policyType }}
                            </p>
                            <p style="margin:0 0 24px 0;font-size:15px;line-height:1.6;color:#555;">
                                Empresa: <strong>{{ $companyName }}</strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 32px 28px;text-align:center;">
                            <a href="{{ $consentUrl }}"
                               style="display:inline-block;background-color:#1a73e8;color:#ffffff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">
                                Revisar y firmar
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 24px 28px;">
                            <p style="margin:0 0 16px 0;font-size:13px;line-height:1.6;color:#888;">
                                Este enlace vence el {{ $expiresAt }}. Despu&eacute;s de esa fecha necesitar&aacute;s solicitar uno nuevo.
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;color:#888;">
                                Si no solicitaste este documento o no reconoces a {{ $companyName }}, puedes ignorar este correo.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;border-top:1px solid #eef1f5;">
                            <p style="margin:0;text-align:center;font-size:11px;color:#aaa;">
                                NodoxSPD &mdash; Este correo fue generado autom&aacute;ticamente. No responder.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
