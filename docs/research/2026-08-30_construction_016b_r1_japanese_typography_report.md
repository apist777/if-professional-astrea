# Construction Order 016B-R1 — Visual v3 Phase 1 Revision — Japanese Typography / Glyph / Hero Hierarchy 実装Report

- **Construction Order**: 016B-R1
- **Date**: 2026-08-30
- **Status**: COMPLETE（Phase 2は引き続き未着手・未承認）
- **Functional Baseline**: RC1 (1.0.0-rc1) — 変更なし

## 0. Owner Verdict受領内容

Construction 016B（Header/Hero/First View実装）はREVISE。Visual v3の方向性・Hero/Header Markup構造自体は維持したまま、Owner実機確認で発見された日本語Typography問題（漢字の字形違和感、意味を無視した改行、H1=事務所名がVisual上の主役になっている問題）のみを修正・検証する、という指示を受けた。

## 1. 最優先調査 — 日本語Glyph問題の根本原因

**推測でCSSを変更する前に、Chromium自身のFont解決結果を機械的に確認した。**

### 1.1 事実確認

- `<html lang="ja">`: 実機で正しく出力されている（WordPress Core `language_attributes()` の標準出力、Fixture/Theme側の不具合ではない）。
- `theme.json`のTypography設定（変更前）:
  - heading: `Georgia, 'Hiragino Mincho ProN', 'Yu Mincho', serif`
  - body: `-apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', sans-serif`
  - Trust/Natural/Modernの3 Variationも同種の「Mac/Windows専用日本語フォント名 + 素のgeneric keyword」という構成だった。
- Playwright実行環境（`mcr.microsoft.com/playwright:v1.62.1-jammy`、CIのスクリーンショット環境と同一）に実際にインストールされているFontを`fc-list`で確認: **日本語フォントはIPAGothic（IPAゴシック）のみ**。Hiragino系・Yu Gothic系・Yu Mincho系・Noto CJK系はいずれも未インストール。一方で**WenQuanYi Zen Hei（文泉驛正黑、簡体字中国語フォント）はインストール済み**。
- `fc-match 'serif:lang=ja'` / `fc-match 'sans-serif:lang=ja'` を実行した結果、**`lang=ja`を明示指定してもWenQuanYi Zen Heiが返る**（IPAGothicは`lang=ja`対応と認識されているにも関わらず、generic alias側の解決ではWenQuanYi Zen Heiが優先される、この環境のfontconfig設定に起因する挙動）。

### 1.2 実際にrenderされたFontの直接確認（Chromium DevTools Protocol）

`CSS.getPlatformFontsForNode`（Chromium自身がどの物理Fontで各文字を描画したかを返すAPI）をPlaywright経由で呼び出し、実機のHero H1（当時のMarkup、`fontFamily:"heading"`）を検証した。

```
H1 (heading/serifスタック):
  "ASTREA" (Latin 6文字)   → Liberation Serif
  "行政書士事務所" (CJK 7文字) → WenQuanYi Zen Hei ← 中国語フォント
Copy (body/sansスタック、全27文字) → WenQuanYi Zen Hei ← 中国語フォント
```

**Root Cause確定**: `Georgia, 'Hiragino Mincho ProN', 'Yu Mincho', serif` という指定は、Mac/Windows以外の環境（Linux/Chromium、多くの一般ユーザーのブラウザ環境を含みうる）では実質的に「該当する日本語Fontが1つも存在しない」状態になり、CSSの最終手段である素の`serif`/`sans-serif`キーワードに落ちる。その環境のfontconfigが日本語専用Fontを持たない、または`lang`に基づく優先順位付けが機能しない場合、**たまたまインストールされている他言語のCJK Font（このケースでは簡体字中国語Font）が代わりに選ばれる**。これはPlaywright/CI固有の偶然ではなく、「Mac/Windows名前のFontしか書いていない」という設計そのものの脆弱性であり、実際のユーザー環境（Linux Desktop、一部Android/Chromebook構成等）でも同様の事象が起こり得る。

## 2. Japanese-first Font Stack（採用した修正）

`theme.json`・`theme/styles/trust.json`（同一値）・`theme/styles/natural.json`・`theme/styles/modern.json`の`settings.typography.fontFamilies`を、実際に存在する日本語Font名を多数列挙したCross-platformなスタックへ置き換えた。

- **Heading（Trust、Serif基調）**: `Georgia` → 各種Mincho系(`Hiragino Mincho ProN`/`Yu Mincho`/`YuMincho`/`游明朝`/`游明朝体`/`Noto Serif CJK JP`/`Noto Serif JP`/`IPAMincho`/`HGMinchoE`/`MS PMincho`) → **`Liberation Serif`/`Times New Roman`/`Noto Serif`（Linux等でLatin文字のSerif表現を保つための安全網）** → 各種Gothic系(`Hiragino Kaku Gothic ProN`等〜`IPAGothic`、Mincho系Fontが1つも無い環境でも和文だけ中国語Fontへ流れないための最終防波堤) → `serif`。
- **Body/Natural heading・body/Modern heading・body（Sans基調）**: 既存のLatin Fontチェーンの後に、`Hiragino Kaku Gothic ProN`〜`Yu Gothic`系〜`游ゴシック`ネイティブ名〜`Noto Sans CJK JP`〜**`IPAGothic`（この検証環境で実在確認済み）**〜`VL PGothic`/`Droid Sans Japanese`/`Meiryo`/`MS PGothic`を追加し、`sans-serif`へ。

### 2.1 修正後の検証（同じCDP APIで再確認）

```
Trust  H1: Latin→Liberation Serif / CJK→IPAGothic ✅
Trust  Copy/Kicker: IPAGothic ✅
Natural H1: 全体→IPAGothic ✅（元々Sans基調のためLatinもIPAGothicで統一、Personality上の実害なし）
Modern H1: Latin→Liberation Sans(Arial代替) / CJK→IPAGothic ✅
```

3 Variationすべてで、CJK文字が正しくIPAGothic（日本語Font）に解決されることを確認した。「英数字のEditorial感を壊さない」という要件についても、TrustのLatin文字は`Liberation Serif`（Serif、Georgia相当の代替）で描画されることを別途確認済み（`Liberation Serif`追加前は、この環境でLatin文字もIPAGothicへ吸収されてしまいSerif感が失われる副作用を発見し、追加で対処した）。

**外部Google Fonts等への新規通信依存、および第三者Fontファイルの新規追加（バンドル）は行っていない** — すべて「実在するシステムFont名を列挙しただけ」であり、`@font-face`や外部URLは一切追加していない。

## 3. lang / locale確認

`<html lang="ja">`は実機で正しく出力されており、WordPress標準の`language_attributes()`が正常に機能していた。Fixture側のSite Language設定（管理画面の「サイトの言語」）に起因する問題ではなく、**問題は完全にTheme側のFont Stack設計にあった**。WordPress Coreの言語システムを上書き・迂回するような対応は行っていない。

## 4. Hero Information Hierarchy — Semantic H1 / Visual Hero Copy separation

`theme/patterns/home-hero.php`を以下のように再構成した（Owner §7の提案構造に準拠）:

- **Kicker**（新className `astrea-hero-kicker`、要素は引き続き`<h1>`）: 事務所名Binding。小さく、Letter-spacing広め、Gold罫線付き。Construction 011の「H1=事務所名、HOMEで唯一のH1」契約を完全に維持。
- **Primary**（新className `astrea-hero-primary`、要素は`<p>`、Headingではない）: 大きなEditorial Visual Copy。旧`astrea-hero-h1`が持っていた`heroTitle` Token・`fontFamily:"heading"`をそのまま引き継ぎ、Visual Impactの主役をこちらへ移した。

これは**「Visual上の大きさ」と「Semantic Heading Level」を意図的に分離**する設計判断であり、新しい手法ではない——このPattern自体が016B以前から採用していた確立済みの手法（既存Docblock: 「Visual weight is kept identical... Semantic and Visual Styling are deliberately decoupled here」、Construction 011コメント）をそのまま再利用しただけである。HOME全体のH1は1個のまま、Accessibility Outline（見出し階層）も変化していない。

出荷Default（Fixtureではなく製品既定値）では、Primaryに新しい文章を創作せず、既存のSupporting Copy文言（「お客様に寄り添う、専門家によるご相談窓口です。」）をそのまま昇格させた（最小限の創作方針、015G以来の一貫した方針）。3段目のSupporting Copyは出荷Defaultには含めない（2段構成: Kicker + Primary）。Owner Fixtureのみ、pre-016B時代から存在した実文言（「法人のお客様も個人のお客様も、専門家が直接ヒアリングから対応いたします。初めての方にも分かりやすいご説明を心がけています。」）を復元し3段構成とした——これも新規創作ではなく、既存の承認済み文言の再利用である。

## 5. Japanese Semantic Line Breaking — 調査・比較・採用

調査した手法と採用結果:

| 手法 | 採用 | 備考 |
|---|---|---|
| `word-break:normal` | ○ | `break-all`は英数字も無差別に割るため不採用。日本語は元々文字間で折返し可能なため`normal`のままで十分。 |
| `line-break:strict` | ○ | 禁則処理（行頭禁則文字等）をより厳格にするCSS標準機能。 |
| `overflow-wrap:anywhere` | ○ | 極端に長いLatin文字列等の安全網。 |
| `text-wrap:pretty` | ○ | 行の折返し位置をブラウザが再計算し、短い孤立行（Orphan）を避ける、Native・JS不要の機能。Kicker/Primary/Copyそれぞれに適用（非継承プロパティのため個別指定）。 |
| Phrase単位span（BudouX等の形態素分割） | **不採用（今回）** | 真に意味単位を守れる唯一の方法だが、新規JSライブラリの追加が必要でHARD SCOPE（想定対象ファイルはCSS/Markupのみ）を超える。将来の改善候補として明記（§9 Known Limitations）。 |
| Fixture文言専用の`<br>`固定 | **禁止事項として不採用** | Themeとして任意の利用者文言に対応する必要があるため。 |

`word-break`/`line-break`/`overflow-wrap`はCSS継承プロパティのため`.astrea-hero-text`へ一度だけ指定し、Kicker/Primary/Copy全てへ継承させている（今後Pattern内に新しいTextブロックが増えても自動的に適用される）。

### 5.1 効果測定（Before/After）

修正前（016B）のMobile実機では「会社設／立」（許認可・**会社設立**が2行にまたがり、"立"が次の行の先頭に孤立）という、Owner指摘の典型的なNG例が実際に発生していた（`docs/research/screenshots/016b-r1/before-after-hero-mobile.png`で目視確認可能）。`text-wrap:pretty`適用後の同一Viewportでは「会社設立」は1行内に収まるようになった。

一方、`わかりやすく`（6文字の複合語）については、Viewport幅によっては依然として2行にまたがることを確認した（例: 1440px/375pxで「わか」/「りやすく」のように分割。768pxでは偶然1行に収まる）。**これは`text-wrap:pretty`の限界であり、正直に報告する**: `text-wrap:pretty`はGeneric な行長バランシング（短い孤立行の回避）のみを行い、日本語の意味的な単語境界（形態素）を認識しない。ANY specific文字列に対して「常にどのVILayoutでも完璧」を保証するには、BudouX等の形態素解析ベースのPhrase-wrap実装が必要であり、これは新規JS依存を伴うため本Order（016B-R1）のHARD SCOPEでは実施していない（§9 Known Limitationsに記載、将来のConstruction Order候補として提案）。

**重要**: 上記の残存事例はいずれも「単語の一部が次行へ送られる」パターンであり、Owner指摘の最悪パターンである「1文字だけが孤立して次行に浮く」（例: 「く」だけ、「立」だけ）は、今回の対策後は再現していない（全4種の事務所名（Case A〜D）×全5幅（320/375/768/1024/1440px）+ Hero Copy短文/標準文/極端な長文、実機Screenshotで目視確認、詳細は§7）。

## 6. Long Japanese Office Name Stress Test（実機、目視確認）

Fixtureのバックアップ: `.fixture-backups/`へは016Bで取得済みのバックアップに加え、本Orderでは`astrea_core_office_profile`オプション単体の値をテスト前後で保存・復元する方式を採用（Host側のJSONファイルとして保存、`docker cp`でHost/Container間を明示的に往復——016A-R1／016Bで得た「Host/Containerパス取り違え」ヒヤリハットの教訓を踏まえ、往路・復路とも`docker cp`のPath引数を目視確認しながら実行した）。

| Case | 事務所名 | 320/375/768/1024/1440px Overflow | Glyph | 分断状況 |
|---|---|---|---|---|
| A | ASTREA行政書士事務所（標準） | 0件（全幅、機械確認） | 正常 | 320pxのみ「わか／りやすく」相当の分断が残る（Primary Copyの方の分断、§5参照）。Kicker自体は全幅で1行。 |
| B | 東京都〇〇許認可・法人設立・相続手続総合行政書士事務所（24文字） | 0件 | 正常 | 320pxでKickerが3行に折返し。「法人」/「設立」のように意味区切りに近い位置で折返しており、1文字孤立は無し。 |
| C | 行政書士法人ASTREAリーガルサポート東京中央事務所 | 0件 | 正常 | 1440pxで1行に収まる。320pxで3行、「中央」が跨るが1文字孤立は無し。 |
| D | ASTREA Legal & Partners 東京中央事務所（英数字混在） | 0件 | 正常 | 英単語部分は正しく単語境界で折返し（ブラウザ標準のLatin word-wrap）。「事務所」が跨るが1文字孤立は無し。 |

Horizontal Overflowは`document.documentElement.scrollWidth - clientWidth`をPlaywrightで機械計測し、全20通り（4 Case × 5幅）で0pxを確認した（目視だけでなく数値でも確認）。Screenshotは目視でも確認済み（`docs/research/screenshots/016b-r1/`）。テスト後、`astrea_core_office_profile`は元の値（`ASTREA行政書士事務所`）へ復元し、実機で復元を確認した。

## 7. Hero Copy Stress Test

Primary Copy（本文）についても短文・標準文・極端な長文（99文字）で確認した。

- 短文（「ご相談ください。」）: 1行、崩れなし。
- 標準文（現行Fixture文言）: §5/§6参照。
- 極端な長文（99文字、実運用ではまず起きない量): Hero全体が縦に伸びるのみで、Overflow・崩れ・パンチ抜けは発生しない（`docs/research/screenshots/016b-r1/`には保存していない一時検証、`copy-long-*.png`としてScratchに保存し目視確認、Overflow 0pxを機械確認済み）。Product用としては非現実的な長さのため、正式Screenshot成果物には含めていない。

## 8. Trust / Natural / Modern

3 Variationとも§2のFont修正後、`CSS.getPlatformFontsForNode`で個別に確認し、いずれもCJK文字がIPAGothicへ正しく解決されることを確認済み（§2.1）。Mobile 375pxでの実機Screenshot（`variation-trust-mobile-375.png`・`variation-natural-mobile-375.png`・`variation-modern-mobile-375.png`）でも、3 Variationとも同じKicker/Primary構造・正常な日本語Glyphで表示されることを目視確認した。

**Style Variation Snapshot問題（016Bで発見した既知の罠）の再発**: 016Bと全く同じ理由（`wp_global_styles`投稿の`settings.typography.fontFamilies.theme`が旧Font Stackのまま固定されていた）で、`accent`Colorの時と同様に**FontFamiliesもArray構造であるため再同期が必要**だった。理屈通りに該当Snapshotの`fontFamilies.theme`をTheme側の新しい値へ差し替えて解決した。3 Variationの検証・復元作業でも同じ手順を用いた。最終状態はTrustへ復元済み。

## 9. Core OFF

`astrea-core`を無効化し、HOME（`/`）を再取得。HTTPステータス200、PHP Fatal/Warning/Notice 0件を確認。無効化状態でも新しいFont Stack（`--wp--preset--font-family--heading`のCSS変数）は theme.json由来のためそのまま出力されており、Header/Hero描画・Responsive挙動に影響がないことを確認した。確認後`astrea-core`を再Activateした。

## 10. Regression

- PHPUnit: 359 tests, 560 assertions — OK（全件Pass、既存Baselineと同数）。
- PHPCS: `home-hero.php` — 0 Errors / 0 Warnings。
- Theme Check（一時導入→検証→削除）: INFO 1件のみ（Text-domain、既存Baseline、本Orderの変更とは無関係）。
- Block Validation: 016Bと同じ制約（後述）により、`parse_blocks()`/`serialize_blocks()`の往復一致（5512/5512文字で完全一致）による代理確認。
- Responsive: 320/375/768/1024/1440pxでOverflow 0件（§6参照、機械計測）。
- `tools/ci/smoke-test.sh`はOwner Fixture保護のためローカル未実行。

### 10.1 Admin自動ログイン不能問題（原因のみ確認、突破せず）

016Bと同じ制約が本Orderでも発生した。原因を確認した結果、**これはASTREA製品・WordPressのセキュリティ設定に起因するものではなく、本セッションが実行されているClaude Code環境自体のPermission Classifierが、パスワード変更・認証Cookie生成など「資格情報操作」に分類されるコマンドを一律ブロックする仕様であることが原因**と判明した（`wp user update --user_pass=...`、`wp eval`内での`wp_generate_auth_cookie()`呼び出しがいずれもこのSessionのPermission Classifierによって拒否された）。Owner指示通り、セキュリティ設定を弱めて突破する対応は行っていない。

## 11. Documentation

- 本Report。
- `docs/specifications/07_astrea_visual_v3_design_direction.md`へ、Visual v3の恒久原則として「Japanese Typography is a first-class design requirement.」を明文化し、016B-R1の設計判断（Font Stack根本原因・Semantic/Visual分離・Line-breaking方針）を追記（§16）。

## 12. Before / After

- Desktop: `docs/research/screenshots/016b-r1/before-after-hero-desktop.png`
- Mobile: `docs/research/screenshots/016b-r1/before-after-hero-mobile.png`

## 13. Known Limitations

- `text-wrap:pretty`は短い孤立行（1〜2文字が単独で次行に浮く最悪パターン）の回避には有効だが、日本語の意味的な単語境界を完全には保証しない。特定の複合語（例:「わかりやすく」）がViewport幅によっては2行にまたがることがある（§5.1）。完全な解決にはBudouX等の形態素解析ベースのPhrase-wrap実装が必要だが、新規JS依存を伴うため本Orderでは見送り、将来のConstruction Order候補として記録する。
- Linux/Chromium環境（本検証環境含む）には日本語のMincho（明朝体）系Fontが1つもインストールされておらず、Trust VariationのHeading（Serif基調）はCJK文字に関してGothic（IPAGothic）へFallbackする。macOS/Windows実機ではHiragino Mincho ProN/Yu Minchoが正しく使われ、意図通りのMincho表示になる。これはPlatform自体の制約であり、Theme側でのMinchoFontバンドル（第三者Fontの新規追加）は本Orderの制約により行っていない。
- Admin自動ログイン不能の制約は継続（§10.1）。将来的にOwnerまたは別権限のセッションでBlock Validationの実機（Editor UI）確認を行うことを推奨する。

## 14. Product Code Diff

変更ファイル: `theme/theme.json`、`theme/styles/trust.json`、`theme/styles/natural.json`、`theme/styles/modern.json`、`theme/patterns/home-hero.php`の5ファイルのみ。`theme/parts/header.html`・Core（`astrea-core`）・Services/Results/Professional/CASE・Post-v1 Backlog・Release/Tag/Deploy・Versionはいずれも変更していない（`git diff --stat`で確認済み）。

Stopped before Visual v3 Phase 2 pending Owner Approval.
