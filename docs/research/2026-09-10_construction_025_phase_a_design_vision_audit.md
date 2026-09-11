# Construction 025 Phase A — ASTREA Original Design Vision Restoration / Visual・Product Gap Audit

- Start: 2026-09-10 09:36:26 JST（実測）
- End: 2026-09-10 09:42:21 JST（実測）
- Duration: 0:05:55
- Modifier: Chloe
- Mode: **AUDIT ONLY**。Theme/Core product code・demo content・version番号・release ZIP・commit・push・deploy・VPS・Construction 024再開・/astrea/公開・Project-if CTA・WordPress.org提出、いずれも一切実施していない。

## 1. Preflight Status

- `docs/research/design-reference/astrea-original-design-vision.png`（1,429,903 bytes、2026-08-29作成）の**実在を確認し、実際に目視した**（ファイル名・README等からの推測ではない）。
- 副次的に、Order本文で言及されていた`docs/demo-assets/yamada-live-demo/images/astrea-demo-yamada-results-background.png`（1,619,562 bytes）も既に配置済みであることを確認した（本Phaseでは使用・組み込みは行っていない）。
- リポジトリ状態: `git status --short`は本Phase開始前と変わらず（Construction 024の未コミット差分なし、Theme/Core無変更）。

## 2. Original Design Vision — 目視結果の要約

実際に見えた要素（推測ではなく画像から直接確認したもの）:

- **Header**: ロゴ「ASTREA 行政書士事務所」+ 小さなタグライン「ASTREA PROFESSIONAL OFFICE」、右側にナビゲーション6項目、電話番号+営業時間（平日9:00-18:00）、濃紺の塗りつぶし「お問い合わせ」ボタン。
- **Hero**: 左側テキストプレーン（白背景）に kicker・大見出し・リード文・電話ボタン+アウトラインボタン、左端に縦書き装飾ラベル。右側は**斜めの境界線（diagonal clip）**で区切られた実写オフィス写真（高層ビルからの眺望、デスク）。
- **Service**: 3カラム、各カラムに大きな連番（01/02/03）+小アイコン+見出し+説明+「詳しく見る→」、カラム間に縦罫線。
- **Case**: グレー背景セクション、見出し行右に「事例をもっと見る→」、3カードすべてに実写真（左上に連番バッジ重畳）+タイトル+説明。
- **Results**: 濃紺の帯、3項目、各アイコン（アウトライン円形）+大きな数字/単位+ラベル、項目間に縦罫線。**背景写真は一切使用されておらず、無地の濃紺塗り**。
- **About**: 左に大判ポートレート写真（自然光、ブラインドの影、**右端が白背景へ柔らかくフェードする処理**）、右にプロフィール文+アウトラインボタン。
- **Price**: **4カラムが1行に横並び**、各アイコン+サービス名+大きな価格+小さな注記。
- **CTA帯**: 濃紺、中央見出し、アウトライン電話ボタン+**ゴールド塗りつぶし**のお問い合わせボタン。
- 全体を通じ、濃紺（`#0f1c2e`前後と見られる非常に濃い紺）とゴールドのアクセントが一貫。

## 3. Current ASTREA — コード確認結果の要約

**重要な結論を先に述べる: 目視で確認した「Original Design Visionとの視覚的な差」の大部分は、Theme/Coreのコード側の機能不足ではなく、Construction 019→023で構築された「やまだ行政書士事務所」デモが、既存の機能を使い切っていない（画像を設定していない等）ことに起因していた。**

以下、コード（`theme/patterns/*.php`、`theme/theme.json`のstyles.css、`core/includes/*-block.php`）を実際に読んで確認した事実:

| 要素 | 確認結果 |
| --- | --- |
| Hero写真プレーン | `home-hero.php`に`core/cover`ブロックが既に実装済み（`astrea-hero-photoplane`）。画像未設定時はGutenberg標準の「no-image」表示（意図的な無地）。**サイトオーナーが標準のCover Block画像コントロールから写真を設定できる設計が既に存在する。** |
| Hero斜め境界 | `theme.json`に`.astrea-hero-textplane{clip-path:polygon(0 0,82.4% 0,61.8% 100%,0 100%);}`が実装済み。Construction 016Eで実際にこのOriginal Design Vision画像を測定して実装されたことがコードコメントに明記されている。 |
| Hero縦書き装飾ラベル | `.astrea-hero-vertical`実装済み（Office Profileの事務所名にバインド、`writing-mode:vertical-rl`）。 |
| Results アイコン・大数字・縦罫線 | `results-list-block.php`でアイコン（`astrea_result`のMETA_ICON、管理画面で選択可）+ 数字/単位分割表示（`render_value()`）を実装済み。`theme.json`に`.wp-block-astrea-result-item{border-left:1px solid rgba(255,255,255,.18);}`（1件目除く）で縦罫線も実装済み。 |
| Results背景写真 | 現在は完全に無地の濃紺（`core/group`のbackgroundColor）。Original Design Vision自体も背景写真なし（無地濃紺）——**この点はOriginal Design Visionとむしろ一致**しており、Orderが用意した`results-background.png`はVisionの復元ではなく**新規のオプション拡張**である。 |
| Case Featured Image | `case-list-block.php`で`$case['photo_id']`（標準のFeatured Image、`wp_get_attachment_image()`）を使用。未設定時は`.is-empty`の意図的な空状態div（壊れた画像ではない）。**3件すべてが標準のFeatured Image UIで写真を持てる設計。** Construction 023で1件のみ画像を設定したのは、023-A/023-Bの画像仕様策定がCase #1のみを対象にしたためであり、Theme/Coreの制約ではない。 |
| Price アイコン・4カラム | `price-list-block.php`でアイコン+グループラベル+名前+金額+備考を実装済み。ただし`theme.json`の現在のCSSは`grid-template-columns:repeat(2,1fr)`——**Original Design Visionの1行4カラムとは異なり、2×2グリッドになっている。これは実際のコード上の差分（Theme CSSのみ、Core変更不要）。** |
| Service 縦罫線・連番 | `.wp-block-astrea-service-item{border-top:1px solid ...}` + `:not(:first-child){border-left:1px solid ...}` + `counter-increment`による連番——実装済み、Original Design Visionと概ね一致。 |
| About/代表者写真 | `.wp-block-astrea-representative-photo{aspect-ratio:1.7;overflow:hidden;}`+`object-fit:cover`。**Original Design Visionの「右端が白背景へ柔らかくフェードする」処理は現状未実装**（フェード/グラデーションマスクのCSSは見つからなかった）。 |
| Header タグライン・営業時間 | 現状のヘッダーは事務所名のみバインド。Original Design Visionの「ASTREA PROFESSIONAL OFFICE」タグラインおよび電話番号下の営業時間表示は**未実装**。 |
| CTA帯（ゴールドボタン） | `home-cta.php`で`backgroundColor:"accent"`（ゴールド）のボタンを実装済み——Original Design Visionと一致。 |
| カラーパレット | `accent`（ゴールド）`#B99A5C`はVisionの金色と近い。`primary`（ネイビー）`#1f3a5c`はVisionのより濃い紺と比べてやや明るく見えるが、正確な数値比較はしていない（目視の印象レベル）。 |

## 4. Section-by-Section Audit

凡例: WP editability分類 A=標準UIで完全に編集可能／B=編集可能だがUX改善が必要／C=現状コード依存／D=未対応

### A. Header

| 項目 | 内容 |
| --- | --- |
| REFERENCE | ロゴ+タグライン、6項目ナビ、電話番号+営業時間、濃紺の塗りつぶしCTAボタン |
| CURRENT | 事務所名のみ、ナビ、電話ボタン+アウトラインお問い合わせボタン |
| GAP | タグライン行・営業時間表示が欠落。CTAボタンがoutlineでVisionの塗りつぶしと異なる |
| ROOT CAUSE | パターンにタグライン/営業時間用のブロックが存在しない（機能不足ではなく単に未追加） |
| WORDPRESS-NATIVE SOLUTION | `office_name`直下に小さいParagraphを追加しOffice Profileの新規/既存フィールドにバインド。営業時間は既存の`business_hours`データから表示するDynamic Block/Bindingを検討 |
| PRODUCT CHANGE REQUIRED | Theme: `parts/header.html`に軽微な追加。Core: 営業時間を表示専用で公開するread boundary関数が必要な場合のみ軽微な追加 |
| RISK | 低（レイアウト追加のみ、既存要素の削除なし） |
| PRIORITY | 中 |

### B. Hero

| 項目 | 内容 |
| --- | --- |
| REFERENCE | 左テキスト+右写真、斜め境界、縦装飾ラベル |
| CURRENT | **同一の構造が既にコードに実装済み**（§3参照）。デモは写真を設定していないため無地に見える |
| GAP | **コード上のGAPなし。デモコンテンツのGAPのみ**（Hero写真が未設定） |
| ROOT CAUSE | Construction 023-A/023-Bの画像仕様策定でHero写真が対象に含まれていなかった（見落とし） |
| WORDPRESS-NATIVE SOLUTION | Site Editor／該当CoverブロックからHero写真プレーンへ画像を設定するだけ |
| PRODUCT CHANGE REQUIRED | **なし** |
| RISK | なし（コード変更不要） |
| PRIORITY | 高（ただしTheme/Core改修ではなく、デモコンテンツ追加として） |

### C. Service

| 項目 | 内容 |
| --- | --- |
| REFERENCE | 3カラム、連番+アイコン、縦罫線 |
| CURRENT | 同等の構造が既に実装済み（連番・アイコン・上罫線・左罫線） |
| GAP | 軽微（アイコンの視覚的な太さ・余白バランスは実機比較が必要） |
| ROOT CAUSE | ほぼ一致、大きな差はコードから確認できず |
| WORDPRESS-NATIVE SOLUTION | 該当なし（現状維持を基本線とし、実機比較で微調整の要否を判断） |
| PRODUCT CHANGE REQUIRED | 未定（実機視覚比較が必要、Phase Aでは確定しない） |
| RISK | 低 |
| PRIORITY | 低 |

### D. Case

| 項目 | 内容 |
| --- | --- |
| REFERENCE | 3件すべて写真付き |
| CURRENT | Featured Image機構は3件とも完全に対応済み（コード上は同一）。デモでは1件のみ画像設定 |
| GAP | **コード上のGAPなし。デモコンテンツのGAPのみ** |
| ROOT CAUSE | 023-A/023-Bの画像仕様策定がCase #1のみを対象にした |
| WORDPRESS-NATIVE SOLUTION | 通常の投稿編集画面のFeatured Imageから残り2件に画像を設定するだけ |
| PRODUCT CHANGE REQUIRED | **なし** |
| RISK | なし |
| PRIORITY | 高（デモコンテンツ追加として） |

### E. Results

| 項目 | 内容 |
| --- | --- |
| REFERENCE | 濃紺無地、アイコン、大数字、縦罫線 |
| CURRENT | アイコン・大数字・縦罫線とも実装済み。背景は無地濃紺——**Visionと一致** |
| GAP | **実質的なGAPなし**（Vision自体が無地濃紺のため、`results-background.png`は「Visionへ戻す」ためではなく新規オプション） |
| ROOT CAUSE | Order側の前提（Visionに背景写真がある）が実際の画像と異なっていた |
| WORDPRESS-NATIVE SOLUTION | 任意: 将来的にオプションとして`core/cover`でResultsを包み、背景画像+オーバーレイ透明度をSite Editorから設定可能にする拡張は可能（Hero同様のパターン）。ただし**Original Design Vision復元という目的においては必須ではない** |
| PRODUCT CHANGE REQUIRED | 目的次第。Vision復元だけが目的なら不要。任意機能として追加するなら`home-results-teaser.php`をCoverでラップ（Theme-onlyパターン変更、Core変更不要） |
| RISK | 低（追加する場合も既存の無地表示が既定値のまま維持されるよう設計可能） |
| PRIORITY | 低（Vision復元の観点では優先度低。任意拡張としてなら中） |

### F. About

| 項目 | 内容 |
| --- | --- |
| REFERENCE | 大判ポートレート、右端が白背景へ柔らかくフェード |
| CURRENT | ポートレートは`aspect-ratio:1.7`+`object-fit:cover`で表示されるが、フェード処理は未実装 |
| GAP | フェード処理のみ（構造・比率は概ね妥当） |
| ROOT CAUSE | CSSにフェード用のグラデーションマスクが実装されていない（見落とし、意図的な除外ではない） |
| WORDPRESS-NATIVE SOLUTION | 画像自体はFeatured Imageのまま、コンテナに`mask-image`または疑似要素グラデーションをTheme CSSのみで追加（画像ファイル自体の加工は不要） |
| PRODUCT CHANGE REQUIRED | Theme CSSのみ（`theme.json`の`.wp-block-astrea-representative-photo`に追記） |
| RISK | 低〜中（`mask-image`のブラウザ対応を要確認） |
| PRIORITY | 中 |

### G. Price

| 項目 | 内容 |
| --- | --- |
| REFERENCE | 4カラムが1行 |
| CURRENT | `grid-template-columns:repeat(2,1fr)`——2×2グリッド |
| GAP | **確認されたコード上の実差分**（レイアウトのみ、データ構造は同一） |
| ROOT CAUSE | Theme CSSのカラム数指定 |
| WORDPRESS-NATIVE SOLUTION | 該当なし（CSS修正のみで解決、ユーザー操作は不要） |
| PRODUCT CHANGE REQUIRED | Theme CSSのみ（`repeat(4,1fr)`または`repeat(auto-fit,minmax(...))`への変更、モバイル時の折り返しを別途考慮） |
| RISK | 低（デスクトップでの折り返し崩れがないか実機確認要） |
| PRIORITY | 中 |

### H. FAQ / Voice

| 項目 | 内容 |
| --- | --- |
| REFERENCE | 直接比較対象はOriginal Design Visionの画像内に含まれていない（画像はPrice/CTAで終わっている） |
| CURRENT | 罫線ベースのミニマルな実装（過剰な装飾なし） |
| GAP | 確認できる明確な問題なし |
| ROOT CAUSE | — |
| WORDPRESS-NATIVE SOLUTION | 現状維持を推奨 |
| PRODUCT CHANGE REQUIRED | なし |
| RISK | — |
| PRIORITY | 低 |

### I. CTA

| 項目 | 内容 |
| --- | --- |
| REFERENCE | 濃紺帯、アウトライン電話ボタン+ゴールド塗りつぶしお問い合わせボタン |
| CURRENT | 同一構成が既に実装済み |
| GAP | 実質的なGAPなし |
| ROOT CAUSE | — |
| WORDPRESS-NATIVE SOLUTION | 該当なし |
| PRODUCT CHANGE REQUIRED | なし |
| RISK | — |
| PRIORITY | 低 |

### J. Footer

Original Design Vision画像はFooterまで含んでいないため直接比較はできない。現状のFooter（事務所名/住所/電話+ナビ+クレジット）は構造として妥当と判断した。

### K. Overall

- Typography・spacing・navy/gold運用は既に高い水準で統一されている（`theme.json`のCSS変数体系が一貫）。
- 最大の発見は「デザインの実装不足」ではなく「デモの画像投入不足」であったこと。
- 唯一のコード上の確定GAPはPrice のカラム数（G）。About のフェード処理（F）とHeader のタグライン/営業時間（A）は小規模な追加。

## 5. WordPress Editability Matrix

| 項目 | 分類 | 変更経路 |
| --- | --- | --- |
| Hero背景写真 | **A** | Site Editor / 該当Cover Block の画像コントロール |
| Hero斜め境界・縦ラベル | A（表示は自動、内容は編集可） | 事務所名編集はOffice Profile経由 |
| Case写真（3件とも） | **A** | 投稿編集画面のFeatured Image |
| Results数字/ラベル/アイコン | **A** | ASTREA Core管理画面（実績の追加・編集） |
| Price項目/金額/アイコン | **A** | ASTREA Core管理画面（料金の追加・編集） |
| About/代表者写真 | **A** | 投稿編集画面のFeatured Image |
| Header タグライン・営業時間表示 | **D**（未実装） | 追加実装後はOffice Profile経由でA相当になる想定 |
| About写真フェード処理 | 該当なし（CSSのみで自動適用） | ユーザー操作不要（Theme側の表示ルール） |
| Price 4カラムレイアウト | 該当なし（CSSのみで自動適用） | ユーザー操作不要 |
| Results背景写真（新規オプション） | 追加実装すれば**A** | Site Editor / Coverブロックの画像コントロール（Hero同様） |

**結論: 「コードを書かないと変更できない」という状態のVision要素は、現時点でHeaderのタグライン/営業時間のみ（D）。他はすべて標準WordPress UIで既に対応済み、または対応済みになる設計。**

## 6. Theme/Core Change Scope

| 変更 | 対象 | Theme/Core |
| --- | --- | --- |
| Price 4カラム化 | `theme.json` styles.css | **Theme のみ** |
| About写真フェード | `theme.json` styles.css | **Theme のみ** |
| Header タグライン追加 | `theme/parts/header.html`（軽微） | **Theme のみ**（Office Profileに既存フィールドがあれば追加フィールド不要） |
| Header 営業時間表示 | `theme/parts/header.html` + 表示用read boundary | Theme + **Core（軽微、既存の`business_hours`データを読むだけの関数追加）** |
| Results背景写真（任意） | `theme/patterns/home-results-teaser.php`を`core/cover`でラップ | **Theme のみ**（Hero と同一手法の横展開） |

**Core側で新しいCPT・postmeta・データモデルの追加が必要な項目は一つも確認されなかった。** 既存の`business_hours`データを「表示専用で公開する」小さな関数（Coreの「唯一の公開読み取り境界」原則に従う）が必要になる可能性がある程度。

## 7. Version-Up Recommendation

- **Theme**: 1.0.2 → **1.0.3（パッチ想定）または1.1.0（Owner裁量）**。いずれの変更も既存テンプレート・既存Site Editorカスタマイズ・既存パターンを削除/breaking changeするものではなく、CSS追加・パターン内の軽微な追加のみ。既存ユーザーの保存済みコンテンツへの影響なし。
- **Core**: 営業時間表示用の小さな公開関数を追加する場合のみ**Core 1.0.1 → 1.0.2相当の軽微な追加**が必要になる可能性がある。それ以外（Price CSS、Aboutフェード、Hero/Results写真活用）はCore変更不要——**Coreは無理に更新しない**という本Order自身の原則に従う。

## 8. Proposed Construction Plan（Order提案の025-B〜025-Jから大幅に簡素化）

実コード調査の結果、Original Design Visionの大部分は**既にTheme/Coreに実装済み**であり、Order提案の大規模な9段階（025-B〜025-J）は本Phaseの発見事実に見合わない過大な分割だと判断した。より安全かつ実態に即した分割を提案する:

- **025-B: Demo Content Completion**（Theme/Core変更なし。Construction 025の一部としてではなく、Construction 024系列の「やまだ行政書士事務所」デモコンテンツ拡充として実施すべき）
  - Hero背景写真の設定
  - Case #2・#3への写真設定
  - 対象: `docs/demo-assets/yamada-live-demo/`（ローカルデモのみ、Theme/Core無変更）
- **025-C: Small Theme-Only Visual Refinements**
  - Price 4カラム化（CSS）
  - About写真フェード処理（CSS）
  - Header タグライン追加（パターン軽微追加）
- **025-D: Header Business Hours Display**（Core軽微追加を伴う唯一の項目、独立Phaseとして切り出し慎重に扱う）
- **025-E: Results Background Image（任意拡張）**（Vision復元とは別軸の新機能。Owner判断で実施有無を決める）
- **025-F: Integrated Visual QA / Fresh Install / Upgrade Test / Release Candidate**

Order提案の025-G〜025-J相当（FAQ/Voice/CTA/Footer専用フェーズ）は、§4の監査で明確なGAPが見つからなかったため**不要**と判断する。

## 9. Test / Visual QA Plan

- PHPUnit（既存399件）
- PHPCS（既存67ファイル、0 violations基準を維持）
- Theme Check / Plugin Check（既存の受入基準を維持）
- Playwright: 1440px・390px、horizontal overflow=0、console error=0、H1 unique
- Site Editor実機確認: Hero/Results背景画像の設定・解除・overlay調整が実際にUIから可能なこと
- Featured Image実機確認: Case 3件・Professional のFeatured Image設定/解除
- Fresh install（新規サイト、Theme単体・Theme+Core）
- Upgrade path: 実際に1.0.2稼働中の「やまだ行政書士事務所」ローカル環境（Construction 023-B時点）へ新versionを上書きし、既存コンテンツ・Site Editorカスタマイズが破壊されないことを確認
- Visual Regression: Construction 023-Bで撮影済みのスクリーンショット（`docs/research/screenshots/023-B/`）と実装後の同一条件スクリーンショットを並べて比較

## 10. Blockers / Risks

- **リスクは低い**: 監査の結果、大規模なアーキテクチャ変更は不要と判明したため、当初懸念されたBreaking Change・Core大幅改修のリスクは実質的に解消された。
- 唯一の留意点: Header営業時間表示のためのCore関数追加は、たとえ軽微でもCore側の「唯一の公開読み取り境界」原則（`OfficeProfile\get_office_profile()`等の既存パターン）に厳密に従う必要があり、実装Phaseで丁寧な設計レビューが必要。
- About写真フェード（`mask-image`）はブラウザ互換性の確認が必要。

## 11. Final Verdict

**A. READY FOR CONSTRUCTION 025-B**

ただし、Order原案の「025-B: Foundation / Global Visual System」ではなく、本報告§8で再提案した「025-B: Demo Content Completion」から着手することを推奨する。これはTheme/Core変更を一切伴わず、既存の標準WordPress機能（Featured Image、Cover Block画像設定）のみで、Original Design Visionとのギャップの大部分を解消できるためである。

Construction 024は引き続きHOLD。VPSには一切触れていない。

---

**STOP — Owner承認をお待ちします。** 025-Bの内容（提案通りDemo Content Completionから着手するか、あるいはOrder原案通りFoundation/Global Visual Systemから着手するか）についてご判断をお願いします。
