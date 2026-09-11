# Construction 025-B2 — ASTREA Original Design Vision Restoration — Demo Final Asset Integration & Visual Re-Audit

- Start: 2026-09-10 10:28:19 JST（実測、本Phase最初のファイル作成時刻）
- End: 2026-09-10 10:48:42 JST（実測）
- Duration: 0:20:23
- Modifier: Chloe
- Mode: Theme/Core変更ゼロ。commit/push未実施（Order §16の指示通り、Owner確認前のためGit操作禁止）。

**Final Verdict: A. DEMO VISUAL ACCEPTANCE CANDIDATE READY**

---

## 1. Preflight（Order §2）

`git status --short --branch`実施済み。Owner確認済みの過去screenshots 235件の削除（意図的な容量整理）はそのまま手を触れていない（復元・stage・commitともに未実施、本Phaseでも再確認したが変化なし）。新規追加されたHero/Case #2/Case #3の画像・Zone.Identifierファイルは、Owner自身の資産投資として認識した。Theme/Core配下は本Phase開始前・終了後とも無変更（§16で最終確認）。

## 2. Asset Inventory（Order §3）— 前Phaseで実施済み、変化なし

| ファイル | 用途 | サイズ | 寸法 | SHA256（先頭16桁） | 破損チェック |
| --- | --- | --- | --- | --- | --- |
| `astrea-demo-yamada-professional-portrait.png` | 代表者写真 | 1,725,005 B | 1616×973 | f2b3cf5ad1275335 | OK |
| `astrea-demo-yamada-hero-office.png` | Hero背景 | 1,786,244 B | 1672×941 | 6d6463e51e404689 | OK |
| `astrea-demo-yamada-case-01-construction-permit.png` | 対応事例#1 | 1,812,903 B | 1774×887 | aaa7896d42a6fc4a | OK |
| `astrea-demo-yamada-case-02-inheritance.png` | 対応事例#2 | 2,030,685 B | 1536×1024 | 3e61f2ce87698990 | OK |
| `astrea-demo-yamada-case-03-restaurant-incorporation.png` | 対応事例#3 | 2,228,949 B | 1536×1024 | c58ed38f7391c053 | OK |
| `astrea-demo-yamada-results-background.png` | 実績背景（今回未使用） | 1,619,562 B | 1774×887 | 616ebb57fb334513 | OK |

全6点、PNGシグネチャ・IENDチャンク・IHDR寸法とも整合。破損なし。

補足（軽微、非ブロッキング）: Hero・Case #2・Case #3の写真内に、装飾的な壁面サイン・額装ポスターとして日本語/英語のキャッチコピーが写り込んでいる（例: Hero内「一歩先の安心を、共に築く。」）。これはUI要素ではなく背景装飾（オフィス内の額縁・チョークボード等）であり、Order §6が禁止する「画像へ文字を焼き込む加工」（＝本Phase自身が画像に文字を合成する行為）には該当しない。既存Owner供給画像の内容そのものであり、指摘のみに留める。

## 3. Zone.Identifier Cleanup（Order §4）— 前Phaseで実施済み

以下4件を削除済み（いずれも25バイト、内容`[ZoneTransfer]\nZoneId=3`、対応する実データなしを確認済み）:

- `ChatGPT Image 2026年9月10日 10_18_44.png:Zone.Identifier`
- `ChatGPT Image 2026年9月10日 10_19_49.png:Zone.Identifier`
- `astrea-demo-yamada-case-01-construction-permit (2).png:Zone.Identifier`
- `astrea-demo-yamada-hero-office.png:Zone.Identifier`

削除後`ls | grep -i zone`で残存なしを確認済み。PNG原本には一切手を加えていない。

## 4. Web Optimization（Order §5）— 前Phaseで実施済み、変化なし

| ファイル | 元サイズ | Web版サイズ | 処理 | 品質 |
| --- | --- | --- | --- | --- |
| `astrea-demo-yamada-hero-office.jpg` | 1,786,244 B | 187,769 B (−89.5%) | クロップなし・そのままJPEG変換（Cover Blockの流動的コンテナに合わせるため） | q85 |
| `astrea-demo-yamada-case-02-inheritance.jpg` | 2,030,685 B | 150,578 B (−92.6%) | 2:1へセンタークロップ（crop_offset [0,128]） | q85 |
| `astrea-demo-yamada-case-03-restaurant-incorporation.jpg` | 2,228,949 B | 200,027 B (−91.0%) | 2:1へセンタークロップ（crop_offset [0,128]） | q85 |

過剰圧縮なし（q85は023-Bで採用した実績値と同一）。PNG原本は無変更のまま`images/`に保持。

## 5. Hero Integration（Order §6）

**手法**: ASTREA Theme標準の`astrea-hero-photoplane`（`core/cover`ブロック）が持つ、WordPress標準の背景画像コントロール・オーバーレイ濃度スライダーのみを使用。具体的には、HOMEページの`post_content`に対して`parse_blocks()`→ブロック属性更新（`url`/`id`/`dimRatio: 100→20`）→WordPress Core自身のCover Block保存markup仕様に一致する`innerHTML`再構築→`serialize_blocks()`→`wp_update_post()`という、WordPress自身のブロックAPIのみで完結する手順で実施した。

**実装過程で1件の不具合が発生し、その場で特定・修正した**: 当初のスクリプトでPHPのfunction宣言が条件分岐の内側かつ呼び出し箇所より後ろに配置されていたため、"Call to undefined function"のFatal Errorが発生（PHPの関数巻き上げは無条件・トップレベル宣言のみに適用されるため）。原因をSQLite直接検証で特定し、全function宣言をファイル冒頭の無条件トップレベルへ移動して修正、再実行して解消した。

**結果**（HOMEページ post_content、実際の保存済みmarkup）:
```html
<!-- wp:cover {"dimRatio":20,"overlayColor":"contrast","minHeight":62,"minHeightUnit":"vh","className":"astrea-hero-photoplane","url":"http://127.0.0.1:8895/wp-content/uploads/2026/09/astrea-demo-yamada-hero-office.jpg","id":42} -->
<div class="wp-block-cover astrea-hero-photoplane" style="min-height:62vh">
  <img class="wp-block-cover__image-background has-contrast-background-color has-background-dim has-background-dim-20" alt="" src="...hero-office.jpg" data-object-fit="cover"/>
  <div class="wp-block-cover__inner-container"></div>
</div>
<!-- /wp:cover -->
```

- Hero copy（見出し・電話番号・CTAボタン）はWordPress側のまま完全に無変更（Order §6の指示通り）。
- 画像への文字焼き込み加工は一切行っていない。
- Demo専用CSS・Yamada専用CSS・テンプレートハードコード・`background-image:url()`固定・PHP条件分岐・functions.php/Core側のワークアラウンドは一切使用していない — 使ったのはブロックのdata属性（`url`/`id`/`dimRatio`）のみで、これはBlock/Site Editorで画像を選び濃度スライダーを動かした場合とバイト単位で同一の保存markupになる（後述§7参照）。

## 6. Case Integration（Order §7）

Case #2・#3とも、Case #1（023-B）で使用したのと全く同じ標準WordPress機構——`wp_upload_bits()`→`wp_insert_attachment()`→`wp_generate_attachment_metadata()`→`set_post_thumbnail()`——のみで実施。

| Case | Featured Image | Attachment ID |
| --- | --- | --- |
| #1（建設業許可を初回申請で取得） | 既存維持（023-B時のまま） | 41（現ローカル環境）／独立クリーンリビルド環境では38 |
| #2（相続手続きを2ヶ月で完了） | 新規設定 | 43／独立クリーンリビルド環境では40 |
| #3（飲食店の会社設立をサポート） | 新規設定 | 44／独立クリーンリビルド環境では41 |

テンプレートハードコード・CSS背景画像・base64埋め込み・Yamada固有処理・Case ID条件分岐は一切使用していない。

## 7. Case Visual Consistency（Order §8）

元画像の寸法がCase #1（1774×887、ネイティブ2:1）とCase #2/#3（1536×1024、ネイティブ1.5:1）で異なるが、全て同一の2:1センタークロップ処理を通しているため、実際の表示は完全に統一されている（下記スクリーンショットで確認）:

| 項目 | 結果 |
| --- | --- |
| 表示アスペクト比 | 3件とも2:1で統一 |
| カード高さ | 3件とも同一（グリッド由来） |
| フォーカルポイント | 3件とも被写体（人物）が上寄り〜中央に自然配置、極端な首/顔切れなし |
| 視覚的な重み | 3件とも同程度の情報量・彩度で統一感あり |
| タイトル・説明文の位置 | 3件ともカード下部・同一レイアウト |
| 「詳しく見る」リンク | 3件とも同一スタイル・同一位置 |
| 01/02/03ナンバリング | 健在、3件とも同一デザインのダークバッジ |
| 3カラムリズム（デスクトップ） | 崩れなし |
| モバイル縦積み | 崩れなし、クロップ破綻なし |

スクリーンショット: `docs/research/screenshots/025-B2/desktop-1440-case.png`／`mobile-390-case.png`

## 8. Clean Rebuild Persistence（Order §9）

再現性パッケージ（`docs/demo-assets/yamada-live-demo/`）を更新し、**完全に独立した新規環境**（`~/astrea-rebuild-test-025b2/`、別ポート8896、ゼロから`download-and-install`）で以下のフルパイプラインを実行して検証した:

```
activate theme/core → cleanup.php → build-content.php → polish.php →
permalinks.php → integrate-final-images.php → integrate-025b2-hero-and-cases.php
```

結果（独立環境のSQLite直接検証）:

- Professional（post 4）→ Featured Image 37（正式ポートレート）
- Case #1（post 9）→ Featured Image 38
- Case #2（post 11「相続手続きを2ヶ月で完了」）→ Featured Image 40
- Case #3（post 12「飲食店の会社設立をサポート」）→ Featured Image 41
- HOMEページ（post 31）の`post_content`に`astrea-demo-yamada-hero-office`のURLと`dimRatio:20`を含む — Hero統合も再現

HTTPスモークテスト: `http://127.0.0.1:8896/` → status 200、`astrea-demo-yamada-hero-office`文字列がレンダリング済みHTMLに存在、PHP Fatal/Warning文字列0件。

**このロジックはTheme/Coreには一切実装していない** — すべて`docs/demo-assets/yamada-live-demo/scripts/`配下のPHPスクリプト（Owner管理下のリポジトリ内、Theme/Core外）としてのみ存在する。

さらに、`integrate-025b2-hero-and-cases.php`はファイル名によるアタッチメント既存チェックを行うため**冪等**（同一環境で再実行しても添付を重複作成しない）ことも、既存環境への再実行で確認済み（添付ID 42/43/44のまま、45/46/47が新規作成されないことを確認）。

再現性パッケージの更新内容:
- `README.md` — Hero/Case #2/#3追加を反映、内容物一覧・再現手順を更新
- `yamada-demo-export.wxr` — 更新後の状態でエクスポートし直し（32→36 items、85,575バイト）
- `images/`・`images/web/` — 新規3点のPNG原本・JPEG最適化版を追加
- `scripts/integrate-025b2-hero-and-cases.php` — 修正済み・冪等化・クリーンリビルド対応版を保存

## 9. WordPress Native Editability確認（Order §11）

| 項目 | 分類 | 確認内容 |
| --- | --- | --- |
| Hero背景画像 | A（標準UIで完結） | Block/Site Editorで対象ページを開き、Cover Blockが「Attempt Block Recovery」等の警告なしにレンダリングされることを確認（下記§11参照）。画像・濃度とも標準のCover Blockサイドバーコントロールで再設定可能な状態 |
| Case Featured Image | A | 通常の投稿編集画面のFeatured Imageパネルで確認・変更可能（Case #1で実証済み、023-B踏襲） |

## 10. Visual QA（Order §12）

### Desktop 1440px

| 項目 | 結果 |
| --- | --- |
| HTTP status | 200 |
| horizontal overflow | 0 |
| console error | 0 |
| broken image | 0 |
| H1 | 一意 |
| Hero写真 | 表示あり、斜め境界（clip-path）健在、コピー・CTA判読可能 |
| Case | 3件とも写真あり、欠落なし、3カラムレイアウト統一 |
| PHP warning / JS error / missing media request | いずれも0件 |

### Mobile 390px

| 項目 | 結果 |
| --- | --- |
| HTTP status | 200 |
| horizontal overflow | 0 |
| console error | 0 |
| broken image | 0 |
| H1 | 一意 |
| Hero | 画像あり、縦積みレイアウトで単純矩形へリセット（既存設計通り）、コピー・CTA判読可能 |
| Case | 3件とも縦積みで崩れなし、クロップ破綻なし |

スクリーンショット: `docs/research/screenshots/025-B2/desktop-1440-{full,hero,case}.png`／`mobile-390-{full,hero,case}.png`

## 11. Editor Validation Check（追加確認、Order §11関連）

Hero Cover Blockの`innerHTML`を手動再構築している以上、Gutenbergのブロックバリデーションで「このブロックには予期しない、または無効な内容が含まれています」という復旧警告が出るリスクを事前に認識していたため、Block Editorで対象ページ（`post=32&action=edit`）を実際に開いて確認した。

結果: **警告バナーなし**。エディタキャンバス上にもHero写真が正しくレンダリングされ、ブロック復旧（Attempt Block Recovery）の表示は一切発生しなかった。手動再構築した`innerHTML`が、WordPress Core自身のCover Block保存仕様（`wp-block-cover__image-background has-{color}-background-color has-background-dim has-background-dim-{N}`、`data-object-fit="cover"`）とバイト単位で一致していることの実地証明となった。

## 12. Original Design Vision Re-Audit（Order §13）

`docs/research/design-reference/astrea-original-design-vision.png`を再度直接目視し、更新後のASTREA実機（`docs/research/screenshots/025-B2/desktop-1440-full.png`）と対比した。

| Section | 判定 | 変化 | 備考 |
| --- | --- | --- | --- |
| Header | MINOR GAP | 変化なし（本Phase対象外・凍結） | タグライン・営業時間の表示なし。Theme実装課題（B） |
| **Hero** | **MATCH** | **MAJOR GAP → MATCH** | 斜め境界・実写オフィス写真とも揃い、Visionとの視覚的整合を達成。原因はDemo Content不足（A）で、画像投入のみで解消 |
| Service | MINOR GAP | 変化なし | 構造はほぼ一致（Theme実装、軽微） |
| **Case** | **MATCH** | **MAJOR GAP → MATCH**（3件中3件） | 3件とも実写・統一クロップで揃った。原因はDemo Content不足（A）で、画像投入のみで解消 |
| Results | MATCH | 変化なし | Vision自体が無地ネイビーのため一致 |
| About | MINOR GAP | 変化なし（本Phase対象外・凍結） | ポートレート右端のフェード処理未実装。Theme実装課題（B） |
| Price | **MATCH（実測により判明）** | 想定より良好 | 実機確認の結果、HOMEページの価格ブロックは既に`--compact`バリアント（1×4グリッド、区切り線あり）を使用しており、Phase Aで指摘した2×2グリッドの汎用バリアントとは別物だった。**Visionと既に一致**しており、追加対応は不要 |
| CTA | MATCH | 変化なし | ゴールドボタン＋アウトライン電話ボタン、Vision通り |
| Footer | 比較対象外 | — | Vision画像自体がPrice直後で切れており、Footerセクションの参照情報が存在しない |

**根本原因の総括（Order §13必須項目）**: 本Phase開始前に残っていた「ASTREAがVisionより見劣りする」という印象の根本原因は、実測の結果 **(A) Demo Content不足が支配的**だったことが確定した。Hero・Case #2/#3という3箇所の画像未設定こそがMAJOR GAPの正体であり、これらはTheme/Coreのコード変更を一切伴わず、既存の標準WordPress機能（Cover Block・Featured Image）へ正式画像を投入するだけで解消した。残るGAP（Header/About/Service、いずれもMINOR）はTheme実装上の細部（B）だが、Owner受け入れ基準「明確に見劣りしない」（ピクセルパーフェクト不要）に照らして、**受け入れを妨げる水準ではない**と判断する。

## 13. 025-C候補の再分類（Order §14、実装は行わない）

| 項目 | Phase A時点の分類 | 025-B2後の再分類 | 理由 |
| --- | --- | --- | --- |
| Price 2×2グリッド | 要修正候補 | **NO LONGER NECESSARY** | 実機確認により、HOMEページは既に`--compact`（1×4）バリアントを使用しておりVisionと一致。汎用2×2グリッドCSSは別の用途（他ページ等）のためのバリアントであり、HOMEの表示には影響していない |
| About写真フェード | 要修正候補 | **OPTIONAL** | 依然として未実装（軽微な視覚差）。Hero/Caseの解消により全体の見劣り感が大きく改善したため、緊急性は低い |
| Headerタグライン/営業時間 | 要修正候補 | **OPTIONAL** | 依然として未実装。情報として有用だが、現状でもHeader自体は事務所名・ナビ・電話・CTAを備えており、Vision対比で致命的な見劣りではない |

**Owner確認済み方針の通り、いずれも本Phaseでは実装しない。** Owner が完成画面（本報告書のスクリーンショット）をご確認いただいた上で、次のOrderにて要否をご判断いただきたい。

## 14. Screenshot Evidence（Order §15）

`docs/research/screenshots/025-B2/`に以下6点を新規保存（過去の削除済み235件は復元していない）:

- `desktop-1440-full.png` / `desktop-1440-hero.png` / `desktop-1440-case.png`
- `mobile-390-full.png` / `mobile-390-hero.png` / `mobile-390-case.png`

## 15. Git Safety（Order §16）

```
$ git diff --stat -- theme/ core/
(no output)
```

Theme/Core配下は本Phase開始前・終了後とも無変更（ゼロdiff確認済み）。

変更のあったパスは、Order §16のホワイトリストの範囲内:
- `docs/demo-assets/yamada-live-demo/`（README.md, yamada-demo-export.wxr, images/, scripts/）
- `docs/research/screenshots/025-B2/`（新規）
- `docs/research/2026-09-10_construction_025b2_final_asset_integration.md`（本報告書、新規）

過去screenshots 235件の削除（Owner既知・容量整理）はそのまま無変更。`docs/research/references/`配下の無関係なZone.Identifier 1件、および`if-professional-astrea.code-workspace`は本Phase開始前から存在する未追跡ファイルであり、本Phaseの対象外として一切触れていない。

**`git add`・`git commit`・`git push`のいずれも実行していない**（Order §16の明示的指示通り）。

## 16. Files Changed（サマリー）

- Theme/Core: 変更ゼロ
- 新規/更新（`docs/demo-assets/yamada-live-demo/`）: `README.md`（更新）、`yamada-demo-export.wxr`（更新）、`images/`内PNG3点（新規）、`images/web/`内JPEG3点（新規）、`scripts/integrate-025b2-hero-and-cases.php`（新規）
- 新規（報告書・証跡）: 本報告書、`docs/research/screenshots/025-B2/`（6枚）
- 削除: Zone.Identifierファイル4件（前Phaseで実施済み、本報告に記録）

## 17. Final Verdict

**A. DEMO VISUAL ACCEPTANCE CANDIDATE READY**

Hero・Case #1〜#3・Representative Portraitの5点すべてが、既存ASTREA Theme/Coreの標準機能（Cover Block・Featured Image）のみを用いて正式統合された。Theme/Coreコード変更はゼロ。独立クリーンリビルド環境での再現性も確認済み。Original Design Visionとの視覚的ギャップは、当初MAJOR GAPだったHero・Caseが完全にMATCHへ改善し、残るGAPはいずれも軽微（MINOR/OPTIONAL）。

---

**STOP — 025-C・Construction 024・VPS作業・本番デモ公開のいずれにも進みません。** Owner に本報告書のスクリーンショット（`docs/research/screenshots/025-B2/`）をご確認いただいた上で、次のOrderをお待ちします。
