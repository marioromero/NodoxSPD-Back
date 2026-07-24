<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma de Documento</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:system-ui,-apple-system,sans-serif;color:#1a1a2e;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="min-height:100vh;">
        <tr>
            <td align="center" style="padding:24px 16px;">
                <table role="presentation" width="100%" style="max-width:480px;background-color:#ffffff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.06);overflow:hidden;">
                    <tr>
                        <td style="padding:32px 28px 8px 28px;text-align:center;">
                            <h1 style="margin:0 0 16px 0;font-size:20px;font-weight:700;color:#1a3c5e;">
                                {{ $businessName }} requiere tu firma
                            </h1>
                            <p style="margin:0 0 24px 0;font-size:15px;line-height:1.6;color:#555;">
                                Has sido invitado a firmar el documento
                                <strong>{{ $policyName }}</strong>.
                                Revisa el contenido y confirma tu consentimiento desde el siguiente enlace seguro.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 32px 28px;text-align:center;">
                            <a href="{{ $magicUrl }}"
                               style="display:inline-block;padding:14px 32px;background-color:#1a73e8;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">
                                Leer y Firmar Documento
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 28px 24px 28px;text-align:center;">
                            <p style="margin:0;font-size:12px;line-height:1.5;color:#999;">
                                Si no puedes ver el botón, copia y pega este enlace en tu navegador:<br>
                                <a href="{{ $magicUrl }}" style="color:#1a73e8;word-break:break-all;">{{ $magicUrl }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;border-top:1px solid #eef1f5;text-align:center;">
                            <p style="margin:0;font-size:11px;color:#aaa;">
                                Este enlace expirará. Si no esperabas este correo, puedes ignorarlo.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
