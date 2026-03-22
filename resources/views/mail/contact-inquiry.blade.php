<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>お問い合わせ</title>
</head>
<body>
<p>以下の内容でサイトからお問い合わせがありました。</p>
<dl>
    <dt>お名前</dt>
    <dd>{{ $name }}</dd>
    <dt>メールアドレス</dt>
    <dd>{{ $email }}</dd>
    <dt>電話番号</dt>
    <dd>{{ $phone ?? '（未入力）' }}</dd>
    <dt>お問い合わせ種別</dt>
    <dd>{{ $inquiryTypeLabel }}（{{ $inquiryType }}）</dd>
</dl>
<p>お問い合わせ内容</p>
<pre style="white-space: pre-wrap; font-family: inherit;">{{ $body }}</pre>
</body>
</html>
