# ASTREA FREE v1 — RC1 Acceptance Checklist

RC1候補をTagする前に確認する項目。実際のTag作成はConstruction 014の対象外（正式命令を待つ）。

| # | 領域 | 確認項目 | Construction 014時点の状態 |
|---|------|----------|------------------------------|
| 1 | Functional | Setup（Office Profile／HOME／基本ページ／Navigation生成）が一連で動作する | ✅ 確認済み（ZIP Clean Install Testで実施） |
| 2 | Functional | Dynamic Block（料金一覧・FAQ一覧・取扱業務一覧等）が実データを表示する | ✅ Construction 012/013で確認済み |
| 3 | Responsive | 320/375/768/1024/1440pxで崩れがない | ✅ Construction 012で確認済み（Finding 1はConstruction 013で対応済み） |
| 4 | Accessibility | 見出し階層・ランドマーク・Skip Link・キーボード操作・フォーカス・コントラスト | ✅ Construction 012で確認済み（axe-core自動Scan含む） |
| 5 | Security | XSS・SQLi等のStress Fixtureに対する耐性 | ✅ Construction 012で確認済み |
| 6 | SEO | meta description・OGP・構造化データ・既知SEO Pluginとの共存 | ✅ Construction 006/012で確認済み |
| 7 | Editor | Block Editor / Site Editorでの警告 | ⚠️ WordPress 7.1のcore/group・core/cover警告あり（Known Issueとして記録、Release Blockingではない。Construction 014A） |
| 8 | Setup | Setup画面の各項目が実際の状態を反映する | ✅ 確認済み |
| 9 | Core OFF | Core無効化でFatal無し、Theme単体動作 | ✅ 確認済み（ZIP Clean Install Test含む） |
| 10 | Uninstall | 完全削除（データ削除画面）が正しい範囲のみ削除する | ✅ Construction 009で実装・確認済み |
| 11 | Packaging | Theme/Core ZIPが正しいRoot Directory構造を持つ | ✅ 確認済み（`tools/release/package.sh`） |
| 12 | Clean Install | 生成したZIPそのものを独立環境へInstallして動作確認 | ✅ Construction 014で実施済み（本Reportに詳細記録） |
| 13 | Compatibility | WordPress 7.1 / PHP 8.3での動作確認 | ✅ 確認済み。PHP 7.4実機確認は未実施（Documentationに明記） |
| 14 | Documentation | readme.txt（Theme/Core）がInstallation/FAQ/Known Issuesを含み、実装と一致する | ✅ Construction 014で作成済み |
| 15 | License | LICENSE / license.txt / style.css / astrea-core.php / readme.txtの間でLicense表記が一致する | ✅ 確認済み |
| 16 | Artifact | Theme/Core ZIP、SHA-256 Checksumが生成されている | ✅ Construction 014で生成済み（`dist/`、Git管理外） |
| 17 | CI | PHP syntax + Coding Standards / Theme・Core independence smoke test / PHPUnitが全てGreen | ⏳ 本Construction Orderのcommit分は都度確認する |

## Known Exceptions（RC1 BLOCKERとしない項目）

- **WordPress 7.1 core/group・core/cover Editor Validation Warning**（Construction 014A）：WordPress Core自体の挙動であり、ASTREA側の修正候補が存在しない。実データの損失が無いことを実機確認済み。readme.txtのKnown Issuesに記載済み。

## Post v1 Backlogとして維持する項目（Release Prepでは修正しない）

- Finding 6：Professional Archiveの空Excerpt表示
- Finding 7：Search画面のBreadcrumbラベル
- Finding 8：Price Groupの表示

いずれもConstruction 012の監査で発見され、Release Blockingとして扱わないことが既に確定している。Release Prep（Construction 014）を理由にScope Creepさせない。
