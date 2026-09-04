# Construction Order 016I（Owner指示ラベル: "016E"）— Internal Pages Visual v3 軽量再監査 施工報告

## 0. Order Label についての事前注記（正直な申告）

本Orderは、Owner支給テキスト内で "016E" と名乗っている（"INTERNAL PAGES VISUAL v3 RECONSTRUCTION — HOMEのVisual v3デザイン言語をサイト全体へ展開する"）。しかし `HISTORY.csv` を確認したところ、**"016E" は既に本プロジェクトの実履歴で使用済み**（2026-09-01〜09-03、016E/016E-R1/016E-R2/016E-R3/016E-R4として完了済み、Hero Text Plane・Flow・CTAの調整を扱った別のOrder）である。本Reportおよび `HISTORY.csv` では、この既存履歴との衝突を避けるため、実際に採番されていない次の番号 **016I** をこのOrderの記録用ラベルとして使用する。Owner支給テキストの意図・スコープ・受入基準はすべてそのまま尊重しており、変更しているのは記録上のラベルのみである。

## 1. Pre-Construction Audit（Order §4 必須）— 前提の食い違いの発見

Order本文は「内部ページはまだVisual v2、Visual v3化されているのはHOMEのみ」という前提で書かれていたが、実際のリポジトリ状態（`git log`、及び全内部ページのLive Screenshot）を確認した結果、この前提は**現在の実態と一致しなかった**：Construction 016F（`75da28d`）と016F-R1（`644e225`）で、Service/CASE Archive+Single、Professional Archive+Single、FAQ Archive、VOICE Archive、Price、Office、Contact、Search、404の全内部ページに対して、HOMEと同一のVisual v3デザイン言語（Kicker+Title+ruleのPage Header System、Wide Grid、Card再設計、`--astrea-v3-*`トークン共有）が既に展開済みであることを確認した。

このため、Ownerに前提の食い違いを提示し、4つの対応案（① 軽量な再監査として扱う（推奨）／② Order作成者が016F以前の状態を見ていた可能性を確認／③ 前提を無視してフル再構築を強行／④ その他）を提示した。**Ownerは①「軽量な再監査として扱う（推奨）」を明示的に選択した。** 本Reportはこの指示に基づき、「016F/016F-R1の成果を正とし、Orderが列挙する全ページ種別（Office/Contact/404/Searchを含む）をその正とOrder自身の要求基準の両方に照らして検証し、実際に見つかった不整合・退行のみを最小修正する」という監査として実施した。

## 2. 発見した唯一の実欠陥 — 内部ページ Page Title Kicker衝突

Pre-Construction Auditのスクリーンショット取得中（`zoom-top.png`、Service Single 1440px上部クロップ）、内部ページ共通のPage Title Kicker（`::before`による絶対配置のラベル、例："SERVICE"）が、その直下のH1本文と視覚的に重なっていることを発見した。これは本プロジェクトで最も繰り返し発見されてきたバグパターン——**絶対配置Kickerを持つ見出し要素自身に`padding-top`が無いと、Kickerの`top:0`が見出し自身のPadding-Box起点に張り付き本文と衝突する**——の、内部ページ版として未発見だったインスタンスである（016HがHOMEのSection Heading Kickerで、016H-R1がCASE/RESULTS/VOICEのKicker間隔で、それぞれ同根の問題を別のSelectorに対して修正済みだったが、内部ページのPage Title Kicker（`.astrea-archive-header h1,main.alignfull>.wp-block-post-title,main.alignfull>h1`）はこれまで一度もこの観点で検証されたことがなかった）。

再現を確認した9ページ：Service Archive／Service Single（最も顕著）／CASE Archive／CASE Single／Professional Archive／FAQ Archive／VOICE Archive／Search／404。

### 2.1 Root Cause

内部ページのPage Title共有Selectorは、016Fで`position:relative;display:flex;align-items:baseline`等をKicker配置のために追加していたが、`padding-top`を持たないままだった。HOME側の同種の問題（016H）と全く同じ原因。

### 2.2 修正内容

`theme/theme.json`の該当Selector（`.astrea-archive-header h1,main.alignfull>.wp-block-post-title,main.alignfull>h1`）に、016Hで確立・実証済みの値を1行追加した：

```
padding-top:var(--wp--preset--spacing--medium);
```

新規Tokenの発明・個別ページごとのMagic Number・Negative Margin類は一切使用していない。016H／016H-R1と全く同じ、既存Token（medium=32px）の再利用のみ。

### 2.3 修正の変更ファイル

`theme/theme.json`（CSSのみ、1箇所）。他のファイルへの変更なし（`git diff --stat`で確認：`theme/theme.json | 2 +-`のみ）。

## 3. 修正の検証

### 3.1 影響9ページ全数の修正後再確認（1440px、Kicker/H1境界クロップ）

`docs/research/screenshots/016e-audit/kicker-{service-archive,service-single,case-archive,case-single,professional-archive,faq-archive,voice-archive,search,404}.png` の9枚すべてで、Kicker（オレンジ文字ラベル）とH1本文の間に明確な間隔があり、重なりが解消されていることを確認した。

### 3.2 HOME無影響確認

`home-recheck.png` にて、HOME自身（本Orderのセレクタとは別系統の`h2:has()`セレクタを使用）が016H-R1承認時点の状態と完全に一致していることを確認した。修正は内部ページ専用Selectorにのみ適用されており、HOMEのSection Headingには一切影響していない。

## 4. Ownerが列挙した各内部ページの個別監査

Order本文が名指しした全ページ種別を、016F/016F-R1の成果物を正としてOne-by-oneで確認した。

| ページ | スクリーンショット | 結果 |
|---|---|---|
| Service Archive | `01-service-archive.png`, `kicker-service-archive.png`, `mobile375-service-archive.png` | Kicker衝突を修正、他は016F通り正常。モバイルでも崩れなし |
| Service Single | `02-service-single.png`, `zoom-top.png`（発見の元）, `kicker-service-single.png` | 最も衝突が顕著だったページ。修正確認済み |
| CASE Archive | `03-case-archive.png`, `kicker-case-archive.png`, `mobile375-case-archive.png`, `nophoto-case-archive.png` | Kicker修正確認。No-Photo Fallback（写真なしCard）も正常（§5参照） |
| CASE Single | `04-case-single.png`, `kicker-case-single.png`, `nophoto-case-single.png` | 同上。No-Photo Singleは画像領域自体を上品に省略、崩れなし |
| Professional Archive | `05-professional-archive.png`, `kicker-professional-archive.png`, `nophoto-professional-archive.png` | Kicker修正確認。No-Photo Fallback（グラデーション+姓の1文字）健全（§5参照） |
| Professional Single | `06-professional-single.png`, `mobile375-professional-single.png`, `nophoto-professional-single.png` | 016F-R1のテキスト列`flex:0 1 auto`+写真`clamp()`のまま健全。No-Photoは画像領域自体を省略（プレースホルダー無し）で最も安全な処理 |
| FAQ Archive | `07-faq-archive.png`, `kicker-faq-archive.png`, `mobile375-faq-archive.png` | Kicker修正確認。Orderが懸念していた「モバイルで見出しが細い帯になる」不具合は再現せず（§6参照） |
| Office | `08-office.png` | 016G確立の2カラムデザインのまま健全。KickerテキストなしはDecision 028のZero-Fixture-Dependency原則に基づく意図的な設計（Office Profileに"Kicker用の値"というFixtureフィールドが存在しないため、存在しないデータを捏造しない）であり、退行ではない |
| Contact | `10-contact.png` | 016F確立のデザイン（パンくず、末尾に罫線付きH1、リード文、フォーム全項目）のまま健全。KickerテキストなしはOfficeと同じ意図的設計（同一のZero-Fixture-Dependency原則）で一貫している |
| 404 | `11-404.png`, `kicker-404.png` | Kicker修正確認。016F-R1で再構築した「トップページへ」「サイト内をさがす」「お電話でのご相談」の3リンクも正常表示 |
| VOICE Archive | `12-voice.png`（旧）, `full-voice-archive.png`, `kicker-voice-archive.png` | Kicker修正確認。3件のVOICEカードグリッドが正常表示 |
| Search | `12-search.png`（旧）, `full-search.png`, `kicker-search.png` | Kicker修正確認。`--astrea-v3-editorial-max`幅の複数投稿タイプ横断結果一覧が正常表示 |
| Price（Archive相当） | `09-price.png` | 016G確立の`limit`機能・カード表示とも健全、退行なし |

**結論：Order §26 Q8「まだVisual v2に見える内部ページがあるか」に対する誠実な回答は「いいえ」——ただし、修正前は9ページにKicker/H1衝突という実欠陥が存在しており、これは本監査で発見・修正した。**

## 5. No-Photo Fallback の実地検証（Order §9/§11の懸念に対する回答）

現在のFixtureはCASE 3件・Professional 3件のいずれも`_thumbnail_id`を保持しており、No-Photo状態がライブ環境で一度も検証されていなかった。Order §9/§11が明示的に懸念する「巨大な灰色破損画像矩形」「巨大なイニシャルプレースホルダーが欠落コンテンツに見える」という失敗モードを検証するため、以下の手順で**一時的**に確認した：

1. CASE ID 2039・Professional ID 2021の`_thumbnail_id`を`wp post meta get`でバックアップ（値: 2096, 2120）
2. `wp post meta delete`で該当2件のみ一時的に削除
3. Single/Archive双方をスクリーンショット取得（`nophoto-*.png`）
4. `wp post meta update`で即座に元の値へ復元、`wp post meta get`で復元値が2096/2120と一致することを確認

**結果**：
- CASE Single／Archive：画像領域を完全に省略し、テキストのみのレイアウトへ自然に折りたたまれる。灰色破損矩形は一切表示されない。
- Professional Archive：既存の「グラデーション背景+氏名から1文字取った大きな明朝体の漢字」という上品なFallbackが表示される（例：佐藤健一→「田」ではなく実際には氏名の姓の一文字、他の2名は「田」「鈴」で確認）。
- Professional Single：画像領域自体を完全に省略（Archiveのようなグラデーション+漢字プレースホルダーすら出さない）。プロフィール本文がH1から直接始まる、最も安全な処理。

いずれもOrderが懸念する失敗モードは発生しない。既存のFallback設計は016F時点で既に確立済みであり、本Orderの範囲では変更を要しなかった。

## 6. モバイル（375px）確認

Orderが名指しで懸念したFAQ（「見出しが細い帯になる」失敗モード）を含め、Service Archive／CASE Archive／Professional Single／FAQ Archiveの4ページを375pxで確認（`mobile375-*.png`）。Kicker→H1の縦間隔修正後もモバイルでの崩れ・極端な狭小化は再現しなかった。

## 7. Style Variations（Trust／Natural／Modern）

Service Single（衝突が最も顕著だったページ）のPage Title Kicker境界を、`wp_global_styles`（Post ID 825）への一時差し替え手法でNatural／Modern双方確認した（`natural-service-single-kicker.png`、`modern-service-single-kicker.png`）。フォントメトリクスの違い（Natural=明朝系、Modern=Sans系太字）によってもKicker/H1衝突は再発しないことを確認した。Trustへ復元後、差し替え前バックアップとdiffで完全一致を確認済み（§8参照）。

## 8. 自動化テスト・安全性確認

- **PHPUnit**: `npx wp-env run tests-cli "vendor/bin/phpunit"`で398/398実行。既知のPre-existingエラー3件（`wp-phpunit`のAttachment Factory起因、`SeoMetaTest`×2・`SetupTest`×1）のみ、無変更。本Orderで変更したPHPファイルは0件。
- **PHPCS**: 対象PHPファイル0件のため実行対象なし（`git diff --stat`で`theme/theme.json`のみ変更を確認済み）。
- **Theme Check**（公式Plugin、検証専用に一時インストール→検証後アンインストール済み）: `wp theme-check run astrea` — REQUIRED 0 / WARNING 0 / INFO 1（テーマスラッグとText Domainの一致に関する情報のみ、既存・無変更）。
- **Horizontal Overflow**: 7幅（1920/1440/1366/1024/768/375/320）× 13ページ（HOME/Service Archive/CASE Archive/Professional Archive/FAQ/VOICE/Office/Contact/Search/404/Service Single/Professional Single/CASE Single）= 91パターンを機械的に確認、`scrollWidth > clientWidth`の超過0件。
- **Core OFF→ON**: `wp plugin deactivate astrea-core`後HOME HTTP 200、Fatal/Warning無し。`wp plugin activate astrea-core`で再有効化後もHTTP 200。Decision 021（Themeを人質にしない）を維持。
- **Style Variations復元検証**: `wp_global_styles`（Post ID 825）をNatural／Modern差し替え後Trustへ復元、差し替え前バックアップとPython `json.dumps(sort_keys=True)`でdiff、完全一致（`IDENTICAL - Trust restored cleanly`）を確認。

## 9. Fixture整合性

本Orderで一時的に変更したのは、No-Photo検証のためのCASE/Professional各1件の`_thumbnail_id`のみ（§5）。検証直後に即座に復元し、復元値が事前バックアップと一致することを`wp post meta get`で確認済み。既知の`smoke-test.sh`起因のOffice Profile汚染は、本Orderでは`smoke-test.sh`自体を実行していないため発生していない。

## 10. Order §26 Visual Acceptance Questions への回答

- **Q1（HOMEとの視覚的一貫性）**: Yes。Kicker+Title+ruleのPage Header System、Wide Grid、Card意匠、Spacing Tokenのすべてが016F/016F-R1でHOMEと共有のDesign Systemから構築されており、本Orderで発見した1件の欠陥（Kicker/H1衝突）を修正した結果、HOMEと同一水準の視覚品質に揃っている。
- **Q2（Kicker/見出しの衝突）**: 本Orderの主要な発見・修正事項として上記の通り。修正済み。
- **Q3（No-Photo時の破綻）**: §5の通り、CASE/Professionalとも上品なFallback（またはFallback省略）で破綻なし。
- **Q4（モバイルでの崩れ）**: §6の通り、375px幅で崩れ・過度な狭小化は確認されず。
- **Q5（Horizontal Overflow）**: §8の通り、91パターン中0件。
- **Q6（Core OFF時の安全性）**: §8の通り、HTTP 200・Fatal無し。
- **Q7（Style Variation間の一貫性）**: §7の通り、Trust/Natural/Modernいずれも衝突なし。
- **Q8（まだVisual v2に見える内部ページの有無）**: **修正前は「Yes——9ページのPage Title Kickerが衝突していた」が正直な回答だった。** 本Reportで発見・修正した結果、現時点では「いいえ、該当ページなし」。この欠陥を隠さず本Reportに明記する。

## 11. Locked/Prohibited遵守確認

新機能・新Data Model・新CPT Schema・新設定項目・新Setup Flow・新SEO挙動のいずれも追加していない。デモ専用コードの追加なし。Fixtureへの破壊的操作なし（§5・§9の通り一時変更は即座に復元・検証済み）。Core OFF時の安全性を損なう変更なし。Version Bump・Tag・GitHub Release・Deploy・WordPress.org提出のいずれも実施していない。

## 12. 測定値・Commit

- Start: 2026-09-04（本Order着手）
- End: （本Report作成・Commit直前の実測時刻、Commit確定後にHISTORY.csvへ正確な値を記録）
- Duration: 実測ベース、HISTORY.csvへ記録
- Commit: 本Report Commit自身のID（HISTORY.csv確認コミットで反映）
- CI: Push後 `gh run list`/`gh run watch`で確認し、別途HISTORY.csv確認コミットで記録する

---

**Status: AWAITING OWNER INTERNAL PAGES RE-AUDIT ACCEPTANCE**

本Order（記録ラベル016I、Owner支給テキスト上のラベル"016E"）はRelease Readyを宣言しない。次のConstruction Orderへは自律的に進まない。Tag・GitHub Release・Deployのいずれも実施していない。Ownerがスクリーンショット・本Reportを確認し明示的に承認した後にのみ、次工程を決定する。
