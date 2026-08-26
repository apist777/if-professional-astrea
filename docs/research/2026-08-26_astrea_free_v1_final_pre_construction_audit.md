# ASTREA FREE v1 着工前 最終監査（クロエ）

- 監査日: 2026-08-26（JST）
- 担当: クロエ（Claude）
- 種別: Decision統合後の最終着工前監査
- 対象: `docs/specifications/00〜04`、`AGENTS.md`、`HISTORY.md`、`docs/research/`配下の既往監査
- 制約: 製品コード（`theme/`, `core/`, `tools/`）は変更していない。GO判定でも実装は開始していない。

## 0. 本書の位置づけ

本書は以下の作業の最終報告である。

1. 2026-08-25の仕様会議で確定したDecision 001〜020を [`04_astrea_free_v1_preconstruction_decisions.md`](../specifications/04_astrea_free_v1_preconstruction_decisions.md) として正式文書化した（既存のDecision 001は内容を保持したまま形式を統一）。
2. Decisionと矛盾・未確定のまま残っていた既存正式仕様（00〜03）を、Decisionの内容に合わせて更新した。
3. 過去2件の着工前監査（クロエ・コデミ）のP0事項がすべて閉じたかを確認した。
4. ASTREA FREE v1の実装着工可否を最終判定した。

`theme/` `core/` `tools/` ディレクトリの内容（`.gitkeep`のみ）に変更はない。

---

## 1. 最終判定

# **GO**

Decision 001〜020により、クロエ・コデミ双方の過去監査でP0扱いだった事項はすべてCLOSEDとなった。残るOPEN事項は、(a) 実務上の結論はDecisionから導けるが正式仕様への一文追記が望ましい軽微な明文化事項が1件、(b) 性質上「実装フェーズで決定すべき技術詳細」に属する事項が数件のみであり、いずれも着工そのものを妨げるものではない。

**ただし本監査のGO判定は「実装着工可能な仕様状態になった」ことを示すものであり、実装開始そのものを許可するものではない。** 指示の通り、実装（Theme / Coreへのコード追加）は本報告後の別指示を待つ。

---

## 2. Step 1：Decision統合

Decision 001〜020を [`04_astrea_free_v1_preconstruction_decisions.md`](../specifications/04_astrea_free_v1_preconstruction_decisions.md) として正式文書化した。各DecisionにDecision ID／Status／決定内容／理由・設計原則／影響する既存仕様／Theme・Core・WordPress責任境界／実装時に守るべき事項を記載している。

会話内容から新規にクロエが仕様を補完した箇所はない。ユーザー提示内容をそのままDecisionとして記録し、既存仕様・既往監査との対応関係のみを整理した。

Decision番号と主題の対応は04文書冒頭の目次（見出し）を参照。

---

## 3. Step 2：既存正式仕様との整合確認

00〜04を横断確認した結果、以下の通り**すべて整合済み**とした。「原文修正」欄に「済」とあるものは本監査で実際に00/02を修正済み。

| 確認対象 | 結果 | 原文修正 |
|---|---|---|
| 同一事項について異なる仕様が残っていないか | Contact（保存方針）で唯一の実質矛盾を検出、修正済み | 済（02 §15） |
| 古い仕様がDecisionで無効化されたのに残っていないか | 02 §26（Update「明確化する」という未決定表現）、02 §29（バージョン未記載）、01 §22（Child Theme方向未確定）を検出、修正済み | 済（02 §26, §29／01 §22） |
| Theme / Core責任境界 | Decision 002・013と00〜02の記述は一貫 | 不要 |
| Pattern / Template / Template Partの扱い | Decision 002（Patternは正本にしない）・013（表示Architecture）・014（Update）と整合 | 不要 |
| ContactのSecurity / Privacy / Retention | Decision 003〜007により具体化。02 §15を更新 | 済（02 §15） |
| WordPress.org公式Themeとして問題となる設計 | Decision 001（GPL・単一コードベース方針）、011（Child Theme制約なし）と整合。Core必須化の強制は想定されていない | 不要（セクション5に軽微な明文化事項として記録） |
| Plugin territoryとの衝突 | Decision 018（第三者Plugin共存方針）で一般化。02 §16を更新 | 済（02 §16） |
| Accessibility | Decision 017は02 §24の再確認であり矛盾なし | 不要 |
| Update時のユーザーカスタマイズ | Decision 014が02 §26の未決定を確定 | 済（02 §26） |
| Core無効化 / 削除 | Decision 019は02 §27の明確化。矛盾なし | 不要（軽微な追記のみ検討可） |
| PHP / WordPress Compatibility | Decision 020が02 §29の未記載数値を確定 | 済（02 §29） |
| FREE / PRO境界 | Decision 015・016は既存方針の明確化。矛盾なし | 不要 |
| 将来PROを阻害するFREE設計 | Decision 002・013の「単一保存API」「非密結合」方針で軽減。詳細実装は今後の設計時留意事項 | 不要 |
| Lock-in | Decision 001（GPL）・002・013・019で強化。矛盾なし | 不要 |
| Security | Decision 003・007・009・017で具体化。矛盾なし | 不要 |
| Privacy | Decision 004（保存期間・自動削除）・016（WordPress標準Privacy機能活用）で具体化。矛盾なし | 不要 |
| WordPress標準からの不必要な逸脱 | 全Decisionが標準機構優先の方向で一貫 | 不要 |

00（開発コンセプト）は本監査の結果、修正不要と判断した。00は最上位原則を扱う文書であり、今回のDecisionはすべて00の原則の具体化であって、00自体との矛盾は検出されなかった。

03（ユーザージャーニー）も修正不要と判断した。Decision 016（初期セットアップ）は03の一本道フローと矛盾せず、その実現方法を具体化するものである。

---

## 4. Step 3：P0事項のCLOSED / OPEN判定

### 4.1 クロエ着工前監査（2026-08-25）P0

| # | 論点 | 判定 | 根拠 |
|---|---|---|---|
| 3.1 | 配布チャネル未決定 | **CLOSED** | Decision 001（WordPress.org掲載を目指す、単一コードベース、真のFREEを正本とする） |
| 3.2 | ASTREA Core必須 / 任意の明文化 | **CLOSED（軽微な明文化事項が残存）** | Decision 001・002・013から実務上の結論（事実上推奨、技術的には任意でTheme単体もPHP fatalを起こさない）は導けるが、02仕様書への明示的な一文追加は未実施。着工を止める性質の論点ではない（詳細はセクション5参照） |
| 3.3 | Contact メール不達による問い合わせ消失リスク | **CLOSED** | Decision 003（アドレス確認Token）・004（Core一時保存、メール成否に関わらず保存） |
| 3.4 | Search Console「認証情報」の実装範囲 | **CLOSED** | Decision 009（HTMLタグ方式に限定、API/OAuth/順位分析は作らない） |
| 3.5 | Breadcrumb：視覚UIか構造化データのみか | **CLOSED** | Decision 010（両方標準対応） |
| 3.6 | Child ThemeがFREE v1スコープか | **CLOSED** | Decision 011（FREE v1では提供しない） |

### 4.2 コデミ技術基盤監査（2026-08-25）P0

| # | 論点 | 判定 | 根拠 |
|---|---|---|---|
| P0-1 | WordPress / PHP最低対応版 | **CLOSED** | Decision 020（PHP 8.3+、WordPress 7.0初期Target／7.1基準） |
| P0-2 | Theme / Core公開slug・namespace・text domain・versioning規約 | **CLOSED** | Decision 012（技術識別子） |
| P0-3 | CoreデータモデルとSchema Version / Migration / Delete契約 | **一部CLOSED、詳細はOPEN（実装フェーズ決定・非P0）** | 削除契約はDecision 019でCLOSED。Schema Version / Migrationの具体的機構はDecision 020で「Versioned Migration方式を採る」という方針のみ確定し、詳細機構は意図的に実装フェーズへ委ねられている（04文書「残る確認事項」参照） |
| P0-4 | Block Bindings / 動的Block / Patternの表示戦略 | **CLOSED（大枠）、個別選択はOPEN（実装フェーズ決定・非P0）** | Decision 013で「単純な値はBlock Bindings、構造・処理はDynamic Block」という使い分け方針が確定。Block単位の個別選択は実装設計時の技術詳細として意図的に残されている |
| P0-5 | CoreなしでのTheme、Theme変更後のCoreという依存契約 | **CLOSED** | Decision 002・013（非密結合、Core無効時のフォールバック方針） |
| P0-6 | Site Editor保存データとTheme更新の扱い | **CLOSED** | Decision 014 |
| P0-7 | ローカル環境、CI provider、最小テストマトリクス | **CLOSED** | Decision 020（WSL2 + Docker + wp-env、GitHub Actions等CI） |

### 4.3 「実装時に決めればよい技術詳細」として意図的にP0へ格上げしなかった事項

ユーザー指示「実装時に決めればよい技術詳細を無理にP0へ格上げしない」に従い、以下は正式仕様レベルでは決定済み（方針FIX）とし、個別の実装方法は各機能の設計フェーズで決定する事項として扱う。

- Schema Version / Migrationの具体的な保存場所・実行タイミング（Decision 020で方針のみ確定）
- Block Bindings / Dynamic Blockの個別Block単位での使い分け（Decision 013で方針のみ確定）
- Price（自由記述）と構造化データの整合方法（04文書「残る確認事項」3）
- Pattern と Style Variationの共有方式（同4）
- 空状態（0件）UIパターンの統一（同5）
- 営業時間・臨時休業データモデルの詳細（同6）

これらはいずれも「着工そのもの」を妨げず、該当機能の設計着手時に決定すればよい。

---

## 5. 残存する唯一の非ブロッキングOPEN事項

**ASTREA CoreがFREE v1において「必須」か「任意」かの明文の一文が、正式仕様（02仕様書）にまだ存在しない。**

- Decision 001（WordPress.org掲載を目指す）とDecision 013（Theme/Core非密結合、Core無効時のフォールバック）を組み合わせれば、「実務上は強く推奨するが、技術的にはTheme単体でもPHP fatalを起こさない」という結論が論理的に導ける。
- しかし、この結論をクロエが02仕様書へ独自判断で明文化することは、「Decisionの意味を変える独自判断は禁止」という今回の指示の範囲を超えると判断し、行っていない。
- **着工を阻害する事項ではない。** Theme/Core双方の実装において「Core非活性時にPHP fatalを起こさない」という設計原則（Decision 013の実装時遵守事項）に従って作れば、この一文の有無に関わらず正しい実装になる。次回の仕様会議等で、確認のみ行うことを推奨する。

---

## 6. 監査対象外で確認された環境上の事実（判断待ちではなく、実行待ちの事項）

以下はDecision・仕様の話ではなく、Decision 020で確定した開発環境方針の「実行」がまだ行われていないという事実確認である。着工可否の判定には影響しないが、実装着手時に最初に行うべき作業として記録する。

- リポジトリに`.git`が存在せず、バージョン管理が機能していない（コデミ監査・クロエ監査の双方で既出）。
- `package.json` / `composer.json` / `phpcs.xml` / `.wp-env.json` / CI workflow は未作成（Decision 020の方針を実行に移す最初のステップ）。

---

## 7. 変更したファイルの一覧

| ファイル | 変更内容 |
|---|---|
| `docs/specifications/04_astrea_free_v1_preconstruction_decisions.md` | 新規作成（既存のDecision 001は内容保持のうえ形式統一）。Decision 002〜020を追加 |
| `docs/specifications/02_astrea_free_v1_specification.md` | 冒頭に04参照の追記注記。§15 CONTACT、§16 SEO Foundation、§18 GA4/Search Console、§26 Update、§29 Compatibility/Migration、§31 やらないことリストを更新 |
| `docs/specifications/01_astrea_product_plan_v0.1.md` | 冒頭に04参照の追記注記。§22 Child Themeを更新 |
| `docs/research/2026-08-26_astrea_free_v1_final_pre_construction_audit.md` | 本書（新規作成） |

`docs/specifications/00_astrea_development_constitution.md`、`03_astrea_free_v1_user_journey.md` は変更なし（確認の結果、修正不要と判断）。

製品コード（`theme/`, `core/`, `tools/`）は変更していない。

---

## 8. 次のアクション（クロエからの提案、実行はしない）

1. コデちゃん・ユーザーへ本監査結果を報告し、GO判定の確認を得る。
2. GO確認後、別指示にて `docs/specifications/05_astrea_free_v1_construction_baseline.md` を作成し、着工時正式仕様として凍結する。
3. 実装着手の最初の一歩として、Decision 020の開発環境方針（Git初期化、wp-env、CI）を整備する。
4. セクション5の軽微な明文化事項（Core必須/任意の一文）は、次回仕様確認の際に一言確認しておくことを推奨する。
