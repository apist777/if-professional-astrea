# Construction Order 016C — HOME Visual v3 Content Sections 実装Report

- **Construction Order**: 016C
- **Date**: 2026-08-31
- **Status**: COMPLETE（RELEASE HOLD継続、Owner確認待ち）
- **Functional Baseline**: RC1 (1.0.0-rc1) — 変更なし

## 0. Order概要と前提

016B-R2完了報告（1920×1080 First View含む）に続き、Ownerより「016C — HOME Visual v3 Content Sections」を受領した。従来のような詳細な章立てOrder文書ではなく簡潔な指示だったため、016A/016A-R1で承認済みの`docs/specifications/07_astrea_visual_v3_design_direction.md`§6〜9（Services/Results/Professional/CASE）と016B-R2で確立した「Design Fidelity Gate」原則を実装の拠り所とし、以下の指示を実装した:

- Servicesを3カラム＋大型アイコンへ
- Resultsを巨大数字へ
- Professionalを人物主体へ
- CASEを写真主体Editorialへ
- セクション見出しを中央ポン置きから脱却
- HeroからFooterまで同じ時代のサイトにする

## 1. Section Heading — 中央ポン置きからの脱却

`h2:has(+ .wp-block-astrea-*)`規則を、Hero Kicker（016B-R1）と同じ「左寄せ＋Gold細罫線」のEditorial記法へ統一した。Services/CASE/Results/Price/FAQ/VOICE/Professionalの全見出しが同一の視覚言語を共有し、Hero→Footerの一貫性（Order最終指示）に直接寄与する。

## 2. Services — 3カラム＋大型アイコン＋Editorial番号

- `core/includes/service-list-block.php`: 各Serviceに汎用の「folder」アイコン（インラインSVG、`currentColor`でVariationごとに再配色）を追加。`astrea_service`投稿タイプ自体にアイコン選択フィールドが無く、新設（新規postmeta・管理画面UI・マイグレーション）は本Orderの範囲を超えるため、任意のService名にも通用する単一の汎用アイコンを統一適用する設計とした（判断根拠はファイル内Docblockに記録）。
- `theme.json` CSS: Card（背景・枠線・角丸）を廃し、3カラムGrid＋上罫線＋CSS Counterによる「01/02/03」ページ番号（新規Core属性不要）へ刷新。

## 3. Results — 巨大数字

- `settings.custom.typography.resultsNumber`を`clamp(1.75rem, 3.5vw, 2.5rem)`から仕様書§7指定の`clamp(3.2rem, 6vw, 5.4rem)`へ拡大。
- `.wp-block-astrea-results-list`を、Decision 028（0件時はSection全体を見出しごと非表示にする）を破らずにFull-bleed濃色帯として実装。**静的なラッピングGroupは使えない**——Dynamic Blockが0件時に空文字列を返す一方、静的なラッパー自体は消えられず、背景色付きの「空の帯」が残ってしまうため（`home-professional-teaser.php`のDocblockに記録済みの同種の制約）。かわりに、**存在する時にしか描画されない`.wp-block-astrea-results-list`要素自身**へ直接Full-bleedスタイルを適用した。

## 4. Professional — 人物主体

- `core/includes/professional-profile-block.php`: 画像サイズを`medium`から`large`へ（`wp_get_attachment_image()`は引き続きsrcset/sizesを自動生成するため、固定ピクセル画像への後退ではない）。仕様書§8が求める「Link」を追加（既存の`single-astrea_professional.html`テンプレートへの導線が欠落していたのを解消）。
- `theme.json` CSS: 円形Avatar＋Surface背景のCard型から、写真が行の55–60%を占める非対称Row型へ刷新。
- **Owner Fixtureの実写真差し替え**: 代表者「佐藤健一」の`_thumbnail_id`が015F/015G時代の小さな自作Avatar画像（頭文字+グラデーション）のままだったため、これを56%幅の行へ引き伸ばすと巨大な文字が破綻して見える実害を実機で発見した。Owner提供の実Wide Photo資産（`professional-sato-kenichi-wide.png`、1536×1024）をMedia Libraryへ登録し、Featured Imageとして差し替えた。

## 5. CASE — 写真主体Editorial

- `core/includes/case-list-block.php`: Media列（写真、または意図的な空状態）と、既存の`related_services`フィールド（新規データ不要）を用いたCategoryラベルを追加。
- `theme.json` CSS: Card型から Number／Body／Media の3カラムEditorial Row型へ刷新。先頭行（Feature）は番号・タイトル・写真とも拡大表示、2行目以降（Secondary）は縮小表示——行位置（`:first-child`）のみで判定し、新規Coreフラグは追加していない。No-Photo Fallbackは`.is-empty`（Surface背景＋Gold細線円）へ自然に縮退。
- **Owner Fixtureの実写真追加**: 3件のCASEすべてが無画像だったため、Feature（1件目、「建設業許可を初回申請で取得」）へOwner提供の実CASE用資産（`case-legal-documents.png`）を追加し、写真ありの実演と、016A-R1でも意図的に採用していた無写真Fallback実演の両方を1画面で確認できるようにした。

## 6. 発見・解決した実装上のバグ（推測せず、実測で特定）

### 6.1 Archive Pageとのクラス名衝突

`.wp-block-astrea-service-item`／`.wp-block-astrea-case-item`はHOME Teaser（Dynamic Block）だけでなく、既存のArchiveページ（`archive-astrea_service.html`等、Query Loop）でも**意図的に同じクラス名を共有**していた。新CSSが同じセレクタを上書きしようとした結果、後方に位置する既存のArchive用CSSに（Source Order基準で）敗れ、新スタイルが一切反映されない事象が発生した。`Chromium`の`document.styleSheets`を直接列挙し、競合する2つのルールを実際に特定した（推測でCSSを積み増す前に根本原因を確認）。**対処**: 新ルールをすべて`.wp-block-astrea-service-list .wp-block-astrea-service-item`のようにLIST Wrapper配下へScopeし、HOME専用にしてArchiveへの影響をゼロにした（Archive実機で無変更を確認済み、§9参照）。

### 6.2 CSS Grid自動配置バグ（CASE Row）

`grid-template-columns:auto 1fr auto`でNumber/Body/Mediaを1行に配置する設計に対し、`grid-row`を明示しなかったため、Chromiumの自動配置アルゴリズムがBody要素を2行目へ送ってしまい、縦に大きな空白が生じる不具合を`getBoundingClientRect()`実測で特定した。`grid-row:1`を3要素すべてへ明示して解決。

### 6.3 Resultsの64pxオーバーフロー（2段階の根本原因）

1. 当初`calc(50% - 50vw)`によるFull-bleed手法を採用したが、`.entry-content`自体が016B-R2の修正によりすでに全幅（align:full）になっており、この手法が前提とする「中央寄せされた狭いParent」という条件と矛盾し、64pxの非対称なOverflowを引き起こした。より単純で頑健な`max-width:none;width:100%;margin:0`へ変更。
2. それでも同じ64pxのOverflowが再現し、`getComputedStyle()`で`margin-right:-64px`という不可解な値を発見。実際の原因は`width:100%`+`padding`+デフォルトの`box-sizing:content-box`——Paddingが幅に加算される古典的な挙動だった。`box-sizing:border-box`を明示して解決。64px（Padding 32px×2）という数値の一致から特定した。

いずれもOrder §「勝手にDesignを変更しない」原則・016B-R2で確立した「Root Cause先行」原則に従い、`margin-left:-9999px`のような場当たり的Hackではなく、実測に基づいた最小修正で解決した。

## 7. Regression / 実機確認

- Horizontal Overflow: 320/375/768/1024/1366/1440/1920pxの7幅すべてで0px（Playwright機械計測）。
- Trust/Natural/Modern: 3 Variationとも同一Markup・同一Geometryのまま、Palette/Radius Tokenのみで意図した個性を維持することを実機確認（Variation固有CSSは追加していない）。検証後Trustへ復元済み。
- Core OFF: `astrea-core`無効化でHTTPステータス200、PHP Fatal/Warning/Notice 0件。再Activate済み。
- Archive Page: Service Archiveの実機確認で、Card型デザインが無変更のまま維持されていることを確認（§6.1のScoping対応の検証）。
- PHPUnit: 359 tests, 560 assertions — OK。
- PHPCS: 変更した3つのCore PHPファイルすべて 0 Errors / 0 Warnings。
- Theme Check（一時導入→検証→削除）: INFO 1件のみ（Text-domain、既存Baseline、本Orderと無関係）。

## 8. No-Photo Resilience

- Services: 元々Photography非依存（Icon＋番号のみ）。
- CASE: Secondary 2件は無写真Fallback（`.is-empty`）を実演、Featureは実写真を実演——016A-R1と同じ「両状態を1画面で見せる」方針を踏襲。
- Professional: `no-photo`クラスの既存Fallback（写真列なし、左寄せText主体）を維持、CSSのみ更新（構造は無変更）。

## 9. Screenshots

`docs/research/screenshots/016c/`配下:

- `01-home-full-1920.png`（HOME全体、Hero〜Footer手前まで）
- `02-services-1920.png` / `03-case-1920.png` / `04-results-1920.png` / `05-professional-1920.png`（各Section拡大）
- `06-home-full-mobile-375.png`（Mobile全体）
- `variation-natural-content.png` / `variation-modern-content.png`（参考）

## 10. Product Code Diff

変更ファイルは4件のみ: `core/includes/service-list-block.php`・`core/includes/professional-profile-block.php`・`core/includes/case-list-block.php`・`theme/theme.json`。Header/Hero（016B/016B-R1/016B-R2成果）・Price/FAQ/VOICE本体・Footer・Release/Tag/Deploy・Versionは無変更（見出しの共通ルールのみ本Orderで更新、§1参照）。

## 11. Remaining Notes / 今後の検討事項

- Servicesアイコンは全Service共通の汎用glyphであり、Service個別のアイコンではない。個別選択が必要な場合はCPTへのアイコンフィールド新設が別途必要（本Orderの範囲外）。
- CASE Secondary（2/3件目）は引き続き無写真。Owner保有の低解像度CASE素材（512×260前後）はFeature表示には解像度不足であることが016A時点で既知（Spec §3 Finding）——Secondaryへの追加は現状の小さな表示サイズ（190×120px）であれば十分な解像度のため、今後の対応候補として残す。
- Archive Page自体のVisual v3化（同種のEditorial化）は本Orderの範囲外。

Owner確認をお待ちしています。RELEASE HOLD継続。
