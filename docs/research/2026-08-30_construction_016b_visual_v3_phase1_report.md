# Construction Order 016B — Visual v3 Implementation Phase 1（Header / Hero / First View）実装Report

- **Construction Order**: 016B
- **Date**: 2026-08-30
- **Status**: COMPLETE（Phase 1のみ。RELEASE HOLD継続、Phase 2はOwner Approval待ちでSTOP）
- **Functional Baseline**: RC1 (1.0.0-rc1) — 変更なし

## 0. 前提・参照

- Source of Truth: `docs/specifications/07_astrea_visual_v3_design_direction.md`（016A/016A-R1承認済み設計）、`docs/research/visual-v3/mockups/visual-v3-home-desktop-r1.html`（Owner承認Mockup最終版）。
- 本Orderで初めてVisual v3の実Product Code実装を許可された。範囲は **Header + Hero + First View（レスポンシブ挙動）のみ**。Services/Results/Professional/CASE/Price/FAQ/VOICE/Footer/Archive/Single v3実装、既存5件のPost v1 Backlog Findings対応、Release/Tag/Deploy関連操作は本Orderの範囲外として一切行っていない。

## 1. 変更ファイル一覧

| ファイル | 変更内容 |
|---|---|
| `theme/parts/header.html` | 外側GroupへclassName `astrea-site-header` 追加。既存の電話CTAボタンの隣へ新規「お問い合わせ」CTAボタン（`is-style-outline astrea-header-contact`、`href="#"`）を追加。 |
| `theme/patterns/home-hero.php` | Hero Pattern全面刷新。`core/cover`（`className:"astrea-hero-v3"`、`dimRatio:100`・`overlayColor:"contrast"`・画像なしが出荷Default）＋Eyebrow（`astrea-hero-eyebrow`、事務所名Binding）＋H1（`astrea-hero-h1`、事務所名Binding、Construction 011契約を維持）＋Supporting Copy（`astrea-hero-copy`）＋既存の電話/お問い合わせButtons。 |
| `theme/theme.json` | `settings.color.palette`へ`accent`（`#B99A5C`）追加。`settings.custom.typography`へ`heroTitle`（`clamp(2.4rem, 4.6vw, 4rem)`）・`heroEyebrow`（`0.78rem`）追加。`styles.css`へHeader/Hero用CSS追加（Desktop/Tablet/Mobile、Next Section Hint、Mobile Overlap構図を含む）。 |
| `theme/styles/trust.json` | `accent` `#B99A5C` を追加。 |
| `theme/styles/natural.json` | `accent` `#c2a46b` を追加。 |
| `theme/styles/modern.json` | `accent` `#a8925c` を追加。 |

Core（`astrea-core`）側の変更はゼロ。新規Block/Block Bindings Sourceも追加していない（Header CTAは既存の`href="#"`静的リンク規約を踏襲、Bindings新設不要と判断）。

## 2. 実装アプローチの要点

- **Header**: Site-wide Template Partであるため、写真の上へ物理的にOverlapさせる実装（`position:absolute`／Negative Margin）は採用せず、**Hero直上へ隙間なく接続する透過・軽量なHeader**とした。理由と設計判断の詳細は`docs/specifications/07_astrea_visual_v3_design_direction.md`§15に記録。
- **Hero（写真なし=出荷Default）**: `core/cover`に`url`を設定しないことで、Gutenberg標準機能のみで単色Cover（`overlayColor`のみ）となる。追加コード無しで「意図的な、壊れていないDefault」を実現（Design Direction §11）。
- **Hero（写真あり）**: Owner Fixtureには実Owner資産`docs/research/visual-v3/assets/model-house/hero-office-city.png`をMedia Libraryへ登録（Media ID 2096）し、`dimRatio:75`で実写真上の白文字コントラストを確保した状態で反映した。
- **Mobile Hero Overlap**: 016A-R1承認構図（写真右上ブリード＋Textプレートの55pxオーバーラップ）を、`:has(.wp-block-cover__image-background)`で「写真が実際に設定されている場合のみ」Scopeして実装。写真なしの出荷Defaultはこの構図を使わず、コンパクトな単色Hero（Column Stack、CTA全幅）へ自然にFallbackする。オーバーラップ実装では016A-R1で発見済みのCSS Margin Collapsing罠（親へ`overflow:hidden`が必要）を最初から踏まえて実装した。
- **Next Section Hint**: Markupを追加せず、`.astrea-hero-v3::after`のCSS疑似要素のみで表現（Gutenberg Block ValidationのRound-trip検証を壊さないための設計判断）。

## 3. Block Validation確認

Application PasswordやAuth Cookie生成を伴うWordPress管理画面への自動ログインは、本セッションの権限ポリシー上実行できなかった（資格情報操作系コマンドが実行時ブロックされた）ため、Editor実機でのBlock Validation警告有無の直接目視確認は行っていない。代替として、実際に保存されているHOME（Page ID 1914）のPost ContentをWordPress自身の`parse_blocks()`→`serialize_blocks()`で再直列化し、元の保存済みMarkupとバイト単位で一致することを確認した（完全一致、5470 vs 5470文字）。これはGutenbergの実際のInvalid Content判定（Block Attributesから再生成した想定Markupと保存済みMarkupの比較）そのものではないが、強い代理指標である。加えてHeader Template Part・Hero Patternとも、既存のFixtureで実際にEditorから生成・保存された構造（属性→className→Markupの対応）をそのまま踏襲して手書きしており、新規パターンの組み合わせ（`textColor`・`className`・`metadata.bindings`の併用）は本Themeの他Pattern（例: 既存Closing CTA）で実績のある組み合わせのみを使用した。

## 4. レスポンシブテスト（実機、320/375/768/1024/1440px）

実際のWordPress（`http://localhost:8888/`）をPlaywright（`mcr.microsoft.com/playwright:v1.62.1-jammy`）で検証。

| 幅 | 結果 |
|---|---|
| 1440px | Header+Hero一体の見た目、H1/Eyebrow/Copy/CTA/電話ボタンとも正常。Horizontal Overflowなし。 |
| 1024px | 同上、Desktop Layoutを維持。 |
| 768px | Desktop Layout（768pxはMobile Breakpoint`max-width:600px`の対象外）で正常表示、Overflowなし。 |
| 375px | Mobile Overlap構図（写真右上ブリード＋濃色プレート）が正しく発現、CTAは全幅Stack。 |
| 320px | 同上、320pxでもOverflowなし（「お問い合わせはこちら」ボタンのみ内部で2行折返し、実害なし）。 |

いずれもPHP Fatal/Warning/Notice出力0件（レスポンス本文Grepで確認）。

## 5. 長い日本語文字列ストレステスト

`.fixture-backups/fixture-backup-20260830_010405.sql`へOwner Fixture DBを事前Backup（`docker cp`でHostへ退避、Host/Container Path取り違えを事前に確認した上で実行）。`astrea_core_office_profile`オプションの`office_name`を一時的に「東京都〇〇許認可・法人設立・相続手続総合行政書士事務所」（24文字）へ変更し、Desktop（1440px）・Mobile（375px）を確認。

- Desktop: H1が3行に自然折返し、Overflowなし。
- Mobile: プレート内でH1が3行、Copyが4行に折返し、プレートの高さがContentに応じて伸長し崩れなし。

確認後、同オプションを元の値（`ASTREA行政書士事務所`）へ復元し、実機で表示が元に戻っていることを確認済み。スクリーンショットは`docs/research/screenshots/016b/stress-long-name-*.png`。

## 6. Accessibility確認

- H1はページ全体で1個のみ（`astrea-hero-h1`、事務所名）。
- `<header>`・`<nav>`（Desktop/Mobile Overlay Menu双方）・`<main>` のLandmarkが実機HTMLに存在。
- Hero画像には内容説明型のAlt Text（`都市の街並みを背景にしたオフィス外観のイメージ写真`）を設定（既存Fixture Hero画像と同じ規約）。
- 電話/お問い合わせCTAはButtonとしてキーボードTab到達可能（既存`core/button`のFocus Style・Outlineをそのまま継承、新規CSS未変更）。
- Contrast: 新規`accent`（Gold）はWCAG AA計算の結果、明背景でのText用途は2.3〜3.0:1で不合格、暗背景（Hero Overlay上）では5.2〜6.2:1で合格。この結果に基づき、`accent`はHero Eyebrowの装飾罫線（非Text要素）のみに使用し、Text色としては使用していない。Hero本文（White on 暗Overlay/暗プレート）は既存015G Heroと同じ配色のため、既存実績どおり十分なコントラストを確保。

## 7. Core OFF確認

`astrea-core`を`wp plugin deactivate`で無効化し、HOME（`/`）を再取得。HTTPステータス200、PHP Fatal/Warning 0件を確認。電話/お問い合わせBindingは値取得元プラグインが無いため静的な既定ラベル・`href="#"`へ安全にFallbackし、壊れたMarkup・空白領域は発生しない。確認後`astrea-core`を再度Activateし元に戻した。

## 8. Style Variation確認（Trust/Natural/Modern）

`wp_global_styles`投稿（Snapshot、post ID 825）の内容を各Variationの値へ一時的に差し替えて実機確認（新規CSSは一切追加せず、既存Token機構のみで検証）。

- **Trust**（Default）: Navy Overlay・Navy Button・Gold罫線。
- **Natural**: Warm Brown系Overlay・Green Primary Button・Pill形状Button。Mobile Overlap構図も正常。
- **Modern**: 濃いGray/Black系Overlay・Black角無しButton。Mobile Overlap構図も正常。

3 Variationとも構造Markup・Theme CSSは完全共通で、Token差し替えのみで意図した見た目の違いが実現できることを確認した（Design Direction §13準拠）。検証用スクリーンショット: `docs/research/screenshots/016b/variation-natural-desktop-1440.png`・`variation-modern-desktop-1440.png`・`variation-modern-mobile-375.png`。検証後、Trust本来のSnapshot内容（`accent`追加を反映した修正版）へ復元し、実機の`--wp--preset--color--primary`がTrustの`#1f3a5c`に戻っていることを確認した。

## 9. 発見した実装ヒヤリハット（対応済み）

Style Variationの「選択済みSnapshot」（`wp_global_styles`投稿、post_name `wp-global-styles-astrea`）が旧いToken値のまま固定されており、`theme.json`・`theme/styles/*.json`への`accent`追加が実機（Owner Fixture）へ反映されない事象が発生した（015B/015Cで既知の「Style Variation選択時のSnapshot特性」と同種）。調査の結果、このSnapshotの`settings.color.palette.theme`は**配列として丸ごとTheme側を上書き**する一方、`settings.custom.typography`のような**Object配下の個別キーはTheme側の値とMergeされる**ため、今回新規追加した`heroTitle`/`heroEyebrow`（Objectキー）は追加作業なしで反映され、`accent`（Palette配列の要素）だけが反映されなかった、という原因を特定した。該当Snapshot投稿のPalette配列へ`accent`エントリを追記して復旧した（Trust/Natural/Modern検証時も同様の一時差し替え・復元を実施）。将来同種のTokenをPalette配列へ追加する場合は、既にVariationを選択済みの環境（本Fixture含む）では同じ再同期が必要になることを`docs/specifications/07_astrea_visual_v3_design_direction.md`§15へ記録した。

## 10. Regression結果

- PHPCS: `wp-content/themes/astrea/patterns/home-hero.php` — 0 Errors / 0 Warnings（Docker内PHP 8.3環境）。
- PHPUnit: 359 tests, 560 assertions — OK（全件Pass、既存Baselineと同数）。
- Theme Check（プラグイン一時導入→検証→削除）: INFO 1件のみ（Text-domain単一使用の情報メッセージ、既存Baseline、本Orderの変更とは無関係）。Warning/Error 0件。
- `tools/ci/smoke-test.sh`はOwner Fixture保護のためローカル未実行。CI（Clean環境）へ委ねる。

## 11. Mockup vs 実機の差分（参考、Pixel-perfect一致は主張しない）

- Desktop Hero: Mockupは特定の1枚の承認写真とMockup専用CSSでの検証だったのに対し、実機は「写真なしでも成立する出荷Default」と「実写真設定時」の両方を1つのPatternで両立させている。実写真設定時の見た目（`hero-real-desktop-1440.png`）はMockup（`visual-v3-home-desktop-r1.html`）と概ね一致するが、Header領域はMockupの半透明Overlap表現ではなく実装の「隙間なし・軽量Header」表現になっている（§2・spec §15参照）。
- Typography: Eyebrow/H1/CopyのFont SizeはMockupの値をToken化してそのまま採用しており、実機のサイズ感はMockupと一致する。
- Mobile: 写真ブリード幅・オーバーラップ量（80%幅・55pxオーバーラップ）はMockup/016A-R1と同一の数値を採用。配色（濃色プレート＋白文字）のみ上記の理由で変更。
- Whitespace/Section遷移: Next Section Hintは罫線のみとなり、Mockupの"01 SERVICES"ラベルは実装していない（§2参照）。

## 12. OWNER SCREENSHOTS

すべて `docs/research/screenshots/016b/` 配下（実際のWordPress、Mockup HTMLではない）。

**必須スクリーンショット（Order §指定ファイル名）**:
- `hero-real-desktop-1440.png`
- `hero-real-desktop-1024.png`
- `hero-real-tablet-768.png`
- `hero-real-mobile-375.png`
- `hero-real-mobile-320.png`
- `home-upper-real-desktop-1440.png`
- `home-upper-real-mobile-375.png`

**参考スクリーンショット（Order必須ではないが本Orderの追加検証項目の証跡として保存）**:
- `variation-natural-desktop-1440.png` / `variation-modern-desktop-1440.png` / `variation-modern-mobile-375.png`（Style Variation確認）
- `stress-long-name-desktop-1440.png` / `stress-long-name-mobile-375.png`（長い事務所名ストレステスト）

## 13. Completion Conditions（要点）

- Header/Hero/First ViewのみのProduct Code実装 — 完了。
- Services/Results/Professional/CASE/Price/FAQ/VOICE/Footer/Archive/Single v3実装 — 未着手（範囲外）。
- Post v1 Backlog Findings対応 — 未着手（範囲外）。
- Release/Tag/Deploy関連操作 — 未実施（範囲外）。
- 実機Responsive Test Matrix（320/375/768/1024/1440） — 完了。
- 長い日本語ストレステスト — 完了、Fixture復元済み。
- Accessibility確認 — 完了。
- Core OFFテスト — 完了、再Activate済み。
- Style Variation確認（Trust/Natural/Modern） — 完了、Trust復元済み。
- Regression（PHPUnit/PHPCS/Theme Check） — 完了、全てPass。
- 実WordPressスクリーンショット取得 — 完了（上記12節）。
- Mockup vs 実機差分の文書化 — 完了（上記11節）。
- Documentation/Specification更新 — 完了（`docs/specifications/07_astrea_visual_v3_design_direction.md`§15）。
- HISTORY.csv更新 — 本Report後に実施。
- Git commit / CI green確認 — 本Report後に実施。

Stopped before Visual v3 Phase 2 pending Owner Approval.
