# Construction 025-E0 — Results Background Capability Audit

- Start: 2026-09-10 11:47:25 JST（実測）
- End: 2026-09-10 12:06:32 JST（実測）
- Duration: 0:19:07
- Modifier: Chloe
- Mode: Audit Only。theme/・core/・demo content・background image設定のいずれも変更ゼロ。commit/push未実施。

**Final Verdict: D. UNSUPPORTED — THEME 1.0.3 CHANGE REQUIRED**

---

## 0. Preflight時の付帯報告（本Order対象外・参考情報）

`HISTORY.csv`が本Order開始前から更新（差分あり）状態だったことを確認した。内容を見ると、本Construction 025-E0のOrder文自体が、既存の`013 RESEARCH`行の途中に誤って貼り付けられ、CSV行が破壊されている（Ownerが`HISTORY.csv`をIDEで開いていた際の貼り付け先間違いと推測される）。**本Phaseでは一切手を加えていない**（復元も追加変更もしていない）。Ownerが元に戻したい場合は`git checkout -- HISTORY.csv`（未コミットのため復元可能）。本件は本Order §16のホワイトリスト外のため、報告のみに留める。

## 1. Preflight（Order §4）

```
$ git status --short --branch
## main...origin/main
（HISTORY.csv含む複数の変更、詳細は上記0節および末尾§17参照）

$ git diff -- theme/ core/
(no output)
```

過去screenshots 235件の削除（Owner既知の意図的容量整理）はそのまま無変更。開始時点でTheme/Core配下は無変更を確認。

## 2. Current Results Architecture（Order §5）

**RESULTSは完全にカスタムのDynamic Block（`astrea/results-list`）で構成されている。Group / Cover / Columns のいずれでもない。**

- Pattern: `theme/patterns/home-results-teaser.php` — 中身は`<!-- wp:astrea/results-list {"heading":"実績"} /-->`の1行のみ。ラッピングするGroup/Cover等は一切存在しない（Hero等と異なり、そもそも背景を持つ親コンテナが無い設計）。
- Block登録: `core/includes/results-list-block.php`（`Astrea\Core\Result`名前空間）。`register_block_type()`の`attributes`は`heading`（string）と`emptyMessage`（string）の2つのみ。**`supports`キー自体が存在しない** — つまりcolor/background/spacing等、WordPressの標準Block Supports機構による設定パネルが一切生成されない。
- レンダリング: 完全サーバーサイドレンダリング（`render_results_list_block()`）。各Result項目は`astrea_result`カスタム投稿タイプから取得し、アイコン（`Astrea\Core\IconSystem\render()`）・値・ラベルをプレーンHTMLとして出力するのみ。画像関連の属性・出力は一切ない。
- **background-colorの設定箇所**: `theme/theme.json`の`styles.css`内、`.wp-block-astrea-results-list{...background:var(--wp--preset--color--contrast);...}`という**Theme側CSSのハードコード**。ブロックの属性やインラインスタイルではなく、クラス名を直接ターゲットにしたグローバルCSSルールである。
- background-image capability: **ブロック自体には存在しない**（属性なし、supportsなし）。
- overlay capability: **存在しない**（`core/cover`のような`dimRatio`/`overlayColor`の概念自体がこのブロックにはない）。
- focal point capability: **存在しない**（背景画像自体を持てないため、focal pointを議論する対象がそもそも無い）。
- spacing / padding / min-height: すべてTheme側`theme.json`の`.wp-block-astrea-results-list{padding-block:...;padding-inline:...}`というCSSハードコードで一括制御されており、Block Inspectorからの個別調整経路はない（Global StylesでもCoreの`supports`が無いため同様に触れない）。

## 3. Background Image capability

**判定: PARTIAL**（ブロック自体はNOだが、標準UIでの回避策が部分的に機能するため）

- `astrea/results-list`ブロック自体を選択した状態のBlock Inspectorには、Typography/Background/Colorのパネルが一切表示されない（「Advanced」のみ）。実機確認済み（スクリーンショット参照）。
- 標準の`core/group`ブロックでResultsパターンを包む（Group内に挿入する）ことは可能で、Groupブロック自体は標準で「Background → Image / Color / Gradient」パネルを持つ。実際にMedia Libraryから既存画像を選択し適用できることを実機確認した。
- しかし、この方法では**Results本体（3列の数値・アイコン・ラベルが乗っている部分）の背後には画像が表示されない**。理由は次項参照。

## 4. Overlay capability

**判定: NO**

- `astrea/results-list`ブロックにoverlay関連の属性・UIは一切ない。
- 回避策のGroupブロックのBackground機能にも、`core/cover`が持つようなオーバーレイ濃度スライダー・オーバーレイカラーの概念は存在しない（Image/Color/Gradientの選択のみ）。
- 仮に`core/cover`でラップした場合でも、次項の「子要素の不透明背景による遮蔽」問題は解消しない（Coverのoverlayは自分自身の背景画像に対してのみ効き、内部に配置した`astrea/results-list`自体の不透明な`background-color`は変わらず前面に描画される）。

## 5. Focal Point capability

**判定: NO**

背景画像をResults本体に設定する経路自体が存在しないため、focal pointを議論する対象が無い。回避策のGroup Background機能にも、Cover同様のfocal pointピッカーは実装されていない（Image/Color/Gradientの選択のみで、位置調整UIは無い）。

## 6. Replace / Remove capability

該当なし（そもそも設定できないため、置換・削除も議論の対象外）。回避策のGroup画像であれば、標準のMedia Library UIで選択・置換・削除（「Clear Media」相当）が可能なことは確認したが、これは「Results本体」の画像ではなく「Resultsの外側に付加した無関係な装飾ラッパー」の画像に過ぎない。

## 7. Content Editability

**判定: YES（問題なし）**

背景画像の有無に関わらず、RESULTS見出し・300/800/98%等の数値・ラベル・アイコン・区切り線はすべて`astrea_result`投稿タイプ由来の通常のWordPress編集対象のまま維持されることを確認した。今回のテストでも、画像を文字・数字に焼き込む処理は一切行っておらず、実際のDynamic Blockをそのまま使用した。

## 8. Standard WordPress UI Test（Recreate Test、Order §7）

Yamada Demoの本番Resultsには一切手を加えず、**Disposable Test Page**（post ID 63、テスト完了後に`wp_delete_post()`で完全削除済み）上で以下を検証した。

**手順**: 新規ページ作成 → `core/group`ブロックを挿入 → 標準のPattern挿入UIから「HOME - RESULTS Teaser」パターン（＝`astrea/results-list`そのもの）をGroup内に挿入 → GroupのBlock Inspector「Background → Image」から既存Media Library内の画像（テスト用途のため既存資産の1枚を使用、正式なResults背景画像は使用していない）を選択・適用 → 公開 → フロントエンドで実機確認。

**結果（DOMの実測、祖先チェーンのcomputed style）**:

| 要素 | 幅 | 高さ | background-image | background-color |
| --- | --- | --- | --- | --- |
| `.wp-block-astrea-results-list`（Results本体） | 545px | 453.6px | none | `rgb(27,36,48)`（不透明navy） |
| `.wp-block-group`（ラッピングGroup） | 640px | 673.5px | 設定した画像URL | transparent |

フロントエンド実機スクリーンショットでも、Group自体の余白部分（Results本体カードの外側の隙間）にのみ背景画像が見え、Results本体カード（数値・アイコンが乗っている領域）は完全に不透明navyで覆われ、画像は一切透けて見えない。これはCSSの基本仕様（子要素の不透明`background-color`は、その子要素の矩形内で親要素の背景を完全に遮蔽する）による必然的な結果であり、`core/cover`でラップした場合も同一の理由で解消しない（Coverのoverlayは自分の背景画像にのみ作用し、内側の`astrea/results-list`自身の不透明背景は変わらないため）。

さらに副次的な問題として、Group内にネストすると`.wp-block-astrea-results-list`が期待する`main.alignfull>.wp-block-post-content .wp-block-astrea-results-list`というセレクタ連鎖に一致しなくなり、本来の全幅（edge-to-edge）レイアウトも崩れる（640px幅のGroup内に545px幅で収まってしまう）ことも確認した。

**結論**: 標準WordPress UIのみでは、「背景画像＋オーバーレイ＋3列指標＋アイコン＋大きな数字＋区切り線」という一体的なデザインを**再現できない**。

スクリーンショット: `docs/research/screenshots/025-E0/`（後述）

## 9. Capability Classification（Order §8）

**D — Unsupported**

現行ASTREA 1.0.2のResults構造では、意図した視覚効果（背景画像が数値・アイコンの背後に見える状態）を実現する経路が標準UI上に存在しない。Groupでのラップは「画像を選択・適用する」という操作自体は可能（この点でCよりは近いように見えるが）、その結果としてResults本体には画像が全く反映されず実質的に無意味であり、実現には以下のいずれかのTheme側コード変更が必須である。

- `astrea/results-list`のCSS（`.wp-block-astrea-results-list{background:...}`）の変更
- パターン構造の変更（Cover等でのラップ＋Core Block Support拡張）

いずれもTheme改修（テンプレート編集相当）が必要であり、custom CSS/PHP/demo-specific hackを使わない限り実現不可能という点で、Order定義のD要件（"custom CSS / template edit / PHP / demo-specific hackが必要"）に合致する。

## 10. Product Change Required?

**YES**（背景画像＋オーバーレイをResultsセクションの正式機能として提供したい場合）

## 11. 推奨アーキテクチャ（Order §9、実装は行わない・設計案のみ）

優先順位に従い検討した結果:

### 推奨: `core/cover`ベースへの再構成（優先度1）

Hero（`astrea-hero-photoplane`）で既に実証済みのパターンをResultsにも適用する。

1. `theme/patterns/home-results-teaser.php`を、`astrea/results-list`を`core/cover`で包む構造に変更する（Hero同様、デフォルトは背景画像なし＝WordPress標準のCover無地状態）。
2. `theme/theme.json`の`.wp-block-astrea-results-list{background:var(--wp--preset--color--contrast);...}`ルールを、**Cover側（`.astrea-results-photoplane`のような専用クラス）に移設**し、Results本体自身の不透明背景は撤去して透明にする。これによりCoverの`overlayColor`/`dimRatio`がそのままResultsの見た目を制御するようになり、Hero同様「画像を選ぶ・オーバーレイ濃度を調整する」という標準Cover Block UIだけで完結する。
3. 数値・アイコン・ラベルはそのまま`astrea/results-list`の出力を使う（レンダリングロジック自体は不変）。

### 変更対象ファイル

- `theme/patterns/home-results-teaser.php`（Pattern構造変更）
- `theme/theme.json`（`styles.css`内のCSSルール移設・新規クラス追加）

### Theme/Core scope

- **Theme変更: 必要**（Pattern・theme.json CSSのみ）
- **Core変更: 不要**（`astrea/results-list`ブロック自体の登録・レンダリングロジックは無変更で済む — HeroのCover Blockと同じ「WordPress標準ブロックの属性を使うだけ」の設計のため）

### Backward Compatibility / 既存Results contentへの影響

- **重要な考慮点**: 既存の公開済みコンテンツ（Yamada Demo自身の`post_content`を含む）は、現在`<!-- wp:astrea/results-list {"heading":"実績"} /-->`という**ラップされていない生の形**で保存されている。Pattern定義を変更しても、**既存の投稿本文は自動的には書き換わらない**（WordPressのPatternは「挿入時のひな形」であり、後からの変更は既存コンテンツに遡及しない）。
- したがって、`.wp-block-astrea-results-list`自身のCSS背景ルールを完全に削除してしまうと、**既存のラップされていないResultsは背景色を失って透明になってしまう**（後方互換性の破壊）。
- **安全な設計**: `.wp-block-astrea-results-list`自身のCSSルールは維持したまま（デフォルトの不透明navyを残す）、新規に追加する`core/cover`ラッパー側にのみ、画像設定時にResults本体側が透明化する専用の追加クラス（例: `.astrea-results-photoplane .wp-block-astrea-results-list`のような子孫セレクタで上書き）を導入する。これにより、①既存の生の`astrea/results-list`（Yamada Demoを含む）は一切見た目が変わらない、②新規にPatternから挿入したユーザーだけがCoverラッパー経由で画像を使える、という完全後方互換な設計が可能。
- 既存Results contentの互換性: 上記設計であれば、DBマイグレーション・コンテンツ書き換えは一切不要。
- Site Editor editability: Cover Block標準UIがそのまま使えるため、Hero同様の編集体験になる。
- responsive: Coverブロックは標準でレスポンシブ対応済み（Hero同様、モバイルでも問題なし）。
- accessibility: 背景画像使用時のオーバーレイ濃度・コントラスト比は、Hero同様、テーマ側で妥当なデフォルト値（例: dimRatio 20〜40程度を推奨表示）を設ける必要がある。数値・ラベルは引き続きプレーンテキストのため、スクリーンリーダー等への影響はない。

### 却下した代替案

- **Custom PHP UI**（ブロック独自のメディアアップローダーをPHPで実装）: Order優先順位の最後の手段に該当し、Hero・Case・Representativeがすべて標準WordPress機構（Cover/Featured Image）で統一されている現行製品の設計思想と矛盾するため推奨しない。
- **Theme custom CSSのみでの対応**: `astrea/results-list`自体に背景画像を持たせる属性が無い以上、CSSだけでは画像URLをユーザーが変更する手段を提供できない（`background-image:url()`をCSSにハードコードするのはDemo-specific hackであり、Order §9で明示的に禁止されている設計）。

## 12. Versioning Recommendation

- **ASTREA Theme 1.0.3**相当のPatch Releaseとして妥当と判定する（Pattern構造変更＋theme.json CSSのみ、後方互換設計）。
- **Core bump required? NO** — `astrea-core`プラグイン側のコード（ブロック登録・レンダリング）は無変更で実現可能。
- **Database migration required? NO** — 既存コンテンツを書き換える必要がない後方互換設計とするため。

## 13. Results Asset verification（Order §11、確認のみ・投入なし）

| 項目 | 値 |
| --- | --- |
| ファイル | `docs/demo-assets/yamada-live-demo/images/astrea-demo-yamada-results-background.png` |
| Existence | あり |
| Dimensions | 1774×887 |
| Filesize | 1,619,562 バイト |
| SHA256 | `616ebb57fb334513d2aeedca755907d9a3c26b80ba4ffb7f02b209c8abedd492` |

Yamada Demoへの投入・Web最適化版の作成のいずれも実施していない（Order指示通り）。前回確認時（Construction 025-B2）からファイル内容・ハッシュとも変化なし。

## 14. Original Vision relationship（Order §12）

`docs/research/design-reference/astrea-original-design-vision.png`のResultsセクションを再確認した。Vision自体は**無地navyの背景**であり、背景写真は使用されていない（Phase A・025-B2での確認と一致、変化なし）。

**分類: Product flexibility enhancement（Vision restoration requirementではない）**

Original Visionを再現するという観点だけで見れば、現行の無地navy実装は既にVisionと一致（MATCH）しており、背景画像機能の追加は一切不要である。しかし、Owner asset（`astrea-demo-yamada-results-background.png`）のような写真を使いたいASTREA利用者が将来的に現れた場合、それを標準UIだけで実現する手段が現行製品には存在しない、という**製品としての表現力の限界**が今回のテストで実証された。したがって、これは「Visionに合わせるための修正」ではなく、「ASTREA利用者全般の選択肢を広げるための、独立した製品機能拡張」として位置づけるべきである。

## 15. Theme diff / Core diff（Order §13、終了時）

```
$ git status --short
（0節・末尾参照。HISTORY.csvの既存差分以外、本Phaseで生じた変更は本報告書と証跡スクリーンショットのみ）

$ git diff --stat -- theme/ core/
(no output)
```

Theme diff = 0、Core diff = 0（開始時・終了時とも変化なし）。

## 16. Changed files

- 新規: 本報告書 `docs/research/2026-09-10_construction_025e0_results_background_capability_audit.md`
- 新規: `docs/research/screenshots/025-E0/`（監査用スクリーンショット、下記参照）
- Theme/Core/Demo Content: 変更ゼロ
- Disposable Test Page（post ID 63、ローカルPlayground環境のみ・リポジトリ外）: 作成後、`wp_delete_post()`で完全削除済み。Yamada Demo本番のResultsセクション（HOME post_content）は本Phase開始前後で完全に無変更であることをSQLite直接確認済み。

## 17. Screenshot Evidence

`docs/research/screenshots/025-E0/`に保存:

- `01-group-inspector-background-panel.png` — 標準core/groupのBackground Imageパネル
- `02-resultslist-inspector-no-background.png` — `astrea/results-list`選択時、Background/Color系パネルが一切無いことの証跡
- `03-frontend-occlusion.png` — フロントエンド実機、画像がResults本体カードの外側にしか見えないことの証跡

## 18. Final Verdict

**D. UNSUPPORTED — THEME 1.0.3 CHANGE REQUIRED**

現行ASTREA 1.0.2のResultsセクションは、標準WordPress UIのみでは背景画像・オーバーレイ・focal pointのいずれも実現できない。原因は`astrea/results-list`ブロックにcolor/background系のBlock Supportsが一切登録されておらず、かつTheme側CSSでハードコードされた不透明navy背景が、標準Group/Coverでのラップという回避策すら無効化してしまうためである。実現には、Hero（`astrea-hero-photoplane`）で既に確立された`core/cover`ベースの設計をResultsにも適用するTheme改修（Core変更・DBマイグレーションともに不要、後方互換設計可能）が必要であり、Theme 1.0.3のPatch Release候補として妥当である。

ただし、Original Design Vision自体は無地navyであり、この機能はVision復元のためではなく、ASTREA利用者全般への表現力向上のための独立した製品機能拡張として検討されるべきものである。

---

**STOP — Results背景投入・Theme変更・1.0.3施工・Construction 024・VPS・公開のいずれにも進みません。** Owner のご判断をお待ちします。
