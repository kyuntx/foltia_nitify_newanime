# foltia ANIME LOCKER 新アニメ番組通知スクリプト

foltia ANIME LOCKER ( https://foltia.com/ANILOC/ )でしょぼいカレンダーから同期されたアニメの新番組をメールで通知します。

## 使い方
- notify_newanime.php を /home/foltia/ 以下の適当な場所に置きます。 （ここでは /home/foltia/tools/ に置いたとします）
- notify_newanime.php の以下の部分を編集します
```php
// foltia ANIME LOCKER のURI
$foltiauri = "http://192.168.xxx.xxx/";

// メールの送信先アドレス、送信元アドレス、サブジェクト
$mailto = "to@example.jp";
$mailfrom = "from@example.com";
$mailsubj = "[foltia ANIME LOCKER] New program notification";

// CSV形式で送信する場合は 1 とする
$mailcsv = 0;
```
- foltia サーバからメールが送信できるように設定を行います。
  - postfix から他のSMTPサーバに relay させる場合は /etc/postfix/main.cf に以下のように設定します。
```
relayhost = [smtp.example.jp]
```
- foltia ユーザの crontab に登録します。
  - デフォルトでは、毎日 5:19 にしょぼいカレンダーとの同期が走っているため、そのしばらく後くらいがよさそうです。（ここでは6:19）

```crontab
19 6 * * * php /home/foltia/tools/notify_newanime.php >/dev/null 2>&1
```
- うまくいっていれば、新番組が同期されると通知されるはずです。

## 動き
- 以下の条件を全て満たす場合に通知されます。（foltiaの新番組画面で白背景のもの）
  - 録画予約済みではない
  - 他局で同一番組を録画予約済みではない
  - 再放送ではない
- 一度通知した番組は通知しません
  - /home/foltia/newprogram.txt に前回実行時の情報を保存するため、こちらを削除すると再通知されます。
- 基本的な動作は php/animeprogram/index.php をパクっています。

## 通知メールサンプル
```
Subject: [foltia ANIME LOCKER] New program notification

New Anime programs are available.
http://192.168.xxx.xxx/animeprogram/index.php?mode=new

TID: 4437
放送局: テレビ東京
番組名: 銀魂.
放送日時: 2017/01/09(月) 01:35(0)
syobocal: http://cal.syoboi.jp/tid/4437
予約: http://192.168.xxx.xxx/reservation/reserveprogram.php?tid=4437
	
TID: 4437
放送局: テレビ大阪
番組名: 銀魂.
放送日時: 2017/01/10(火) 01:05(0)
syobocal: http://cal.syoboi.jp/tid/4437
予約: http://192.168.xxx.xxx/reservation/reserveprogram.php?tid=4437
	
TID: 4410
放送局: 超!A&G+
番組名: Fate/Grand Order カルデア・ラジオ局
放送日時: 2017/01/10(火) 21:00(0)
syobocal: http://cal.syoboi.jp/tid/4410
予約: http://192.168.xxx.xxx/reservation/reserveprogram.php?tid=4410
```

## ライセンス
オリジナル　foltia、 foltia ANIME LOCKER に準じます。
