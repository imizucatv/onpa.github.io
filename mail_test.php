<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);

require './php/PHPMailer-6.9.3/src/Exception.php';
require './php/PHPMailer-6.9.3/src/PHPMailer.php';
require './php/PHPMailer-6.9.3/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    
    // SMTPの基本設定
    $mail->isSMTP();
    $mail->SMTPDebug = 2; // デバッグ出力を有効化
    $mail->CharSet = 'UTF-8';
    
    // ここにSMTP設定を入力してください
    $mail->Host = 'sv10267.xserver.jp';  // SMTPサーバーのホスト名
    $mail->Port = 587;                    // SMTPポート
    $mail->SMTPAuth = true;               // SMTP認証を有効化
    $mail->Username = 'icn.website.form@interark.co.jp'; // SMTPユーザー名
    $mail->Password = 'Z!L!zDkTBNDG6t5G.C*m!7Q$M+HhXFDx'; // SMTPパスワード
    
    // メール設定
    $from = 'icn.website.form@interark.co.jp';
    $fromname = '射水ケーブルネットワーク株式会社';
    $to = 'icn.website.form@interark.co.jp';
    $subject = 'テストメール';
    $body = 'これはテストメールです。SMTPサーバーの設定テスト用に送信しています。';
    
    $mail->setFrom($from, $fromname);
    $mail->addAddress($to, 'テスト宛先');
    $mail->Subject = $subject;
    $mail->Body = $body;
    
    // メール送信
    if(!$mail->send()) {
        throw new Exception("メール送信に失敗しました: " . $mail->ErrorInfo);
    }
    
    echo "テストメールを送信しました。";
    
} catch (Exception $e) {
    echo "エラーが発生しました: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>メール送信テスト</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-info { 
            background: #f5f5f5;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #ddd;
        }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h2>メール送信テスト結果</h2>
    <div class="debug-info">
        <p>PHPのバージョン: <?php echo PHP_VERSION; ?></p>
        <p>エラー表示: <?php echo ini_get('display_errors') ? 'オン' : 'オフ'; ?></p>
        <p>メモリ制限: <?php echo ini_get('memory_limit'); ?></p>
        <p>最大実行時間: <?php echo ini_get('max_execution_time'); ?>秒</p>
    </div>
</body>
</html>
