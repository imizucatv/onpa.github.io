<?php
date_default_timezone_set('Asia/Tokyo');

mb_language('Japanese');
mb_internal_encoding('UTF-8');

$LOG_FILE = __DIR__ . '/mail_error.log';

$phpmailerSrc = dirname(__DIR__) . '/PHPMailer-6.9.3/src';
require $phpmailerSrc . '/PHPMailer.php';
require $phpmailerSrc . '/SMTP.php';
require $phpmailerSrc . '/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$SMTP_HOST = 'poi.canet.ne.jp';
$SMTP_PORT = 587;
$SMTP_USER = 'info@canet.ne.jp';
$SMTP_PASS = '3710shin';
$SMTP_SECURE = PHPMailer::ENCRYPTION_STARTTLS;

$FROM_NAME = '射水ケーブルネットワーク株式会社';
$FROM_ADDR = 'info@canet.ne.jp';

$TO_ADMIN = [
    'kikaku@imizucable.com',
];

$subject_admin = '【射水ケーブルネットワーク】ネットLP お問い合わせフォーム';
$subject_user  = '【射水ケーブルネットワーク】お問い合わせありがとうございます';
$admin_intro   = '射水ケーブルネットワーク ネットLPよりお問い合わせがありました。';

$contentType = isset($_SERVER['CONTENT_TYPE']) ? (string)$_SERVER['CONTENT_TYPE'] : '';
$isJsonRequest = stripos($contentType, 'application/json') !== false;

if ($isJsonRequest) {
    header('Content-Type: application/json; charset=UTF-8');
}

function respondRedirect($path)
{
    header('Location: ' . $path);
    exit;
}

function respondValidationError($errors, $isJsonRequest)
{
    if ($isJsonRequest) {
        echo json_encode(['ok' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
        exit;
    }

    respondRedirect('index.html#form');
}

function respondSendError($isJsonRequest)
{
    if ($isJsonRequest) {
        echo json_encode(['ok' => false, 'errors' => ['メール送信に失敗しました。']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    respondRedirect('error.html');
}

function respondSuccess($isJsonRequest)
{
    if ($isJsonRequest) {
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    respondRedirect('thanks.html');
}

function firstValue($data, $keys)
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }
    }

    return '';
}

function normalizeText($value, $allowMultiline = false)
{
    if (is_array($value)) {
        $items = [];
        foreach ($value as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $items[] = $item;
            }
        }
        $value = implode('、', $items);
    }

    $value = trim((string)$value);
    $value = str_replace(["\r\n", "\r"], "\n", $value);

    if (!$allowMultiline) {
        $value = preg_replace('/\s+/u', ' ', $value);
    }

    return $value;
}

function normalizeInquiryType($data)
{
    $legacyType = normalizeText(firstValue($data, ['type']), false);
    if ($legacyType !== '') {
        return $legacyType;
    }

    $content = normalizeText(firstValue($data, ['your-content']), false);
    $kind    = normalizeText(firstValue($data, ['your-kind']), false);

    if ($content !== '' && $kind !== '') {
        return $content . ' / ' . $kind;
    }

    if ($content !== '') {
        return $content;
    }

    return $kind;
}

function makeMailer($host, $port, $user, $pass, $secure, $fromAddr, $fromName)
{
    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $host;
    $mailer->Port = $port;
    $mailer->SMTPAuth = true;
    $mailer->Username = $user;
    $mailer->Password = $pass;
    $mailer->CharSet = 'UTF-8';
    $mailer->Encoding = 'base64';
    $mailer->SMTPDebug = 0;
    $mailer->SMTPAutoTLS = true;
    $mailer->Timeout = 10;

    if ($secure !== '') {
        $mailer->SMTPSecure = $secure;
    }

    $mailer->setFrom($fromAddr, $fromName);
    $mailer->isHTML(false);

    return $mailer;
}

if (!$isJsonRequest && (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST')) {
    respondRedirect('index.html#form');
}

$data = $_POST;
if ($isJsonRequest) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    $data = is_array($decoded) ? $decoded : [];
}

$typeRaw    = normalizeInquiryType($data);
$nameRaw    = normalizeText(firstValue($data, ['name', 'your-name']), false);
$addressRaw = normalizeText(firstValue($data, ['address', 'your-address']), false);
$phoneRaw   = normalizeText(firstValue($data, ['tel', 'phone', 'your-phone']), false);
$emailRaw   = normalizeText(firstValue($data, ['email', 'your-email']), false);
$messageRaw = normalizeText(firstValue($data, ['message', 'your-text']), true);

$replaceBefore = ['①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧', '⑨', '⑩', '№', '㈲', '㈱', '髙'];
$replaceAfter  = ['(1)', '(2)', '(3)', '(4)', '(5)', '(6)', '(7)', '(8)', '(9)', '(10)', 'No.', '（有）', '（株）', '高'];

$nameRaw = str_replace($replaceBefore, $replaceAfter, $nameRaw);
$addressRaw = str_replace($replaceBefore, $replaceAfter, $addressRaw);
$messageRaw = str_replace($replaceBefore, $replaceAfter, $messageRaw);

$errors = [];
if ($typeRaw === '') {
    $errors[] = 'お問い合わせの種類を選択してください。';
}
if ($nameRaw === '') {
    $errors[] = 'お名前が未入力です。';
}
if ($addressRaw === '') {
    $errors[] = 'ご住所が未入力です。';
}
if ($phoneRaw === '') {
    $errors[] = '電話番号が未入力です。';
} elseif (strlen(preg_replace('/\D/u', '', $phoneRaw)) < 10) {
    $errors[] = '電話番号は10桁以上の数字で入力してください。';
}
if ($emailRaw === '' || !filter_var($emailRaw, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'メールアドレスを正しく入力してください。';
}

if (!empty($errors)) {
    respondValidationError($errors, $isJsonRequest);
}

$type = $typeRaw;
$name = $nameRaw;
$address = $addressRaw;
$phone = $phoneRaw;
$email = $emailRaw;
$message = $messageRaw;
$messageDisplay = ($message !== '') ? $message : '（未記入）';
$now = date('Y-m-d H:i:s');

$bodyAdmin = "{$admin_intro}\n\n";
$bodyAdmin .= "------------------------------------------------------------\n";
$bodyAdmin .= "[ お問い合わせの種類 ] {$type}\n";
$bodyAdmin .= "[ お名前 ] {$name}\n";
$bodyAdmin .= "[ ご住所 ] {$address}\n";
$bodyAdmin .= "[ 電話番号 ] {$phone}\n";
$bodyAdmin .= "[ メールアドレス ] {$email}\n";
$bodyAdmin .= "[ お問い合わせ内容 ]\n{$messageDisplay}\n\n";
$bodyAdmin .= "[ 送信日時 ] {$now}\n\n";
$bodyAdmin .= "----\n";
$bodyAdmin .= "このメールは射水ケーブルネットワークLPのお問い合わせフォームから送信されました。\n";

$bodyUser = "{$name} 様\n\n";
$bodyUser .= "──────────────────────────\n\n";
$bodyUser .= "この度はお問い合わせ頂き誠にありがとうございました。\n";
$bodyUser .= "改めて担当者よりご連絡をさせていただきます。\n\n";
$bodyUser .= "─ご送信内容の確認─────────────────\n";
$bodyUser .= "[ お問い合わせの種類 ] {$type}\n";
$bodyUser .= "[ お名前 ] {$name}\n";
$bodyUser .= "[ ご住所 ] {$address}\n";
$bodyUser .= "[ 電話番号 ] {$phone}\n";
$bodyUser .= "[ メールアドレス ] {$email}\n";
$bodyUser .= "[ お問い合わせ内容 ]\n{$messageDisplay}\n\n";
$bodyUser .= "───\n";
$bodyUser .= "このメールは射水ケーブルネットワーク株式会社のお問い合わせフォームから送信されました。\n";

$replyToName = str_replace(["\r", "\n"], '', $nameRaw);

try {
    if ($SMTP_USER === '' || $SMTP_PASS === '') {
        throw new Exception('SMTP credentials are not configured.');
    }

    $mailer = makeMailer($SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASS, $SMTP_SECURE, $FROM_ADDR, $FROM_NAME);
    foreach ($TO_ADMIN as $adminAddress) {
        $mailer->addAddress($adminAddress);
    }
    $mailer->addReplyTo($emailRaw, $replyToName);
    $mailer->Subject = $subject_admin;
    $mailer->Body = $bodyAdmin;

    // ★追加: フォームからPDFファイルがアップロードされているか確認して添付
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $fileName = !empty($_FILES['pdf_file']['name']) ? $_FILES['pdf_file']['name'] : '料金試算結果.pdf';
        $mailer->addAttachment($_FILES['pdf_file']['tmp_name'], $fileName);
    }

    $mailer->send();

    // ユーザー宛自動返信メールの設定
    $mailer->clearAddresses();
    $mailer->clearReplyTos();
    $mailer->clearAttachments(); // ★追加: 管理者宛のみ添付し、自動返信メールからは添付を外す場合

    $mailer->addAddress($emailRaw, $replyToName);
    $mailer->addReplyTo($FROM_ADDR, $FROM_NAME);
    $mailer->Subject = $subject_user;
    $mailer->Body = $bodyUser;

    // ※もしユーザー宛の自動返信メールにも試算結果PDFを添えて送りたい場合は、
    // 上の $mailer->clearAttachments(); をコメントアウト（または削除）してください。

    $mailer->send();

    respondSuccess($isJsonRequest);
} catch (Exception $e) {
    error_log(date('[Y-m-d H:i:s] ') . $e->getMessage() . PHP_EOL, 3, $LOG_FILE);
    respondSendError($isJsonRequest);
}
