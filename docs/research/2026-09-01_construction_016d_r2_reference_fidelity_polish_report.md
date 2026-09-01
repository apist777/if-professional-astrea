# Construction Order 016D-R2 実装報告 — HOME Visual v3 Owner Reference Full-Page Fidelity Polish

- **Status**: AWAITING OWNER VISUAL ACCEPTANCE（RELEASE HOLD維持）
- **Who**: クロエ (Chloe)
- **Start**: 2026-09-01 14:19 JST
- **End**: 2026-09-01 16:40 JST
- **Commit**: `061e53f`（CI green確認済み: PHPUnit / Theme-Core independence smoke test / PHP syntax+Coding Standards、全Job成功）
- **Owner Reference**: `docs/research/references/visual-v3-owner-reference.png`（872×1804px、HOMEの上から「まずはお気軽にご相談ください」CTA直後まで。FAQ/Voice/Flowは写っていない）

## 0. 本Orderの前提と、016D-R1との違い

016D-R1は、Owner Referenceの実画像が施工中に配置されなかったため、Order自身の文章仕様のみをSource of Truthとして実装した。Owner はその結果を「Geometry方向は承認、しかしProfessional/Price/左右余白などはまだ見本と差が大きい」と評価し、本Order（016D-R2）ではOwner Referenceの実画像を正本として使うことを明示的に要求し、「016D-R1の文章仕様だけを代替Referenceとして使用することは禁止」と釘を刺した。

本Orderは、画像配置前にSTOPし、Owner本人による画像配置後の再開指示を受けてから着工した（詳細は本Reportの元になった会話ログ参照）。

## 1. Reference測定（Playwright Canvas Pixel Sampling）

PIL/ImageMagickがサンドボックス環境に存在しなかったため、Playwright（Chromium）のCanvas API（`drawImage`→`getImageData`）でPixel単位のEdge検出スクリプトを自作し、Reference画像そのものを実測した。単一Y行のScanでは内部余白の影響で不正確な結果が出たため、Section毎にY範囲を指定してスキャンし、範囲内のmin(left)/max(right)を取る方式へ改めた。

主な発見:

- Services/CASE/Results/Priceの左余白が、Reference画像幅（872px）に対して一貫して約11.5%だった。
- 実装済みの`--astrea-v3-grid-max:1200px`は、1920px幅で左余白約18.75%となり、Referenceより明らかに狭い（Grid内側に余裕がありすぎる=横方向の迫力が弱い）。
- Professionalは写真:テキスト比率が概ね45–50%:50–55%で、016Dの実装値（46%/54%）と既に近かった。
- Priceはアイコン+ラベルが横並びの1行（縦積みではない）、列間に薄い縦罫線あり、Group/Categoryラベルの独立表示なし。
- Final CTAはPrice直下に直接続いている（HOME最下部ではない）。
- Heroは斜め（diagonal）の写真/文字分割・タグライン中心の小さいKickerだが、本Orderの明示的なScope Freeze（Hero大規模改修禁止、Kickerの内容=事務所名という011番の意味論契約）によりこの2点は意図的に変更していない。
- Reference画像自体にFAQ/Voice/Flowは写っていない（Final CTAの直後で画像が終わっている）。この3 Sectionは直接比較対象がなく、既存のEditorial言語（Wide Grid・Kicker+罫線・控えめなSurface・カード化禁止）から外挿して設計した——画像と直接一致するとは主張しない。

## 2. 実装した変更（Section別）

### 2.1 Section Heading Kicker（新規・恒久原則化、Spec 07 §21）

全SectionのH2に、Reference通りの英語Kicker（SERVICE/CASE/RESULTS/ABOUT/PRICE/FAQ/VOICE、Flowは`FLOW`をClass Selectorで追加参加）+ 見出し末尾の追従Gold罫線を追加した。実装はCSSのみ（Markup変更ゼロ）。既存のDecision 028自己非表示Selector（`h2:has(+ .wp-block-astrea-X-list)`）へ`::before`/`::after`を足しただけであり、H2自体が0件時に存在しないため、Kickerが自己非表示の抜け穴になることは構造上あり得ない。

### 2.2 Shared Wide Grid — 下半分への拡張 + Grid-max改定

Price/FAQ/Voice/Flowへ既存の共有Grid Formula（`padding-inline:max(gutter,calc((100%-max)/2))`）を適用し、`contentSize:600–720px`のような独自の中央寄せを廃止した。あわせて`--astrea-v3-grid-max`を`1200px`→`1440px`へ改定（§1の実測に基づく）。

1920px幅での実測（Playwright `getBoundingClientRect`+`getComputedStyle`）:

| 要素 | 左余白 | 右余白 |
|---|---|---|
| Header | 240px | 240px |
| Hero Text Plane | 240px | (photo plane側) |
| Services/CASE/Results/Professional/Price/FAQ/Voice | 240px | 240px |
| Final CTA（内側テキスト、意図的に600px中央寄せ） | 648px | 648px |

Header/Hero/Services〜Voiceが全て240px（12.5%、Reference実測の約11.5%に近い）で完全一致していることを確認した。Final CTAのみ意図的に狭い中央寄せ（Referenceも同様に短いメッセージ+ボタンを中央寄せにしており、Wide Gridいっぱいに広げると不自然になるため）。

### 2.3 Professional（§3）

- H3（代表者名）末尾にGold追従罫線を追加（Section見出しとは別に、名前自体にも罫線）。
- 本文Reading Columnの`max-width`を`34ch`→`560px`へ拡大——旧実装は右側約半分が空白のまま残っていた（Reference比較Screenshotで発見）。
- CTAリンクのLabelを「詳しく見る」→「プロフィールを見る」に変更（Professional専用の文字列。CASEの「詳しく見る」は別ファイルの独立した文字列であり無関係）、Outline Button（枠線+Hover反転）のVisual Treatmentを追加。
- 背景色は§9 Section Rhythmの一覧に従い意図的に無変更（white/light、CASE/Voiceの「subtle surface」とは区別）。

### 2.4 Price（§4）

- Icon+Nameを縦積みから横並び1行へ変更。
- 列間の薄い縦罫線を復元（実装後にReference拡大画像を再確認した結果、当初「罫線なし」と判断していたのは誤りだったため、CSSレビュー中に自己訂正した）。
- Group/Categoryラベルは非表示（Reference通り、Icon自体がカテゴリを表現）。
- 実装中に、`.wp-block-astrea-price-list--compact`関連のCSSが離れた2箇所に重複定義されていたバグを発見・修正（Spec 07 §21に恒久原則化）。修正前はGroup LabelのCSS `display:none`が後方の古いルールに負けて表示され続け、Icon+Name横並びも意図通りに反映されない実害が実機Screenshotで確認された。

### 2.5 Final CTA（§5、今回初めてScope内）

`core/includes/setup-home.php`の`HOME_PATTERN_SLUGS`順序を変更し、`astrea/home-cta`をPrice直後（FAQ/Voice/Flowより前）へ移動。Owner Fixture（Page ID 1914）の`post_content`も同じ順序へ手動更新（このPatternはWordPress Core標準の`register_block_pattern`+一度限りの組み立てであり、既存Fixtureへは自動反映されないため）。ボタンの組み合わせをReference通りに入れ替え（電話=Outline、お問い合わせ=Solid Gold——旧実装は逆だった）。既存のContact URL/電話番号Bindingsをそのまま再利用、新機能は追加していない。

### 2.6 FAQ（§6）

外側`.wp-block-astrea-faq-list`へWide Grid Frameを適用しつつ、内側の各Q/A項目（`.wp-block-astrea-faq-item`）には`max-width:720px`の独立したReading Column制約を与える二層構造にした（「全テキストを1440pxへ引き伸ばす」ではない）。Accordion UIは追加していない（既存の常時展開Markupを維持）。Q/Aラベルの色をGold（Q）/Secondary Gray（A）で分離。

### 2.7 Voice（§7）

薄灰色角丸カード+Shadow相当のGridを廃し、Wide Grid Frame + Surface背景の3カラムEditorial Layoutへ変更。個々のItemは背景/角丸/Shadowを持たず、上罫線のみで区切る。引用文はHeading Fontで落ち着いたTypographyへ、属性（会社名等）はPrimary色で階層化。

### 2.8 Flow（§8）

既存の01/02/03データ・Markup構造は変更せず、Desktopでは横並び3-Step（CSS Counterで採番、上罫線+大きな番号）、782px以下では縦積みへ自動的に戻る。新規Flow機能は追加していない。背景色をSurface（灰）→Base（白/light）へ変更（§9 Section Rhythmに従う）。

### 2.9 Section Rhythm（§9）

| Section | 指定 | 実装 |
|---|---|---|
| Hero | light/photo | 変更なし（Text Plane白+Photo Plane） |
| Services | white | 変更なし |
| CASE | subtle surface | **新規**：Surface背景+縦Padding追加 |
| Results | dark | 変更なし（016D確立） |
| Professional | light | 変更なし（意図的に無背景のまま） |
| Price | white | 変更なし |
| Final CTA | dark | 変更なし（Contrast背景、既存） |
| FAQ | white | 変更なし |
| Voice | subtle surface | **新規**：Surface背景へ変更（旧: 個別カードのみSurface） |
| Flow | light/white | **変更**：Surface（灰）→Base（白） |
| Footer | dark | 変更なし（既存） |

### 2.10 Hero/Header（§12）

Reference/実機の座標比較の結果、Header/Hero Text Planeの左端はGrid-max改定（§2.2）により既にReferenceの実測比率へ揃った。斜めPhoto分割・タグライン中心Kickerへの変更はOrderの明示的Scope Freeze（Hero大規模改修禁止、Kicker=事務所名の意味論契約維持）により見送った——「触らない」のではなく、Fidelity上優先度が低く、かつ変更がConstruction 011番の契約を壊すため。

### 2.11 CASE（§11、写真バリエーション検証）

Fixtureの実際のCASE投稿3件のうち、当初1件のみ写真ありだった（Scenario B: Mixed）。Order許可（Demo目的、Product要件ではない）に基づき残り2件へも既存Library内の写真（`hero-office-city.png`）を仮設定し、Scenario A（全3件写真あり）・Scenario B（Mixed、修正前の状態としてEvidence保存）・Scenario C（全3件写真なし、一時的に検証後Scenario Aへ復元）の3パターンをすべて実機Screenshotで確認し、いずれもFatal/レイアウト崩れなしを確認した。最終Fixtureの状態はScenario A（全3件写真あり）。

## 3. Reference Fidelity Evidence

`docs/research/screenshots/016d-r2/`に格納:

| ファイル | 内容 |
|---|---|
| `01-reference.png` | Owner Reference原本 |
| `02〜07-full-home-*.png` | 1920/1440/1366/1024/375/320px、実機フルページ |
| `08-professional-reference-vs-actual.png` | Professional Reference/Actual比較 |
| `09-price-reference-vs-actual.png` | Price Reference/Actual比較 |
| `10-lower-home-before-after.png` | 016D-R1時点(Before)/016D-R2(After)比較 |
| `11-case-all-photo.png` | CASE Scenario A（全3件写真あり、最終Fixture状態） |
| `12-case-mixed-photo.png` | CASE Scenario B（Mixed、本Order着手時点） |

## 4. テスト結果

- **PHPUnit**: 397/397 Pass, 661 assertions（既存＋新規変更なし、Assertion数は環境Permission修正後の実測値）。実行前、`tests-cli`コンテナ内`wp-content/uploads`のOwnershipが`kenta`のまま（`www-data`実行ユーザーから書き込み不可）でSeoMetaTest/SetupTestが3件Errorになる環境要因を発見、Docker内部Volume（Host Bind Mount対象外、`.wp-env.json`の`mappings`に含まれないPathであることを確認済み）へ`chown`し解消——Host Bind Mount（`tests/`等）へは一切変更を加えていない。
- **PHPCS**: 変更した4 PHPファイル（`professional-profile-block.php`/`setup-home.php`/`home-cta.php`/`home-flow.php`）全件 0 Errors/0 Warnings。
- **Theme Check**: 本Orderでは未再実行（Enqueue/Text Domain/File Header等、Theme Checkが検出しうる観点の変更なし——CSS/既存文字列の置換/Pattern順序のみ。前回Baseline結果=INFO 1件のみを継続採用）。
- **Core OFF**: 未再確認（本Orderでは新規Core機能を追加していないため016D-R1確認結果を継続採用）。
- **Horizontal Overflow**: 320/375/1024/1366/1440/1920pxの6幅すべて0px（Playwright機械計測）。320pxで一度25pxのOverflowを検出（Price 4列Gridが未収束）、Responsive Overrideの配置場所（Source Order）を修正して解消——詳細はSpec 07 §21参照。
- **Trust/Natural/Modern 3 Variation**: `wp_global_styles`投稿（ID 825）へ各Variationの`color.palette`/`typography.fontFamilies`/`custom.border`/`elements.button.border.radius`を差し替えて実機確認、3件とも新CSS（Price/CTA/FAQ/Voice/Flow）が正常動作することを確認、Trustへ復元済み。
- **CASE 写真バリエーション**: §2.11参照、A/B/C全て確認済み。

## 5. 変更ファイル

- `core/includes/professional-profile-block.php`（CTA文言・構造は変更なし、Label文字列のみ）
- `core/includes/setup-home.php`（`HOME_PATTERN_SLUGS`順序変更）
- `theme/patterns/home-cta.php`（ボタン組み合わせ入れ替え、className追加）
- `theme/patterns/home-flow.php`（className追加、Section Rhythm背景変更）
- `theme/theme.json`（Kicker CSS新規、Price/CASE/Voice/FAQ/Flow/Professional CSS変更、Grid-max改定、Responsive Override追加）
- `docs/specifications/07_astrea_visual_v3_design_direction.md`（§21追加）

Fixtureデータ（Page ID 1914のpost_content順序、CASE 2/3の`_thumbnail_id`）はGit管理外のWordPress DB状態であり、リポジトリのDiffには含まれない。

## 6. 既知の・意図的な乖離（正直な報告）

- **Hero斜め分割・タグラインKicker**: Reference通りに変更していない（§2.10参照、Scope Freezeと意味論契約による意図的判断）。
- **FAQ/Voice/Flow**: Reference画像に該当箇所が写っていないため、直接のPixel比較エビデンスは存在しない。既存のEditorial言語からの外挿である旨、正直に記載する。
- **Price見出しの文言**: Referenceは「料金の目安」、実装は既存の「料金」のまま（Order §4は見出しの「処理（Treatment）」再現を求めており、Scope Freezeが本文Copy変更を除外しているため、Visual Treatmentのみ再現し文言は変更していない）。
- **Theme Check / Core OFF**: 本Orderでは再実行せず、前回Baseline結果を継続採用（§4参照）。

## 7. Definition of Done 照合

- 1920/1440でHOME全体を見たとき、Resultsより上と下で別テーマに見えない状態を実機Screenshotで確認した（`02-full-home-1920.png`、`10-lower-home-before-after.png`）。
- Professional/Price/CTAはOwner Referenceと同じ横方向の座標（240px/240px、Grid-max改定後）で揃っている。
- FAQ/Voice/Flowまで含め、カード化・角丸多用・Shadow多用のない、Wide Grid + Kicker + 罫線という一貫したVisual Languageで統一されている。

## 8. Owner Acceptance Gate

RELEASE HOLD継続。tag/GitHub Release/Project-if deploy/WordPress.org submission/v1.0.0-final/Construction 016Eへの自動進行は一切行っていない。

**Status: AWAITING OWNER VISUAL ACCEPTANCE**
