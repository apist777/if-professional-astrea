# If Professional ASTREA — Development History

このファイルは、AI開発担当による主要な調査・設計・実装・レビューの履歴を記録する。

## 記録ルール

- 日時は日本時間（JST）で記録する。
- 担当者は `コデミ（Codex）` または `クロエ（Claude）` とする。
- 小さな確認や会話は記録不要。
- 調査、仕様策定、設計、実装、修正、レビュー、テスト等の主要作業を記録する。
- 詳細資料がある場合は `docs/research/` または `docs/specifications/` のファイルをリンクする。
- 新しい履歴を上に追記する。

---

## History

| 日付 | 時刻 | 担当 | 内容 | 関連資料 |
|---|---:|---|---|---|
| 2026-08-26 | 17:05 | クロエ（Claude） | CONSTRUCTION ORDER 002実施・完了。ASTREA CoreにOffice Profile（事務所名・代表者・住所・電話・営業時間＋臨時休業・SNSリンク）を実装。Options APIの単一Option（`astrea_core_office_profile`、schema_version同梱、独自DB Tableなし）＋WordPress標準Settings APIで保存、フィールド別Sanitization/Validation（不正値は個別ロールバック＋エラー表示）、`get_office_profile()`という公開境界、Block Bindings Source（`astrea-core/office-profile`）でTheme側header.htmlのcore/paragraphへ接続。Nonce拒否(403)・保存成功・不正値ロールバック・Core無効化時の安全なフォールバック・データ保持・再有効化復元・権限チェックをすべて実機（wp-env）で検証。PHPUnit 20件/40assertions追加、smoke-test.shをE〜Iへ拡張。CI初回失敗（`composer.lock`がホストPHP8.1でignore-platform-reqを使って生成されており実PHP8.3で解決不能）を発見し、wp-envコンテナの実PHP8.3で再生成して解消。GitHub Actions全ジョブ最終Green。判定：**CONSTRUCTION 002 COMPLETE**。仕様変更なし（Office Profileの項目範囲について要確認事項1件を報告） | [実施報告](docs/research/2026-08-26_construction_order_002_report.md)、[CI run](https://github.com/apist777/if-professional-astrea/actions/runs/32944574074) |
| 2026-08-26 | 14:35 | クロエ（Claude） | CONSTRUCTION ORDER 001 最終検収完了。Docker/PHP拡張の環境ブロッカー解除後、PHPCS実行・wp-env起動・Theme only/Theme+Core/Core無効化/Core再有効化の実活性化・smoke test完走をすべてPASSで確認。CI初回失敗（`package-lock.json`未コミット、`wp-env destroy`の非対話化不足）を発見し2件修正、GitHub Actions CIを最終的に成功させた。GitHub（`apist777/if-professional-astrea`、Private）へ`main`をpush。判定：**CONSTRUCTION 001 VERIFIED**。仕様変更なし | [実施報告（最終検収追記済み）](docs/research/2026-08-26_construction_order_001_report.md)、[CI run](https://github.com/apist777/if-professional-astrea/actions/runs/32933648423) |
| 2026-08-26 | 12:30 | クロエ（Claude） | CONSTRUCTION ORDER 001実施。Git初期化（既定ブランチmain）、開発基盤（package.json / composer.json / phpcs.xml / .wp-env.json / GitHub Actions CI）、ASTREA Theme最小骨格（`theme/`、slug `astrea`）、ASTREA Core最小骨格（`core/`、slug `astrea-core`）を構築。Decision 021（Core任意・公式推奨）に基づき`Astrea\Theme\is_core_active()`によるCore検出の入口を実装。PHP構文チェック・JSON妥当性・Composer依存解決・トップレベル実行スタブテストは合格。ただしこのセッションはDocker不在のためwp-envでの実活性化smoke testおよびローカルPHPの拡張不足によるPHPCS実行は未実施（CI上での実行に委譲）。仕様矛盾なし | [実施報告](docs/research/2026-08-26_construction_order_001_report.md) |
| 2026-08-26 | 10:40 | クロエ（Claude） | ASTREA CoreをFREE v1において「任意Plugin・公式推奨」と最終FIX（Decision 021）。02仕様書§3・04文書へ反映し、残存していた唯一の非ブロッキング事項をCLOSED。00〜04・Decision 001〜021・2026-08-26最終監査GO判定を統合し、`05_astrea_free_v1_construction_baseline.md`をConstruction Baselineとして確定。P0/着工阻害事項は全件CLOSED。判定：**BASELINE READY**（製品コード実装は未着手のまま） | [Baseline](docs/specifications/05_astrea_free_v1_construction_baseline.md)、[Decision統合](docs/specifications/04_astrea_free_v1_preconstruction_decisions.md) |
| 2026-08-26 | 09:10 | クロエ（Claude） | 仕様会議でFIXしたDecision 001〜020を正式文書化（04を新規作成）。既存仕様（01, 02）をDecisionに合わせて更新。過去2件の着工前監査のP0事項を全件CLOSED判定し、最終着工前監査を実施。最終判定：**GO**（実装はまだ開始せず待機）。残存事項は非ブロッキングの明文化1件のみ | [Decision統合](docs/specifications/04_astrea_free_v1_preconstruction_decisions.md)、[最終監査](docs/research/2026-08-26_astrea_free_v1_final_pre_construction_audit.md) |
| 2026-08-25 | 17:20 | クロエ（Claude） | ASTREA FREE v1 着工前監査を実施。仕様間の矛盾・自己言及的未決定事項、Contact機能のメール不達リスク、配布チャネル未決定、Core必須性、SEO競合/ロックイン/PRO阻害リスク等を整理。判定：CONDITIONAL GO（実装未着手）。コデミの並行技術基盤調査と役割分担のうえ相互参照 | [着工前監査](docs/research/2026-08-25_astrea_free_v1_pre_construction_audit.md) |
| 2026-08-25 | 17:01 | コデミ（Codex） | 正式仕様4本を読了し、WordPress標準、Block Theme / FSE、Theme / Core分離、ローカル開発環境、テスト / CI、Compatibilityを対象とする技術基盤調査を実施（実装なし） | [技術基盤調査](docs/research/2026-08-25_astrea_technical_foundation_research.md) |
| 2026-08-25 | --:-- | クロエ（Claude） | ASTREA開発参加 | - |
