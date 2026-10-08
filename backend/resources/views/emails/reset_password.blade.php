<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khôi phục mật khẩu ReadsNews</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 560px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            padding: 32px 24px;
            text-align: center;
        }
        .brand-title {
            color: #ffffff;
            font-size: 22px;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .brand-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
            margin-top: 6px;
        }
        .content {
            padding: 32px 28px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .message {
            font-size: 14px;
            color: #475569;
            margin-bottom: 24px;
        }
        .btn-wrapper {
            text-align: center;
            margin: 28px 0;
        }
        .btn-reset {
            display: inline-block;
            background: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            padding: 13px 32px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }
        .token-box {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 20px 0;
            font-size: 13px;
            color: #475569;
        }
        .token-value {
            font-family: monospace;
            font-weight: 700;
            color: #4f46e5;
            word-break: break-all;
        }
        .warning-note {
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            padding-top: 18px;
            margin-top: 24px;
        }
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 class="brand-title">ReadsNews</h1>
            <span class="brand-badge">Trang tin đọc thành tiếng AI</span>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">Xin chào {{ $userName }},</div>
            <p class="message">
                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản ReadsNews của bạn. Nhấn vào nút bên dưới để tiến hành tạo mật khẩu mới:
            </p>

            <div class="btn-wrapper">
                <a href="{{ $resetUrl }}" target="_blank" class="btn-reset">Đặt Lại Mật Khẩu</a>
            </div>

            <p class="message" style="font-size: 13px;">
                Hoặc bạn có thể truy cập trực tiếp qua đường dẫn sau:
                <br>
                <a href="{{ $resetUrl }}" style="color: #4f46e5; word-break: break-all;">{{ $resetUrl }}</a>
            </p>

            <div class="token-box">
                Mã xác thực (Token): <span class="token-value">{{ $token }}</span>
            </div>

            <div class="warning-note">
                • Liên kết này chỉ có hiệu lực trong vòng <strong>60 phút</strong>.
                <br>
                • Nếu bạn không yêu cầu thay đổi mật khẩu, vui lòng bỏ qua email này. Tài khoản của bạn vẫn được bảo mật an toàn.
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            © {{ date('Y') }} ReadsNews. Nền tảng đọc báo thông minh hỗ trợ giọng đọc AI.
        </div>
    </div>
</body>
</html>

