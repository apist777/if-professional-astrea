# Construction 027 — ASTREA Official Starter Site Foundation
「やまだ行政書士事務所」→「ASTREA行政書士事務所」

- Start: 2026-09-11 19:30頃 JST（Order受領時）
- End: 2026-09-11 20:01:51 JST（実測）
- Modifier: Chloe
- Mode: **ローカル実装 + Local Clean Rebuild + QA + スクリーンショット取得のみ。commit / push / VPS deployは一切実施していない。**

---

## PHASE 0 — PRECHECK / INVENTORY

```
$ cd ~/if-professional-astrea
$ git status --short --branch
## main...origin/main
 M HISTORY.csv   ← Owner自身の編集中ファイル（今回は一切触れていない）
 M docs/demo-assets/yamada-live-demo/yamada-demo-export.wxr   ← 2026-09-10 16:07更新、今回の作業開始前から存在する差分
 D docs/research/screenshots/012/...（多数）  ← Owner削除済み旧screenshots、復元していない
 ?? docs/research/2026-09-11_construction_024...等（クロミちゃん自身の未commit報告書、今回は触れていない）

$ git rev-parse HEAD
b2222bf546343ebf2536ce3fea7937304a5eeff9（main）
```

### 旧Yamada Identity Inventory（網羅的洗い出し結果）

Theme/Core本体（`theme/`・`core/`）を全文検索した結果、**「山田」「やまだ」「Yamada」は一切存在しない**ことを確認した（製品コードは元々完全に汎用設計）。

すべてのYamada固有情報は`docs/demo-assets/yamada-live-demo/`配下に限定されていた:

| ファイル | 内容 |
| --- | --- |
| `scripts/build-content.php` | Office Profile（`office_name`＝やまだ行政書士事務所）、`blogname`、Professional投稿タイトル（山田 太郎）、代表者ポートレートのlabel／placeholderファイル名 |
| `scripts/integrate-final-images.php` | 画像パス（`astrea-demo-yamada-*`）、代表者ポートレートのtitle／alt テキスト（山田太郎） |
| `scripts/integrate-025b2-hero-and-cases.php` | 画像パス（`astrea-demo-yamada-*`）、docblockの"Yamada Demo build"表記 |
| `scripts/make-results-web-jpeg.php` | 画像パス |
| `scripts/integrate-025e1-results-background.php` | 画像パス、docblockの"Yamada Demo HOME page"表記 |
| `scripts/fix-internal-link-portability.php` | docblockの"Yamada Demo"表記（1箇所のみ） |
| `scripts/theme-core-activation-blueprint.json` | `blogname`の初期値 |
| `README.md` | タイトル・全体を通じて「やまだ行政書士事務所」表記 |
| `images/`・`images/web/` | ファイル名に`yamada`を含む画像12点（PNG6点＋JPG6点）——**これらは実際にWordPressへアップロードされ、公開URL（例: `/wp-content/uploads/.../astrea-demo-yamada-hero-office-*.jpg`）としてサイト訪問者に露出していたことをConstruction 024-R1のQAログで確認済み** |
| `yamada-demo-export.wxr` | ファイル名・内容とも「やまだ行政書士事務所」のまま（既知の課題、後述） |
| `placeholder-images/yamada-professional-portrait.png` | Construction 023時点の**旧・不使用プレースホルダー**（README上も「現在は不使用、履歴として保持」と明記済み） |

**過去のConstruction report（023/023-A/023-B/024-R1/025系）内の「やまだ行政書士事務所」「山田太郎」表記は、当時の実際の名称としてそのまま残しており、一切書き換えていない。** Owner削除済みの旧screenshots（`docs/research/screenshots/012〜016系`等）も復元していない。

---

## PHASE 1 — OFFICIAL IDENTITY

| | 旧 | 新 |
| --- | --- | --- |
| 事務所名 | やまだ行政書士事務所 | **ASTREA行政書士事務所** |
| 代表者 | 山田 太郎 | **伊府 伊夫男**（いふ いふお） |
| 職名 | 行政書士 | 行政書士（変更なし。新しい肩書は追加していない） |

単純な全置換ではなく、ファイルごとに文脈を確認しながら変更した（詳細は後述「変更ファイル」参照）。**登録番号・住所・電話番号など、山田という名前に紐づかない汎用的な架空情報（`行政書士登録番号：第98765432号`、`東京都新宿区西新宿1-1-1 新宿タワー10F`、`03-9876-5432`）は変更していない**——Order Phase 1が対象とする「山田固有情報」には該当しないため。

**読み仮名について**: 「いふ いふお」という読みの情報はASTREA Core自体に氏名の読み仮名を保持するフィールドが存在しない（`ProfessionalProfile`のmeta構成は資格・経歴・学歴・所属・登録情報のみ）。フロントエンドも氏名をふりがな付きで表示する仕組みは持たない（旧「山田 太郎」も同様にふりがな無し表示だった）。したがって今回、読みをデータとして別途保存する箇所は作っていない（Theme/Core機能追加はOrderで禁止されているため）。

---

## PHASE 2 — FICTIONAL DEMO DISCLOSURE

`build-content.php`が事務所概要ページへ追加する既存の開示文（Construction 023由来）をそのまま活用した:

> このWebサイトは If Professional ASTREA のデモサイトです。掲載されている事務所・人物・サービス内容・実績・お客様の声等は、デモ用に作成された架空の情報です。実在の事務所・人物とは一切関係ありません。

事務所名・人物名を含まない一般的な文面のため、**無変更で今回もそのまま機能する**（新しいASTREA行政書士事務所／伊府伊夫男というidentityにもそのまま自然に適用される）。デザインを壊す形での強調変更は行っていない。

---

## PHASE 3 — CONTENT POLICY

Services（3件）・Prices（4件）・FAQ（4件）・Results（3件）・Voices（3件）・Cases（3件）の本文は、いずれも人物名・旧事務所名を含まない一般的な行政書士業務内容の記述だったため、**大幅な作り直しは行わず、既存の「完成例としての価値」をそのまま維持した**。「YOUR OFFICE」「代表者名を入力してください」のような空テンプレートへの後退はしていない。

---

## PHASE 4 — REPRESENTATIVE IMAGE

代表者ポートレート画像（`astrea-demo-starter-professional-portrait.jpg`、旧`astrea-demo-yamada-professional-portrait.jpg`）を実機で目視確認した（本報告書のスクリーンショット参照）。

**判定: 画像自体に氏名・旧事務所名の焼き込みは一切なく、汎用的な行政書士ポートレート写真として新しい「伊府 伊夫男」identityへそのまま自然に流用可能。** 画像内にはオフィスデスク・ノートPC・観葉植物・書類等が写るのみで、氏名プレート・看板・名刺等のテキスト要素は存在しない。

**したがって画像変更は不要と判断し、新しい人物画像は生成していない。** ファイル名のみ、Phase 5のasset renamingの一環として`yamada`→`starter`へ変更した（画像データそのものは無変更、`git mv`によるファイル名変更のみ）。

---

## PHASE 5 — ASSET / PIPELINE NAMING

### 5-1. ディレクトリ名変更

```
docs/demo-assets/yamada-live-demo/ → docs/demo-assets/astrea-starter-site/
```

`git mv`で実施（履歴を保持したリネーム）。Order提示の2案（`astrea-starter-professional-office`／既存命名規則に沿った中立名称）のうち、Order内で繰り返し使われている「ASTREA Starter Site」という用語と直接対応し、既存の`<名称>-live-demo`パターンとも語感が揃う**`astrea-starter-site`**を採用した。

### 5-2. 画像ファイル名変更

`images/`・`images/web/`配下の`astrea-demo-yamada-*`パターンの全12ファイル（PNG6点＋JPG6点）を`astrea-demo-starter-*`へ`git mv`でリネームし、参照元の全スクリプト（`build-content.php`／`integrate-final-images.php`／`integrate-025b2-hero-and-cases.php`／`make-results-web-jpeg.php`／`integrate-025e1-results-background.php`）内のパス文字列を対応更新した。

**リネームした理由**: これらの画像ファイル名は内部実装の詳細ではなく、WordPressのメディアライブラリを経由して**公開URL（`/wp-content/uploads/.../astrea-demo-yamada-hero-office-*.jpg`等）として実際に訪問者へ露出する**ことをConstruction 024-R1のQAログで確認済みだったため、Phase 0の網羅的洗い出しの結果、単なる内部命名の問題ではなく実際のidentity漏洩箇所と判断し、リネーム対象に含めた。

### 5-3. あえてリネーム・変更しなかったもの

| 対象 | 理由 |
| --- | --- |
| `placeholder-images/`ディレクトリおよび`yamada-professional-portrait.png`・`fix-placeholders.php` | README自身が「現在は不使用、履歴として保持」と明記した歴史的記録。Order「過去の...履歴まで機械的に書き換えない」に該当すると判断し、無変更。 |
| `yamada-demo-export.wxr`（ファイル名・内容とも） | ①内容は2026-09-10時点（今回のOrder着手前）から既に更新されていた、Owner自身の別の作業に由来する差分であり、今回のConstruction 027では一切触れていない（`git diff HEAD`・ファイルmtimeで確認済み）。②Construction 025-CLOSEOUT-Hの時点で既に「実際にテスト済みの主たる再現経路はscriptsパイプラインであり、WXRは代替経路の副次的資産」と位置付けられていた経緯を踏襲し、今回もこのファイルの内容・ファイル名を変更していない。README側に「既知の課題・要更新」として明記した（Known Issues参照）。 |
| Prices/Services/FAQ等の本文 | Phase 3参照。人物名・旧事務所名を含まないため変更不要。 |
| 過去のConstruction report（`docs/research/*.md`） | 歴史的記録として無変更。 |

---

## PHASE 6 — REPRODUCIBILITY

**正式な再現性ソース（`docs/demo-assets/astrea-starter-site/scripts/`）を先に更新し、その後ローカルでゼロから再構築できることを実証した。** 公開VPS上の直接編集を正本にはしていない（そもそも今回VPSには一切触れていない）。

### Construction 024-R1 VPS adaptation の分類（Order Phase 6後半の要求）

| 分類 | 内容 |
| --- | --- |
| **Playground固有** | 各スクリプト冒頭の`require_once '/wordpress/wp-load.php';`（`wp eval-file`実行時は不要、デプロイ時の作業コピー側で除去） |
| **Playground固有** | `/images/`・`/images/web/`への絶対パス参照（実VPSでは存在しないため転送先パスへ書き換えが必要） |
| **通常WordPress処理との差異** | `fix-internal-link-portability.php`の一部トップレベルコードでの`$wpdb`暗黙参照（`wp eval-file`の関数スコープでは`global $wpdb;`の明示宣言が必要） |
| **共通化可能** | それ以外すべて（Office Profile／Professional／Service／Case／Result／Price／FAQ／Voice投入、画像アップロード・Featured Image設定、Cover Block属性設定、Navigation/Contact CTA接続ロジック）はWordPress標準APIのみに依存し、実行方式を問わず共通利用できる |

（詳細は`docs/demo-assets/astrea-starter-site/README.md`「Construction 027 — VPS向けアダプテーションの分類」節、および元データのConstruction 024-R1レポート§14-5に記録済み。**今回、Starter Import機能そのものは実装していない**——分類・記録のみ。）

---

## PHASE 7 — LOCAL REBUILD

`~/co-c027-rebuild`（新規、`download-and-install`でゼロから、port 8911）で、更新済みの10ステップパイプライン（activate → cleanup → build-content → polish → permalinks → integrate-final-images → integrate-025b2-hero-and-cases → make-results-web-jpeg → integrate-025e1-results-background → fix-internal-link-portability）を実行。

**注意（テスト基盤のみの問題、製品・パイプライン本体の問題ではない）**: ローカルの再生用Blueprint JSON（`~/owner-review-pipeline.json`）が旧identityのまま古くなっていたため（025-CLOSEOUT-Hで一度遭遇したのと同種の問題）、今回は個別パッチではなく**現在の`scripts/`配下の内容から全10ステップを毎回再生成する方式に変更**し、以後この種の陳腐化を構造的に防止した。

手動修正なしで達成した結果:

| 項目 | 結果 |
| --- | --- |
| Site title | **ASTREA行政書士事務所** ✅（ページタイトル・Header・Footerで確認） |
| Representative | **伊府 伊夫男** ✅（Professionalsページ・Home代表者紹介セクションで確認） |
| 旧identityの残存 | **0**（レンダリング後HTML全文検索で「山田」「やまだ」「Yamada」「yamada」いずれも0件。画像src全件が`astrea-demo-starter-*`で公開URLレベルでも漏洩なし） |
| Header CTA → Contact | ✅ |
| Hero CTA → Contact | ✅ |
| Bottom CTA → Contact | ✅ |
| Phone CTA（3箇所） | ✅ `tel:03-9876-5432` |
| Desktop 1440 smoke test（7ページ） | ✅ status=200・overflow=0・console error=0 全ページ |
| Mobile 390 smoke test（7ページ） | ✅ 同上 |
| Block validation | 合計8件（既存の014A系統2件含む、025系のbaselineと完全一致）、**新規warning 0** |
| Theme | 1.0.3（無変更） |
| Core | 1.0.1（無変更） |

製品Theme/Coreへのデモ固有hackは一切追加していない（`git diff --stat -- theme/ core/`は空）。

---

## PHASE 8 — STARTER-SITE READINESS AUDIT

将来のStarter Site Import機能設計のため、コンテンツを3分類した。

### A. ユーザーが最初に変更すべき項目

| 項目 | 現在の値 | 変更方法（既存のWordPress標準機能） |
| --- | --- | --- |
| 事務所名 | ASTREA行政書士事務所 | ASTREA Core「事務所情報」画面（Office Profile） |
| 代表者名・資格・経歴・学歴・所属・登録番号 | 伊府 伊夫男 他 | Professional Profile投稿の編集画面 |
| 電話番号 | 03-9876-5432 | 事務所情報画面（`phone`→自動的に`tel:`リンクへ変換） |
| 住所 | 東京都新宿区西新宿1-1-1 新宿タワー10F | 事務所情報画面 |
| Contact情報（フォーム送信先等） | ASTREA Core標準のContact Form | 標準のContact Form設定 |
| Logo | なし（テキストロゴ「ASTREA」表示） | Site Editor標準のサイトロゴ機能 |
| 代表者写真 | `astrea-demo-starter-professional-portrait.jpg` | Professional Profile投稿のFeatured Image |
| Hero copy（キャッチコピー・リード文） | 「お客様に寄り添う、専門家によるご相談窓口です。」 | HOMEページのBlock Editorで直接編集 |

### B. 必要に応じて変更する項目

Services（取扱業務3件）／Prices（料金4件）／Cases（対応事例3件）／Results（実績3件）／Voices（お客様の声3件）／FAQ（4件）——いずれも「完成例」としてそのまま使える内容だが、実際の事務所の業務内容に応じて差し替え可能。すべて標準のCPT編集画面（一覧・追加・編集）から操作できる。

### C. ASTREA側で維持すべき構造

- Layout／Block Patterns（Hero diagonal cut、Results Cover背景、Cases 3カラムグリッド等）
- Navigation構造（Header/Footer共通の`wp_navigation`、参照ベースリンク）
- Responsive design（1440px/390pxブレークポイント）
- Design tokens（`theme.json`のColor/Typography/Spacing）
- CTA構成（Header/Hero/Bottom 3箇所のContact導線、Phone CTAのBlock Bindings）

**この分類はレポートへの記録のみであり、設定UIやStarter Import機能そのものはまだ実装していない**（Order Phase 8/今回のOrder全体の明示的な禁止事項の通り）。

---

## PHASE 9 — VISUAL REVIEW GATE

Desktop 1440px／Mobile 390pxをローカル環境（port 8911）で確認。スクリーンショット3点を`docs/research/screenshots/027/`へ保存した:

- `construction-027-desktop-home-1440.png`
- `construction-027-mobile-home-390.png`
- `construction-027-representative-section.png`

いずれも本会話内で画像として提示する。

---

## Known Issues

1. **`yamada-demo-export.wxr`が旧identity・旧ファイル名のまま**（Phase 5-3参照）。主たる再現経路（scriptsパイプライン）は完全に新identityへ移行済みだが、代替経路（wp-admin WXRインポート）を使う場合は旧identityのままインポートされる。別途、新identityでの正式な再エクスポートが必要（今回のOrder範囲外、意図的に不可触）。
2. Professional Profileの氏名読み仮名（ふりがな）を保持・表示する仕組みがASTREA Core側に存在しない。「いふ いふお」という読みは本報告書に記録するのみで、データとしては保存していない（Theme/Core機能追加は今回禁止のため）。
3. Owner自身の未コミット`HISTORY.csv`・`docs/demo-assets/yamada-live-demo/yamada-demo-export.wxr`（Construction 027着手前からの差分）には一切触れていない。

---

## 変更ファイル一覧

```
$ git status --short docs/demo-assets/
（renameを含む29ファイル: ディレクトリ名変更＋画像12点リネーム＋スクリプト内容更新9件＋README全面改稿＋JSON1行更新）
```

Theme/Core側の変更: **0**（`git diff --stat -- theme/ core/`は空）。

---

## Construction 027 — Visual Review Ready

- Site Name: ASTREA行政書士事務所
- Representative: 伊府 伊夫男
- Old Yamada Identity: 0 / intentional historical references only
- Reproducibility: PASS
- Theme/Core Modification: NONE
- Desktop: PASS
- Mobile: PASS
- Starter Site Readiness Audit: COMPLETE
- Commit: NOT DONE
- Push: NOT DONE
- Deploy: NOT DONE
- Owner Review: REQUIRED

STOP
