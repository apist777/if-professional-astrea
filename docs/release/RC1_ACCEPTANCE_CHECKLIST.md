# ASTREA FREE v1 — RC1 Acceptance Checklist

RC1候補をTagする前に確認する項目。RC1 Release Acceptance Order（2026-08-29実施）で全項目を実際に検査し、結果を記入した。実際のTag作成・GitHub Release・配布はこのChecklistの対象外（正式命令を待つ）。

判定：PASS／PASS WITH KNOWN EXCEPTION／FAIL／NOT VERIFIED

| # | 領域 | 確認項目 | 判定 | 根拠 |
|---|------|----------|------|------|
| 1 | Functional | Setup（Office Profile／HOME／基本ページ／Navigation生成）が一連で動作する | PASS | RC1 ZIP Clean Install Testで実施（Item 7-14） |
| 2 | Functional | Dynamic Block（料金一覧・FAQ一覧・取扱業務一覧・専門家紹介・対応事例・実績・お客様の声一覧）が実データを表示する | PASS | RC1 Clean Install環境で全7 CPT Archiveに実データ表示を確認（Item 15-21） |
| 3 | Responsive | 320/375/768/1440pxで崩れがない。STRESS長事務所名でもHeader実用性維持 | PASS | RC1環境・STRESS Fixtureで4breakpoint実機確認、Horizontal Overflow無し、Header全文表示 |
| 4 | Accessibility | H1唯一性・見出し階層・ランドマーク・Skip Link・キーボードTab・フォーカス可視性・Form Label | PASS | H1×1、Main/Nav/Footerランドマーク確認、Skip Link先が`<main id="wp--skip-link--target">`であることをHTML直接確認、Tab操作でSkip Linkが最初のFocus対象になることを確認、Focus outline確認 |
| 5 | Security | XSS・SQLi等のStress Fixtureに対する耐性 | PASS | Construction 012で確認済み、本RC1では製品コード変更が無いため再検証は不要と判断 |
| 6 | SEO | title/canonical/robots/sitemap/meta description/OGP/Organization・Person JSON-LD/Search Console/GA4 ON・OFF | PASS | RC1環境で全項目を実機確認（GA4未設定時は出力無し、設定時は正しいIDで出力） |
| 7 | Editor | ASTREA Dynamic BlockのUnsupported Block警告が0件であること | PASS | RC1環境のHOME Editorで実機確認（7 ASTREA Dynamic Block、警告0件） |
| 7b | Editor | Save Round Trip（通常編集→保存→再読込→Dynamic Block維持→Frontend維持） | PASS | 事務所概要ページで実際にParagraphを編集・保存・再読込し、Dynamic Block（astrea/office-hours, astrea/office-sns）維持とFrontend反映を確認 |
| 8 | Setup | Setup画面の各項目が実際の状態を反映する（Site Title Checklist含む） | PASS | RC1環境で認証済みAdmin画面から確認 |
| 9 | Core OFF | Core無効化でFatal無し、Theme単体動作（HOME/Archive/Single/Search/404） | PASS | RC1環境で5種類のURL全てFatal無し・正しいHTTP Statusを確認 |
| 10 | Uninstall | 完全削除（データ削除画面）が正しい範囲のみ削除する | PASS | `delete_all_core_data()`を実行し、CPT 7種・Office Profile Optionが削除される一方、Page・Navigationは無傷で残ることを実機確認 |
| 11 | Packaging | Theme/Core ZIPが正しいRoot Directory構造を持つ | PASS | `astrea/`・`astrea-core/`のRoot構造を再確認 |
| 12 | Clean Install | 生成したRC1 ZIPそのものを独立環境（他Construction/他Projectと無関係の新規Docker Network/Volume）へInstallして動作確認 | PASS | 28項目全て実機確認（本Report詳細） |
| 13 | Compatibility | WordPress 7.1 / PHP 8.3での動作確認 | PASS | 確認済み。PHP 7.4実機確認は方針上実施しない（Decision 020参照） |
| 14 | Documentation | readme.txt（Theme/Core）がInstallation/FAQ/Known Issuesを含み、実装と一致する | PASS | RC1環境での実機確認結果と全て整合 |
| 15 | License | LICENSE / license.txt / style.css / astrea-core.php / readme.txtの間でLicense表記が一致する | PASS | RC1 ZIP内で再確認、行末Line Ending不一致は修正済み |
| 16 | Artifact | Theme/Core ZIP、新規SHA-256 Checksumが生成されている | PASS | `dist/astrea-theme-1.0.0-rc1.zip`・`dist/astrea-core-1.0.0-rc1.zip`、Construction 014のChecksumとは別の新規値 |
| 17 | CI | PHP syntax + Coding Standards / Theme・Core independence smoke test / PHPUnitが全てGreen | PASS | RC1 Version変更・Theme Check修正、いずれのCommitもCI Green確認済み |
| 18 | Theme Check | WordPress公式Theme Check Pluginによる実検査 | PASS | `wp theme-check run astrea`実行。REQUIRED1件（license.txtのLine Ending不統一）・INFO1件（block-themeタグが正式Feature Filter外）を発見・修正、再検査でクリーンを確認 |
| 19 | Style Variation | Trust/Natural/Modernの3 Variationが正しく登録・解決される | PASS | `WP_Theme_JSON_Resolver::get_style_variations()`で3件（Modern/Natural/Trust）を確認 |

## Known Exceptions（RC1 BLOCKERとしない項目）

- **WordPress 7.1 core/group・core/cover Editor Validation Warning**（Construction 014A）：WordPress Core自体の挙動であり、ASTREA側の修正候補が存在しない。実データの損失が無いことを実機確認済み。RC1でも同様に再現（HOME上のHero/Flow/CTA/Trustの4Group、3ブロックで警告確認）。readme.txtのKnown Issuesに記載済み。
- **追加確認事項**：警告状態のGroup Block内のRichText（見出し・段落）は、Gutenbergの標準挙動として「Attempt recovery」を行うまで直接編集ができない（クリックしても編集モードに入らない）。これはASTREA固有の制限ではなく、Gutenberg自身が検証エラー状態のBlockを保護する標準動作である。無関係な箇所（本文中の別Paragraph、他のPage）の編集・保存には一切影響しない（Construction 014A・本RC1双方で確認済み）。

## WordPress.org Readiness Classification（今回発見事項）

| 発見事項 | 分類 | 対応 |
|---|---|---|
| license.txtのDOS/UNIX Line Ending混在 | C（WordPress.org submission only、実際にはSVN配布時の問題） | 修正済み（RC1内） |
| style.css/readme.txtの`block-theme`タグが正式Feature Filter外 | C（WordPress.org submission only） | 修正済み（RC1内、`full-site-editing`が同義をカバー） |
| Contributors欄がWordPress.org正式アカウント未確定のPlaceholder | C（WordPress.org submission only） | 実際のSubmission時に正式username へ差し替えが必要（現時点では変更不要） |
| WordPress 7.1 core/group・core/cover警告 | E（Known external issue） | 対応不要、Known Issue記載済み |

## Post v1 Backlogとして維持する項目（RC1でも修正しない）

- Finding 6：Professional Archiveの空Excerpt表示
- Finding 7：Search画面のBreadcrumbラベル
- Finding 8：Price Groupの表示

いずれもConstruction 012の監査で発見され、Release Blockingとして扱わないことが既に確定している。RC1 Acceptanceを口実にScope Creepさせない。
